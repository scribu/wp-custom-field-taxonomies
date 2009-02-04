<?php

// Version 0.5b
// TODO: multiple widgets support

if ( ! class_exists('scbForms_05') )
	require_once('scbForms.php');

abstract class scbWidget_05 extends scbForms_05 {
	// Widget args
	protected $name = '';
	protected $slug = '';

	// Default options for installation
	protected $defaults = array();

	// scbOptions object holder
	protected $options = NULL;


//_____MAIN METHODS_____


	public function __construct($file) {
		$this->setup();

		// Check for required fields
		if ( empty($this->name) )
			return false;

		if ( empty($this->slug) )
			$this->slug = sanitize_title_with_dashes($this->name);

		// Create options object
		if ( ! class_exists('scbOptions_05') )
			require_once('scbOptions.php');

		$this->options = new scbOptions_05($this->slug);
		$this->options->setup($file, $this->defaults);

		add_action('plugins_loaded', array($this, 'init'));
	}

	// This is where the widget name and defaults go
	abstract protected function setup();

	// This is where the actual widget content goes (no echoing)
	abstract protected function content();

	// This is where the widget form fields go
	abstract protected function control();

	// This adds an input field
	public function input($args, $options = array() ) {
		$args = wp_parse_args($args, array(
			'extra' => 'class="widefat"',
			'check' => 'false'
		));

		// Add default label position
		if ( !in_array($args['type'], array('checkbox', 'radio')) && empty($args['desc_pos']) )
			$args['desc_pos'] = 'before';

		// First check options
		parent::check_names($args['names'], $options);

		// Then add prefix to names and options fields
		$new_names = (array) $args['names'];
		$new_options = array();
		foreach ( $new_names as $i => $name ) {
			$new_name = $this->add_prefix($name);
			$new_names[$i] = $new_name;
			$new_options[$new_name] = $options[$name];
		}

		// Finally, replace the $names arg
		if ( 1 == count($new_names) )
			$args['names'] = $new_names[0];
		else
			$args['names'] = $new_names;

		// Hijack $desc and replace with $title
		if ( $args['desc'] )
			$desc = "<small>{$args['desc']}</small>";
		$args['desc'] = $args['title'];
		unset($args['title']);

		$inputs = parent::input($args, $new_options);

		return "<p>{$inputs}\n<br />\n$desc\n</p>\n";
	}


//_____HELPER METHODS (SHOULD NOT BE CALLED DIRECTLY)_____


	// Adds the widget hooks
	public function init() {
		if ( !function_exists('register_sidebar_widget') )
			return;

		register_sidebar_widget($this->name, array($this, 'display'));
		register_widget_control($this->name, array($this, 'handler'), 250, 200);
	}

	// Wraps the content with default widget args
	public function display($args) {
		extract($this->options->get());
		extract($args);

		$content = $this->content();

		echo $before_widget . $before_title . $title . $after_title . $content . $after_widget;
	}

	// Updates options and displays fields
	public function handler() {
		$hidden_key = $this->add_prefix('submit');

		// Update options
		if ( isset($_POST[$hidden_key]) ) {
			foreach ( array_keys($this->options->get()) as $name )
				$new_options[$name] = $_POST[$this->add_prefix($name)];

			$this->options->update($new_options);
		}

		echo "<input name='{$hidden_key}' type='hidden' value='1' />\n";

		$this->control();
	}

	protected function add_prefix($field) {
		return $this->slug . '-' . $field;
	}
}
