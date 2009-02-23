<?php
/**
 * The following are template tags that you can use in your theme.
 * They are loaded automatically and do not need to be copied elsewhere.
 */

// Check if the current page is a meta page.
function is_meta() {
	global $cfTaxonomies;

	return $cfTaxonomies->is_meta;
}

// Display the meta title. You can choose the format.
function get_meta_title($format = '%name%: %value%', $between = ' and ') {
	global $cfTaxonomies;

	return $cfTaxonomies->get_meta_title($format, $between);
}

/**
 * Similar to get_post_meta (values are links)
 * $id: usually $post->ID
 * $key: one of the defined taxonomy keys
 * $glue: what to put between values (optional)
 */
function get_linked_meta($id, $key, $glue = ', ', $relative = false) {
	global $cfTaxonomies;

	return $cfTaxonomies->get_linked_meta($id, $key, $glue, $relative);
}

/**
 * Display a tag cloud using meta values as tags
 * $key: one of the defined taxonomy keys
 * $auth_id: restrict cloud tags to a single user (optional)
 * $args: see wp_tag_cloud() for available args (optional)
 */
function meta_cloud($key, $auth_id = '', $args = '') {
	global $cfTaxonomies;

	return $cfTaxonomies->meta_cloud($key, $auth_id, $args);
}

/**
 * Get all defined taxonomies as an associative array
 * $key => $value
 */
function get_meta_taxonomies() {
	global $cfTaxonomies;

	return $cfTaxonomies->map;
}

/**
 * Display an advanced search box.
 * Make sure that your theme has wp_footer() called somewhere in footer.php
 * $exclude: an array of meta keys to be excluded
 */
function meta_filter_box($exclude = array()) {
	global $cfTaxonomies;

	return $cfTaxonomies->filter_box($exclude);
}

/**
 * EXTRA TEMPLATE TAG
 * This is based on the other template tags and is provided for convenience. (should be used within The Loop)
 * You can copy the following function into your functions.php theme file and modify it to your needs. (Don't forget to give it a different name)
 */
function all_meta_info() {
	global $post;

	foreach ( get_meta_taxonomies() as $key => $name ) {
		$value = get_linked_meta($post->ID, $key);

		if ( empty($name) )
			$name = ucfirst($key);

		if ( $value )
			$output .= sprintf("\t<tr><th><strong>%s</strong></th><td>%s</td></tr>\n", $name, $value);
	}

	if ( $output )
		echo "<table id='extra-info' cellspacing='0'>\n{$output}</table>\n";
}
