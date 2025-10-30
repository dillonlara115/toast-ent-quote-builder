<?php
/**
 * Setup functions on activate & deactivate events:
 * - Initialize custom options, database, etc.
 * - Upgrade custom options, database, etc.
 * - Cleanup on deactivate
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'classes/base.php';

class teqb_Setup extends teqb_Base {

	/**
	 * Constructor
	 */
	public function __construct($config = []) {
		parent::__construct($config);
	}

	/**
	 * Specify all codes required for plugin activation here.
	 */
	public function activate() {
		// Initialize custom things on plugin activation
		$this->install();
	}

	/**
	 * Specify all codes required for plugin deactivation here.
	 */
	public function deactivate() {
	}

	/**
	 * Specify all codes required for plugin uninstall here.
	 *
	 */
	public function uninstall() {
	}
	

	public function install() {
		
		// Initialize plugin options
		$this->initOptions();
		$this->ensure_builder_caps();
	}

	/**
	 * Storing custom options
	 */
	public function initOptions() {
	}

	protected function get_builder_capability_list() {
		return array(
			'read_teqb_builder',
			'read_private_teqb_builders',
			'edit_teqb_builder',
			'edit_teqb_builders',
			'edit_others_teqb_builders',
			'edit_published_teqb_builders',
			'edit_private_teqb_builders',
			'publish_teqb_builders',
			'delete_teqb_builder',
			'delete_teqb_builders',
			'delete_others_teqb_builders',
			'delete_private_teqb_builders',
			'delete_published_teqb_builders',
		);
	}

	public function ensure_builder_caps() {
		if (!function_exists('get_role') || !class_exists('WP_Role')) {
			return;
		}

		$admin = get_role('administrator');
		if (!$admin instanceof WP_Role) {
			return;
		}

		foreach ($this->get_builder_capability_list() as $capability) {
			if (!$admin->has_cap($capability)) {
				$admin->add_cap($capability);
			}
		}
	}

}
