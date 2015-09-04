=== TRACK Connect ===
Contributors: trackhs
Tags: trackpm, track sync, track hs, track
Requires at least: 3.7
Tested up to: 4.2.3
Stable tag: 1.6.4

Creates and syncs listing-type posts from TRACK PM, a cloud-based property management system (www.trackhs.com).

== Description ==

TRACK Connect uses custom post types, taxonomies, templates, and widgets to create a listing management system for WordPress. It includes custom templates and widgets for front end display.

Single listings display the custom data automatically with no need to insert shortcodes to display listing data. If it's entered, it will display on the page.

Allows for any number of custom single listing templates to be created and displayed on a per listing basis.

== Installation ==

1. Upload the entire `track-connect` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Listings > Settings and input your Domain code and API token, then Save
4. Now you can hit the Sync Units button

= How to use the listings shortcode =

= Basic usage =
Just enter the following shortcode on any post or page

`[listings]`

= Advanced usage =
The shortcode accepts the following parameters:

`id` = listing post id (accepts one or more id's), exclusive, cannot be combined with other parameters, except for columns
`limit` = limit the number of posts to show, defaults to all
`columns` = display output in columns, accepts values 2-6, default is 1 column
`taxonomy` = taxonomy to display (must be used with the term parameter)
`term` = term to display (must be used with the taxonomy parameter)

Example advanced usage:
`[listings taxonomy="status" term="active" limit="10" columns="3"]`
This will display all listings in the "Status" taxonomy, assigned to the "Active" term, limited to 10 listings, in 3 columns

== Changelog ==

= 1.4 =
* New quote widget, general fixes

= 1.3 =
* Availability, formatting and taxonomy fixes

= 1.2 =
* Pagination fixes

= 1.1.1 =
* Domain update

= 1.1 =
* General bug fixes and updates

= 1.0 =
* Initial public release