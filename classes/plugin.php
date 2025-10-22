<?php
/**
 * The core plugin class.
 *
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/setup.php';

class teqb_Plugin extends teqb_Setup {
	public $config;
	protected $quote_builder;
	
	public function __construct($config) {
		parent::__construct($config);
		$this->config = $config;
		add_action('init', array(&$this, 'init'));
	}

	public function init() {
		// Initialize the Quote Builder
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/quote-builder.php';
		$this->quote_builder = new teqb_Quote_Builder($this->config);
	}
	
	/**
	 * Plugin activation
	 */
	public function activate() {
		// Call parent activate method
		parent::activate();
	}
	
	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Call parent deactivate method
		parent::deactivate();
	}

}
