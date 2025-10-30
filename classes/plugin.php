<?php
/**
 * The core plugin class.
 *
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/setup.php';

class teqb_Plugin extends teqb_Setup {
	public $config;
	protected $quote_builder;
	protected $admin;
	
	public function __construct($config) {
		parent::__construct($config);
		$this->config = $config;

		add_action('init', array($this, 'register_post_types'));
		add_action('init', array($this, 'init'));
	}

	public function init() {
		$this->ensure_builder_caps();
		// Initialize the Quote Builder
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/quote-builder.php';
		$this->quote_builder = new teqb_Quote_Builder($this->config);

		if (is_admin()) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'classes/admin.php';
			$this->admin = new teqb_Admin($this->config, $this->quote_builder);
		}
	}

	/**
	 * Register custom post types used by the plugin.
	 */
	public function register_post_types() {
		$labels = array(
			'name'                  => __('Quote Entries', 'teqb'),
			'singular_name'         => __('Quote Entry', 'teqb'),
			'menu_name'             => __('Quote Entries', 'teqb'),
			'name_admin_bar'        => __('Quote Entry', 'teqb'),
			'add_new'               => __('Add New', 'teqb'),
			'add_new_item'          => __('Add New Quote Entry', 'teqb'),
			'edit_item'             => __('Edit Quote Entry', 'teqb'),
			'new_item'              => __('New Quote Entry', 'teqb'),
			'view_item'             => __('View Quote Entry', 'teqb'),
			'search_items'          => __('Search Quote Entries', 'teqb'),
			'not_found'             => __('No quote entries found.', 'teqb'),
			'not_found_in_trash'    => __('No quote entries found in Trash.', 'teqb'),
			'all_items'             => __('Quote Entries', 'teqb'),
			'item_published'        => __('Quote entry saved.', 'teqb'),
			'item_updated'          => __('Quote entry updated.', 'teqb'),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'supports'           => array('title'),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'rewrite'            => false,
			'query_var'          => false,
		);

		register_post_type('teqb_quote', $args);

		$builder_labels = array(
			'name'                  => __('Quote Builders', 'teqb'),
			'singular_name'         => __('Quote Builder', 'teqb'),
			'menu_name'             => __('Quote Builders', 'teqb'),
			'name_admin_bar'        => __('Quote Builder', 'teqb'),
			'add_new'               => __('Add New', 'teqb'),
			'add_new_item'          => __('Add New Quote Builder', 'teqb'),
			'edit_item'             => __('Edit Quote Builder', 'teqb'),
			'new_item'              => __('New Quote Builder', 'teqb'),
			'view_item'             => __('View Quote Builder', 'teqb'),
			'search_items'          => __('Search Quote Builders', 'teqb'),
			'not_found'             => __('No quote builders found.', 'teqb'),
			'not_found_in_trash'    => __('No quote builders found in Trash.', 'teqb'),
			'all_items'             => __('Quote Builders', 'teqb'),
			'item_published'        => __('Quote builder saved.', 'teqb'),
			'item_updated'          => __('Quote builder updated.', 'teqb'),
		);

		$builder_args = array(
			'labels'             => $builder_labels,
			'public'             => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'supports'           => array('title', 'editor'),
			'capability_type'    => array('teqb_builder', 'teqb_builders'),
			'map_meta_cap'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'menu_icon'          => 'dashicons-layout',
		);

		register_post_type('teqb_builder', $builder_args);
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
