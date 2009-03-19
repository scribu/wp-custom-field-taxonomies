<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 1.1.0.2
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

define('CFT_AJAX_KEY', 'ajax-meta-search');
define('CFT_AJAX_URL', get_bloginfo('url') . '?' . CFT_AJAX_KEY . '=');
define('CFT_AJAX_URL_JS', "<script type='text/javascript'>window.cft_suggest_url = '" . CFT_AJAX_URL . "';</script>");

class CFT_core {
	public $map;
	public $matches;
	public $rankings;
	public $is_meta;

// Core methods

	public function __construct() {
		// Load options
		if ( ! class_exists('scbOptions_06') )
			require_once(dirname(__FILE__) . '/inc/scbOptions.php');

		$this->options = new scbOptions_06('cf_taxonomies');

		// Generate map with the latest settings
		$this->make_map();

		// Detect query
		if ( ! $this->is_meta = $this->detect_query() )
			return false;

		require_once(dirname(__FILE__) . '/query.php');
		$GLOBALS['CFT_query'] = new CFT_query($this->matches, $this->options->get('relevance'));

		// Set ajax response
		add_action('init', array($this, 'ajax_meta_search'));

		// Customize template and title
		add_action('template_redirect', array($this, 'add_template'), 999);
		add_filter('wp_title', array($this, 'set_title'), 20, 3);
	}

	public function make_map() {
		$this->map = (array) $this->options->get('map');

		foreach ( $this->map as $key => $name )
			if ( empty($name) )
				$this->map[$key] = ucfirst($key);

		// for convenience,
		return $this->map;
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

	public function add_template() {
		$template = TEMPLATEPATH . "/meta.php";

		if ( file_exists($template) ) {
			include($template);
			die;
		}
	}

	public function set_title($title, $sep, $seplocation = '') {
		$newtitle[] = $this->get_meta_title();
		$newtitle[] = " $sep ";

		if ( ! empty($title) )
			$newtitle[] = $title;

		if ( 'right' != $seplocation )
			$newtitle = array_reverse($newtitle);

		return implode('', $newtitle);
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

	public function meta_cloud($metaArgs, $cloudArgs = '') {
		extract(wp_parse_args($metaArgs, array(
			'key' => NULL,
			'auth_id' => NULL,
			'relative' => false
		)));

		if ( ! $this->is_defined($key) )
			return false;

		$cloudArgs = wp_parse_args($cloudArgs, array(
			'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
			'format' => 'flat', 'orderby' => 'name', 'order' => 'ASC',
			'exclude' => '', 'include' => '', 'link' => 'view'
		));

		$tags = $this->get_meta_values($key, $auth_id, $cloudArgs['number']);

		if ( empty($tags) )
			return false;

		// Add links
		foreach ( $tags as $i => $tag )
			$tags[$i]->link = $this->get_meta_url($key, $tag->name, $relative);

		$return = wp_generate_tag_cloud($tags, $cloudArgs);

		if ( 'array' == $cloudArgs['format'] )
			return $return;

		echo $return;
	}

	public function filter_box($exclude = array() ) {
		add_action('wp_footer', array($this, 'filter_box_scripts'));

		// Generate select
		$select = '<option />';
		foreach ( $this->map as $key => $name )
			if ( ! in_array($key, $exclude) )
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

	public function filter_box_scripts() {
		global $wp_scripts;

		$url = $this->get_plugin_url() . '/inc';
		$scriptf = "<script language='javascript' type='text/javascript' src='%s'></script>";

		// CSS
		$scripts[] = "<style type='text/css'>@import url('$url/filter-box.css');</style>";

		// Dependencies
		foreach ( array('jquery', 'suggest') as $name )
			if ( ! @in_array($name, $wp_scripts->done) )
				$scripts[] = sprintf($scriptf, get_option('siteurl') . "/wp-includes/js/jquery/$name.js");

		// Box script
		$scripts[] = CFT_AJAX_URL_JS;
		$scripts[] = sprintf($scriptf, "$url/filter-box.js");

		echo implode("\n", $scripts);
	}

	private function is_defined($key) {
		if ( ! $r = in_array($key, array_keys($this->map)) )
			trigger_error("Undefined meta taxonomy: $key", E_USER_WARNING);

		return $r;
	}

	// AJAX response
	public function ajax_meta_search() {
		if ( !isset( $_GET[CFT_AJAX_KEY] ) )
			return;

		global $wpdb;

		$key = $wpdb->escape(trim($_GET[CFT_AJAX_KEY]));
		$hint = $wpdb->escape(trim($_GET['q']));

		if ( ! $this->is_defined($key) )
			die(-1);

		@header('Content-Type: text/html; charset=' . get_option('blog_charset'));

		foreach ( $this->get_meta_values($key, NULL, 10, $hint) as $value )
			echo $value->name . "\n";

		die;
	}

	private function get_meta_values($key, $auth_id = NULL, $limit = NULL, $hint = NULL) {
		global $wpdb;

		$where = "AND m.meta_key = '$key'";

		if ( isset($hint) )
			$where .= " AND m.meta_value LIKE ('%{$hint}%')";

		if ( isset($auth_id) )
			$where .= " AND p.post_author = " . absint($auth_id);

		if ( isset($limit) )
			$limit_clause = 'LIMIT 0, ' . absint($limit);

		$values = $wpdb->get_results("
			SELECT m.meta_value AS name, COUNT(m.meta_value) AS count
			FROM {$wpdb->postmeta} m
			JOIN {$wpdb->posts} p ON m.post_id = p.ID
			WHERE p.post_status = 'publish'
			{$where}
			GROUP BY m.meta_value
			ORDER BY COUNT(m.meta_value) DESC
			{$limit_clause}
		");

		return apply_filters('cft_get_values', $values, $key, $auth_id, $limit, $hint);
	}

	private function get_meta_url($key, $value, $relative = false) {
		if ( is_singular() )
			$relative = false;

		if ( $relative )
			$url = $this->get_current_url();
		else
			$url = trailingslashit(get_bloginfo('url'));

		$url = add_query_arg($key, urlencode($value), $url);

		return apply_filters('cft_get_url', $url, $key, $value, $relative);
	}

	public function make_canonical() {
		// Get canonical location (shouldn't be relative for single posts)
		$location = trailingslashit(get_bloginfo('url'));

		foreach ( $this->matches as $key => $value )
			$location = add_query_arg($key, urlencode($value), $location);

		if ( $this->get_current_url() == $location )
			return; // all good

		wp_redirect($location, 301);
		die;
	}

	private function get_current_url() {
		$pageURL = ($_SERVER["HTTPS"] == "on") ? 'https://' : 'http://';

		if ( $_SERVER["SERVER_PORT"] != "80" )
			$pageURL .= $_SERVER["SERVER_NAME"]. ":" .$_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		else
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

		return $pageURL;
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
	$GLOBALS['CFT_core'] = new CFT_core();
	include_once(dirname(__FILE__) . '/template-tags.php');

	if ( is_admin() ) {
		include_once(dirname(__FILE__) . '/admin.php');
		new settingsCFT(__FILE__);
	}
}

cft_init();

// DEBUG
# add_action('wp_footer', create_function('', 'print_r($GLOBALS["wp_query"]->request);'));
