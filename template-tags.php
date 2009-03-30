<?php
/**
 * The following are template tags that you can use in your theme.
 * They are loaded automatically and do not need to be copied elsewhere.
 */

// Check if the current page is a meta page.
function is_meta() {
	return ! empty($GLOBALS['CFT_core']->query_vars);
}

/**
 * Display the meta title. You can choose the format.
 * $format: how to arrange meta name and meta value 
 * $between: what to put between multiple name => value pairs
 */
function get_meta_title($format = '%name%: %value%', $between = ' and ') {
	return $GLOBALS['CFT_core']->get_meta_title($format, $between);
}

/**
 * Similar to get_post_meta, except that returned values are links
 * $id: use get_the_ID();
 * $key: one of the defined taxonomy keys
 * $glue: what to put between values (optional)
 */
function get_linked_meta($id, $key, $glue = ', ', $relative = false) {
	return $GLOBALS['CFT_core']->get_linked_meta($id, $key, $glue, $relative);
}

/**
 * Display a tag cloud using meta values as tags
 * $metaArgs
 *	'key' => one of the defined taxonomy keys
 *	'auth_id' => restrict cloud tags to a single user (optional)
 *	'relative' => make tag links relative (default: false)
 * $cloudArgs: see wp_tag_cloud() for available args (optional)
 */
function meta_cloud($metaArgs, $cloudArgs = '') {
	return $GLOBALS['CFT_core']->meta_cloud($metaArgs, $cloudArgs);
}

/**
 * Display an advanced search box.
 * Make sure that your theme has wp_footer() called somewhere in footer.php
 * $exclude: an array of meta keys to be excluded
 */
function meta_filter_box($exclude = array()) {
	return $GLOBALS['CFT_core']->filter_box($exclude);
}

/** Display a percent relevance for each post
 * Should be used inside The Loop
 * $echo: whether to echo or return the content
 */
function meta_relevance($echo = true) {
	if ( !isset($GLOBALS['CFT_query']) )
		return false;

	return $GLOBALS['CFT_query']->meta_relevance($echo);
}

/**
 * Get all defined taxonomies as an associative array
 * $key => $value
 */
function get_meta_taxonomies() {
	return $GLOBALS['CFT_core']->map;
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

