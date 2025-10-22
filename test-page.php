<?php
/**
 * Test Page for Quote Builder Data Flow
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load data
require_once plugin_dir_path(__FILE__) . 'includes/data.php';
global $quote_builder_data, $default_location;

// Get location from URL parameter
$location = isset($_GET['location']) ? $_GET['location'] : 'austin';
$location = isset($quote_builder_data[$location]) ? $location : $default_location;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Builder Test</title>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js" defer></script>
</head>
<body>
    <h1>Quote Builder Test Page</h1>
    
    <h2>PHP Debug Output:</h2>
    <pre>
Location: <?php echo htmlspecialchars($location); ?>
Location Name: <?php echo htmlspecialchars($location_data['location_name'] ?? 'Not found'); ?>
Package Count: <?php echo count($location_data['packages'] ?? []); ?>
Add-on Count: <?php echo count($location_data['add_ons'] ?? []); ?>
JSON Data: <?php echo htmlspecialchars($json_data); ?>
JSON Length: <?php echo strlen($json_data); ?>
JSON Valid: <?php echo json_decode($json_data) ? 'Yes' : 'No'; ?>
    </pre>
    
    <h2>Alpine.js Test:</h2>
    <div x-data="{ message: 'Alpine.js works!' }">
        <p x-text="message"></p>
    </div>
    
    <h2>Data Passing Test:</h2>
    <div x-data="testComponent()" data-initial="<?php echo htmlspecialchars($json_data); ?>">
        <p>Message: <span x-text="message"></span></p>
        <p>Data: <span x-text="JSON.stringify(data)"></span></p>
    </div>
    
    <h2>QuoteBuilder Test:</h2>
    <div x-data="quoteBuilder(<?php echo htmlspecialchars($json_data); ?>)">
        <p>Current Step: <span x-text="currentStep"></span></p>
        <p>Total Steps: <span x-text="totalSteps"></span></p>
        <p>Package Count: <span x-text="packageEntries.length"></span></p>
        <p>Add-on Count: <span x-text="addOnEntries.length"></span></p>
        <p>Location: <span x-text="quoteData.location"></span></p>
        <p>Location Name: <span x-text="quoteData.data.location_name"></span></p>
    </div>
    
    <script>
        // Test component
        function testComponent() {
            return {
                message: 'Test Component Working',
                data: null,
                init() {
                    console.log('Test component initialized');
                    const element = document.querySelector('[x-data*="quoteBuilder"]');
                    if (element) {
                        const initialData = element.getAttribute('data-initial');
                        console.log('Found initial data:', initialData);
                        if (initialData && initialData !== '{}') {
                            try {
                                this.data = JSON.parse(initialData);
                                console.log('Parsed data:', this.data);
                            } catch (e) {
                                console.error('Failed to parse initial data:', e);
                            }
                        }
                    }
                }
            }
        }
        
        // Make test component available to Alpine
        document.addEventListener('alpine:init', () => {
            Alpine.data('testComponent', testComponent);
        });
    </script>
</body>
</html>