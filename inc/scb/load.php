<?php

// Autoload the most recent version of scbFramework available

if ( !class_exists('scbLoad') ) :
class scbLoad {
	private $data;

	function __construct($file, $rev) {
		$this->data = array(
			'rev' => $rev,
			'path' => dirname($file),
		);

		$this->set_path();
		$this->set_autoload();
	}

	function set_path() {
		$data = get_option('scbFramework');

		if ( empty($data) or $data['rev'] < $this->data['rev'] or !is_dir($data['path']) )
			update_option('scbFramework', $this->data);
	}

	static function get_path() {
		$data = get_option('scbFramework');

		return $data['path'];
	}

	function set_autoload() {
		if ( function_exists('spl_autoload_register') ) {
			spl_autoload_register(array($this, 'autoload'));
		} else {
			// Load all classes manually (PHP < 5.1)
			foreach ( array('scbForms', 'scbOptions', 'scbOptionsPage', 'scbBoxesPage', 'scbWidget', 'scbCron') as $class )
				$this->autoload($class);
		}
	}

	function autoload($className) {
		// PHP < 5.1
		if ( class_exists($className) )
			return false;

		if ( substr($className, 0, 3) != 'scb' )
			return false;

		$fname = self::get_file_path($className);

		if ( ! @file_exists($fname) )
			$fname = self::get_file_path($className, $this->data['path']);

		if ( ! @file_exists($fname) )
			return false;

		include_once($fname);
		return true;
	}

	static function get_file_path($className, $base = '') {
		if ( empty($base) )
			$base = self::get_path();

		return $base . DIRECTORY_SEPARATOR . substr($className, 3) . '.php';
	}
}
endif;

new scbLoad(__FILE__, 27);

