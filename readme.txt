=== Custom Field Taxonomies ===
Contributors: scribu
Donate link: http://scribu.net/projects
Tags: custom fields, meta, metadata, taxonomy
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: trunk

Use custom fields as ad-hoc taxonomies.

== Description ==
Use custom fields as ad-hoc taxonomies:

= Sort posts by custom field values =
If you go to a url like http://yoursite.com/?foo=bar you will see only posts which have a custom field with the key 'foo' with the value 'bar'.

= Several template tags =
You can find out more about template tags by looking at `template-tags.php`

* `meta_cloud()` for each taxonomy (can also be restricted by author)
* `get_linked_meta()` works just like get_post_meta() with the difference that the values are linked to the sorted post
* `get_meta_taxonomies()` returns all defined taxonomies as an associative array
* `get_meta_title()` return the current key and value to be used as the page title
* `is_meta()` is a conditional tag that indicates if you're on the sorted posts

= Theme template =
If you want post sorted by metadata be displayed differently you can:
Go to your theme directory, make a copy of index.php and call it meta.php.
You can edit meta.php like any other theme file to suit your needs.

= Settings page =
It also has a nice settings page where you can define which fields to be used as taxonomies.

Features still to be added:

* import from Custom Field Template plugin
* widgets
* permalinks

== Installation ==

1. Unzip the archive and put the folder into your plugins folder (/wp-content/plugins/).
1. Activate the plugin from the Plugins admin menu.
1. Add the taxonomies in the settings page.
1. Add template tags to your theme.

= Examples =
Say you have this meta taxonomy defined:

mood - Mood

If you want to display linked values from that field for each post, add this inside The Loop:

`<p>Mood: <?php echo get_linked_meta($post->ID, 'yourfield') ?></p>`

