=== Custom Field Taxonomies ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: custom fields, meta, metadata, taxonomy, sort, cms
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 1.4

Use custom fields like regular post tags, sort posts based on custom fields and much more

== Description ==
With this powerfull plugin you can enhance your site navigation by sorting posts in various ways, based on custom field values.

= Filter posts in new ways =
Simple example:

http://yoursite.com/?price=1000
(displays posts which have a meta key 'price' with the value '1000')

Advanced example:

http://yoursite.com/category/special/?price-min=500&price-max=2000&color=white,green,blue
(displays posts in the Special category, with a price between 500 and 2000 and one of these colors: white, green, blue)

= Easily manage custom fields =
On the settings page, besides choosing which custom fields should be searchable, you have several utilities for custom field maintenance:

* search and replace through both custom field keys and custom field values
* remove duplicate custom fields
* add default values for certain custom field keys

= Use a new theme template =
If you want post sorted by metadata to be displayed differently you can:
Copy `meta.php` from the plugin directory to your current theme directory. You can modify it to fit your theme as necessary.

= Customize your theme with template tags =

The plugin features several template tags that will help you display item information easily:

* `meta_filter_box()` generates an advanced search box
* `meta_cloud()` displays all the custom field values for a specific key (can also be restricted by post author)
* `the_meta_title()` outputs a nice title for the search results page
* `all_meta_info()` displays all the meta values for each post
* `get_linked_meta()` works just like get_post_meta() with the difference that the values are links, not text
* `get_meta_taxonomies()` returns all searchable custom fields as an associative array
* `is_meta()` is a conditional tag that indicates if you're on a meta taxonomy page
* `meta_relevance()` displays a percentage that indicates how many key=value pair matches a post has

You can find out more about the available template tags by looking in the **template-tags.php** file

== Installation ==

1. Unzip the archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Go to Posts -> CF Taxonomies and select which custom fields should be searchable.

= Example =
Say you have this meta taxonomy defined:

mood - Current Mood

If you want to display linked values from that field for each post, add this inside The Loop:

`<p>Mood: <?php echo get_linked_meta(get_the_ID(), 'yourfield') ?></p>`

This will output:

Current Mood: [happy](http://example.com/?mood=happy)

== Frequently Asked Questions ==

= "Parse error: syntax error, unexpected T_CLASS..." Help! =

Make sure your new host is running PHP 5. Add this line to wp-config.php:

`var_dump(PHP_VERSION);`

== Screenshots ==

1. The settings page

== Changelog ==

= 1.5 =
* added meta_orderby & meta_order parameters
* added URL key field
* added key-like queries
* fixed bug in settings page
* [more info](http://scribu.net/wordpress/custom-field-taxonomies/cft-1-5.html)

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

