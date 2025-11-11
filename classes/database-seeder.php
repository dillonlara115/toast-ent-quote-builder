<?php
/**
 * Database Seeder for Quote Builder
 * 
 * Imports services, packages, and add-ons from JSON into CPTs
 */

if (!defined('ABSPATH')) {
	exit;
}

class teqb_Database_Seeder {
	
	/**
	 * Seed database from JSON file
	 * 
	 * @param string $json_file_path Path to JSON file (optional, defaults to sanitized_test.json)
	 * @param array $locations Array of location slugs to assign items to (optional, defaults to all)
	 * @return array Results with counts of created items
	 */
	public static function seed($json_file_path = null, $locations = null) {
		if (!$json_file_path) {
			$json_file_path = plugin_dir_path(dirname(__FILE__)) . 'sanitized_test.json';
		}
		
		if (!file_exists($json_file_path)) {
			return array(
				'success' => false,
				'error' => 'JSON file not found: ' . $json_file_path,
			);
		}
		
		$json_content = file_get_contents($json_file_path);
		$data = json_decode($json_content, true);
		
		if (!$data || !isset($data['services'])) {
			return array(
				'success' => false,
				'error' => 'Invalid JSON structure',
			);
		}
		
		// Get default locations if not specified
		if ($locations === null) {
			$location_terms = get_terms(array(
				'taxonomy' => 'teqb_location',
				'hide_empty' => false,
			));
			$locations = array();
			foreach ($location_terms as $term) {
				$locations[] = $term->term_id;
			}
		}
		
		$results = array(
			'success' => true,
			'services_created' => 0,
			'packages_created' => 0,
			'addons_created' => 0,
			'services_updated' => 0,
			'packages_updated' => 0,
			'addons_updated' => 0,
			'errors' => array(),
		);
		
		// Track service IDs for linking packages and addons
		$service_id_map = array();
		
		// First pass: Create all services
		foreach ($data['services'] as $service_data) {
			$service_result = self::create_service($service_data, $locations);
			if ($service_result['success']) {
				$service_id_map[$service_data['id']] = $service_result['post_id'];
				$results['services_created']++;
			} else {
				if (isset($service_result['updated']) && $service_result['updated']) {
					$service_id_map[$service_data['id']] = $service_result['post_id'];
					$results['services_updated']++;
				} else {
					$results['errors'][] = 'Service ' . $service_data['id'] . ': ' . $service_result['error'];
				}
			}
		}
		
		// Second pass: Create packages (need service IDs)
		foreach ($data['services'] as $service_data) {
			if (!isset($service_id_map[$service_data['id']])) {
				continue;
			}
			
			$service_post_id = $service_id_map[$service_data['id']];
			
			if (isset($service_data['packages']) && is_array($service_data['packages'])) {
				foreach ($service_data['packages'] as $package_data) {
					$package_result = self::create_package($package_data, $service_post_id, $locations);
					if ($package_result['success']) {
						$results['packages_created']++;
					} else {
						if (isset($package_result['updated']) && $package_result['updated']) {
							$results['packages_updated']++;
						} else {
							$results['errors'][] = 'Package ' . $package_data['id'] . ': ' . $package_result['error'];
						}
					}
				}
			}
		}
		
		// Third pass: Create addons (need service IDs)
		foreach ($data['services'] as $service_data) {
			if (!isset($service_id_map[$service_data['id']])) {
				continue;
			}
			
			$service_post_id = $service_id_map[$service_data['id']];
			
			if (isset($service_data['addons']) && is_array($service_data['addons'])) {
				foreach ($service_data['addons'] as $addon_data) {
					$addon_result = self::create_addon($addon_data, $service_post_id, $locations);
					if ($addon_result['success']) {
						$results['addons_created']++;
					} else {
						if (isset($addon_result['updated']) && $addon_result['updated']) {
							$results['addons_updated']++;
						} else {
							$results['errors'][] = 'Add-on ' . $addon_data['id'] . ': ' . $addon_result['error'];
						}
					}
				}
			}
		}
		
		return $results;
	}
	
	/**
	 * Create or update a service
	 */
	private static function create_service($service_data, $locations) {
		$service_id = $service_data['id'] ?? '';
		if (empty($service_id)) {
			return array('success' => false, 'error' => 'Missing service ID');
		}
		
		// Check if service already exists
		$existing = get_posts(array(
			'post_type' => 'teqb_service',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_teqb_service_id',
					'value' => $service_id,
					'compare' => '=',
				),
			),
			'post_status' => 'any',
		));
		
		$post_data = array(
			'post_type' => 'teqb_service',
			'post_title' => $service_data['label'] ?? '',
			'post_content' => '',
			'post_status' => 'publish',
		);
		
		if (!empty($existing)) {
			$post_data['ID'] = $existing[0]->ID;
		}
		
		$post_id = wp_insert_post($post_data, true);
		
		if (is_wp_error($post_id)) {
			return array('success' => false, 'error' => $post_id->get_error_message());
		}
		
		// Save meta fields
		update_post_meta($post_id, '_teqb_service_id', $service_id);
		update_post_meta($post_id, '_teqb_subtitle', $service_data['subtitle'] ?? '');
		
		// Calculate starting price from packages
		$starting_price = null;
		if (isset($service_data['packages']) && is_array($service_data['packages'])) {
			$prices = array();
			foreach ($service_data['packages'] as $pkg) {
				if (isset($pkg['price'])) {
					$prices[] = floatval($pkg['price']);
				}
			}
			if (!empty($prices)) {
				$starting_price = min($prices);
			}
		}
		if ($starting_price !== null) {
			update_post_meta($post_id, '_teqb_starting_price', $starting_price);
		}
		
		update_post_meta($post_id, '_teqb_features_title', 'What\'s Included');
		
		// Convert features array to newline-separated string
		if (isset($service_data['features']) && is_array($service_data['features'])) {
			update_post_meta($post_id, '_teqb_features', implode("\n", $service_data['features']));
		}
		
		// Convert paragraphs array to newline-separated string
		if (isset($service_data['paragraphs']) && is_array($service_data['paragraphs'])) {
			update_post_meta($post_id, '_teqb_paragraphs', implode("\n", $service_data['paragraphs']));
		}
		
		// Assign locations
		if (!empty($locations)) {
			wp_set_post_terms($post_id, $locations, 'teqb_location', false);
		}
		
		return array(
			'success' => true,
			'post_id' => $post_id,
			'updated' => !empty($existing),
		);
	}
	
	/**
	 * Create or update a package
	 */
	private static function create_package($package_data, $service_post_id, $locations) {
		$package_id = $package_data['id'] ?? '';
		if (empty($package_id)) {
			return array('success' => false, 'error' => 'Missing package ID');
		}
		
		// Check if package already exists
		$existing = get_posts(array(
			'post_type' => 'teqb_package',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_teqb_package_id',
					'value' => $package_id,
					'compare' => '=',
				),
			),
			'post_status' => 'any',
		));
		
		$post_data = array(
			'post_type' => 'teqb_package',
			'post_title' => $package_data['name'] ?? '',
			'post_content' => '',
			'post_status' => 'publish',
		);
		
		if (!empty($existing)) {
			$post_data['ID'] = $existing[0]->ID;
		}
		
		$post_id = wp_insert_post($post_data, true);
		
		if (is_wp_error($post_id)) {
			return array('success' => false, 'error' => $post_id->get_error_message());
		}
		
		// Save meta fields
		update_post_meta($post_id, '_teqb_package_id', $package_id);
		update_post_meta($post_id, '_teqb_service_id', $service_post_id);
		update_post_meta($post_id, '_teqb_price', floatval($package_data['price'] ?? 0));
		
		// Convert includes array to newline-separated string
		if (isset($package_data['includes']) && is_array($package_data['includes'])) {
			update_post_meta($post_id, '_teqb_includes', implode("\n", $package_data['includes']));
		}
		
		// Bonus options
		if (isset($package_data['bonusOptions']) && is_array($package_data['bonusOptions'])) {
			update_post_meta($post_id, '_teqb_bonus_options', implode("\n", $package_data['bonusOptions']));
		}
		if (isset($package_data['bonusLimit'])) {
			update_post_meta($post_id, '_teqb_bonus_limit', intval($package_data['bonusLimit']));
		}
		
		// Store bundled services as JSON (we'll handle this separately later)
		if (isset($package_data['bundledServices']) && is_array($package_data['bundledServices'])) {
			update_post_meta($post_id, '_teqb_bundled_services', wp_json_encode($package_data['bundledServices']));
		}
		
		// Assign locations
		if (!empty($locations)) {
			wp_set_post_terms($post_id, $locations, 'teqb_location', false);
		}
		
		return array(
			'success' => true,
			'post_id' => $post_id,
			'updated' => !empty($existing),
		);
	}
	
	/**
	 * Create or update an add-on
	 */
	private static function create_addon($addon_data, $service_post_id, $locations) {
		$addon_id = $addon_data['id'] ?? '';
		if (empty($addon_id)) {
			return array('success' => false, 'error' => 'Missing add-on ID');
		}
		
		// Check if addon already exists
		$existing = get_posts(array(
			'post_type' => 'teqb_addon',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_teqb_addon_id',
					'value' => $addon_id,
					'compare' => '=',
				),
			),
			'post_status' => 'any',
		));
		
		$post_data = array(
			'post_type' => 'teqb_addon',
			'post_title' => $addon_data['name'] ?? '',
			'post_content' => '',
			'post_status' => 'publish',
		);
		
		if (!empty($existing)) {
			$post_data['ID'] = $existing[0]->ID;
		}
		
		$post_id = wp_insert_post($post_data, true);
		
		if (is_wp_error($post_id)) {
			return array('success' => false, 'error' => $post_id->get_error_message());
		}
		
		// Save meta fields
		update_post_meta($post_id, '_teqb_addon_id', $addon_id);
		update_post_meta($post_id, '_teqb_service_id', $service_post_id);
		
		// Price (flat) or base (per unit)
		if (isset($addon_data['price'])) {
			update_post_meta($post_id, '_teqb_price', floatval($addon_data['price']));
		}
		if (isset($addon_data['base'])) {
			update_post_meta($post_id, '_teqb_base', floatval($addon_data['base']));
		}
		if (isset($addon_data['unit'])) {
			update_post_meta($post_id, '_teqb_unit', sanitize_text_field($addon_data['unit']));
		}
		if (isset($addon_data['min'])) {
			update_post_meta($post_id, '_teqb_min', intval($addon_data['min']));
		}
		
		// Options (array to newline-separated string)
		if (isset($addon_data['options']) && is_array($addon_data['options'])) {
			update_post_meta($post_id, '_teqb_options', implode("\n", $addon_data['options']));
		}
		
		// Extras (object to JSON string)
		if (isset($addon_data['extras']) && is_array($addon_data['extras'])) {
			update_post_meta($post_id, '_teqb_extras', wp_json_encode($addon_data['extras']));
		}
		
		// Assign locations
		if (!empty($locations)) {
			wp_set_post_terms($post_id, $locations, 'teqb_location', false);
		}
		
		return array(
			'success' => true,
			'post_id' => $post_id,
			'updated' => !empty($existing),
		);
	}
	
	/**
	 * Clear all seeded data (for testing/resetting)
	 */
	public static function clear_all() {
		$post_types = array('teqb_service', 'teqb_package', 'teqb_addon');
		$deleted = 0;
		
		foreach ($post_types as $post_type) {
			$posts = get_posts(array(
				'post_type' => $post_type,
				'posts_per_page' => -1,
				'post_status' => 'any',
			));
			
			foreach ($posts as $post) {
				wp_delete_post($post->ID, true);
				$deleted++;
			}
		}
		
		return array(
			'success' => true,
			'deleted' => $deleted,
		);
	}
}

