<?php

// Adds the CFT Settings page
class settingsCFT extends scbBoxesPage {
	private $sr_row;

	function setup() {
		$this->args = array(
			'page_title' => 'Custom Field Taxonomies',
			'menu_title' => 'CF Taxonomies',
			'page_slug' => 'cf-taxonomies',
			'parent' => 'post-new.php'
		);

		$this->boxes = array(
			array('taxonomies', 'Register Taxonomies', 'normal'),
			array('settings', 'Settings', 'normal'),
			array('replace_values', 'Replace CF Values', 'side'),
			array('replace_keys', 'Replace CF Keys', 'side'),
			array('add_default', 'Add Default Value', 'side'),
			array('duplicates', 'Remove Duplicate Values', 'side'),
		);

		// Used by search & replace boxes
		$this->sr_row = 
		html('tr',
			html('td', 'Replace %1$s')
			.html('td', '<input class="regular-text" name="%1$s_search" value="" type="text" />')
			.html('td class="with"', 'with')
			.html('td', '<input class="regular-text" name="%1$s_replace" value="" type="text" />')
			.html('td', '<input class="button" name="%1$s_action" value="Go" type="submit" />')
		);

		// Suggest
		add_action('wp_ajax_meta-search', array($this, 'ajax_meta_search'));
	}

	function page_head() {
		wp_enqueue_style('cft_css', $this->plugin_url . 'inc/admin/admin.css', array(), CFT_core::ver);
		wp_enqueue_script('cft_js', $this->plugin_url . 'inc/admin/admin.js', array('jquery', 'suggest'), CFT_core::ver);
	}

	function replace_values_handler() {
		if ( !isset($_POST["value_action"]) )
			return;

		global $wpdb;

		$what = array('meta_value' => $_POST["value_replace"]);
		$where = array('meta_value' => $_POST["value_search"]);

		$key = trim($_POST["value_key"]);

		if ( $key != '*' )
			$where['meta_key'] = $key;

		$count = (int) $wpdb->update($wpdb->postmeta, $what, $where);

		$message = "Replaced <strong>{$count}</strong> values: <em>{$search}</em> &raquo; <em>{$replace}</em>";

		if ( $key != '*' )
			$message .= " in <em>$key</em> custom field.";
		else
			$message .= ".";

		$this->admin_msg($message);
	}

	function replace_values_box() {
		$select = $this->input(array(
			'type' => 'select',
			'name' => 'value_key',
			'text' => '(any)',
			'value' => array_keys($this->options->map)
		));

		$form = 
		html('p', 
			"In {$select} taxonomy, "
			.html('table', sprintf($this->sr_row, 'value'))
		);

		echo $this->form_wrap($form, false);
	}


	function replace_keys_handler() {
		if ( !isset($_POST["key_action"]) )
			return;
	
		global $wpdb;

		$search = $wpdb->escape($_POST["key_search"]);
		$replace = $wpdb->escape($_POST["key_replace"]);

		$what = array('meta_key' => $replace);
		$where = array('meta_key' => $search);

		$count = (int) $wpdb->update($wpdb->postmeta, $what, $where);

		$this->admin_msg("Replaced <strong>{$count}</strong> keys: <em>{$search}</em> &raquo; <em>{$replace}</em>.");
	}

	function replace_keys_box() {
		echo $this->form_wrap(html('table', sprintf($this->sr_row, 'key')), false);
	}


	function add_default_handler() {
		if ( !isset($_POST["add_default"]) )
			return;

		global $wpdb;

		$key = $wpdb->escape($_POST["default_key"]);
		$value = $wpdb->escape($_POST["default_value"]);

		// Get posts that don't have a custom field $key
		$ids = $wpdb->get_col("
			SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'post'
			AND post_status = 'publish'
			AND ID NOT IN (
				SELECT post_id
				FROM {$wpdb->postmeta}
	  			WHERE meta_key = '{$key}'
	  			GROUP BY post_id
			)
		");

		if ( empty($ids) ) {
			$this->admin_msg("All posts have values for <em>{$key}</em> CF.");
			return;
		}

		// Format values for insert statement
		foreach ( $ids as $id )
			$values[] = "($id, '$key', '$value')";
		$values = implode(',', $values);

		$count = (int) $wpdb->query("INSERT INTO {$wpdb->postmeta}(post_id, meta_key, meta_value) VALUES $values");

		$this->admin_msg("Added $key: '{$value}' to <strong>{$count}</strong> posts.");
	}

	function add_default_box() {
		$select = $this->input(array(
			'type' => 'select',
			'name' => 'default_key',
			'text' => '(any)',
			'value' => array_keys($this->options->map)
		));
	
		$form = 
		html('p', "In {$select} taxonomy, add default value:")
		.html('p', 
			'<input class="regular-text" name="default_value" value="" type="text" />'
			.'<input class="button" name="add_default" value="Go" type="submit" />'
		);
		echo $this->form_wrap($form, false);

		echo html('p', 'This will add a certain value to posts that don\'t already have a value for that taxonomy. Useful when you add a new taxonomy.');
	}


	function duplicates_handler() {
		if ( 'Remove duplicates' != $_POST['action'] )
			return;

		global $wpdb;

$wpdb->show_errors = true;

		// MySQL doesn't allow SELECTing from the target table. Workaround is to create a temporary table
		$count = (int) $wpdb->query("
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_id NOT IN(
				SELECT meta_id FROM (
					SELECT meta_id FROM {$wpdb->postmeta}
					GROUP BY post_id, meta_key, meta_value
				) as tmp
			)
		");

		$this->admin_msg("Removed <strong>{$count}</strong> duplicates.");
	}

	function duplicates_box() {
		echo html('p', 'Have duplicate custom fields ( key=value ) on the same post might cause errors with this plugin. Clicking the button bellow will remove these duplicates.');
		echo html('p', 'Please <strong>backup</strong> your database first.');

		echo $this->form_wrap('', 'Remove duplicates');
	}


	function settings_handler() {
		if ( 'Save settings' != $_POST['action'] )
			return;

		$new_opts = array();
		foreach( array('relevance', 'rank_by_order', 'allow_and', 'allow_or') as $key )
			$new_opts[$key] = (bool) $_POST[$key];

		if ( ! $new_opts['relevance'] )
			$new_opts['rank_by_order'] = false;

		$this->options->set($new_opts);

		$this->admin_msg("Settings <strong>saved</strong>.");
	}

	function settings_box() {
		echo $this->css_wrap(".url {font-family: 'Courier'; font-size: 110%}");

		$rows = array(
			array(
				'type' => 'checkbox',
				'names' => 'allow_or',
				'desc' => "Allow <span class='url'>key=value1,value2</span> (value1 OR value2) queries"
			),
			array(
				'type' => 'checkbox',
				'names' => 'allow_and',
				'desc' => "Allow <span class='url'>key=value1+value2</span> (value1 AND value2) queries
							<br><strong style='margin-left:2em; line-height: 2'>Note</strong>: A '+' in the URL is equivalent to a <em>space</em>!"
			),
			array(
				'type' => 'checkbox',
				'names' => 'relevance',
				'desc' => "Show posts that don't match all <span class='url'>key=value</span> pairs"
			),
			array(
				'type' => 'checkbox',
				'names' => 'rank_by_order',
				'desc' => 'Consider the order of key=value pairs when ordering posts',
				'extra' => 'style="margin-left:2em"'
			),
		);

		$output = '';
		foreach ( $rows as $row )
			$output .= html('p', $this->input($row, $this->options->get()));

		echo $this->form_wrap($output, 'Save settings');
	}

	private $columns = array('key', 'query_var', 'title');

	function taxonomies_handler() {
		if ( 'Save taxonomies' != $_POST['action'] )
			return;

		$restricted_keys = array_keys(WP_Query::fill_query_vars(array()));
		$restricted_keys = array_merge($restricted_keys, CFT_core::get_other_vars());

		$new_map = $query_vars = $errors = array();

		$row_count = count((array) $_POST['key']);

		for ( $i = 0; $i < $row_count; $i++) {
			foreach ( $this->columns as $column ) {
				$$column = trim($_POST[$column][$i]);
			}

			if ( empty($key) ) {
				$errors[] = 'Empty CF key';
				continue;
			}

			if ( empty($query_var) )
				$query_var = $key;

			$query_var = sanitize_title_with_dashes($query_var);

			if ( empty($query_var) ) {
				$errors[] = sprintf('Empty URL key for "%1$s"', $key);
				continue;
			}

			if ( in_array($query_var, $restricted_keys) ) {
				$errors[] = sprintf('Restricted URL key: "%1$s"', $query_var);
				continue;
			}

			if ( in_array($query_var, $query_vars) ) {
				$errors[] = sprintf('Duplicate URL key: "%1$s" for CF key "%2$s"', $query_var, $key);
				continue;
			}

			$query_vars[] = $query_var;

			if ( empty($title) )
				$title = ucfirst($key);

			$new_map[$key] = compact('query_var', 'title');
		}

		$this->options->map = $new_map;

		$msg = 'Meta taxonomies <strong>saved</strong>.';

		if ( !empty($errors) ) {
			$msg .= '</p><p>' . 'Errors:';
			$list = '';
			foreach ( $errors as $error )
				$list .= html('li', $error);

			$msg .= html('ul', $list);
		}

		$this->admin_msg($msg);
	}

	function taxonomies_box() {
		$thead = 
		html('thead',
			html('tr',
				html('th scope="col"', 'CF Key')
				.html('th scope="col"', 'URL Key')
				.html('th scope="col"', 'Title')
				.html('th scope="col"', '')
			)
		);

		$map = $this->options->map;
		
		if ( empty($map) )
			$map = array('' => array());

		$tbody = '';
		foreach ( $map as $key => $args ) {
			extract($args);

			$trow = '';
			foreach ( $this->columns as $column )
				$trow .= html('td', 
					"\n\t\t" . $this->input(array(
						'type' => 'text',
						'names' => $column . '[]',
						'values' => @$$column,
						'desc' => false,
				)), "\n\t");

			$trow .= html('td class="delete"', 
				"\n\t\t" . html_link('#', 'Delete')
			, "\n\t");

			$tbody .= html('tr', $trow, "\n");
		}

//*		
		$tbody .= html('tr', 
			html('td colspan="4"', 
				html('a id="add" href="#"', 'Add row')
			)
		);
/**/

		$tbody = html('tbody', $tbody);

		$table = html('table class="widefat"', $thead.$tbody);

		echo $this->form_wrap($table, 'Save taxonomies', 'action', 'button no-ajax');
	}


	// AJAX response
	function ajax_meta_search() {
		if ( !current_user_can('manage_options') )
			die(-1);

		global $wpdb;

		$hint = $wpdb->escape(trim($_GET['q']));
		$field = $wpdb->escape('meta_' . trim($_GET['field']));

		$values = $wpdb->get_col("
			SELECT DISTINCT $field
			FROM {$wpdb->postmeta}
			WHERE $field LIKE ('%{$hint}%')
			AND meta_key NOT LIKE ('\_%')
		");

		echo implode("\n", $values);
		die;
	}

/*
	function cf_template_import() {
		global $custom_field_template;
		
		if ( !isset($custom_field_template) )
			return;

		$options = $custom_field_template->get_custom_field_template_data();
		foreach( array_keys($options['custom_fields']) as $id )
			print_r($custom_field_template->get_custom_fields($id));
	}
*/
}

