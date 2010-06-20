<?php
/*
Plugin Name: Custom Field Taxonomies
Version: 2.0-alpha
Description: Convert custom fields to tags, categories or taxonomy terms
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

if ( !is_admin() ) 
	return;

// Load scbFramework
require_once dirname(__FILE__) . '/scb/load.php';

function _cft_init() {
	require_once dirname(__FILE__) . '/admin.php';
	CFT_Admin::init();
}
scb_init('_cft_init');

