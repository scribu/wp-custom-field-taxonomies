<?php
/*
 * This is where the template tags are defined. 
 * They are loaded by the plugin and do not need to be copied elsewhere.
 * Just use them as you please.
 */

// Check if the current page is a meta page.
function is_meta() {
	global $cfTaxonomies;

	return $cfTaxonomies->is_meta;
}

// Display the meta title. You can choose the format.
function get_meta_title($format = '%key%: %value%') {
	global $cfTaxonomies;

	return $cfTaxonomies->get_meta_title($format);
}

// Similar to get_post_meta (values are links)
// $id: usually $post->ID
// $key: one of the defined taxonomy keys
// $glue: what to put between values (optional)
function get_linked_meta($id, $key, $glue = ', ') {
	global $cfTaxonomies;

	return $cfTaxonomies->get_linked_meta($id, $key, $glue);
}

/*
Display a tag cloud using meta values as tags
$key: one of the defined taxonomy keys
$auth_id: restrict cloud tags to a single user (optional)
$args: see wp_tag_cloud() for available args (optional)
*/
function meta_cloud($key, $auth_id = '', $args = '') {
	global $cfTaxonomies;

	return $cfTaxonomies->meta_cloud($key, $auth_id, $args);
}

// Get all defined taxonomies as an associative array
// $key => $value
function get_meta_taxonomies() {
	global $cfTaxonomies;

	return $cfTaxonomies->map;
}
