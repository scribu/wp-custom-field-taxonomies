<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 1.5b2
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

class CFT_Core {
	const VERSION = '1.5';

	static $options;

	private static $query_vars;
	private static $other_keys = array('meta_orderby', 'meta_order');
	private static $other_query_vars;

	static function init($options) {
		self::$options = $options;

		register_activation_hook(__FILE__, array(__CLASS__, 'upgrade'));

		if ( ! self::collect_query_vars() )
			return false;

		require_once dirname(__FILE__) . '/query.php';
		CFT_query::init(self::$query_vars, self::$other_query_vars);

		add_action('template_redirect', array(__CLASS__, 'add_template'));
		add_filter('wp_title', array(__CLASS__, 'set_title'), 10, 3);

		if ( defined('CFT_DEBUG') )
			add_action('wp_footer', array(__CLASS__, 'debug'));
	}

	static function upgrade() {
		$map = (array) self::$options->map;

		// CFT < 1.5
		if ( is_array(reset($map)) )
			return;

		foreach ( $map as $cf_key => $title ) {
			if ( !$title )
				$title = ucfirst($cf_key);

			$map[$cf_key] = array(
				'query_var' => $cf_key,
				'title' => $title
			);
		}

		self::$options->map = $map;
	}

	static function get_map($key_type) {
		$r = array();
		if ( $key_type == 'key' )
			foreach ( self::$options->map as $key => $args )
				$r[$key] = $args['title'];
		else
			foreach ( self::$options->map as $args )
				$r[$args['query_var']] = $args['title'];

		return $r;
	}

	static function get_raw_map() {
		return self::$options->map;
	}

	static function is_defined($key) {
		return isset(self::$options->map[$key]);
	}

	// Get information about a particular query var
	static function get_info($query_var) {
		foreach ( self::$options->map as $key => $args )
			if ( $args['query_var'] == $query_var ) {
				$args['key'] = $key;
				return $args;
			}

		return false;
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
		if ( is_admin() || empty(self::$options->map) )
			return false;

		$qv = array_keys(self::get_map('query_var'));

		self::$query_vars = scbUtil::array_extract($_GET, $qv);

		$types = array('min', 'max', 'like');

		foreach ( $qv as $query_var ) {
			$vars = array();
			foreach ( $types as $type )
				if ( isset($_GET["$query_var-$type"]) )
					$vars[$type] = $_GET["$query_var-$type"];

			if ( !empty($vars) )
				self::$query_vars[$query_var] = $vars;
		}

		self::$query_vars = apply_filters('cft_query_vars', self::$query_vars, self::$options->map);

		self::$other_query_vars = scbUtil::array_extract($_GET, self::$other_keys);

		return !empty(self::$query_vars) || !empty(self::$other_query_vars);
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
		$map = CFT_Core::get_map('query_var');
	
		foreach ( CFT_Core::$query_vars as $key => $value ) {
			$name = $map[$key];

			if ( is_array($value) ) {
				if ( isset($value['like']) )
					$value = esc_html($value['like']);
				elseif ( (isset($value['min']) || isset($value['max'])) )
					$value = esc_html(@$value['min']) . ' &mdash; ' . esc_html(@$value['max']);
			}
			else {
				$value = esc_html($value);
			}

			$title[] = str_replace(array('%name%', '%value%'), array($name, $value), $format);
		}

		return implode($between, $title);
	}

// Helper methods

	static function get_meta_values($key, $args = '') {
		extract(wp_parse_args($args, array(
			'auth_id' => 0,
			'hint' => '',
			'orderby' => 'name',
			'order' => 'ASC',
			'include' => '',
			'exclude' => '',
			'limit' => '',
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

		$query_var = self::$options->map[$key]['query_var'];

		if ( $relative )
			$url = self::get_current_url();
		else
			$url = trailingslashit(get_bloginfo('url'));

		$url = add_query_arg($query_var, urlencode($value), $url);

		return apply_filters('cft_get_url', $url, $key, $value, $relative);
	}
	
	private static function get_current_url() {
		$pageURL = ($_SERVER["HTTPS"] == "on") ? 'https://' : 'http://';

		if ( $_SERVER["SERVER_PORT"] != "80" )
			$pageURL .= $_SERVER["SERVER_NAME"]. ":" .$_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		else
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

		return $pageURL;
	}

	private static function terms_clause($str) {
		if ( empty($str) )
			return '';

		$terms = preg_split('/[\s,]+/', $str);

		return '(' . scbUtil::array_to_sql($terms) . ')';
	}

	static function debug() {
		global $wp, $wp_query;

		$query = $wp_query->request;
		foreach (array('FROM', 'JOIN', 'WHERE', 'AND', 'LIMIT', 'GROUP', 'ORDER', "\tWHEN", 'END') as $c)
			$query = str_replace(trim($c), "\n".$c, $query);

		echo "<pre style='text-align:left; font-size: 12px'>" . trim($query) . "</pre>";
	}
}

function _cft_init() {
	// Load scbFramework
	require_once dirname(__FILE__) . '/scb/load.php';

	$options = new scbOptions('cf_taxonomies', __FILE__, array(
		'map' => array(),
		'relevance' => true,
		'rank_by_order' => false,
		'allow_and' => false,
		'allow_or' => false,
	));

	CFT_Core::init($options);

	require_once dirname(__FILE__) . '/template-tags.php';
	require_once dirname(__FILE__) . '/filter-box/filter-box.php';

	if ( is_admin() ) {
		require_once dirname(__FILE__) . '/admin/admin.php';
		new CFT_Admin(__FILE__, $options);
	}
}

_cft_init();

