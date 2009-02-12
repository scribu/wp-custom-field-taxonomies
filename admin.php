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
		add_action('wp_ajax_meta-key-search', array($this, 'ajax_meta_key'));
	}

	public function page_head() {
		wp_enqueue_script('cft_table', $this->plugin_url . '/inc/table.js', array('jquery', 'suggest'), '0.7');
?>
<style type="text/css">
td {vertical-align: middle}
input.widefat {display: block; width: 200px}
.delete {
	display: block;
	width: 16px;
	height: 16px;
	text-indent: -999px;
	margin-top: 4.5px;
	background: url("<?php echo $this->plugin_url ?>/inc/cancel.png") no-repeat
}
</style>
<?php
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

		ob_start();
?>
	<table class="widefat" style="width:auto">
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
?>
		<tr>
		<?php foreach ( $rows as $row ) { ?>
			<td><?php echo $this->input($row) ?></td>
		<?php } ?>
			<td><a class="delete" href="#">Delete</a></td>
		</tr>
	<?php } ?>
		<tr>
			<td colspan="3"><a id="add" href="#">Add row</a></td>
		</tr>
		</tbody>
		</table>
<?php
		echo $this->submit_button();
		echo $this->form_wrap(ob_get_clean());

		echo $this->page_footer();
	}

	// Custom form handler
	protected function form_handler() {
		if ( 'Save Changes' != $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		if ( !empty($_POST['key']) )
		foreach ( $_POST['key'] as $i => $key ) {
			$key = str_replace(' ', '_', trim($key));
			if ( !empty($key) )
				$new_map[$key] = trim($_POST['title'][$i]);
		}

		$this->options->update(array('map' => $new_map));

		echo '<div class="updated fade"><p>Settings <strong>saved</strong>.</p></div>';
	}

	public function ajax_meta_key() {
		global $wpdb;

		$hint = trim($_GET['q']);

		$values = $wpdb->get_col("
			SELECT DISTINCT meta_key
			FROM {$wpdb->postmeta}
			WHERE meta_key LIKE ('%{$hint}%')
			AND meta_key NOT LIKE ('\_%')
			ORDER BY meta_key ASC
		");

		echo join($values, "\n");
		die();
	}
}
