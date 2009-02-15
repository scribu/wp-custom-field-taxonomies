<?php
if ( !class_exists('scbOptionsPage_07') )
	require_once(dirname(__FILE__) . '/inc/scbOptionsPage.php');

// Adds the CFI Settings page
class settingsCFT extends scbOptionsPage_07 {
	protected function setup() {
		$this->options = $GLOBALS['CFT_options'];

		$this->defaults = array(
			'map' => ''
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
		wp_enqueue_script('cft_js', $this->plugin_url . '/inc/admin/admin.js', array('jquery', 'suggest'), '0.7');
		wp_enqueue_style('cft_css', $this->plugin_url . '/inc/admin/admin.css', array(), '0.7');
	}

	public function page_content() {
/*
		global $custom_field_template;
		
		if ( isset($custom_field_template) ) {
			$options = $custom_field_template->get_custom_field_template_data();
			foreach( array_keys($options['custom_fields']) as $id )
				print_r($custom_field_template->get_custom_fields($id));
		}
*/
		echo $this->page_header();
?>
<div class="section" id="cf-management">
<h3>Replace custom field keys or values</h3>
<table>
<?php
	$output = '';
	foreach ( array('value', 'key') as $field )
		$output .= sprintf('
	<tr>
		<td>Replace %1$s</td>
		<td><input class="widefat" name="%1$s_search" value="" type="text" /></td>
		<td>with</td>
		<td><input class="widefat" name="%1$s_replace" value="" type="text" /></td>
		<td><input class="button-primary" name="%1$s_action" value="Go" type="submit" /></td>
	</tr>
', $field);
	echo $this->form_wrap($output);
?>
</table>
</div>

<?php
		ob_start();
?>
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
		echo $this->submit_button();
		echo "<div id='cf-taxonomies'>\n" . $this->form_wrap(ob_get_clean()) . "</div>\n";

		echo $this->page_footer();
	}

	// Custom form handler
	protected function form_handler() {

		foreach ( array('value', 'key') as $field )
			if ( isset($_POST["{$field}_action"]) ) {
				check_admin_referer($this->nonce);
				$this->do_replace($field, $_POST["{$field}_search"], $_POST["{$field}_replace"]);
				return;
			}

		if ( 'Save Changes' != $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		if ( !empty($_POST['key']) )
		foreach ( $_POST['key'] as $i => $key ) {
			$key = sanitize_title_with_dashes($key);
			if ( !empty($key) )
				$new_map[$key] = trim($_POST['title'][$i]);
		}

		$this->options->update(array('map' => $new_map));

		echo '<div class="updated fade"><p>Settings <strong>saved</strong>.</p></div>';
	}

	// Helper for form handler
	private function do_replace($field, $search, $replace) {
		global $wpdb;

		$meta_field = "meta_{$field}";

		$count = $wpdb->update($wpdb->postmeta, array($meta_field => $replace), array($meta_field => $search));

		echo "<div class='updated fade'><p>Replaced <strong>{$count}</strong> {$field}s: <em>{$search}</em> &raquo; <em>{$replace}</em>.</p></div>";
	}

	// AJAX response
	public function ajax_meta_search() {
		global $wpdb;

		$hint = trim($_GET['q']);
		$field = 'meta_' . trim($_GET['field']);

		$values = $wpdb->get_col("
			SELECT DISTINCT $field
			FROM {$wpdb->postmeta}
			WHERE $field LIKE ('%{$hint}%')
			AND meta_key NOT LIKE ('\_%')
		");

		echo join($values, "\n");
		die();
	}
}

