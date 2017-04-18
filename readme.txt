=== EDD FES Draft ===
Contributors: tsunoa, rubengc
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=64N6CERD8LPZN
Tags: tsunoa, rubengc, easy digital downloads, digital, download, downloads, edd, fes, frontend, submission, submissions, draft, e-commerce
Requires at least: 4.0
Tested up to: 4.7.3
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds draft submissions to Easy Digital Downloads Frontend Submissions plugin.

== Description ==

EDD FES Draft will add a complete draft functionality with some extra options (everyone could be enabled or disabled at any moment).

Allow your sellers to save product drafts to continue editing them later. In addition, the auto-save functionality will save all changes of the product being edited.

Keep the visibility of products already published! If a vendor creates a new version of a product that has already been published EDD FES Draft will automatically create a copy of the product and submit as pending the new one version.

= Features of EDD FES Draft =

* Allow vendors to save products as draft
* AJAX auto save
* Preview button
* Checkbox to toggle disabled status of submit to pending button
* Prevents already published products change their status (will create a copy that get removed on approve)
* Button on downloads list to decline submissions as draft
* Adds a meta box with all differences between original download a new version submitted

There's a [GIT repository](https://github.com/rubengc/edd-fes-draft) too if you want to contribute a patch.

This plugin requires [Easy Digital Downloads](http://wordpress.org/extend/plugins/easy-digital-downloads/ "Easy Digital Downloads") and  [Frontend Submissions](https://easydigitaldownloads.com/downloads/frontend-submissions/ "Frontend Submissions").

== Installation ==

= From WordPress backend =

1. Navigate to Plugins -> Add new.
2. Click the button "Upload Plugin" next to "Add plugins" title.
3. Upload the downloaded zip file and activate it.

= Direct upload =

1. Upload the downloaded zip file into your `wp-content/plugins/` folder.
2. Unzip the uploaded zip file.
3. Navigate to Plugins menu on your WordPress admin area.
4. Activate this plugin.

== Frequently Asked Questions ==

= Can I disable some functionalities of this plugin?  =

Yes, you can enable or disable all functions of this plugin (auto save, preview button, etc).

== Suggestions ==

If you have suggestions about how to improve EDD FES Draft, you can [write us](mailto:contact@tsunoa.com "Tsunoa") so we can bundle them into EDD FES Draft.

== Screenshots ==

1. Screenshot from frontend (Theme: vendd)

2. Screenshot from EDD settings page

3. Changes metabox

== Upgrade Notice ==

== Changelog ==

= 1.0.3 =
* Completely rewrite of the entire plugin
* New meta box with all differences between original download and new version submitted

= 1.0.2 =
* Support for auto approve setting

= 1.0.1 =
* Fix: If vendor saves quickly the download, sometimes creates a duplicated submission (one with draft status and other with pending status), added some checks to prevent this
* Add: Added a button on downloads list to decline pending submissions setting the status as draft (by default EDD FES put the declined submissions with trash status)

= 1.0 =
* Initial release