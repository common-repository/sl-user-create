=== SL User Create ===
Contributors: GwynethLlewelyn
Donate link: https://paypal.me/gwynethllewelyn/
Tags: second life, login, registration, sl
Requires at least: 3.0
Tested up to: 6.3.1
Stable tag: 0.2.6

Allows Second Life® users to get automatically registered to a WordPress site by touching an object with a special script.

== Description ==

Need to automatically register new users on a WordPress site with their Second Life® avatar names? This plugin allows you to do so, by exhibiting a script that you can copy and place into an in-world object. Users just need to touch the object to get automatically registered; if they are already registered, they will just get a link to your site.

New users will receive a password via the Second Life Instant Messaging Service, as well as a link to tell them the URL for your site. The new profile will include their avatar name as a login and their SL profile picture (if available via Web) will become their WordPress profile picture. If you have some special meta fields enabled on your WordPress profile, they will be filled in with some data from SL as well (e.g. location).

Thanks to DALL-E by OpenAI for the cute logo.

== Installation ==

1. After installing the plugin, if you're using a cache manager (e.g. W3 Total Cache) make sure you add **register-avatar\.php** and **register-object\.php** to the exception list, or you'll get multiple registrations with the same name!
2. Go to the Settings menu and look at the option for "SL User Create". You should be shown a pre-formatted LSL script.
3. Launch Second Life.
4. Create an object in your land. Make sure that scripts are active!
5. Right-click to open the object's Build popup, and go to the Contents tab.
6. Create a new script inside (just click on the button).
7. Delete everything in that script.
8. Now go back to the WordPress admin page you've opened, and copy the script and paste it inside your LSL script in Second Life.
9. Save the LSL script in Second Life; it should recompile.
10. The LSL script will now try to contact your blog and register itself.

Now any avatar wishing to register for their blog will only need to touch this object and get immediately registered. You can have multiple objects, on several regions, even on different grids, and owned by different users.

Avatar names have to be unique across your blog, which means that the same user cannot register from different grids with the same name (but they can change the password if they forgot it, and don't need to log in back to their original grid). Note that WP logins might not work with dots in it and might get be replaced by underscores.

From 0.2.0 onwards there is a security page which allows a few options to limit access from potential hackers.

== Frequently Asked Questions ==

= I get errors when the in-world object tries to contact the external URL! =

A few people reported that their hostname is nowhere near what WordPress thinks it is, and so, obviously, registration will fail when the in-world object tries to contact your WordPress site.

0.2.4 changed the way the hostname is "figured out", but if it still gets it wrong, change on the LSL script what appears under **string http_host = "<your host name>"** Note: no trailing slashes.

= Can I place multiple objects for registration? =

Yes! The admin panel will list the currently active objects, where you can also delete the ones that are inactive. Each object will count how many avatars have been registered through it, so that you can keep track of which objects have been attracting more registrations.

You're also welcome to edit the LSL script, if you're talented enough, to personalise the user experience.

Objects can also be on different grids, so long as these are running OpenSimulator 0.7.0.2 or more recent.

= One user forgot the password. What now? =

Ask them to touch the object again. It will give them a popup box where they can reset the password and have it sent to them by Instant Message.

Note that this will work on any object registered with your site, no matter where it's placed (yes, even on a different grid!).

= Can I use this on my OpenSimulator grid, too? =

Yes, you can. This plugin was tested on an OpenSimulator grid running 0.9.X; versions earlier than 0.7 might not work. Please note that if you have registered on one grid with your avatar name, and then register on a **different** grid with the **same** avatar name, you'll just change the password — this plugin assumes that avatar names are unique across grids (because WordPress logins have to be unique).

Some errors are less obvious on OpenSimulator and difficult to track; this has to do with the way error messages are passed back.

Also, the auto-deletion feature might not work well under OpenSimulator (i.e. it gives an error, but in some cases, the object will actually disappear).

== Screenshots ==

1. Admin page with LSL script
2. Admin interface to manage registration objects
3. Configuring security settings

== Changelog ==

= 0.2.6 =
* Polishing, to better comply to WordPress style standards

The remaining (historical) changelog is kept on the file `changelog.txt`, as per the WP recommendations.

== Upgrade Notice ==

I got some reports that in some installations, the signature check fails. For that purpose, a new option was introduced to ignore signatures completely. This will be automatically disabled if the **md5()** function is not found.

**functions.php** was missing from the distribution! Thanks to ANSI Soderstrom for pointing it out to me.

Also, ANSI Soderstrom fixed a slight bug that prevented IMs to be sent to the users.

== Security ==

First of all, please take into account that there is no absolute fail-proof solution. Hackers will definitely be more creative than I am in preventing them to override security to register with your site. If you're afraid they might subvert your system and register accounts on your WordPress site, **DON'T USE THIS PLUGIN**!

If you're bold enough to try it out, read on.

This plugin was designed for having multiple locations for SL residents to register with your WordPress site. The caveat is that this will require you to provide them with the LSL script which you copy from the plugin's main page. Of course the script can be made no-modify and no-transfer, and so nobody will be able to read it, but we all know there are means to get access to it if someone really, really wants to (specially on OpenSimulator-based grids).

So there are four levels of protection in this plugin. Please note that three of them are possible to be forged; the fourth one is a bit more tricky but not totally impossible to subvert; how you use them all together is up to you, depending on how widespread and flexible you wish residents to register with your WordPress website.

The first level is a cryptographic signature. This is set from the plugin's admin page (actually you have two signatures; one long string with random garbage, and a 4-digit PIN code). All in-world requests need to be signed or the plugin will refuse access; this prevents anyone not knowing your secret key and PIN and trying to send a forged HTTP request to be refused access.

But keys can be compromised. Your first line of protection is simply to delete the culprits — all objects registered with your WP site will be listed, and you can easily delete the objects from the "Objects" tab, and change the keys. The plugin will also send a remote delete request. Of course, a hacker having access to the script will be able not only to figure out the original keys but also to remove the code for the remote deletion command. And if you have spread out registration objects all around SL and OpenSimulator grids, changing the keys will prevent **all** objects from working, which might not be feasible.

So the second level of protection is to restrict registration objects to certain avatars (we'll assume they're the only ones you deem to be legitimate). You can, for instance, just limit objects to be owned by you and your close friends or associates (or just your alts). This means that a hacker will not be able to create an object and place a hacked script inside; SL (and OpenSimulator) send the object owner's name and avatar key (UUID), and the plugin can refuse requests from anyone not on the list. On the security page, this is the first area (Allowed avatars (for registration objects)). There is no limit to the list.

This can be easily subverted: if a hacker knows your avatar name and knows what keys and PIN are in use, they can register on a OpenSimulator grid with **your** name and continue to create accounts on your WP site.

A related protection is to ban some avatar names from ever registering again. This is the second area. Note that this doesn't affect who can create **registration objects** and register them with your site; it prevents **avatars** from creating a login on your WordPress site. A banned avatar will be unable to create a login ever again, but they might still have an active registration object, and this area will not prevent **others** from registering via their object (meaning that the hacker will be able to create alts to log in).

Also, please note that things like avatar names and so forth are actually headers on the HTTP requests made by the in-world object, and these are sent by the SL grid itself (or the OpenSimulator grid). What this means is that hackers cannot launch attacks from within a grid (specially one that is not under their control), but they can create a piece of software that "pretends" to make a request from the grid but is actually running on a webserver with hacked headers.

The last level of protection tries to avoid the pitfalls of the three first protection levels. You can add a list of authorised domain names (full or partial) or IP addresses from which connections can be initiated. By default, when you install the plugin for the first time, it will only accept connections from SL's grid — all servers in it should look like "simXXX.agni.lindenlab.com", so filtering by "lindenlab.com" should only allow objects on the SL grid to register — all other grids will be banned. This will also prevent hackers from using their own webservers; headers can be forged, but the IP address from which the hacker is actually connecting is much, much harder to forge (I won't claim it's impossible, but it's beyond most human beings :) ). Even a hacker using a proxy or an "anonymiser" service to mask their original IP address will not work: you might never figure out where they are actually connecting from, but the plugin will know the request doesn't come from Second Life (or one OpenSimulator grid you actually trust), so it will block it nevertheless.

Instead of blocking a whole grid, you can just selectively accept requests from specific servers (e.g. only from "simXXX.agni.lindenlab.com"). Under OpenSimulator grids, this might not work as expected, because the same server might actually host a lot of different regions under the same IP address, and it will not be possible to differentiate between them. Also note that you can use IP addresses and not domain names, for quick-and-dirty OpenSimulator grids who never even bothered to register a DNS address and just have IP addresses (like home-run grids). Linden Lab's simulators should all be accessible via DNS, so it's safer that way; but they might shuffle regions around. The way to figure that out is by taking a look at "About Second Life", which should give you the DNS address of the simulator you're on.

So what would be the maximum level of protection?

* Making sure that nobody knows your keys and 4-digit PIN
* Restricting the registration only to yourself
* Limit the access from a single grid or even a single region/simulator

Again, this is not 100% secure, but should give you pretty much the same level of protection that WordPress itself provides: after all, if it's too difficult to subvert this plugin's authentication system, a hacker might try to register directly to your site, bypassing this plugin. These days, this is rather hard to do as well.

Also note that new users are created with the "Subscriber" role. This gives them the ability to post comments and create a profile, that's all. Of course, the ability to post comments also allows them to spam you...

Hackers might also try to launch a Distributed Denial-of-Service attack against you, but, again, this is pretty much impossible to do from within SL (LL's grid is proofed against launching DDoS attacks from in-world). It can be done outside SL, but it's far easier to attack the WordPress site itself.