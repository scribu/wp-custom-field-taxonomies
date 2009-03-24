<?php

// Version 0.7

if ( ! class_exists('scbOptionsPage_07') )
	require_once(dirname(__FILE__) . '/scbOptionsPage.php');

abstract class scbBoxesPage_07 extends scbOptionsPage_07 {
	protected $boxes;

	public function page_init() {
		parent::page_init();
		add_action('load-' . $this->pagehook, array($this, 'boxes_init'));
	}

	protected function page_footer() {
		$this->boxes_js_init();
		parent::page_footer();
	}

	protected function form_handler() {
		if ( empty($_POST) )
			return;

		check_admin_referer($this->nonce);

		do_action('form-handler-' . $this->pagehook);
	}

	public function boxes_init() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		
		$this->add_boxes();
	}

	private function add_boxes() {
		foreach($this->boxes as $i) {
			// Add boxes
			add_meta_box($i[0], $i[1], array($this, "{$i[0]}_box"), $this->pagehook, $i[2]);
			// Add handlers
			add_action('form-handler-' . $this->pagehook, array($this, "{$i[0]}_handler"));
		}
	}

	// Adds necesary code for JS to work
	private function boxes_js_init() {
?>
<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function($) {
		// close postboxes that should be closed
		$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		// postboxes setup
		postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
	});
	//]]>
</script>

<form style='display: none' method='get' action=''>
	<p>
<?php
	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
?>
	</p>
</form>
<?php
	}
}

