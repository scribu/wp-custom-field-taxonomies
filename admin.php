<?php

// Adds the CFT Settings page
class settingsCFT extends scbBoxesPage 
{
	function __construct($file, $options, $map)
	{
		$this->map = $map;

		$this->args = array(
			'page_title' => 'Custom Field Taxonomies',
			'menu_title' => 'CF Taxonomies',
			'page_slug' => 'cf-taxonomies',
			'parent' => 'post-new.php'
		);

		$this->boxes = array(
			array('taxonomies', 'Register Taxonomies', 'normal'),
			array('settings', 'Settings', 'normal'),
			array('replace_values', 'Replace Values', 'side'),
			array('replace_keys', 'Replace Keys', 'side'),
			array('add_default', 'Add Default Value', 'side'),
			array('duplicates', 'Remove duplicates', 'side'),
		);

		// Used by search and replace boxes
		$this->sr_row = '
		<tr>
			<td>Replace %1$s</td>
			<td><input class="normal-text" name="%1$s_search" value="" type="text" /></td>
			<td>with</td>
			<td><input class="normal-text" name="%1$s_replace" value="" type="text" /></td>
			<td><input class="button" name="%1$s_action" value="Go" type="submit" /></td>
		</tr>
		';

		// Suggest
		add_action('wp_ajax_meta-search', array($this, 'ajax_meta_search'));

		parent::__construct($file, $options);
	}

	function page_head()
	{
		wp_enqueue_script('cft_js', $this->plugin_url . '/inc/admin/admin.js', array('jquery', 'suggest'), '1.2');
		wp_enqueue_style('cft_css', $this->plugin_url . '/inc/admin/admin.css', array(), '1.2');
	}

	function replace_values_handler()
	{
		if ( !isset($_POST["value_action"]) )
			return;
	
		global $wpdb;

		$search = $wpdb->escape($_POST["value_search"]);
		$replace = $wpdb->escape($_POST["value_replace"]);
		$key = $wpdb->escape($_POST["value_key"]);

		$what = array('meta_value' => $replace);
		$where = array('meta_value' => $search);

		if ( $key != '*' )
			$where['meta_key'] = $key;

		$count = (int) $wpdb->update($wpdb->postmeta, $what, $where);

		$message = "Replaced <strong>{$count}</strong> values: <em>{$search}</em> &raquo; <em>{$replace}</em>";

		if ( $key != '*' )
			$message .= " in <em>{$this->map[$key]}</em> taxonomy.</p>";
		else
			$message .= ".";

		$this->admin_msg($message);
	}

	function replace_values_box()
	{
		$select = $this->input(array(
			'type' => 'select',
			'name' => 'value_key',
			'text' => '(any)',
			'value' => $this->map
		));

		$form = array();
		$form[] = "<p>In {$select} taxonomy, ";
		$form[] = sprintf("<table>{$this->sr_row}</table>\n", 'value');
		$form[] = '</p>';
		echo $this->form_wrap(implode("\n", $form), false);
	}


	function add_default_handler()
	{
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

		if ( empty($ids) )
		{
			$this->admin_msg("All posts have values for <em>{$this->map[$key]}</em> taxonomy.");
			return;
		}

		// Format values for insert statement
		foreach ( $ids as $id )
			$values[] = "($id, '$key', '$value')";
		$values = implode(',', $values);

		$count = (int) $wpdb->query("INSERT INTO {$wpdb->postmeta}(post_id, meta_key, meta_value) VALUES $values");

		$this->admin_msg("Added {$this->map[$key]}: '{$value}' to <strong>{$count}</strong> posts.");
	}

	function add_default_box()
	{
		$select = $this->input(array(
			'type' => 'select',
			'name' => 'default_key',
			'text' => '(any)',
			'value' => $this->map
		));
	
		$form = array();
		$form[] = "<p>In {$select} taxonomy, add default value:";
		$form[] = '<input class="normal-text" name="default_value" value="" type="text" />';
		$form[] = '<input class="button" name="add_default" value="Go" type="submit" />';
		$form[] = '</p>';

		echo $this->form_wrap(implode("\n", $form), false);
		echo '<p>This will add a certain value to posts that don\'t already have a value for that taxonomy. Useful when you add a new taxonomy.</p>';
	}


	function replace_keys_handler()
	{
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

	function replace_keys_box()
	{
		echo $this->form_wrap(sprintf("<table>{$this->sr_row}</table>\n", 'key'));
	}


	function duplicates_handler()
	{
		if ( 'Remove duplicates' != $_POST['action'] )
			return;

		global $wpdb;

		$ids = $wpdb->get_col("
			SELECT meta_id
			FROM {$wpdb->postmeta}
			GROUP BY post_id, meta_key, meta_value
			HAVING COUNT(meta_value) > 1
		");
		$ids = implode(',', $ids);

		$count = (int) $wpdb->query("
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_id IN({$ids})
		");

		$this->admin_msg("Removed <strong>{$count}</strong> duplicates.");
	}

	function duplicates_box()
	{
		echo "<p>If on the same post you have duplicate custom fields ( key=value ), then this plugin might not display the right posts. Clicking the button bellow will fix this problem.</p>";
		echo "<p>Please <strong>backup</strong> your database first.</p>\n";
		echo $this->form_wrap($this->submit_button('Remove duplicates'));
	}


	function settings_handler()
	{
		if ( 'Save settings' != $_POST['action'] )
			return;

		$this->options->update_part(array(
			'relevance' => (bool) $_POST['relevance'],
			'rank_by_order' => (bool) $_POST['rank_by_order']
		));

		$this->admin_msg("Settings <strong>saved</strong>.");
	}

	function settings_box()
	{
		$rows = array(
			array(
				'type' => 'checkbox',
				'names' => 'relevance',
				'desc' => 'Show posts that don\'t match all key=value pairs (for multiple matches)'
			),
			array(
				'type' => 'checkbox',
				'names' => 'rank_by_order',
				'desc' => 'Additionally, consider the order of key=value pairs when ordering posts'
			)
		);

		$output = '';
		foreach ( $rows as $row )
			$output .= "<p>" . $this->input($row, $this->options->get() ) . "</p>\n";

		echo $this->form_wrap($output, 'Save settings');
	}


	function taxonomies_handler()
	{
		if ( 'Save taxonomies' != $_POST['action'] )
			return;

		$restricted_keys = array_keys(WP_Query::fill_query_vars(array()));

		foreach ( $_POST['key'] as $i => $key )
	{
			$key = sanitize_title_with_dashes($key);

			if ( empty($key) || in_array($key, $restricted_keys) )
				continue;

			$new_map[$key] = trim($_POST['title'][$i]);
		}

		$this->options->update_part(array('map' => $new_map));
		
		// Rebuild map
		$this->map = CFT_core::make_map();

		$this->admin_msg("Taxonomies <strong>saved</strong>.");
	}

	function taxonomies_box()
	{
		ob_start();
?>
	<table class="widefat">
	  <thead>
		<tr>
			<th scope="col">Key</th>
			<th scope="col">Title</th>
			<th scope="col"></th>
		</tr>
	  </thead>
	  <tbody>
<?php
	$map = $this->options->get('map');
	if ( !empty($map) )
		foreach ( $map as $key => $title )
	{
			$rows = array(				
				array(
					'type' => 'text',
					'names' => 'key[]',
					'values' => $key
				),
				array(
					'type' => 'text',
					'names' => 'title[]',
					'values' => $title
				)
			);

			echo "<tr>\n";
			foreach ( $rows as $row )
				echo "\t<td>{$this->input($row)}</td>\n";
			echo "\t<td><a class='delete' href='#'>Delete</a></td>\n";
			echo "</tr>\n";
	 	} 
?>
		<tr>
			<td colspan="3"><a id="add" href="#">Add row</a></td>
		</tr>
	  </tbody>
	</table>
<?php
		echo $this->form_wrap(ob_get_clean(), 'Save taxonomies');
	}


	// AJAX response
	function ajax_meta_search()
	{
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

		echo join($values, "\n");
		die;
	}

/*
	function cf_template_import()
	{
		global $custom_field_template;
		
		if ( !isset($custom_field_template) ) 
			return;

		$options = $custom_field_template->get_custom_field_template_data();
		foreach( array_keys($options['custom_fields']) as $id )
			print_r($custom_field_template->get_custom_fields($id));
	}
*/
}

