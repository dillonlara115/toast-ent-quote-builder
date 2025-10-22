<?php
/**
 * Simple Test Template for Data Flow
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

// Encode as JSON
$json_data = json_encode($test_data);
if ($json_data === false) {
    $json_data = '{}';
}

// Debug output
echo '<h1>Simple Test Template</h1>';
echo '<h2>PHP Debug Output:</h2>';
echo '<pre>';
echo 'Location: ' . htmlspecialchars($location) . "\n";
echo 'Location Name: ' . htmlspecialchars($location_data['location_name'] ?? 'Not found') . "\n";
echo 'Package Count: ' . count($location_data['packages'] ?? []) . "\n";
echo 'Add-on Count: ' . count($location_data['add_ons'] ?? []) . "\n";
echo 'JSON Data: ' . htmlspecialchars($json_data) . "\n";
echo 'JSON Length: ' . strlen($json_data) . "\n";
echo 'JSON Valid: ' . (json_decode($json_data) ? 'Yes' : 'No') . "\n";
echo '</pre>';

// Test Alpine.js
echo '<h2>Alpine.js Test:</h2>';
echo '<div x-data="{ message: \'Alpine.js works!\' }">';
echo '<p x-text="message"></p>';
echo '</div>';

// Test data passing
echo '<h2>Data Passing Test:</h2>';
echo '<div x-data="testComponent()" data-initial="' . htmlspecialchars($json_data) . '">';
echo '<p>Message: <span x-text="message"></span></p>';
echo '<p>Data: <span x-text="JSON.stringify(data)"></span></p>';
echo '</div>';

// Test quoteBuilder
echo '<h2>QuoteBuilder Test:</h2>';
echo '<div x-data="quoteBuilder(' . htmlspecialchars($json_data) . ')">';
echo '<p>Current Step: <span x-text="currentStep"></span></p>';
echo '<p>Total Steps: <span x-text="totalSteps"></span></p>';
echo '<p>Package Count: <span x-text="packageEntries.length"></span></p>';
echo '<p>Add-on Count: <span x-text="addOnEntries.length"></span></p>';
echo '<p>Location: <span x-text="quoteData.location"></span></p>';
echo '<p>Location Name: <span x-text="quoteData.data.location_name"></span></p>';
echo '</div>';
?>