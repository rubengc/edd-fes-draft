=== EDD FES Draft ===
Contributors: rubengc
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=64N6CERD8LPZN
Tags: easy digital downloads, digital, download, downloads, edd, rubengc, fes, frontend, submission, submissions, draft, e-commerce
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds draft submissions to Easy Digital Downloads Frontend Submissions plugin.

== Description ==
This plugin requires [Easy Digital Downloads](http://wordpress.org/extend/plugins/easy-digital-downloads/ "Easy Digital Downloads").
This plugin requires [Frontend Submissions](https://easydigitaldownloads.com/downloads/frontend-submissions/ "Frontend Submissions").

Once activated, EDD FES Draft will add a complete draft functionality with some extra options (everyone could be enabled or disabled at any moment):

1. Allow vendors to save products as draft
1. AJAX auto save
1. Preview button
1. Checkbox to toggle disabled status of submit to pending button
1. Prevents already published products change their status (will create a copy that get removed on approve)
1. Button on downloads list to decline submissions as draft

There's a [GIT repository](https://github.com/rubengc/edd-fes-draft) too if you want to contribute a patch.


== Installation ==

1. Unpack the entire contents of this plugin zip file into your `wp-content/plugins/` folder locally
1. Upload to your site
1. Navigate to `wp-admin/plugins.php` on your site (your WP Admin plugin page)
1. Activate this plugin
1. That's it!

OR you can just install it with WordPress by going to Plugins >> Add New >> and type this plugin's name

== Frequently Asked Questions ==

= Can I disable some functionalities of this plugin?  =

Yes, you can enable or disable all functions of this plugin (auto save, preview button, etc).

== Screenshots ==

1. Screenshot from frontend (Theme: vendd)

2. Screenshot from EDD settings page

== Upgrade Notice ==

== Changelog ==

= 1.0.2 =
* Support for auto approve setting

= 1.0.1 =
* Fix: If vendor saves quickly the download, sometimes creates a duplicated submission (one with draft status and other with pending status), added some checks to prevent this
* Add: Added a button on downloads list to decline pending submissions setting the status as draft (by default EDD FES put the declined submissions with trash status)

= 1.0 =
* Initial release