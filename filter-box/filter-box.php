<?php

// Sets up scripts and AJAX suggest
class CFT_Filter_Box {
	const ajax_key = 'ajax-meta-search';

	static function init() {
		add_action('wp_ajax_' . self::ajax_key, array(__CLASS__, 'ajax_meta_search'));
		add_action('wp_ajax_nopriv_' . self::ajax_key, array(__CLASS__, 'ajax_meta_search'));
	}

	static function scripts() {
		$url = plugin_dir_url(__FILE__);

		wp_enqueue_script('cft-filter-box', $url . 'filter-box.js', array('jquery', 'suggest'), CFT_Core::VERSION);
		wp_print_scripts(array('cft-filter-box'));

		$ajax_url = admin_url('admin-ajax.php?action=' . self::ajax_key . '&key=');

		$scripts[] = "<style type='text/css'>@import url('$url/filter-box.css');</style>";
		$scripts[] = "<script type='text/javascript'>window.cft_suggest_url = '" . $ajax_url . "';</script>";

		echo implode("\n", $scripts);
	}

	static function ajax_meta_search() {
		$query_var = trim($_GET['key']);
		$hint = trim($_GET['q']);

		if ( ! $info = CFT_Core::get_info($query_var) )
			die(-1);

		foreach ( CFT_Core::get_meta_values($info['key'], array('number' => 10, 'hint' => $hint)) as $value )
			echo $value->name . "\n";

		die;
	}

	static function display($exclude = array()) {
		add_action('wp_footer', array(__CLASS__, 'scripts'));

		$map = CFT_Core::get_map('query_var');

		foreach ( $exclude as $key )
			unset($map[$key]);

		$select = scbForms::input(array(
			'type' => 'select',
			'name' => 'cft_filter',
			'value' => $map
		));
	?>
<form class="meta-filter-box" method='GET' action="<?php bloginfo('url'); ?>">
<fieldset>
<table class="meta-filters"></table>
<div class="select-meta-filters">
	Add filter <?php echo $select; ?>
</div>
<input name="action" type="submit" value="Go" />
</fieldset>
</form>
	<?php
	}
}

CFT_Filter_Box::init();
