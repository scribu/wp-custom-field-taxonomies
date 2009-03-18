<?php
if ( !class_exists('scbOptionsPage_07') )
	require_once(dirname(__FILE__) . '/inc/scbOptionsPage.php');

// Adds the CFI Settings page
class settingsCFT extends scbOptionsPage_07 {
	protected function setup() {
		$this->options = $GLOBALS['CFT_core']->options;

		$this->defaults = array(
			'map' => '',
			'relevance' => false
		);

		$this->args = array(
			'page_title' => 'Custom Field Taxonomies',
			'short_title' => 'CF Taxonomies',
			'page_slug' => 'cft-settings'
		);

		$this->nonce = 'cft-settings';

		// Suggest
		add_action('wp_ajax_meta-search', array($this, 'ajax_meta_search'));
	}

	public function page_head() {
		wp_enqueue_script('cft_js', $this->plugin_url . '/inc/admin/admin.js', array('jquery', 'suggest'), '1.0');
		wp_enqueue_style('cft_css', $this->plugin_url . '/inc/admin/admin.css', array(), '1.0');
	}

	public function page_content() {
		echo $this->page_header();
?>
<div class="section" id="cf-management">
	<h3>Replace values</h3>
	<?php ob_start(); ?>
	In
	<select name="value_key">
		<option value="*" selected="selected">(any)</option>
		<?php
	$sandr_row = '
	<tr>
		<td>Replace %1$s</td>
		<td><input class="widefat" name="%1$s_search" value="" type="text" /></td>
		<td>with</td>
		<td><input class="widefat" name="%1$s_replace" value="" type="text" /></td>
		<td><input class="button" name="%1$s_action" value="Go" type="submit" /></td>
	</tr>
	';

	foreach ( $GLOBALS['CFT_core']->make_map() as $key => $name )
		echo "<option value='$key'>$name</option>\n";
	?>
	</select> taxonomy, <br />
	<table>
	<?php printf($sandr_row, 'value'); ?>
	</table>
	<?php echo $this->form_wrap(ob_get_clean()); ?>

	<h3>Replace keys</h3>
	<table>
	<?php echo $this->form_wrap(sprintf($sandr_row, 'key')); ?>
	</table>

	<h3>Remove duplicates</h3>
	<p>If on the same post you have duplicate custom fields ( key=value ), then this plugin might not display the right posts. Clicking the button bellow will fix this problem. Please <strong>backup</strong> your database first.</p>
	<?php echo $this->form_wrap($this->submit_button('Remove duplicates')); ?>
</div>

	<?php ob_start(); ?>
	<h3>Register taxonomies</h3>
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
		foreach ( $map as $key => $title ) {
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
		echo $this->submit_button('Save taxonomies');
		echo "<div id='cf-taxonomies'>\n" . $this->form_wrap(ob_get_clean()) . "</div>\n";
?>
	<h3>Settings</h3>
<?php
		$rows = array(
			array(
				'title' => 'Relevance',
				'type' => 'checkbox',
				'names' => 'relevance',
				'desc' => 'Show posts that don\'t match all key=value pairs<br/>(for multiple matches)'
			)
		);

		echo $this->form_table($rows);

		echo $this->page_footer();
	}

	// Custom form handler
	protected function form_handler() {
		if ( empty($_POST) )
			return;

		check_admin_referer($this->nonce);

		// Manage taxonomies
		if ( 'Save taxonomies' == $_POST['action'] )
			return $this->update_taxonomies();

		// Remove duplicates
		if ( 'Save Changes' == $_POST['action'] )
			return $this->update_settings();

		// Remove duplicates
		if ( 'Remove duplicates' == $_POST['action'] )
			return $this->remove_duplicates();

		// Replace values
		if ( isset($_POST["value_action"]) )
			return $this->replace_value($_POST["value_search"], $_POST["value_replace"], $_POST["value_key"]);

		// Replace keys
		if ( isset($_POST["key_action"]) )
			return $this->replace_key($_POST["key_search"], $_POST["key_replace"]);
	}

	private function update_taxonomies() {
		$restricted_keys = array_keys(WP_Query::fill_query_vars(array()));

		foreach ( $_POST['key'] as $i => $key ) {
			$key = sanitize_title_with_dashes($key);

			if ( empty($key) || in_array($key, $restricted_keys) )
				continue;

			$new_map[$key] = trim($_POST['title'][$i]);
		}

		$this->options->update_part(array('map' => $new_map));

		echo "<div class='updated fade'><p>Settings <strong>saved</strong>.</p></div>\n";
	}

	private function update_settings() {
		$this->options->update_part(array('relevance' => (bool) $_POST['relevance']));

		echo "<div class='updated fade'><p>Settings <strong>saved</strong>.</p></div>\n";
	}

	private function remove_duplicates() {
		global $wpdb;
		$ids = $wpdb->get_col("
			SELECT meta_id
			FROM {$wpdb->postmeta}
			GROUP BY post_id, meta_key, meta_value
			HAVING COUNT(meta_value) > 1
		");
		$ids = implode(',', $ids);

		$deleted = $wpdb->query("
			DELETE FROM {$wpdb->postmeta}
			WHERE meta_id IN({$ids})
		");

		echo "<div class='updated fade'><p>Removed <strong>{$deleted}</strong> duplicates.</p></div>\n";
	}

	private function replace_value($search, $replace, $key) {
		global $wpdb;

		$what = array('meta_value' => $replace);
		$where = array('meta_value' => $search);

		if ( $key != '*' )
			$where['meta_key'] = $key;

		$count = $wpdb->update($wpdb->postmeta, $what, $where);

		$message = "<div class='updated fade'><p>Replaced <strong>{$count}</strong> values: <em>{$search}</em> &raquo; <em>{$replace}</em>.</p></div>\n";

		if ( $key != '*' ) {
			$map = $this->options->get('map');
			$message = str_replace(".</p>", " in <em>{$map[$key]}</em> taxonomy.</p>", $message);
		}

		echo $message;
	}

	private function replace_key($search, $replace) {
		global $wpdb;

		$what = array('meta_key' => $replace);
		$where = array('meta_key' => $search);

		$count = $wpdb->update($wpdb->postmeta, $what, $where);

		echo "<div class='updated fade'><p>Replaced <strong>{$count}</strong> keys: <em>{$search}</em> &raquo; <em>{$replace}</em>.</p></div>\n";
	}

	// AJAX response
	public function ajax_meta_search() {
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
	private function cf_template_import() {
		global $custom_field_template;
		
		if ( !isset($custom_field_template) ) 
			return;

		$options = $custom_field_template->get_custom_field_template_data();
		foreach( array_keys($options['custom_fields']) as $id )
			print_r($custom_field_template->get_custom_fields($id));
	}
*/
}

