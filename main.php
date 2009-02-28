<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 0.9.3
Description: Use custom fields to make ad-hoc taxonomies
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/custom-field-taxonomies

Copyright (C) 2009 scribu.net (scribu AT gmail DOT com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class cfTaxonomies {
	public $map;
	public $matches;
	public $is_meta;

// Setup methods

	public function __construct() {
		// Generate map
		$this->map = (array) $GLOBALS['CFT_options']->get('map');
		foreach ( $this->map as $key => $name )
			if ( empty($name) )
				$this->map[$key] = ucfirst($key);

		// Detect query
		if ( ! $this->is_meta = $this->detect_query() )
			return false;

		// Retrieve appropriate posts
		if ( 1 == count($this->matches) )
			add_filter('request', array($this, 'single_match'));
		else
			add_filter('posts_where', array($this, 'multiple_match'));

		// Customize template and title
		add_action('template_redirect', array($this, 'add_template'), 999);
		add_filter('wp_title', array($this, 'set_title'), 20, 3);
	}

	private function detect_query() {
		if ( is_admin() || empty($_GET) || empty($this->map) )
			return false;

		$keys = array_keys($this->map);

		foreach ( $_GET as $key => $value )
			if ( in_array($key, $keys) )
				$this->matches[$key] = wp_specialchars($value);

		return !empty($this->matches);
	}

	// Use built in request args
	public function single_match($request) {
		$request['meta_key'] = key($this->matches);
		$request['meta_value'] = reset($this->matches);

		return $request;
	}

	// Find posts manually
	public function multiple_match($where) {
		global $wpdb;

		// Build CASE clauses
		foreach ( $this->matches as $key => $value )
			if ( empty($value) )
				$clauses[] = $wpdb->prepare("WHEN '%s' THEN meta_value IS NOT NULL", $key);
			else
				$clauses[] = $wpdb->prepare("WHEN '%s' THEN meta_value = '%s'", $key, $value);
		$clauses = implode("\n", $clauses);

		// Get posts that have all key=>value matches
		$ids = $wpdb->prepare("
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE
				CASE meta_key
					{$clauses}
				END
			GROUP BY post_id
			HAVING COUNT(post_id) = %d
		", count($this->matches));

		// Ignore other clauses if not displaying multiple posts
		if ( is_singular() )
			$where = " AND {$wpdb->posts}.post_type = 'post' AND {$wpdb->posts}.post_status = 'publish'";

		return $where . " AND {$wpdb->posts}.ID IN ({$ids})";
	}

	public function add_template() {
		$template = TEMPLATEPATH . "/meta.php";
		if ( file_exists($template) ) {
			include($template);
			die();
		}

		return;
	}

	public function set_title($title, $sep, $seplocation = '') {
		if ( empty($title) )
			if ( 'right' == $seplocation )
				return $this->get_meta_title() . " $sep ";
			else
				return " $sep " . $this->get_meta_title();
		else
			if ( 'right' == $seplocation )
				return $this->get_meta_title() . " $sep " . $title;
			else
				return $title . " $sep " . $this->get_meta_title();
	}

// Template tags

	public function get_meta_title($format = '%name%: %value%', $between = ' and ') {
		foreach ( $this->matches as $key => $value ) {
			$name = $this->map[$key];

			$title[] = str_replace(array('%name%', '%value%'), array($name, stripslashes($value)), $format);
		}

		return implode($between, $title);
	}

	public function get_linked_meta($id, $key, $glue = ', ', $relative = false) {
		if ( ! $this->is_defined($key) )
			return false;

		$values = get_post_meta($id, $key);

		if ( !$values )
			return false;

		foreach ( $values as $i => $value )
			$values[$i] = sprintf('<a href="%s">%s</a>', $this->get_meta_url($key, $value, $relative), $value);

		if ( count($values) > 1 )
			return implode($glue, $values);
		else
			return reset($values);
	}

	public function meta_cloud($key, $auth_id = '', $args = '') {
		if ( ! $this->is_defined($key) )
			return false;

		$tags = $this->get_meta_values($key, $auth_id);

		if ( empty($tags) )
			return false;

		foreach ( $tags as $i => $tag ) {
			$link = $this->get_meta_url($key, $tag->name);

			$tags[$i]->link = $link;
			$tags[$i]->id = $tag->term_id;
		}

		$return = wp_generate_tag_cloud($tags, $args); // Here's where those top tags get sorted according to $args

		if ( 'array' == $args['format'] )
			return $return;
		else
			echo $return;
	}

	public function filter_box($exclude = array() ) {
		add_action('wp_footer', array($this, 'filter_box_scripts'));

		// Generate select
		$select = '<option />';
		foreach ( $this->map as $key => $name )
			if ( !in_array($key, $exclude) )
				$select .= sprintf('<option value="%s">%s</option>', $key, $name);
		$select = "<select>{$select}</select>\n";
?>
<form class="meta-filter-box" method='GET' action="<?php bloginfo('url'); ?>">
<fieldset>
	<table class="meta-filters">
	</table>
	<div class="select-meta-filters">
		Add filter <?php echo $select; ?>
	</div>
	<input name="action" type="submit" value="Go" />
  </fieldset>
</form>
<?php
	}

// Helper methods

	private function is_defined($key) {
		if ( ! $r = in_array($key, array_keys($this->map)) )
			trigger_error("'{$key}' is not defined as a custom taxonomy", E_USER_WARNING);

		return $r;
	}

	private function get_meta_values($key, $auth_id) {
		global $wpdb;

		if ( !empty($auth_id) )
			$extra_clause = "AND p.post_author = {$auth_id}";

		$values = $wpdb->get_results($wpdb->prepare("
			SELECT m.meta_value AS name, COUNT(m.meta_value) AS count
			FROM {$wpdb->postmeta} m
			JOIN {$wpdb->posts} p ON m.post_id = p.ID
			WHERE p.post_status = 'publish'
			AND m.meta_key = '{$key}'
			{$extra_clause}
			GROUP BY m.meta_value
			ORDER BY COUNT(m.meta_value)
		"));

		return $values;
	}

	private function get_meta_url($key, $value, $relative = false) {
		global $wp_rewrite;

		$match = $key . '=' . urlencode($value);

		if ( $relative ) {
			$url = '?';
			if ( $_SERVER['QUERY_STRING'] )
				$url .= $_SERVER['QUERY_STRING'] . '&';
			$url .= $match;
		} else {
			$front = $wp_rewrite->using_permalinks ? rtrim($wp_rewrite->front, '/') : rtrim(get_bloginfo('url'), '/');
			$url = $front . '/?' . $match;
		}

		return $url;
	}

	public function filter_box_scripts() {
		global $wp_scripts;

		$scriptf = "<script language='javascript' type='text/javascript' src='%s'></script>";

		if ( ! @in_array('jquery', $wp_scripts->done) )
			$scripts[] = sprintf($scriptf, get_option('siteurl') . "/wp-includes/js/jquery/jquery.js");

		$scripts[] = sprintf($scriptf, $this->get_plugin_url() . '/inc/filter-box.js');
?>
<style type="text/css">
.meta-filters label {display:block}
.select-meta-filters {float:right}
</style>
<?php
		echo implode("\n", $scripts);
	}

	private function get_plugin_url() {
		// < WP 2.6
		if ( !function_exists('plugins_url') )
			return get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

		return plugins_url(plugin_basename(dirname(__FILE__)));
	}
}

// Init
function cft_init() {
	if ( !class_exists('scbOptions_05') )
		require_once(dirname(__FILE__) . '/inc/scbOptions.php');

	$GLOBALS['CFT_options'] = new scbOptions_05('cf_taxonomies');
	$GLOBALS['cfTaxonomies'] = new cfTaxonomies();

	if ( is_admin() ) {
		include_once(dirname(__FILE__) . '/admin.php');
		new settingsCFT(__FILE__);
	}

	include_once(dirname(__FILE__) . '/template-tags.php');
}

cft_init();

