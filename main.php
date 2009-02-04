<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 0.5.1
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

	var $key;
	var $value;

	var $is_meta;

	function __construct() {
		$this->map = $GLOBALS['CFT_options']->get('map');

		if ( !$this->detect_query() )
			return;

		add_filter('query_string', array($this, 'set_query'));
		add_filter('wp_title', array($this, 'set_title'), 20, 3);
		add_action('template_redirect', array($this, 'add_template'));
	}

	function detect_query() {
		$matching_keys = @array_intersect(@array_keys($this->map), @array_keys($_GET));

		if ( !(bool) count($matching_keys) )
			return $this->is_meta = false;
		else
			$this->is_meta = true;

		$this->key = key(array_flip($matching_keys));
		$this->value = htmlentities(urldecode($_GET[$this->key]));

		return true;
	}

	function set_query($string) {
		return "meta_key={$this->key}&meta_value={$this->value}";
	}

	function add_template() {
		$template = TEMPLATEPATH . "/meta.php";
		if ( !file_exists($template) )
			return;

		include($template);
		die();
	}

	function set_title($title, $sep, $seplocation) {
		if ( 'right' == $seplocation )
			return $this->get_meta_title() . " $sep ";
		else
			return " $sep " . $this->get_meta_title();
	}

// END Setup

	function get_meta_title($format = '%key%: %value%') {
		return str_replace(array('%key%', '%value%'), array($this->map[$this->key], stripslashes($this->value)), $format);
	}

	function get_linked_meta($id, $key, $glue = ', ') {
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

	function get_meta_url($key, $value) {
		global $wp_rewrite;

		$front = $wp_rewrite->using_permalinks ? $wp_rewrite->front : trailingslashit(get_bloginfo('url'));

		return sprintf($front.'?%s=%s', $key, urlencode($value));
	}

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

	function meta_cloud($key, $auth_id = '', $args = '') {
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

	include_once(dirname(__FILE__) . '/meta-template.php');
}

cft_init();
