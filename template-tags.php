<?php
/**
 * The following functions are template tags that you can use in your theme.
 */


// Check if the current page is a meta page.
function is_meta() {
	global $wp_query;

	return (bool) $wp_query->is_meta;
}


/**
 * Display the meta title. You can choose the format.
 * $format: how to arrange meta name and meta value
 * $between: what to put between multiple name => value pairs
 */
function the_meta_title($format = '%name%: %value%', $between = '; ') {
	echo get_meta_title($format, $between);
}

/**
 * Same as the_meta_title(), except it returns the result, instead of echo-ing it
 */
function get_meta_title($format = '%name%: %value%', $between = '; ') {
	return CFT_Core::get_meta_title($format, $between);
}


/**
 * Similar to get_post_meta, except that returned values are links
 * $id: use get_the_ID();
 * $key: one of the defined taxonomy keys
 * $glue: what to put between values (optional)
 */
function get_linked_meta($id, $key, $glue = ', ', $relative = false) {
	if ( ! CFT_Core::is_defined($key) )
		return false;

	$values = get_post_meta($id, $key);

	if ( ! $values )
		return false;

	foreach ( $values as $i => $value )
		$values[$i] = html_link(CFT_Core::get_meta_url($key, $value, $relative), $value);

	if ( count($values) > 1 )
		$content = implode($glue, $values);
	else
		$content = reset($values);

	return apply_filters('get_linked_meta', $content, $id, $key, $glue);
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
	extract(wp_parse_args($metaArgs, array(
		'key' => NULL,
		'auth_id' => NULL,
		'relative' => false,
	)), EXTR_SKIP);

	if ( ! CFT_Core::is_defined($key) )
		return false;

	$cloudArgs = wp_parse_args($cloudArgs, array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
		'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view'
	));

	$tempArgs = scbUtil::array_extract($cloudArgs, array('number', 'exclude', 'include', 'orderby', 'order'));
	$tempArgs['auth_id'] = $auth_id;

	$tags = CFT_Core::get_meta_values($key, $tempArgs);

	if ( empty($tags) )
		return false;

	// Add links
	foreach ( $tags as $i => $tag )
		$tags[$i]->link = CFT_Core::get_meta_url($key, $tag->name, $relative);

	$return = wp_generate_tag_cloud($tags, $cloudArgs);

	if ( 'array' == $cloudArgs['format'] )
		return $return;

	echo $return;
}


/**
 * Display an advanced search box.
 * Make sure that your theme has wp_footer() called somewhere in footer.php
 * $exclude: an array of URL keys to be excluded
 */
function meta_filter_box($exclude = array()) {
	CFT_Filter_Box::display($exclude);
}


/**
 * Display a percent relevance for each post
 * Should be used inside The Loop
 */
function meta_relevance($before = 'Relevance: ', $after = '', $echo = true) {
	if ( ! is_meta() )
		return false;

	if ( ! CFT_Core::$options->relevance )
		$relevance = '100%';
	else {
		global $post;
		$relevance = round($post->meta_rank) . '%';
	}

	$result = $before . $relevance . $after;

	if ( ! $echo )
		return $result;

	echo $result;
}


/**
 * Get all defined taxonomies as an associative array:
 * $query_var => $title
 */
function get_meta_taxonomies($key_type = 'key') {
	return CFT_Core::get_map($key_type);
}


/**
 * EXTRA TEMPLATE TAG
 * This is based on the other template tags and is provided for convenience. (should be used within The Loop)
 * You can copy the following function into your functions.php theme file and modify it to your needs. (Don't forget to give it a different name)
 */
function all_meta_info() {
	$output = '';
	foreach ( get_meta_taxonomies() as $key => $name ) {
		$value = get_linked_meta(get_the_ID(), $key);

		if ( empty($name) )
			$name = ucfirst($key);

		if ( $value )
			$output .= sprintf("\t<tr><th><strong>%s</strong></th><td>%s</td></tr>\n", $name, $value);
	}

	if ( $output )
		echo "<table class='extra-info' cellspacing='0'>\n{$output}</table>\n";
}

