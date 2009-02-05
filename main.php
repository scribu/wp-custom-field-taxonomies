<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 0.5.3
Description: Use custom fields to make ad-hoc taxonomies
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/projects/custom-field-taxonomies/

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
	var $map;
	var $matches;
	var $is_meta;

// Setup functions

	function __construct() {
		$this->map = $GLOBALS['CFT_options']->get('map');

		if ( !$this->detect_query() )
			return;

		// Retrieve appropriate posts
		add_filter('posts_join', array($this, 'posts_join'));
		add_filter('posts_groupby', array($this, 'posts_groupby'));

		// Customize title and template
		add_filter('wp_title', array($this, 'set_title'), 20, 3);
		add_action('template_redirect', array($this, 'add_template'));
	}

	function detect_query() {
		if ( empty($_GET) )
			return $this->is_meta = false;

		$keys = array_keys($this->map);

		foreach ( $_GET as $key => $value )
			if ( in_array($key, $keys) )
				$this->matches[$key] = htmlentities($value);

		if ( empty($this->matches) )
			return $this->is_meta = false;

		return $this->is_meta = true;
	}

	function posts_join($join) {
		global $wpdb;

// If IN is used, it will act like 'key=value OR foo=bar' not 'key=value AND foo=bar'
//		foreach ( $this->matches as $key => $value )
//			$bits .= " AND m.meta_key = '{$key}' AND m.meta_value = '{$value}'";

		$bits .= sprintf(" AND m.meta_key = '%s' AND m.meta_value = '%s'", key($this->matches), reset($this->matches));

		$join .= "JOIN {$wpdb->postmeta} m ON (m.post_id = {$wpdb->posts}.ID {$bits})";

		return $join;
	}

	function posts_groupby($group) {
		global $wpdb;

		$group .= " {$wpdb->posts}.ID ";

		return $group;
	}

	function add_template() {
		$template = TEMPLATEPATH . "/meta.php";
		if ( !file_exists($template) )
			return;

		include($template);
		die();
	}

	function set_title($title, $sep, $seplocation = '') {
		if ( 'right' == $seplocation )
			return $this->get_meta_title() . " $sep ";
		else
			return " $sep " . $this->get_meta_title();
	}

// Template tags

	function get_meta_title($format = '%key%: %value%') {
		foreach ( $this->matches as $key => $value ) {
			$name = $this->map[$key];

			if ( empty($name) )
				$name = ucfirst($key);

			$title[] = str_replace(array('%key%', '%value%'), array($name, stripslashes($value)), $format);
		}

		return implode(' or ', $title);
	}

	function is_defined($key) {
		if ( ! $r = in_array($key, array_keys($this->map)) )
			trigger_error("'{$key}' is not defined as a custom taxonomy", E_USER_WARNING);

		return $r;
	}

	function get_linked_meta($id, $key, $glue = ', ') {
		if ( ! $this->is_defined($key) )
			return false;

		$values = get_post_meta($id, $key);

		if ( !$values )
			return false;

		foreach ( $values as $i => $value )
			$values[$i] = sprintf('<a href="%s">%s</a>', $this->get_meta_url($key, $value), $value);

		if ( count($values) > 1 )
			return implode($glue, $values);
		else
			return reset($values);
	}

	function meta_cloud($key, $auth_id = '', $args = '') {
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

// Base functions

	function get_meta_values($key, $auth_id) {
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

	function get_meta_url($key, $value) {
		global $wp_rewrite;

		$front = $wp_rewrite->using_permalinks ? rtrim($wp_rewrite->front, '/') : rtrim(get_bloginfo('url'), '/');

		return sprintf($front.'/?%s=%s', $key, urlencode($value));
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

