<?php

abstract class scbOptionsPage extends scbForms {
	/** Page args
	 * string $parent (default: options-general.php)
	 * string $page_title
	 * string $menu_title (optional)
	 * string $page_slug (optional)
	 * array $action_link (default: Settings)
	 * string $nonce (optional)
	 */
	protected $args;

	// l10n
	protected $textdomain;

	// Hook string created at page init
	protected $pagehook;

	// Plugin dir url
	protected $plugin_url;

	// scbOptions object
	protected $options;

	// Form actions
	protected $actions = array();


//_____MAIN METHODS_____


	// Constructor
	function __construct($file, $options = NULL) {
		$this->setup();

		$this->_check_args();
		$this->_set_url($file);

		if ( ! empty($options) )
			$this->options = $options;

		add_action('admin_menu', array($this, 'page_init'));

		if ( $this->args['action_link'] )
			add_filter('plugin_action_links_' . plugin_basename($file), array($this, '_action_link'));
	}

	// This is where all the page args go (DEPRECATED)
	function setup() {}

	// This is where the css and js go
	function page_head() {}

	// This is where the page content goes
	abstract function page_content();

	// Validate new options
	function validate($new_options) {
		return $new_options;
	}


//_____HELPER METHODS_____


	// To be used in ::page_head()
	function admin_msg($msg, $class = "updated") {
		echo "<div class='$class fade'><p>$msg</p></div>\n";
	}

	// Wraps a string in a <script> tag
	function js_wrap($string) {
		return "\n<script language='text/javascript'>\n" . $string . "\n</script>\n";
	}

	// Wraps a string in a <style> tag
	function css_wrap($string) {
		return "\n<style type='text/css'>\n" . $string . "\n</style>\n";
	}

	function form_wrap($content) {
		return parent::form_wrap($content, $this->nonce);
	}

	function form_table_wrap($content) {
		return "\n<table class='form-table'>\n" . $content . "\n</table>\n";
	}

	function form_row_wrap($title, $content) {
		return "\n<tr>\n\t<th scope='row'>" . $title . "</th>\n\t<td>\n\t\t" . $content . "\n\t</td>\n\n</tr>";
	}

	function form_row($args, $options = NULL) {
		return $this->form_row_wrap($args['title'], $this->input($args, $options));
	}

	// Generates multiple rows and wraps them in a form table
	function form_table_section($rows) {
		$options = isset($this->options) ? $this->options->get() : NULL;

		$output = '';
		foreach ( $rows as $row )
			$output .= $this->form_row($row, $options);

		return $this->form_table_wrap($output);
	}

	function form_table($rows, $action = 'action', $value = 'Save Changes') {
		$output = $this->form_table_section($rows);

		$output .= $this->submit_button($action);

		return parent::form_wrap($output, $this->nonce);
	}

	// Generates a submit form button
	function submit_button($action = 'action', $value = 'Save Changes', $class = "button") {
		if ( in_array($action, $this->actions) )
			trigger_error("Duplicate action for submit button: {$action}", E_USER_WARNING);
		$this->actions[] = $action;

		$args = array(
			'type' => 'submit',
			'names' => $action,
			'values' => $value,
			'extra' => '',
			'desc_pos' => 'none'
		);

		if ( ! empty($class) )
			$args['extra'] = "class='{$class}'";

		$output .= "<p class='submit'>\n";
		$output .= parent::input($args);
		$output .= "</p>\n";

		return $output;
	}


//_____INTERNAL METHODS (DON'T WORRY ABOUT THESE)_____


	// Registers a page
	function page_init() {
		extract($this->args);
		$this->pagehook = add_submenu_page($parent, $page_title, $menu_title, $priority, $page_slug, array($this, '_page_content_hook'));

		add_action('admin_print_styles-' . $this->pagehook, array($this, 'page_head'));
	}

	function _action_link($links) {
		$url = add_query_arg('page', $this->args['page_slug'], admin_url($this->args['parent']));
		$links[] = "<a href='$url'>" . $this->args['action_link'] . "</a>";

		return $links;
	}

	// Update options
	function form_handler() {
		if ( empty($_POST['action']) )
			return false;

		check_admin_referer($this->nonce);

		foreach ( $this->options->get() as $name => $value )
			$new_options[$name] = $_POST[$name];

		$new_options = $this->validate($new_options);

		$this->options->update($new_options);

		$this->admin_msg(__('Settings <strong>saved</strong>.', $this->textdomain));
	}

	// Generates a standard page head
	function page_header() {
		$this->form_handler();

		echo "<div class='wrap'>\n";
		echo "<h2>".$this->args['page_title']."</h2>\n";
	}

	// Generates a standard page footer
	function page_footer() {
		echo "</div>\n";
	}

	function _page_content_hook() {
		$this->page_header();
		$this->page_content();
		$this->page_footer();
	}

	// Checks and sets default args
	function _check_args() {
		if ( empty($this->args['page_title']) )
			trigger_error('Page title cannot be empty', E_USER_ERROR);

		$this->args = wp_parse_args($this->args, array(
			'menu_title' => $this->args['page_title'],
			'page_slug' => '',
			'action_link' => __('Settings', $this->textdomain),
			'parent' => 'options-general.php',
			'priority' => 8,
			'nonce' => ''
		));

		if ( empty($this->args['page_slug']) )
			$this->args['page_slug'] = sanitize_title_with_dashes($this->args['menu_title']);
			
		if ( empty($this->args['nonce']) )
			$this->nonce = $this->args['page_slug'];
	}

	// Set plugin_dir
	function _set_url($file) {
		if ( function_exists('plugins_url') )
			$this->plugin_url = plugins_url(plugin_basename(dirname($file)));
		else
			// WP < 2.6
			$this->plugin_url = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname($file));
	}
}

