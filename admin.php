<?php

class CFT_Admin {

	function init() {
		add_action('admin_notices', array(__CLASS__, 'convert'));
		add_action('tool_box', array(__CLASS__, 'display'));
	}

	function convert() {
		global $wpdb;

		if ( !isset($_POST['cf_to_term']) )
			return;

		$cf_key = stripslashes($_POST['cf_key']);
		$taxonomy = stripslashes($_POST['taxonomy']);

		if ( empty($cf_key) ) {
			echo html('div class="error"', html('p', 'Invalid custom field key taxonomy.'));
			return;
		}

		if ( !taxonomy_exists($taxonomy) ) {
			echo html('div class="error"', html('p', 'Invalid taxonomy.'));
			return;
		}

		$rows = $wpdb->get_results($wpdb->prepare("
			SELECT post_id, meta_value 
			FROM $wpdb->postmeta
			WHERE meta_key = %s
		", $cf_key));

		foreach ( $rows as $row )
			wp_set_object_terms($row->post_id, $row->meta_value, $taxonomy, true);

		$r = $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $cf_key));

		echo html('div class="updated"', html('p', sprintf('Converted <em>%d</em> custom fields.', number_format_i18n($r))));
	}

	function display() {
		$cf_input = scbForms::input(array(
			'type' => 'select',
			'name' => 'cf_key',
			'values' => self::get_cf_key_list()
		));

		$tax_input = scbForms::input(array(
			'type' => 'select',
			'name' => 'taxonomy',
			'values' => self::get_tax_list()
		));

		$go_input = scbForms::input(array(
			'type' => 'submit',
			'name' => 'cf_to_term',
			'value' => __('Go'),
#			'extra' => 'class="button" onclick="return confirm(\'Are you sure? This operation can not be undone.\')"'
			'extra' => 'class="button"'
		));

		echo
		html('div class="tool-box"',
			 html('h3 class="title"', __('Custom Field Taxonomies'))
			.scbForms::form_wrap(html('p', 
				 sprintf(__('Convert %s custom fields to terms in the %s taxonomy.'), $cf_input, $tax_input)
				. ' ' . $go_input
			), false)
		);
	}

	protected function get_cf_key_list() {
		global $wpdb;

		$keys = $wpdb->get_col("
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			HAVING meta_key NOT LIKE '\_%'
		");

		if ( $keys )
			natcasesort($keys);
		else
			$keys = array();

		return $keys;
	}

	protected function get_tax_list() {
		$taxonomies = array();
		foreach ( get_object_taxonomies('post', 'objects') as $tax_name => $tax_obj )
			$taxonomies[$tax_name] = $tax_obj->label ? $tax_obj->label : $tax_name;

		return $taxonomies;
	}
}

