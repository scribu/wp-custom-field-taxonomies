=== Custom Field Taxonomies ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: custom fields, meta, metadata, taxonomy, sort, cms
Requires at least: 2.5
Tested up to: 2.8.1
Stable tag: trunk

Use custom fields as ad-hoc taxonomies.

== Description ==
With this powerfull plugin you can enhance the capabilities of your site by making some of your custom fields act like tags:

= Sort posts in new ways =
You can have queries as simple as

http://yoursite.com/?foo=bar
(displays posts which have a meta key 'foo' with the value 'bar')

and as complex as

http://yoursite.com/?s=search_term&key1=valueA&key2=valueB...
(posts that match any regular WordPress query *and* match some or all key=value pairs)

= Easily choose the keys you will use =
In the settings page you can select which custom fields to register as meta taxonomies. (This is mandatory)

= Use built in template tags =
* `meta_filter_box()` generates an advanced meta search box
* `meta_cloud()` displays all the tags for a selected taxonomy (can also be restricted by author)
* `the_meta_title()` outputs a nice title for the search results page
* `all_meta_info()` displays all the meta values for each post
* `get_linked_meta()` works just like get_post_meta() with the difference that the values are links, not text
* `get_meta_taxonomies()` returns all defined taxonomies as an associative array
* `is_meta()` is a conditional tag that indicates if you're on a meta taxonomy page
* `meta_relevance()` displays a percentage that indicates how many key=value pair matches a post has

You can find out more about the available template tags by looking in the **template-tags.php** file

= Use the new theme template =
If you want post sorted by metadata to be displayed differently you can:
Copy meta.php from the plugin directory to your current theme directory. You can modify it to fit your theme, if necessary.

= Manage custom fields =
You can search and replace through both custom field keys and custom field values from the same settings page.

= Remove duplicate custom fields =
You can remove duplicate custom field pairs (key = value) in posts with the click of a button.

== Installation ==

1. Unzip the archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Add the taxonomies in the settings page.
1. Add template tags to your theme.

= Example =
Say you have this meta taxonomy defined:

mood - Current Mood

If you want to display linked values from that field for each post, add this inside The Loop:

`<p>Mood: <?php echo get_linked_meta(get_the_ID(), 'yourfield') ?></p>`

This will output:

Current Mood: [happy](http://example.com/?mood=happy)

== Frequently Asked Questions ==
= Why isn't it working with my theme? =
This is probably because you have `query_posts()` somewhere in there. An easy workaround:
1. Copy meta.php from the plugin directory into your theme directory
1. Make additional customizations, if necessary.

== Screenshots ==

1. The settings page

== Changelog ==

= 1.3.2 =
* fixed meta_cloud() args

= 1.3.1 =
* fixed "Save taxonomies" button
* added two hooks

= 1.3 =
* added AND, OR queries
* fixed compatibility with Smarter Navigation

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

