<?php
/**
 * Test Template for Debugging Data Flow
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load data
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/data.php';
global $quote_builder_data, $default_location;

// Get location from shortcode attributes
$atts = shortcode_atts(['location' => 'austin'], $_GET['atts'] ?? []);
$location = isset($quote_builder_data[$atts['location']]) ? $atts['location'] : $default_location;
$location_data = $quote_builder_data[$location];

// Create test data
$test_data = [
    'location' => $location,
    'data' => $location_data
];

// Output test data
header('Content-Type: application/json');
echo json_encode($test_data, JSON_PRETTY_PRINT);
exit;