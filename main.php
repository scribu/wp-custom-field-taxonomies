<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 1.5a
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

class CFT_core {
	const ver = '1.5';
	static $options;

	private static $map;
	private static $query_vars;
	private static $other_keys = array('meta_orderby', 'meta_order');
	private static $other_query_vars;

	static function init($options) {
		self::$options = $options;

		self::make_map();

		if ( ! self::collect_query_vars() )
			return false;

		require_once dirname(__FILE__) . '/query.php';
		CFT_query::init(self::$query_vars, self::$other_query_vars);

		// Customize template and title
		add_action('template_redirect', array(__CLASS__, 'add_template'), 999);
		add_filter('wp_title', array(__CLASS__, 'set_title'), 20, 3);
	}

	static function make_map() {
		self::$map = (array) self::$options->map;

		foreach ( self::$map as $key => $name )
			if ( empty($name) )
				self::$map[$key] = ucfirst($key);

		return self::$map;
	}

	static function get_map() {
		if ( ! is_array(self::$map) )
			return self::make_map();

		return self::$map;
	}

	static function get_var($key = '') {
		if ( empty($key) )
			return self::$query_vars;

		return self::$query_vars[$key];
	}

	static function get_other_vars() {
		return self::$other_keys;
	}

	private static function collect_query_vars() {
		if ( is_admin() || empty(self::$map) )
			return false;

		$keys = array_keys(self::$map);

		self::$query_vars = scbUtil::array_extract($_GET, $keys);

		foreach ( $keys as $key ) {
			$min = @$_GET["$key-min"];
			$max = @$_GET["$key-max"];

			if ( $min || $max )
				self::$query_vars[$key] = compact('min', 'max');
		}

		self::$other_query_vars = scbUtil::array_extract($_GET, self::$other_keys);

		self::$query_vars = apply_filters('cft_query_vars', self::$query_vars, self::$map);

		return ! empty(self::$query_vars);
	}

	static function add_template() {
		if ( $template = locate_template(array('meta.php')) ) {
			include($template);
			die;
		}
	}

	static function set_title($title, $sep, $seplocation = '') {
		$newtitle[] = self::get_meta_title();
		$newtitle[] = " $sep ";

		if ( ! empty($title) )
			$newtitle[] = $title;

		if ( 'right' != $seplocation )
			$newtitle = array_reverse($newtitle);

		return implode('', $newtitle);
	}

	static function get_meta_title($format = '%name%: %value%', $between = '; ') {
		foreach ( CFT_core::$query_vars as $key => $value ) {
			$name = CFT_core::$map[$key];

			if ( is_array($value) )
				$value = esc_html($value['min']) . ' &mdash; ' . esc_html($value['max']);
			else
				$value = esc_html($value);

			$title[] = str_replace(array('%name%', '%value%'), array($name, $value), $format);
		}

		return implode($between, $title);
	}

// Helper methods

	static function is_defined($key) {
		if ( ! $r = in_array($key, array_keys(self::$map)) )
			trigger_error("Undefined meta taxonomy: $key", E_USER_WARNING);

		return $r;
	}

	static function get_meta_values($key, $args = '') {
		extract(wp_parse_args($args, array(
			'auth_id' => 0,
			'hint' => '',
			'orderby' => 'name',
			'order' => 'ASC',
		)), EXTR_SKIP);

		global $wpdb;

		$key = esc_sql($key);
		$hint = like_escape(esc_sql($hint));
		$auth_id = absint($auth_id);
		$number = absint($number);

		$where = "AND m.meta_key = '$key'";

		if ( ! empty($hint) )
			$where .= " AND m.meta_value LIKE ('%{$hint}%')";

		// Author
		if ( $auth_id != 0 )
			$where .= " AND p.post_author = " . $auth_id;

		// Limit
		if ( $number != 0 )
			$limit_clause = 'LIMIT 0, ' . $number;

		// Exclude / include
		$exterms = self::terms_clause($exclude);
		if ( ! empty($exterms) ) {
			unset($include);
			$where .= " AND m.meta_value NOT IN $exterms";
		}

		$interms = self::terms_clause($include);
		if ( ! empty($interms) )
			$where .= " AND m.meta_value IN $interms";

		$orderby = "ORDER BY $orderby $order";

		$values = $wpdb->get_results("
			SELECT m.meta_value AS name, COUNT(m.meta_value) AS count
			FROM {$wpdb->postmeta} m
			JOIN {$wpdb->posts} p ON m.post_id = p.ID
			WHERE p.post_status = 'publish' {$where}
			GROUP BY m.meta_value {$orderby} {$limit_clause}
		");

		return apply_filters('cft_get_values', $values, $key, $auth_id, $limit, $hint);
	}

	static function get_meta_url($key, $value, $relative = false) {
		if ( is_singular() )
			$relative = false;

		if ( $relative )
			$url = self::get_current_url();
		else
			$url = trailingslashit(get_bloginfo('url'));

		$url = add_query_arg($key, urlencode($value), $url);

		return apply_filters('cft_get_url', $url, $key, $value, $relative);
	}

/*
	// not used (buggy)
	static function make_canonical() {
		// Get canonical location (shouldn't be relative for single posts)
		$location = trailingslashit(get_bloginfo('url'));

		foreach ( self::$query_vars as $key => $value )
			$location = add_query_arg($key, urlencode($value), $location);

		if ( self::get_current_url() == $location )
			return; // all good

		wp_redirect($location, 301);
		die;
	}

	static function get_current_url() {
		$pageURL = ($_SERVER["HTTPS"] == "on") ? 'https://' : 'http://';

		if ( $_SERVER["SERVER_PORT"] != "80" )
			$pageURL .= $_SERVER["SERVER_NAME"]. ":" .$_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		else
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

		return $pageURL;
	}
*/

	private static function terms_clause($str) {
		if ( empty($str) )
			return '';

		$terms = preg_split('/[\s,]+/', $str);

		return '(' . scbUtil::array_to_sql($terms) . ')';
	}

	static function debug() {
		$query = $GLOBALS["wp_query"]->request;
		foreach (array('FROM', 'JOIN', 'WHERE', 'AND', 'LIMIT', 'GROUP', 'ORDER', "\tWHEN", 'END') as $c)
			$query = str_replace(trim($c), "\n".$c, $query);

		echo "<pre style='text-align:left; font-size: 12px'>" . trim($query) . "</pre>";
	}
}

// Sets up scripts and AJAX suggest
class CFT_filter_box {
	const ajax_key = 'ajax-meta-search';

	static function init() {
		add_action('wp_ajax_' . self::ajax_key, array(__CLASS__, 'ajax_meta_search'));
		add_action('wp_ajax_nopriv_' . self::ajax_key, array(__CLASS__, 'ajax_meta_search'));
	}

	static function scripts() {
		global $wp_scripts;

		$url = plugin_dir_url(__FILE__) . 'inc/';
		$scriptf = "<script language='javascript' type='text/javascript' src='%s'></script>";

		// CSS
		$scripts[] = "<style type='text/css'>@import url('$url/filter-box.css');</style>";

		// Dependencies
		foreach ( array('jquery', 'suggest') as $name )
			if ( ! @in_array($name, $wp_scripts->done) )
				$scripts[] = sprintf($scriptf, trailingslashit(get_bloginfo('url')) . "wp-includes/js/jquery/$name.js");

		$ajax_url = admin_url('admin-ajax.php?action=' . self::ajax_key . '&key=');

		$scripts[] = "<script type='text/javascript'>window.cft_suggest_url = '" . $ajax_url . "';</script>";
		$scripts[] = sprintf($scriptf, $url . 'filter-box.js');

		echo implode("\n", $scripts);
	}

	static function ajax_meta_search() {
		$key = trim($_GET['key']);
		$hint = trim($_GET['q']);

		if ( ! CFT_core::is_defined($key) )
			die(-1);

		foreach ( CFT_core::get_meta_values($key, "number=10&hint=$hint") as $value )
			echo $value->name . "\n";

		die;
	}
}

/*
class CFT_rewrite extends scbRewrite {
	function generate() {
		global $wp_rewrite, $wp;

		$tags = array_keys(CFT_core::get_map());

		foreach ( $tags as $tag ) {
			$wp->add_query_var($tag);
			$wp_rewrite->add_rewrite_tag("%$tag%", '([^/]+)', "$tag=");
			$wp_rewrite->add_permastruct($tag, "/%$tag%");
		}
	}
}
*/

function _cft_init() {
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

	$options = new scbOptions('cf_taxonomies', __FILE__, array(
		'map' => '',
		'relevance' => true,
		'rank_by_order' => false,
		'allow_and' => false,
		'allow_or' => false,
	));

	CFT_core::init($options);
	CFT_filter_box::init();

	require_once dirname(__FILE__) . '/template-tags.php';

	if ( is_admin() ) {
		require_once dirname(__FILE__) . '/admin.php';
		new settingsCFT(__FILE__, $options, CFT_core::make_map());
	}

	// DEBUG
	if ( CFT_DEBUG === true )
		add_action('wp_footer', array('CFT_core', 'debug'));
}

_cft_init();

