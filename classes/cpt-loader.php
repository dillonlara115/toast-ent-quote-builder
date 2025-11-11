<?php
/**
 * Helper class to load CPT data for Quote Builder
 */

if (!defined('ABSPATH')) {
	exit;
}

class teqb_CPT_Loader {
	
	/**
	 * Get all services with their packages and add-ons
	 * 
	 * @param string|null $location_slug Optional location slug to filter by
	 * @return array
	 */
	public static function get_all_services($location_slug = null) {
		$args = array(
			'post_type' => 'teqb_service',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'menu_order',
			'order' => 'ASC',
		);
		
		if ($location_slug) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'teqb_location',
					'field' => 'slug',
					'terms' => $location_slug,
				),
			);
		}
		
		$service_posts = get_posts($args);
		$services = array();
		
		foreach ($service_posts as $service_post) {
			$service_id_meta = get_post_meta($service_post->ID, '_teqb_service_id', true);
			if (empty($service_id_meta)) {
				continue;
			}
			
			$service = array(
				'post_id' => $service_post->ID,
				'id' => $service_id_meta,
				'label' => $service_post->post_title,
				'subtitle' => get_post_meta($service_post->ID, '_teqb_subtitle', true),
				'starting_price' => floatval(get_post_meta($service_post->ID, '_teqb_starting_price', true)),
				'features_title' => get_post_meta($service_post->ID, '_teqb_features_title', true) ?: 'What\'s Included',
				'features' => self::text_to_array(get_post_meta($service_post->ID, '_teqb_features', true)),
				'paragraphs' => self::text_to_array(get_post_meta($service_post->ID, '_teqb_paragraphs', true)),
				'packages' => self::get_packages_for_service($service_post->ID, $location_slug),
				'addons' => self::get_addons_for_service($service_post->ID, $location_slug),
			);
			
			$services[] = $service;
		}
		
		return $services;
	}
	
	/**
	 * Get packages for a specific service
	 */
	public static function get_packages_for_service($service_post_id, $location_slug = null) {
		$args = array(
			'post_type' => 'teqb_package',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => '_teqb_service_id',
					'value' => $service_post_id,
					'compare' => '=',
				),
			),
		);
		
		if ($location_slug) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'teqb_location',
					'field' => 'slug',
					'terms' => $location_slug,
				),
			);
		}
		
		$package_posts = get_posts($args);
		$packages = array();
		
		foreach ($package_posts as $package_post) {
			$package_id_meta = get_post_meta($package_post->ID, '_teqb_package_id', true);
			if (empty($package_id_meta)) {
				continue;
			}
			
			$includes = self::text_to_array(get_post_meta($package_post->ID, '_teqb_includes', true));
			$bonus_options = self::text_to_array(get_post_meta($package_post->ID, '_teqb_bonus_options', true));
			
			$package = array(
				'post_id' => $package_post->ID,
				'id' => $package_id_meta,
				'name' => $package_post->post_title,
				'price' => floatval(get_post_meta($package_post->ID, '_teqb_price', true)),
				'includes' => $includes,
				'bonusOptions' => $bonus_options,
				'bonusLimit' => intval(get_post_meta($package_post->ID, '_teqb_bonus_limit', true)),
				'menu_order' => $package_post->menu_order, // Include menu_order for sorting
			);
			
			// Only add additionalTimeMessage if it's set
			$additional_time_message = get_post_meta($package_post->ID, '_teqb_additional_time_message', true);
			if (!empty($additional_time_message)) {
				$package['additionalTimeMessage'] = $additional_time_message;
			}
			
			// Load bundled services if they exist
			$bundled_services_json = get_post_meta($package_post->ID, '_teqb_bundled_services', true);
			if ($bundled_services_json) {
				$bundled_services = json_decode($bundled_services_json, true);
				if (is_array($bundled_services)) {
					$package['bundledServices'] = $bundled_services;
				}
			}
			
			$packages[] = $package;
		}
		
		return $packages;
	}
	
	/**
	 * Get add-ons for a specific service
	 */
	public static function get_addons_for_service($service_post_id, $location_slug = null) {
		$args = array(
			'post_type' => 'teqb_addon',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => '_teqb_service_id',
					'value' => $service_post_id,
					'compare' => '=',
				),
			),
		);
		
		if ($location_slug) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'teqb_location',
					'field' => 'slug',
					'terms' => $location_slug,
				),
			);
		}
		
		$addon_posts = get_posts($args);
		$addons = array();
		
		foreach ($addon_posts as $addon_post) {
			$addon_id_meta = get_post_meta($addon_post->ID, '_teqb_addon_id', true);
			if (empty($addon_id_meta)) {
				continue;
			}
			
			$options = self::text_to_array(get_post_meta($addon_post->ID, '_teqb_options', true));
			$extras_json = get_post_meta($addon_post->ID, '_teqb_extras', true);
			$extras = array();
			if ($extras_json) {
				$decoded = json_decode($extras_json, true);
				if (is_array($decoded)) {
					$extras = $decoded;
				}
			}
			
			$addon = array(
				'post_id' => $addon_post->ID,
				'id' => $addon_id_meta,
				'name' => $addon_post->post_title,
				'price' => floatval(get_post_meta($addon_post->ID, '_teqb_price', true)) ?: null,
				'base' => floatval(get_post_meta($addon_post->ID, '_teqb_base', true)) ?: null,
				'unit' => get_post_meta($addon_post->ID, '_teqb_unit', true),
				'min' => intval(get_post_meta($addon_post->ID, '_teqb_min', true)) ?: null,
				'options' => $options,
				'extras' => $extras,
			);
			
			$addons[] = $addon;
		}
		
		return $addons;
	}
	
	/**
	 * Convert newline-separated text to array
	 */
	private static function text_to_array($text) {
		if (empty($text)) {
			return array();
		}
		return array_filter(array_map('trim', explode("\n", $text)));
	}
	
	/**
	 * Get all available locations
	 */
	public static function get_locations() {
		$terms = get_terms(array(
			'taxonomy' => 'teqb_location',
			'hide_empty' => false,
		));
		
		if (is_wp_error($terms)) {
			return array();
		}
		
		$locations = array();
		foreach ($terms as $term) {
			$locations[] = array(
				'slug' => $term->slug,
				'name' => $term->name,
				'term_id' => $term->term_id,
			);
		}
		
		return $locations;
	}
}

