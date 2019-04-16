=== Simple Membership GetResponse Integration ===
Contributors: smp7, wp.insider
Donate link: https://simple-membership-plugin.com/
Tags: getresponse, autoresponder, email, signup, optin, member, members, membership, access, subscribe
Requires at least: 3.8
Tested up to: 5.0
Stable tag: 1.9.1
License: GPLv2 or later

An addon for the simple membership plugin to signup members to your GetResponse list

== Description ==

This addon allows you to specify a GetResponse list name for each of your access levels. When members join your site, they get signed up to the specified GetResponse list.

This addon requires the [simple membership plugin](http://wordpress.org/plugins/simple-membership/).

After you install this addon, edit your membership level and specify the getresponse list name. Then go to the GetResponse settings interface and specify your API Key.

Read the following page for step by step usage documentation:
https://simple-membership-plugin.com/signup-members-getresponse-list/

== Installation ==

1. Upload `mw-getresponse-signup` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

None

== Screenshots ==

None

== Changelog ==

= 1.9.1 =
- Fixed the double opt-in checkbox not taking effect.

= 1.9 =
- Added a new option in the settings to enable double opt-in for getresponse subscription.

= 1.8 =
- Appropriate interest groups are set when member upgrades to new membership level and when membership level is changed via the admin dashboard. Requires Simple Membership v3.6.0 or higher.
- An update related to Getresponse API v3.0.

= 1.7 =
* Adding architecture to handle interest group name with the list name.

= 1.6 = 
* Getresponse signup will be triggered when members are added via the admin dashboard also. Requires Simple Membership v3.4.5 or higher.

= 1.5 =
* Updated the Getresponse API library to their latest version (v3.0). It requires PHP v5.3 at least.

= 1.4 =
* It will now perform Getresponse signup when used with the WP User import addon.

= 1.3 =
* Minor fix for the form builder integration.

= 1.2 =
* Added integration with the form builder addon.

= 1.1 =
* Minor architectural improvements.

== Upgrade Notice ==

The new GetResponse library requires PHP v5.3. So your server must have PHP v5.3 to use the updated plugin.
