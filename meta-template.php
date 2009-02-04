<?php

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
function get_linked_meta($id, $field, $glue = ', ') {
	global $cfTaxonomies;

	return $cfTaxonomies->get_linked_meta($id, $field, $glue);
}

// Display a tag cloud using meta values as tags (See wp_tag_cloud() for available args)
function meta_cloud($key, $auth_id = '', $args = '') {
	global $cfTaxonomies;

	return $cfTaxonomies->meta_cloud($key, $auth_id, $args);
}

// Get all defined taxonomies as an associative array
function get_meta_taxonomies() {
	global $cfTaxonomies;

	return $cfTaxonomies->map;
}
