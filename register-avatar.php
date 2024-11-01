<?php
/**
 * Copyright 2011-2023 by Gwyneth Llewelyn. All rights reserved.
 *
 * Released under a BSD-3-clause license.
 *
 *
 * Based on my own code for Online Status inSL, https://www.wordpress.org/plugins/online-status-insl/
 *
 **/

require_once('../../../wp-config.php');
include_once(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . "sl-user-create-functions.php");
require_once(ABSPATH . WPINC . '/registration.php');
if (!class_exists('WP_Http'))
	include_once(ABSPATH . WPINC . '/class-http.php');

// This gets called from a Second Life object when an avatar wants to register for the site
// Most of the data will come from the headers (e.g. avatar UUID) except for avatar_name

if (!$_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'])
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	header("Content-type: text/plain; charset=utf-8");
	die(__("Request has to come from Second Life", 'sl-user-create'));
}

// now get the whole serialised array for this plugin's option
// we need it to check the simple signature
$settings = get_option('sl_user_create_settings');

if (!$settings['disable_signature']) // check if user has disabled signature validation
{
	if ($_REQUEST['signature'] != md5($_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'] . $settings['secret'] .":" . $settings['secret_number']))
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(__("Invalid signature", 'sl-user-create'));
	}
}

if (!$_REQUEST['avatar_name'] || !$_REQUEST['avatar_key'])
{
	header(__("HTTP/1.0 405 Method Not Allowed", 'sl-user-create'));
	header("Content-type: text/plain; charset=utf-8");
	die(__("Registration requires a valid avatar name and key", 'sl-user-create'));
}

// Now check permissions
// Is the owner of this object allowed to register it with us?
// We check first if we actually have a list of valid avatars (empty means all are allowed)
if (count($settings['allowed_avatars']) > 0)
{
	if (!in_array($_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'], $settings['allowed_avatars']))
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(sprintf(__("%s not allowed to register objects with %s", 'sl-user-create'), $_SERVER['HTTP_X_SECONDLIFE_OWNER_NAME'], home_url()));
	}
}

// Is this avatar on our banned list?
// We check first if we actually have a list of valid avatars (empty means all are allowed)
if (count($settings['banned_avatars']) > 0)
{
	if (!in_array($_REQUEST['avatar_name'], $settings['banned_avatars']))
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(sprintf(__("%s banned from %s", 'sl-user-create'), $_REQUEST['avatar_name'], home_url()));
	}
}

// More complex validation: is the request coming from a valid address?
// We have to check first if we have a permission list with DNS entries
if (count($settings['allowed_simdns']) > 0)
{
	$passThru = false;

	// check IP address and DNS name...
	$addr = $_SERVER['REMOTE_ADDR'];
	$host = gethostbyaddr($_SERVER['REMOTE_ADDR']);

	foreach($settings['allowed_simdns'] as $dnsEntry)
	{
		// trivial case first; the address or hostname matches
		/*if ($dnsEntry == $addr || $dnsEntry == $host)
		{
			$passThru == true;
			break;
		}*/
		// reverse check: match the end bit of our entry with the host/addr
		// this ought to allow people to have secondlife.com as an entry
		// and validate all requests coming from SL

		if (substr_compare($host, $dnsEntry, -strlen($dnsEntry)) == 0)
		{
			$passThru = true;
			break;
		}

		// do the same for IP addresses
		if (substr_compare($addr, $dnsEntry, -strlen($dnsEntry)) == 0)
		{
			$passThru = true;
			break;
		}
	}

	if (!$passThru)
	{
		header("HTTP/1.0 403 Forbidden");
		header("Content-type: text/plain; charset=utf-8");
		die(sprintf(__("Host %s (%s) is not allowed to register %s with %s", 'sl-user-create'), $host, $addr, $_REQUEST['avatar_name'], home_url()));
	}
}

if (!function_exists("username_exists"))
{
	header(__("HTTP/1.0 404 Function Not Found", 'sl-user-create'));
	header("Content-type: text/plain; charset=utf-8");
	die(__("username_exists not found", 'sl-user-create'));
}

$avatarKey = $_REQUEST['avatar_key'];
$avatarDisplayName = sanitize_user(sanitise_avatarname($_REQUEST['avatar_name']));
$objectKey = $_SERVER['HTTP_X_SECONDLIFE_OBJECT_KEY'];

$objects = get_option('sl_user_create_objects');

// see if this object is registered; if not, abort
if (!isset($objects[$objectKey]))
{
	header(__("HTTP/1.0 404 Not found", 'sl-user-create'));
	header("Content-type: text/plain; charset=utf-8");
	_e("This object has never been registered to this WordPress site before!", 'sl-user-create');
	printf(__("Please contact the owner of %s", 'sl-user-create'), home_url());
	die();
}

header("HTTP/1.0 200 OK");
header("Content-type: text/plain; charset=utf-8");

// register user with WordPress

$user_id = username_exists($avatarDisplayName);
if (!$user_id)
{
	$random_password = wp_generate_password(12, false);

	// Attempt to deal with the new avatar names, which have no last name
	$getDot = stripos($_REQUEST['avatar_name'], " ");
	$avatarFirstName = substr($_REQUEST['avatar_name'], 0, $getDot);
	$avatarLastName = substr($_REQUEST['avatar_name'], $getDot + 1); // may be empty
	if ($avatarLastName == 'Resident') $avatarLastName = "";
	$avatarFullName = $avatarFirstName . ($avatarLastName ? " " . $avatarLastName : "");

	// Get the avatar's decription from the old profile site; to-do for now
	$description = "";

	// See first if this comes from Second Life or not
	if ($_SERVER['HTTP_X_SECONDLIFE_SHARD'] != 'Production')
	{
		$result = wp_remote_get("http://world.secondlife.com/resident/" . $avatarKey);

		if ($result['response']['code'] == 200)
		{
			// parse result; description is in a special meta tag, thanks to LL!
			$whereStart = strstr($result['body'], '<meta name="description" content="');
			$chopStart = stripos($whereStart, '" />');
			$description = sanitize_text_field(substr($whereStart, 34, $chopStart - 34)) . "\n\n";
		}
	}

	// make sure that the generated username is valid
	if (!validate_username($avatarDisplayName))
	{
		// try to use an underscore instead
		$avatarDisplayName = sanitize_user(sanitise_avatarname($_REQUEST['avatar_name'], "_"));

		if (!validate_username($avatarDisplayName))
		{
			// WP doesn't even like the underscore in the username. Abort!
			$objects[$objectKey]["timeStamp"] = time();	// update object's time stamp whenever we get a transaction

			// abort with error, this user cannot be registered
			die ($avatarKey . "|fail|" . sprintf(__('Registration to %s failed. Could not create a valid WP username out of your avatar name', 'sl-user-create'), home_url()));
		}
	}

	$new_user_id = wp_insert_user(array(
		'user_login'	=> $avatarDisplayName,
		'user_pass'		=> $random_password,
		'user_nicename'	=> $avatarFullName,
		'nickname'		=> $avatarFullName,
		'displayname'	=> $avatarFullName,
		'first_name'	=> $avatarFirstName,
		'last_name'		=> $avatarLastName,
		'user_email'	=> $_REQUEST['avatar_email'] ? $_REQUEST['avatar_email'] : $avatarFirstName . "." . $avatarLastName . "@" . substr(home_url(), 7), // future: avatar email may be sent from in-world object too
		'description'	=> $description . sprintf(__('Registered via "%s (%s)" (Shard: %s) - Object name: %s', 'sl-user-create'), gethostbyaddr($_SERVER['REMOTE_ADDR']), $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_SECONDLIFE_SHARD'], $_SERVER['HTTP_X_SECONDLIFE_OBJECT_NAME']),
		'user_url'		=> ($_SERVER['HTTP_X_SECONDLIFE_SHARD'] == 'Production' ? "http://my.secondlife.com/" . $avatarDisplayName : ""),
		//'role'			=> 'Subscriber'
		)
	);

	if (is_wp_error($new_user_id)) // new user insertion failed? We don't know why...
	{
		echo $avatarKey . "|fail|" . sprintf(__('Registration to %s failed. Error: %s', 'sl-user-create'), home_url(), $new_user_id->get_error_message());
	}
	else
	{
		// is this wp_mu or wp on network mode? Then we have to set source_domain

		if (is_multisite())
			add_user_meta($new_user_id, 'source_domain', home_url(), false);

		echo $avatarKey . "|ok|" . sprintf(__("Registration on %s successful! Your login is %s (user id %d) with password %s", 'sl-user-create'), home_url(), $avatarDisplayName, $new_user_id, $random_password); // this will be IMed

		$objects[$objectKey]["count"]++; // for statistics

		update_option('sl_user_create_objects', $objects);
	}
}
else // Registration failed because user_id was already present
{
	// see if we ought to retrieve the password

	if ($_REQUEST['password'])
	{
		// Generate a new password
		$random_password = wp_generate_password(12, false);

		$new_user_id = wp_update_user(array(
			'ID'		=> $user_id,
			'user_pass'	=> $random_password
		));

		if ($new_user_id)
			echo $avatarKey . "|ok|" . sprintf(__("Password reset on %s successful! Your login is %s (user id %d) with password %s", 'sl-user-create'), home_url(), $avatarDisplayName, $new_user_id, $random_password);
		else
			echo $avatarKey . "|fail|" . home_url();	// Hm. This should NOT happen!
	}
	else // no password retrieval, just fail
	{
		echo $avatarKey . "|fail|" . home_url();
	}
}
$objects[$objectKey]["timeStamp"] = time();	// update object's time stamp whenever we get a transaction
?>