<?php
/**
 * Custom Post Types and Taxonomies for Quote Builder
 * 
 * Registers Services, Packages, Add-ons CPTs and Location taxonomy
 */

if (!defined('ABSPATH')) {
	exit;
}

class teqb_Post_Types {
	
	/**
	 * Register all custom post types and taxonomies
	 */
	public static function register() {
		self::register_location_taxonomy();
		self::register_service_cpt();
		self::register_package_cpt();
		self::register_addon_cpt();
	}
	
	/**
	 * Register Location Taxonomy
	 * Applied to Services, Packages, and Add-ons
	 */
	private static function register_location_taxonomy() {
		$labels = array(
			'name'              => __('Locations', 'teqb'),
			'singular_name'     => __('Location', 'teqb'),
			'search_items'      => __('Search Locations', 'teqb'),
			'all_items'         => __('All Locations', 'teqb'),
			'parent_item'       => __('Parent Location', 'teqb'),
			'parent_item_colon' => __('Parent Location:', 'teqb'),
			'edit_item'         => __('Edit Location', 'teqb'),
			'update_item'       => __('Update Location', 'teqb'),
			'add_new_item'      => __('Add New Location', 'teqb'),
			'new_item_name'     => __('New Location Name', 'teqb'),
			'menu_name'         => __('Locations', 'teqb'),
		);
		
		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'query_var'          => true,
			'rewrite'            => false,
			'show_in_rest'       => true,
		);
		
		register_taxonomy('teqb_location', array('teqb_service', 'teqb_package', 'teqb_addon'), $args);
		
		// Register default locations if they don't exist
		self::register_default_locations();
	}
	
	/**
	 * Register default locations
	 */
	private static function register_default_locations() {
		$default_locations = array('austin', 'new-orleans', 'washington');
		
		foreach ($default_locations as $location_slug) {
			if (!term_exists($location_slug, 'teqb_location')) {
				$location_name = ucwords(str_replace('-', ' ', $location_slug));
				wp_insert_term($location_name, 'teqb_location', array('slug' => $location_slug));
			}
		}
	}
	
	/**
	 * Register Service CPT
	 */
	private static function register_service_cpt() {
		$labels = array(
			'name'                  => __('Services', 'teqb'),
			'singular_name'         => __('Service', 'teqb'),
			'menu_name'             => __('Services', 'teqb'),
			'name_admin_bar'        => __('Service', 'teqb'),
			'add_new'               => __('Add New', 'teqb'),
			'add_new_item'          => __('Add New Service', 'teqb'),
			'edit_item'             => __('Edit Service', 'teqb'),
			'new_item'              => __('New Service', 'teqb'),
			'view_item'             => __('View Service', 'teqb'),
			'search_items'          => __('Search Services', 'teqb'),
			'not_found'             => __('No services found.', 'teqb'),
			'not_found_in_trash'    => __('No services found in Trash.', 'teqb'),
			'all_items'             => __('All Services', 'teqb'),
			'item_published'        => __('Service published.', 'teqb'),
			'item_updated'          => __('Service updated.', 'teqb'),
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // Will be added to submenu manually
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'supports'           => array('title', 'editor', 'thumbnail', 'page-attributes'),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'menu_icon'          => 'dashicons-admin-generic',
			'taxonomies'         => array('teqb_location'),
		);
		
		register_post_type('teqb_service', $args);
	}
	
	/**
	 * Register Package CPT
	 */
	private static function register_package_cpt() {
		$labels = array(
			'name'                  => __('Packages', 'teqb'),
			'singular_name'         => __('Package', 'teqb'),
			'menu_name'             => __('Packages', 'teqb'),
			'name_admin_bar'        => __('Package', 'teqb'),
			'add_new'               => __('Add New', 'teqb'),
			'add_new_item'          => __('Add New Package', 'teqb'),
			'edit_item'             => __('Edit Package', 'teqb'),
			'new_item'              => __('New Package', 'teqb'),
			'view_item'             => __('View Package', 'teqb'),
			'search_items'          => __('Search Packages', 'teqb'),
			'not_found'             => __('No packages found.', 'teqb'),
			'not_found_in_trash'    => __('No packages found in Trash.', 'teqb'),
			'all_items'             => __('All Packages', 'teqb'),
			'item_published'        => __('Package published.', 'teqb'),
			'item_updated'          => __('Package updated.', 'teqb'),
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // Will be added to submenu manually
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'supports'           => array('title', 'editor', 'page-attributes'),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'menu_icon'          => 'dashicons-products',
			'taxonomies'         => array('teqb_location'),
		);
		
		register_post_type('teqb_package', $args);
	}
	
	/**
	 * Register Add-on CPT
	 */
	private static function register_addon_cpt() {
		$labels = array(
			'name'                  => __('Add-ons', 'teqb'),
			'singular_name'         => __('Add-on', 'teqb'),
			'menu_name'             => __('Add-ons', 'teqb'),
			'name_admin_bar'        => __('Add-on', 'teqb'),
			'add_new'               => __('Add New', 'teqb'),
			'add_new_item'          => __('Add New Add-on', 'teqb'),
			'edit_item'             => __('Edit Add-on', 'teqb'),
			'new_item'              => __('New Add-on', 'teqb'),
			'view_item'             => __('View Add-on', 'teqb'),
			'search_items'          => __('Search Add-ons', 'teqb'),
			'not_found'             => __('No add-ons found.', 'teqb'),
			'not_found_in_trash'    => __('No add-ons found in Trash.', 'teqb'),
			'all_items'             => __('All Add-ons', 'teqb'),
			'item_published'        => __('Add-on published.', 'teqb'),
			'item_updated'          => __('Add-on updated.', 'teqb'),
		);
		
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // Will be added to submenu manually
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'supports'           => array('title', 'editor', 'page-attributes'),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'menu_icon'          => 'dashicons-plus-alt',
			'taxonomies'         => array('teqb_location'),
		);
		
		register_post_type('teqb_addon', $args);
	}
}

