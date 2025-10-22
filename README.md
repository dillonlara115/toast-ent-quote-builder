# Toast Entertainment Quote Builder

A dynamic WordPress plugin that provides a multi-step quote builder form for event services. The plugin allows users to select event packages, add-ons, and submit quote requests with dynamic pricing calculations.

## Features

- Multi-step quote builder form with Alpine.js
- Dynamic pricing calculations with real-time updates
- Discount application based on total order value
- Responsive design with Flowbite CSS
- Email notifications to admin and customers
- Support for multiple locations with different pricing
- Extensible with hooks and filters for customization

## Installation

1. Upload the `toast-ent-quote-builder` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the shortcode `[toast_quote_builder location="austin"]` on any page or post

## Usage

### Basic Shortcode

```
[toast_quote_builder]
```

This will use the default location (Austin).

### Location-Specific Shortcode

```
[toast_quote_builder location="houston"]
```

This will display pricing and packages specific to Houston.

### Available Locations

- `austin` - Austin location (default)
- `houston` - Houston location
- `dallas` - Dallas location

## Customization

### Adding Filters and Hooks

The plugin provides several filters and hooks for customization:

#### Filters

- `teqb_shortcode_attributes` - Filter shortcode attributes
- `teqb_quote_data` - Filter the entire quote builder data array
- `teqb_location_data` - Filter specific location data
- `teqb_template_content` - Filter the rendered template content
- `teqb_submission_data` - Filter submitted form data
- `teqb_validate_submission` - Custom validation for form submission
- `teqb_admin_email_subject` - Filter admin email subject
- `teqb_customer_email_subject` - Filter customer email subject
- `teqb_email_headers` - Filter email headers
- `teqb_admin_email` - Filter admin email address
- `teqb_admin_email_content` - Filter admin email content
- `teqb_customer_email_content` - Filter customer email content
- `teqb_success_message` - Filter success message
- `teqb_error_message` - Filter error message

#### Actions

- `teqb_before_template` - Action before loading template
- `teqb_before_send_emails` - Action before sending emails
- `teqb_after_send_emails` - Action after sending emails

### Example Customization

```php
// Add a custom location
add_filter('teqb_quote_data', function($quote_builder_data) {
    $quote_builder_data['san_antonio'] = [
        'location_name' => 'San Antonio',
        'packages' => [
            'basic' => [
                'name' => 'Basic Package',
                'price' => 400,
                'description' => 'Perfect for small events in San Antonio.',
                'features' => [
                    'Basic sound system',
                    'Standard lighting',
                    '4 hours of service',
                    '1 technician'
                ]
            ],
            // Add more packages...
        ],
        'add_ons' => [
            // Add add-ons...
        ],
        'discounts' => [
            // Add discount rules...
        ]
    ];
    
    return $quote_builder_data;
});

// Customize admin email subject
add_filter('teqb_admin_email_subject', function($subject, $submission_data) {
    return 'New Quote Request: ' . $submission_data['event_type'] . ' in ' . $submission_data['location'];
}, 10, 2);

// Add custom validation
add_filter('teqb_validate_submission', function($is_valid, $submission_data) {
    // Require at least 20 guests for corporate events
    if ($submission_data['event_type'] === 'Corporate Event' && $submission_data['guests'] < 20) {
        return 'Corporate events require at least 20 guests.';
    }
    
    return $is_valid;
}, 10, 2);
```

## File Structure

```
toast-ent-quote-builder/
├── classes/
│   ├── base.php              # Base class with helper functions
│   ├── plugin.php            # Main plugin class
│   ├── quote-builder.php     # Quote Builder functionality
│   └── setup.php             # Plugin setup functions
├── includes/
│   └── data.php              # Hardcoded pricing data
├── templates/
│   └── quote-builder-template.php  # Form template
├── assets/
│   ├── css/
│   │   └── quote-builder.css # Custom styles
│   └── js/
│       └── quote-builder.js  # JavaScript functionality
├── loader.php                # Plugin loader
├── uninstall.php             # Plugin uninstall script
└── README.md                 # This file
```

## Future Enhancements

The plugin is designed to be easily extended with future features:

1. **Admin Interface**: Add a WordPress admin interface to manage pricing data
2. **Database Integration**: Move pricing data from hardcoded arrays to database tables
3. **Payment Integration**: Add payment processing for deposits
4. **Calendar Integration**: Add availability checking and booking system
5. **Advanced Analytics**: Track quote requests and conversion rates

## Support

For support and feature requests, please contact the plugin developer.

## License

This plugin is licensed under the GPL v2 or later.