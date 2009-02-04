<?php

// Version 0.6

if ( ! class_exists('scbForms_06') )
	require_once(dirname(__FILE__) . '/scbForms.php');

abstract class scbOptionsPage_06 extends scbForms_06 {
	// Page args
	protected $args = array(
		'page_title' => '',
		'short_title' => '',
		'page_slug' => ''
	);

	// Nonce string
	protected $nonce = 'update_settings';

	// Plugin dir url
	protected $plugin_url;

	// scbOptions object holder
	protected $options;

	// Form actions
	protected $actions = array();


//_____MAIN METHODS_____


	// Main constructor
	public function __construct($file) {
		$this->set_url($file);

		$this->setup();

		if ( isset($this->options) )
			$this->options->setup($file, $this->defaults);

		add_action('admin_menu', array($this, 'page_init'));
	}

	// This is where all the page args goes
	abstract protected function setup();

	// This is where the css and js go
	public function page_head() {}

	// This is where the page content goes
	abstract public function page_content();

	// Wraps a string in a <script> tag
	public function wrap_js($string) {
		return "\n<script language='text/javascript'>\n" . $string . "\n</script>\n";
	}

	// Wraps a string in a <style> tag
	public function wrap_css($string) {
		return "\n<style type='text/css'>\n" . $string . "\n</style>\n";
	}

	// Generates a standard page head
	protected function page_header() {
		$this->form_handler();

		$output .= "<div class='wrap'>\n";
		$output .= "<h2>".$this->args['page_title']."</h2>\n";

		return $output;
	}

	// Generates a standard page footer
	protected function page_footer() {
		$output = "</div>\n";

		return $output;
	}

	// Wrap a field in a table row
	public function form_row($args, $options) {
		return "\n<tr>\n\t<th scope='row'>{$args['title']}</th>\n\t<td>\n\t\t". parent::input($args, $options) ."\n\t</td>\n\n</tr>";
	}

	// Generates multiple rows and wraps them in a form table
	protected function form_table($rows, $action = 'Save Changes') {
		$output .= "\n<table class='form-table'>";

		$options = $this->options->get();
		foreach ( $rows as $row )
			$output .= $this->form_row($row, $options);

		$output .= "\n</table>\n";
		$output .= $this->submit_button($action);

		return parent::form_wrap($output, $this->nonce);
	}

	// Generates a submit form button
	protected function submit_button($action = 'Save Changes') {
		if ( in_array($action, $this->actions) )
			trigger_error("Duplicate action for submit button: {$action}", E_USER_WARNING);

		$this->actions[] = $action;
		$output .= "<p class='submit'>\n";
		$output .= parent::input(array(
			'type' => 'submit',
			'names' => 'action',
			'values' => $action,
			'extra' => 'class="button-primary"',
			'desc_pos' => 'none'
		));
		$output .= "</p>\n";

		return $output;
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Set plugin_dir
	protected function set_url($file) {
		if ( function_exists('plugins_url') )
			$this->plugin_url = plugins_url(plugin_basename(dirname($file)));
		else
			// < WP 2.6
			$this->plugin_url = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname($file));
	}

	// Registers a page
	public function page_init() {
		if ( !current_user_can('manage_options') )
			return false;

		extract($this->args);
		$page = add_options_page($short_title, $short_title, 8, $page_slug, array($this, 'page_content'));

		add_action( "admin_print_scripts-$page", array($this, 'page_head'));
	}

	// Update options
	protected function form_handler() {
		if ( 'Save Changes' != $_POST['action'] )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->options->get() as $name => $value )
			$new_options[$name] = $_POST[$name];

		$this->options->update($new_options);

		echo '<div class="updated fade"><p>Settings <strong>saved</strong>.</p></div>';
	}
}
