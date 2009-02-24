=== Custom Field Taxonomies ===
Contributors: scribu
Donate link: http://scribu.net/wordpress
Tags: custom fields, meta, metadata, taxonomy, sort, cms
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: trunk

Use custom fields as ad-hoc taxonomies.

== Description ==
With this powerfull plugin you can enhance the capabilities of your site by making some of your custom fields act like tags:

= Sort posts in new ways =
You can have URLs as simple as

http://yoursite.com/?foo=bar
(displays posts which have a meta key 'foo' with the value 'bar')

and as complex as

http://yoursite.com/?s=anything&key1=valueA&key2=valueB...
(posts that match any regular WordPress query AND match each key=value pair)

= Use built in template tags =
* `meta_filter_box()` generates an advanced meta search box
* `meta_cloud()` displays all the tags for a selected taxonomy (can also be restricted by author)
* `get_linked_meta()` works just like get_post_meta() with the difference that the values are links, not text
* `get_meta_taxonomies()` returns all defined taxonomies as an associative array
* `get_meta_title()` return the current key and value to be used as the page title
* `is_meta()` is a conditional tag that indicates if you're on a meta taxonomy page

You can find out more about the available template tags by looking in the **template-tags.php** file

= Use the new theme template =
If you want post sorted by metadata to be displayed differently you can:
Copy meta.php from the plugin directory to your current theme directory and blend it with your theme.

= Easily choose the keys you will use =
In the settings page you can select which custom fields to register as meta taxonomies.

= Manage custom fields =
You can search and replace through both custom field keys and custom field values, from the same settings page.

== Installation ==

1. Unzip the archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Add the taxonomies in the settings page.
1. Add template tags to your theme.

= Example =
Say you have this meta taxonomy defined:

mood - Mood

If you want to display linked values from that field for each post, add this inside The Loop:

`<p>Mood: <?php echo get_linked_meta($post->ID, 'yourfield') ?></p>`

This will output:

Mood: [happy](http://yoursite.com/?mood=happy)

== Frequently Asked Questions ==
= Why isn't it working with my theme? =
This is probably because you have `query_posts()` somewhere in there. An easy workaround:
1. Create a new file in your theme directory and call it meta.php
1. Copy everything from index.php to meta.php
1. In meta.php, remove any calls to query_posts()
1. Make additional customizations, if necessary.

