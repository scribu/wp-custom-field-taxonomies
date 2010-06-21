=== Custom Field Taxonomies ===
Contributors: scribu
Donate link: http://scribu.net/paypal
Tags: custom fields, meta, metadata, taxonomy
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.4

Convert custom fields to tags, categories or taxonomy terms

== Description ==

With this simple plugin, you can convert all custom fields with a certain key to terms in a certain taxonomy (post tags, categories, etc.), while maintaining the post association.

== Installation ==

1. Unzip the archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Go to Tools -> CF Taxonomies

== Frequently Asked Questions ==

= Error on activation: "Parse error: syntax error, unexpected..." =

Make sure your host is running PHP 5. The only foolproof way to do this is to add this line to wp-config.php (after the opening `<?php` tag):

`var_dump(PHP_VERSION);`
<br>

== Changelog ==

= 2.0 =
* new direction: convert custom fields to taxonomy terms
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-2-0.html)

= 1.4 =
* added support for ranges: ?price-min=100&price-max=300
* security enhancements
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-1-4.html)

= 1.3.4 =
* fixed "Remove duplicates" button
* dropped support for WordPress older than 2.8

= 1.3.3 =
* ajax-ed admin page

= 1.3.2 =
* fixed meta_cloud() args

= 1.3.1 =
* fixed "Save taxonomies" button
* added two hooks

= 1.3 =
* added AND, OR queries
* fixed compatibility with Smarter Navigation
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-1-3.html)

= 1.2 =
* revamped Admin Page
* option to sort posts by key=value order
* doesn't mess with other loops except the main WP loop
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-1-2.html)

= 1.1 =
* wildcard support
* option to show posts that don't match all key=value pairs
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-1-1.html)

= 1.0 =
* several bugfixes and enhancements
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-1-0.html)

= 0.9 =
* meta search box
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-0-9.html)

= 0.8 =
* custom field management
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-0-8.html)

= 0.7 =
* multiple key=value pairs
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-0-7.html)

= 0.6 =
* relative URLs
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-0-6.html)

= 0.5 =
* initial release
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-0-5.html)

