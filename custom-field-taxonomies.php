<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 2.0.3
Description: Convert custom fields to tags, categories or taxonomy terms
Author: scribu
Author URI: http://scribu.net/
Plugin URI: http://scribu.net/wordpress/custom-field-taxonomies
Text Domain: custom-field-taxonomies
Domain Path: /lang
*/

if ( !is_admin() )
	return;

define( 'CFT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load scbFramework
require_once dirname( __FILE__ ) . '/scb/load.php';

function _cft_init() {
	load_plugin_textdomain( 'custom-field-taxonomies', '', dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	require_once dirname( __FILE__ ) . '/admin.php';
	CFT_Admin::init();
}
scb_init( '_cft_init' );

