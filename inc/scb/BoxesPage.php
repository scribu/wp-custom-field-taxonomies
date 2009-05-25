<?php

abstract class scbBoxesPage extends scbOptionsPage {
	public $boxes;

	function page_init() {
		parent::page_init();
		add_action('load-' . $this->pagehook, array($this, 'boxes_init'));
	}

	function page_content() {
		echo "<div id='cf-main' class='metabox-holder'>\n";
		echo "\t<div class='postbox-container'>\n";
		do_meta_boxes($this->pagehook, 'normal', '');
		echo "\t</div>\n</div>\n";

		echo "<div id='cf-side' class='metabox-holder'>\n";
		echo "<div class='postbox-container'>\n";
		do_meta_boxes($this->pagehook, 'advanced', '');
		echo "\t</div>\n</div>\n";
	}

	function page_footer() {
		$this->_boxes_js_init();
		parent::page_footer();
	}

	function form_handler() {
		if ( empty($_POST) )
			return;

		check_admin_referer($this->nonce);

		do_action('form-handler-' . $this->pagehook);
	}

	function boxes_init() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		
		$this->_add_boxes();
	}

	function _add_boxes() {
		foreach($this->boxes as $i) {
			// Add boxes
			add_meta_box($i[0], $i[1], array($this, "{$i[0]}_box"), $this->pagehook, $i[2]);
			// Add handlers
			add_action('form-handler-' . $this->pagehook, array($this, "{$i[0]}_handler"));
		}
	}

	// Adds necesary code for JS to work
	function _boxes_js_init() {
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

