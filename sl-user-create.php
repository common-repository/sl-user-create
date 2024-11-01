<?php
/*
Plugin Name: SL User Create
Plugin URI: https://gwynethllewelyn.net/sl-user-create/
Version: 0.2.6
License: Simplified BSD License
Author: Gwyneth Llewelyn
Author URI: https://gwynethllewelyn.net/
Description: Allows Second Life® users to get automatically registered to a WordPress site by touching an object with a special script.

BSD 3-Clause License

Copyright (c) 2011-2023, Gwyneth Llewelyn
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its
   contributors may be used to endorse or promote products derived from
   this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

---

Based on my own code for Online Status inSL, http://wordpress.org/extend/plugins/online-status-insl/

*/
// Deal with translations. Portuguese only for now.
load_plugin_textdomain('sl-user-create', false, dirname( plugin_basename( __FILE__ ) ));

include_once(WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) . "sl-user-create-functions.php");

if (!class_exists('WP_Http'))
	include_once(ABSPATH . WPINC . '/class-http.php');

/**
 * Setting up the panels
 *
 * @var void
 * @return array
 */
function sl_user_create_get_settings_page_tabs() {
	 $tabs = array(
			'main'			=> __('Script', 'sl-user-create'),
			'objects'		=> __('Objects', 'sl-user-create'),
			'security'		=> __('Security', 'sl-user-create'),
			'instructions'	=> __('Instructions', 'sl-user-create')
	 );
	 return $tabs;
} // end sl_user_create_get_settings_page_tabs()

// Set up styling for page tabs
function sl_user_create_admin_options_page_tabs( $current = 'main' ) {
	 if ( isset ( $_GET['tab'] ) ) :
			$current = $_GET['tab'];
	 else:
			$current = 'main';
	 endif;
	 $tabs = sl_user_create_get_settings_page_tabs();
	 $links = array();
	 foreach( $tabs as $tab => $name ) :
			if ( $tab == $current ) :
				  $links[] = "<a class='nav-tab nav-tab-active' href='?page=sl_user_create&amp;tab=$tab'>$name</a>";
			else :
				  $links[] = "<a class='nav-tab' href='?page=sl_user_create&amp;tab=$tab'>$name</a>";
			endif;
	 endforeach;
	 echo '<div id="icon-themes" class="icon32"><br /></div>';
	 echo '<h2 class="nav-tab-wrapper">';
	 foreach ( $links as $link )
			echo $link;
	 echo '</h2>';
} // end sl_user_create_admin_options_page_tabs()

function sl_user_create_admin_menu_options()
{
	add_options_page(__('SL User Create', 'sl-user-create'), __('SL User Create', 'sl-user-create'), 1,
		'sl_user_create', 'sl_user_create_menu');
}

function sl_user_create_menu()
{
?>
<div class="wrap">
<?php
if (!current_user_can( 'manage_options' )) {
	_e("No access.", 'sl-user-create');
?></div>
<?php
	exit();
}
?>
<?php sl_user_create_admin_options_page_tabs(); ?>
<?php $tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'main' ); ?>
<h2><?php _e('SL User Create', 'sl-user-create'); ?></h2>

<?php
	if ($tab == 'main')
	{
		// automated settings. This uses the "modern" way of setting things,
		// but for now it only applies to the secrets
		settings_fields('sl_user_create-main');
		do_settings_sections('sl_user_create-main');
	}
	else if ($tab == 'objects')
	{
		$objects = get_option('sl_user_create_objects');

		// Check if we have to delete some of the registration objects
		if ($_POST["sl_user_create_form"])
		{
			check_admin_referer('delete-online-registration-objects');

			// loop through settings to delete; we have objectKeys for each

			$statusMessage = ""; // add to this string as we find objects to delete

			foreach ($objects as $registrationObjects)
			{
				if (isset($_POST["deletedRegistrationObjects"]) && in_array($registrationObjects["objectKey"], $_POST["deletedRegistrationObjects"]))
				{
					$statusMessage .= __("Deleting registration object: ", 'sl-user-create') . $registrationObjects["objectName"] .
						" (" . $registrationObjects["objectKey"] . "), " .
						__("Owned by ", 'sl-user-create') . $registrationObjects["avatarDisplayName"] . ", " .
						__("Location: ", 'sl-user-create') . $registrationObjects["objectRegion"] .
						"<br />\n";
					unset($objects[$registrationObjects["objectKey"]]);

					$statusMessage .= __("Sending llDie() to registration object: ", 'sl-user-create');

					// call the PermURL for this object

					$body = array('command' => 'die');

					$url = $registrationObjects['PermURL'];
					$request = new WP_Http;
					$result = $request->request($url,
						array('method' => 'POST', 'body' => $body));
					// test $result['response'] and if OK do something with $result['body']

					if (!is_wp_error($result))
					{
						if ($result['response']['code'] == 200)
						{
							$statusMessage .= __("OK", 'sl-user-create') . " - " . $result['body'];
							unset($objects[$registrationObjects["objectKey"]]); // remove it from list
						}
						else
						{
							$statusMessage .= __("Failed", 'sl-user-create') . " - " . $result['body'];
						}
					}
					else
					{
						$statusMessage .= __("Failed", 'sl-user-create') . " - " . $result->get_error_message();
					}
				}
			}
			/*
			$statusMessage .= __("Dumping original settings: <pre style=\"border: 1px solid #000; overflow: auto; margin: 0.5em;\">") .
				print_r($objects, true) . "</pre><br />\n";
			*/
			// update options with new settings; gets serialized automatically
			if ( !update_option( 'sl_user_create_objects', $objects) )
			{
				$statusMessage .= __( "<strong>Not saved!!</strong><br \>\n", 'sl-user-create' );
			}
			// emit "updated" class showing we have deleted some things
			if ( $statusMessage)
			{
?>
			<div id="message-updated" class="updated"><p><?php _e("Online registration objects <strong>deleted</strong>", 'sl-user-create'); ?><br /><br /><?php echo $statusMessage; ?>
			</p></div>
<?php
			} // endif ($statusMessage)
		} // endif ($_POST["sl_user_create_form"])

		if ( is_array( $objects ) && count( $objects ) > 0 )
		{
?>
<h2><?php _e("Current registration objects being tracked", 'sl-user-create'); ?>:</h2>
<form method='post' id="sl_user_create_form">
<?php wp_nonce_field("delete-online-registration-objects"); ?>
<!--<input type="hidden" name="action" value="delete-online-registration-objects">-->
<table class="wp-list-table widefat fixed" cellspacing="0">
	<thead>
		<tr>
			<th scope='col' class='manage-column column-objname'><?php _e("Object Name", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-objkey'><?php _e("Object Key", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-obversion'><?php _e("Object Version", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-location'><?php _e("Location", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-permurl'><?php _e("PermURL", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-avatarname'><?php _e("Avatar Owner Name", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-avatarkey'><?php _e("Avatar Owner Key", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-avatarcount'><?php _e("# avatars registered", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-date'><?php _e("Last time checked", 'sl-user-create'); ?></th>
			<th scope='col' class='manage-column column-cb check-column'><?php _e("Del?", 'sl-user-create'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
		foreach ($objects as $oneRegObject)
		{
			// deal with unknown variables for PHP 8.X
			if ( empty( $alternate ) )
			{
				/** @var bool */
				$alternate = false;
			}
	?>
	<tr class="format-default <?php echo ($alternate ? "" : "alternate"); $alternate = !$alternate; ?>">
		<td><?php echo $oneRegObject["objectName"]; ?></td>
		<td><?php echo $oneRegObject["objectKey"]; ?></td>
		<td><?php echo $oneRegObject["objectVersion"]; ?></td>
		<td>
<?php
			// parse name of the region and coordinates to create a link to maps.secondlife.com
			$regionName = substr($oneRegObject["objectRegion"], 0, strpos($oneRegObject["objectRegion"], "(") - 1);
			$coords = trim($oneRegObject["objectLocalPosition"], "() \t\n\r");
			$xyz = explode(",", $coords);

			printf('<a href="http://maps.secondlife.com/secondlife/%s/%F/%F/%F?title=%s&amp;msg=%s&amp;img=%s" target="_blank">%s (%d,%d,%d)</a>',
				$regionName, $xyz[0], $xyz[1], $xyz[2],
				rawurlencode($oneRegObject["objectName"]),
				rawurlencode(__("Registration object for ", 'sl-user-create') . home_url()),
				rawurlencode("http://s.wordpress.org/about/images/logos/wordpress-logo-stacked-rgb.png"),
				$regionName, $xyz[0], $xyz[1], $xyz[2]);
?>
		</td>
		<td><?php echo $oneRegObject["PermURL"]; ?></td>
		<td><?php echo $oneRegObject["avatarDisplayName"]; ?></td>
		<td><?php echo $oneRegObject["avatarKey"]; ?></td>
		<td><?php echo $oneRegObject["count"]; ?></td>

		<td class="date column-date"><?php echo date(__("Y M j H:i:s", 'sl-user-create'), $oneRegObject["timeStamp"]); ?></td>
		<td><input type="checkbox" name="deletedRegistrationObjects[]" value="<?php echo $oneRegObject["objectKey"]; ?>" /></td>
	</tr>
<?php
		} // end foreach
?>
	</tbody>
</table>
<input type='submit' class='button-primary alignleft' name='sl_user_create_form' value='<?php _e('Delete', 'sl-user-create'); ?>' />
</form>
<?php
	} // if settings not empty
	else
	{
?>
<br /><br /><strong>
<?php
	_e("No registration objects are being tracked.", 'sl-user-create');
?></strong>
<?php
	} // end if (count($settings['registrationObjects']) > 0)
	} // end tab == 'objects'
	else if ($tab == 'security')
	{
	?>
<form method="post" action="options.php">
<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'sl-user-create'); ?>" />
<?php
		settings_fields('sl_user_create_settings');
		do_settings_sections('sl_user_create');
?>
<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'sl-user-create'); ?>" />
</form>
<?php
	} // end tab == 'security'
	else if ($tab == 'instructions')
	{
		settings_fields('sl_user_create-instructions');
		do_settings_sections('sl_user_create-instructions');
	}
	else {
?>
<h3><?php _e('Error', 'sl-user-create'); ?></h3>

<?php _e('This page should have never been displayed!', 'sl-user-create'); ?>

<?php
	}
} // end sl_user_create_menu()

// Add a settings group, which hopefully makes it easier to delete later on
function sl_user_create_register_settings()
{
	// main (just shows script)
	add_settings_section( 'sl_user_create_main_section', __('Script', 'sl-user-create'), 'sl_user_create_main_section_text', 'sl_user_create-main');
	add_settings_field( 'text_area', __('LSL Script', 'sl-user-create'), 'sl_user_create_text_area', 'sl_user_create-main', 'sl_user_create_main_section');

	// security (shows secrets and lists avatar names & sims that are allowed)
	register_setting('sl_user_create_settings', 'sl_user_create_settings', 'sl_user_create_validate');

	// it's a huge serialised array for now, stored as a WP option in the database;
	//	 if performance drops, this might change in the future

	add_settings_section( 'sl_user_create_security_section', __('Security', 'sl-user-create'), 'sl_user_create_security_section_text', 'sl_user_create');
	add_settings_field( 'secret', __('Secret string', 'sl-user-create'), 'sl_user_create_secret', 'sl_user_create', 'sl_user_create_security_section');
	add_settings_field( 'secret_number', __('Secret number', 'sl-user-create'), 'sl_user_create_secret_number', 'sl_user_create', 'sl_user_create_security_section');
	add_settings_field( 'disable_signature', __('Disable Signature', 'sl-user-create'), 'sl_user_create_disable_signature', 'sl_user_create', 'sl_user_create_security_section');
	add_settings_field( 'allowed_avatars', __('Allowed avatars (for registration objects)', 'sl-user-create'), 'sl_user_create_allowed_avatars', 'sl_user_create', 'sl_user_create_security_section');
	add_settings_field( 'banned_avatars', __('Banned avatars (from registering to the site)', 'sl-user-create'), 'sl_user_create_banned_avatars', 'sl_user_create', 'sl_user_create_security_section');	add_settings_field( 'allowed_simdns', __('Allowed simulator DNS entries', 'sl-user-create'), 'sl_user_create_allowed_simdns', 'sl_user_create', 'sl_user_create_security_section');

	// Instructions
	add_settings_section( 'sl_user_create_instructions_section', __('Instructions', 'sl-user-create'), 'sl_user_create_instructions_section_text', 'sl_user_create-instructions');

	// Registration objects - separate setting because it wreaks havoc otherwise
	register_setting('sl_user_create_objects', 'sl_user_create_objects');

} // end sl_user_create_register_settings()

function sl_user_create_add_defaults()
{
	$sl_user_create_settings = get_option('sl_user_create_settings');
	if ( false === $sl_user_create_settings ) {
		$sl_user_create_settings = array(
			'secret' => wp_generate_password(36, false),
			'secret_number' => date("dm"),		// simple way to get a 4-digit number
			'disable_signature' => false,		// for debugging purposes
			'plugin_version' => "(none)",		// temporary; will be filled later
			'allowed_avatars' => array(),		// avatars allowed to run script (empty means all)
			'banned_avatars' => array(),		// avatars forbidden to register with our site (empty means none)
			'allowed_simdns' => array('lindenlab.com')			// DNS/IP addresses allowed to run script (empty means all) - start just with LL's grid for security reasons
		);
	}

	// Figure out plugin version
	$plugin_data = get_plugin_data( __FILE__ );

	$sl_user_create_settings['plugin_version'] = $plugin_data['Version'];

	update_option('sl_user_create_settings', $sl_user_create_settings);
} // end sl_user_create_add_defaults()

function sl_user_create_validate($input)
{
	// no output if things get changed because the Settings API doesn't support error messages yet
	$mysettings = get_option('sl_user_create_settings');

	if (!isset($input['secret']) || strlen($input['secret']) < 4)
	{
		$mysettings['secret'] = wp_generate_password(36, false);
	}
	else $mysettings['secret'] = $input['secret'];

 	if (!isset($input['secret_number']) || strlen($input['secret_number']) != 4 || !is_numeric($input['secret_number']))
	{
		$mysettings['secret_number'] = date("dm");
 	}
 	else $mysettings['secret_number'] = $input['secret_number'];

 	if (!isset($input['disable_signature']))
 	{
 		$mysettings['disable_signature'] = false;
 	}
 	else $mysettings['disable_signature'] = $input['disable_signature'];

 	if (!isset($input['plugin_version']))
 	{
	 	$plugin_data = get_plugin_data( __FILE__ );

		$mysettings['plugin_version'] = $plugin_data['Version'];
 	}

	// Parse textareas, allowing for some flexibility
	// First clean up the avatars allowed to have registration objects for us
	$mysettings['allowed_avatars'] = preg_split("/[\r\n,]+/", wp_filter_nohtml_kses($input['allowed_avatars']), -1, PREG_SPLIT_NO_EMPTY);

	if (PREG_NO_ERROR != preg_last_error())
		$mysettings['allowed_avatars'] = array($input['allowed_avatars']);

	// This is for avatars banned from our site
	$mysettings['banned_avatars'] = preg_split("/[\r\n,]+/", wp_filter_nohtml_kses($input['banned_avatars']), -1, PREG_SPLIT_NO_EMPTY);

	if (PREG_NO_ERROR != preg_last_error())
		$mysettings['banned_avatars'] = array($input['banned_avatars']);

	// Now clean up the simulator DNS entries
	$mysettings['allowed_simdns'] = preg_split("/[\r\n,]+/", wp_filter_nohtml_kses($input['allowed_simdns']), -1, PREG_SPLIT_NO_EMPTY);

	if (PREG_NO_ERROR != preg_last_error())
		$mysettings['allowed_simdns'] = array($input['allowed_simdns']);
	// we should do a check to see if each DNS entry is valid, but we'll ignore that

 	return $mysettings;
} // end sl_user_create_validate()

/* Main text */

// Text before the option
function sl_user_create_main_section_text()
{
?>
	<p><?php _e('Script for ', 'sl-user-create'); _e('SL User Create', 'sl-user-create'); ?></p>
<?php
} // end sl_user_create_main_section_text()

function sl_user_create_security_section_text()
{
?>
	<p><?php _e('Security options for ', 'sl-user-create'); _e('SL User Create', 'sl-user-create'); ?></p>
<?php
} // end sl_user_create_security_section_text()


function sl_user_create_secret() {
	$options = get_option('sl_user_create_settings');
	echo "<input id='secret' name='sl_user_create_settings[secret]' size='36' type='text' value='{$options['secret']}' />";
	echo '<span class="description">' . __('Secret string','sl-user-create') . '</span>';
} // end sl_user_create_secret()

function sl_user_create_secret_number() {
	$options = get_option('sl_user_create_settings');
	echo "<input id='secret_number' name='sl_user_create_settings[secret_number]' size='4' type='text' value='{$options['secret_number']}' />";
	echo '<span class="description">' . __('4-digit secret salt','sl-user-create') . '</span>';
} // end sl_user_create_secret_number()

function sl_user_create_disable_signature()
{
	$options = get_option('sl_user_create_settings');
?>
	<input type='checkbox' id='disable_signature' name='sl_user_create_settings[disable_signature]' value='1' <?php checked($options['disable_signature'], 1); echo " ";  disabled(!function_exists('md5')); ?> />
<?php
	echo '<span class="description">' . __('Disable signature check (less secure!)','sl-user-create') . '</span>';
} // end sl_user_create_disable_signature()

function sl_user_create_allowed_avatars() {
	$options = get_option('sl_user_create_settings');
?>
<p>
<textarea id="allowed_avatars" name="sl_user_create_settings[allowed_avatars]" cols="40" rows="8" style="font-family: monospace" type="textarea">
<?php if (isset($options['allowed_avatars']) && is_array($options['allowed_avatars']))  echo implode("\n", $options['allowed_avatars']); ?>
</textarea>
<?php
	echo '<span class="description">' . __('Add avatar names allowed to use this script (empty means all are allowed), one per line','sl-user-create') . '</span>';
} // end sl_user_create_allowed_avatars

function sl_user_create_banned_avatars() {
	$options = get_option('sl_user_create_settings');
?>
<p>
<textarea id="banned_avatars" name="sl_user_create_settings[banned_avatars]" cols="40" rows="8" style="font-family: monospace" type="textarea">
<?php if (isset($options['banned_avatars']) && is_array($options['banned_avatars']))  echo implode("\n", $options['banned_avatars']); ?>
</textarea>
<?php
	echo '<span class="description">' . __('Add avatar names banned from this site (empty means all are allowed), one per line','sl-user-create') . '</span>';
} // end sl_user_create_banned_avatars

function sl_user_create_allowed_simdns() {
	$options = get_option('sl_user_create_settings');
?>
<p>
<textarea id="allowed_simdns" name="sl_user_create_settings[allowed_simdns]" cols="40" rows="8" style="font-family: monospace" type="textarea">
<?php if (isset($options['allowed_simdns']) && is_array($options['allowed_simdns'])) echo implode("\n", $options['allowed_simdns']); ?>
</textarea>
<?php
	echo '<span class="description">' . __('Add simulator DNS entries allowed to use this script (empty means all are allowed); partial entries are allowed, i. e. secondlife.com to allow registrations only from the SL Grid','sl-user-create') . '</span>';
} // end sl_user_create_allowed_avatars

function sl_user_create_text_area() {
	$settings = get_option('sl_user_create_settings');
	_e("Please create an object in Second Life on a plot owned by you, and drop the following script inside:", 'sl-user-create'); ?>
<p>
<textarea name="sl-user-create-lsl-script" cols="120" rows="24" readonly style="font-family: monospace">
// Code by Gwyneth Llewelyn to register avatars on WordPress sites
//
// Global Variables
key avatar;
string avatarName;
key registrationResponse;	// to send the PermURL to the blog
key webResponse;			// to send avatar requests to the blog
string objectVersion = "<?php echo $settings['plugin_version']; ?>";
string secret = "<?php esc_attr_e($settings['secret']); ?>";
integer secretNumber = <?php esc_attr_e($settings['secret_number']); ?>;
integer listener;

// modified by SignpostMarv
string http_host = "<?php echo site_url(); ?>";

default
{
    state_entry()
    {
        avatar = llGetOwner();
        avatarName = llKey2Name(avatar);
        llSetText("Registering with your blog at " + http_host + "\nand requesting PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
        // llMinEventDelay(2.0); // breaks on OpenSim
        llRequestURL();     // this sets the object up to accept external HTTP-in calls
    }

    on_rez(integer startParam)
    {
        llResetScript();
    }

    touch_start(integer howmany)  // Allow owner to reset this
    {
        llSetText("Sending registration request to " + http_host + "...", <0.6, 0.6, 0.1>, 1.0);

        string regAvatarName = llKey2Name(llDetectedKey(0));
        string regAvatarKey = llDetectedKey(0);
        string message =
            "avatar_name=" + llEscapeURL(regAvatarName) +
            "&avatar_key=" + llEscapeURL(regAvatarKey) +
            "&signature=" + llMD5String((string)llGetKey() + secret, secretNumber);
            // llOwnerSay("DEBUG: Message to send to blog is: " + message);
        webResponse = llHTTPRequest(http_host + "/wp-content/plugins/sl-user-create/register-avatar.php",
            [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],
            message);
    }

    changed(integer what)
    {
        if (what & CHANGED_OWNER)
            llResetScript();    // make sure the new owner gets a fresh PermURL!
        if (what & (CHANGED_REGION | CHANGED_TELEPORT) ) // you can add CHANGED_REGION_START on SL, but not for OpenSim
        {
            llSetText("Requesting PermURL from SL...", <0.8, 0.8, 0.1>, 1.0);
            llRequestURL();
        }
    }

    // This is just to catch that our website has the widget active
    http_response(key request_id, integer status, list metadata, string body)
    {
    	body = llStringTrim(body, STRING_TRIM);
        if (request_id == registrationResponse)
        {
            if (status == 200)
            {
                llOwnerSay("PermURL sent to gateway! Msg. id is " + body);
            }
            else if (status == 499)
            {
                llOwnerSay("Timeout waiting for gateway! Your PermURL might still be sent, please be patient");
            }
            else
            {
                llOwnerSay("PermURL NOT sent, registration object not activated. Status was " + (string)status + "; error message: " + body);
            }
        }
        else if (request_id == webResponse)
        {
            if (status == 200)
            {
                llOwnerSay("New avatar registration activated on WordPress site! Msg. received is " + body);
                // parse result to send user the password

                list result = llParseString2List(body, ["|"], []);
                key IMuser = llList2Key(result, 0);
                string command = llList2String(result, 1);
                string msg = llList2String(result, 2);

                if (command == "fail")
                {
                    llSetTimerEvent(60.0);
                    integer channel = (integer) llFrand(5000.0) + 1000;
                    llDialog(IMuser, "You are already registered to " + msg + " Reset password?", ["Reset"], channel);
                    listener = llListen(channel, "", IMuser, "Reset");
                }
                else
                    llInstantMessage(IMuser, msg);
            }
            else if (status == 499)
            {
                llOwnerSay("Timeout waiting for WordPress site!");
            }
            else
            {
                llOwnerSay("Avatar NOT registered. Request to WordPress site returned " + (string)status + "; error message: " + body);
            }
        }
        llSetText("", <0.0, 0.0, 0.0>, 1.0);
    }

    listen(integer channel, string name, key id, string message)
    {
        llSetText("Sending password reset request to " + http_host + "...", <0.6, 0.6, 0.1>, 1.0);
        string msg =
            "avatar_name=" + llEscapeURL(name) +
            "&avatar_key=" + llEscapeURL(id) +
            "&password=true" +
            "&signature=" + llMD5String((string)llGetKey() + secret, secretNumber);
            // llOwnerSay("DEBUG: Message to send to blog is: " + msg);
        webResponse = llHTTPRequest(http_host + "/wp-content/plugins/sl-user-create/register-avatar.php",
            [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],
            msg);
    }

    timer()
    {
        llListenRemove(listener);
        llSetTimerEvent(0.0);
    }

    // These are requests made from our blog to this object
    http_request(key id, string method, string body)
    {
        if (method == URL_REQUEST_GRANTED)
        {
            llSetText("Sending PermURL to blog...", <0.6, 0.6, 0.1>, 1.0);

            string avatarName = llKey2Name(llGetOwner());
            string message =
                "object_version=" + llEscapeURL(objectVersion) +
                "&PermURL=" + llEscapeURL(body) +
                "&signature=" + llMD5String((string)llGetKey() + secret, secretNumber);
            // llOwnerSay("DEBUG: Message to send to blog is: " + message);
            registrationResponse = llHTTPRequest(http_host + "/wp-content/plugins/sl-user-create/register-object.php",
                [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"],
                message);
        }
        else if (method == "POST" || method == "GET")
        {
            if (body == "") // weird, no request
            {
                llHTTPResponse(id, 403, "Empty message received");
            }
            else
            {
                list params = llParseStringKeepNulls(body, ["&", "="], []);

                if (llList2String(params, 0) == "command" && llList2String(params, 1) == "die") {
                    llHTTPResponse(id, 200, "Attempting to kill object in-world");
                    llDie();

                }
                else
                {
                    llHTTPResponse(id, 403, "Command not found");
                }
            }
        }
    }
}
</textarea>
<?php
} // end sl_user_create_text_area()


function sl_user_create_instructions_section_text()
{
	echo '<p>';
	_e('Need to automatically register new users on a WordPress site with their Second Life® avatar names? This plugin allows you to do so, by exhibiting a script that you can copy and place into an in-world object. Users just need to touch the object to get automatically registered; if they are already registered, they will just get a link to your site.', 'sl-user-create');
	echo '</p><p>';
	_e('New users will receive a password via the Second Life Instant Messaging Service, as well as a link to tell them the URL for your site. The new profile will include their avatar name as a login and their SL profile picture (if available via Web) will become their WordPress profile picture. If you have some special meta fields enabled on your WordPress profile, they will be filled in with some data from SL as well (e.g. location).', 'sl-user-create');
 	echo '</p><ol><li>';
 	_e('After installing the plugin, if you\'re using a cache manager (e.g. W3 Total Cache) make sure you add <strong>register-avatar.php</strong> and <strong>register-object.php</strong> to the exception list, or you\'ll get multiple registrations with the same name!', 'sl-user-create');
 	echo '</li><li>';
 	_e('Go to the Settings menu and look at the option for "SL User Create". You should be shown a pre-formatted LSL script.', 'sl-user-create');
 	echo '</li><li>';
 	_e('Launch Second Life.', 'sl-user-create');
 	echo '</li><li>';
 	_e('Create an object in your land. Make sure that scripts are active!', 'sl-user-create');
 	echo '</li><li>';
 	_e('Right-click to open the object\'s Build popup, and go to the Contents tab.', 'sl-user-create');
 	echo '</li><li>';
 	_e('Create a new script inside (just click on the button).', 'sl-user-create');
 	echo '</li><li>';
 	_e('Delete everything in that script.', 'sl-user-create');
 	echo '</li><li>';
 	_e('Now go back to the WordPress admin page you\'ve opened, and copy the script and paste it inside your LSL script in Second Life.', 'sl-user-create');
 	echo '</li><li>';
 	_e('Save the LSL script in Second Life; it should recompile.', 'sl-user-create');
 	echo '</li><li>';
 	_e('The LSL script will now try to contact your blog and register itself.', 'sl-user-create');
 	echo '</li></ol><p>';
 	_e('Now any avatar wishing to register for their blog will only need to touch this object and get immediately registered. You can have multiple objects, on several sims, even on different grids, and owned by different users.', 'sl-user-create');
 	echo '</p><p>';
 	_e('Avatar names have to be unique across your blog, which means that the same user cannot register from different grids with the same name (but they can change the password if they forgot it, and don\'t need to log in back to their original grid).', 'sl-user-create');
 	echo '</p><p>';
 	_e('From 0.2.0 onwards there is a security page which allows a few options to limit access from potential hackers.', 'sl-user-create');
 	echo '</p>';

} // end sl_user_create_instructions_section_text()

register_activation_hook(__FILE__, 'sl_user_create_add_defaults');
add_action('admin_menu', 'sl_user_create_admin_menu_options');
add_action('admin_init', 'sl_user_create_register_settings' );
?>