<?php
/**
 * Quote Builder Data Structure
 * 
 * This file contains the hardcoded pricing data for different locations.
 * In the future, this can be replaced with admin-managed data.
 */

if (!defined('ABSPATH')) {
    exit;
}

$quote_builder_data = [
    'austin' => [
        'location_name' => 'Austin',
        'packages' => [
            'basic' => [
                'name' => 'Basic Package',
                'price' => 500,
                'description' => 'Perfect for small gatherings and intimate events. Includes basic sound system and lighting.',
                'features' => [
                    'Basic sound system',
                    'Standard lighting',
                    '4 hours of service',
                    '1 technician'
                ]
            ],
            'standard' => [
                'name' => 'Standard Package',
                'price' => 1000,
                'description' => 'Our most popular package for medium-sized events with enhanced features.',
                'features' => [
                    'Premium sound system',
                    'Enhanced lighting effects',
                    '6 hours of service',
                    '2 technicians',
                    'Basic decorations'
                ]
            ],
            'premium' => [
                'name' => 'Premium Package',
                'price' => 1800,
                'description' => 'The ultimate experience for large events with all premium features.',
                'features' => [
                    'Professional sound system',
                    'Advanced lighting and effects',
                    '8 hours of service',
                    '3 technicians',
                    'Premium decorations',
                    'Event planning consultation'
                ]
            ]
        ],
        'add_ons' => [
            'photo_booth' => [
                'name' => 'Photo Booth',
                'price' => 300,
                'description' => 'Fun photo booth with props and instant prints'
            ],
            'dj_service' => [
                'name' => 'DJ Service',
                'price' => 400,
                'description' => 'Professional DJ with extensive music library'
            ],
            'live_band' => [
                'name' => 'Live Band',
                'price' => 800,
                'description' => '3-piece live band for entertainment'
            ],
            'uplighting' => [
                'name' => 'Uplighting',
                'price' => 200,
                'description' => 'Colorful uplighting to enhance ambiance'
            ],
            'dance_floor' => [
                'name' => 'Dance Floor',
                'price' => 250,
                'description' => 'Portable dance floor (20x20 feet)'
            ],
            'catering' => [
                'name' => 'Catering Service',
                'price' => 500,
                'description' => 'Basic catering for up to 50 guests'
            ]
        ],
        'discounts' => [
            [
                'threshold' => 1500,
                'discount' => 0.1,
                'message' => '10% discount on orders over $1500'
            ],
            [
                'threshold' => 2500,
                'discount' => 0.15,
                'message' => '15% discount on orders over $2500'
            ]
        ]
    ],
    'houston' => [
        'location_name' => 'Houston',
        'packages' => [
            'basic' => [
                'name' => 'Basic Package',
                'price' => 450,
                'description' => 'Perfect for small gatherings and intimate events. Includes basic sound system and lighting.',
                'features' => [
                    'Basic sound system',
                    'Standard lighting',
                    '4 hours of service',
                    '1 technician'
                ]
            ],
            'standard' => [
                'name' => 'Standard Package',
                'price' => 900,
                'description' => 'Our most popular package for medium-sized events with enhanced features.',
                'features' => [
                    'Premium sound system',
                    'Enhanced lighting effects',
                    '6 hours of service',
                    '2 technicians',
                    'Basic decorations'
                ]
            ],
            'premium' => [
                'name' => 'Premium Package',
                'price' => 1600,
                'description' => 'The ultimate experience for large events with all premium features.',
                'features' => [
                    'Professional sound system',
                    'Advanced lighting and effects',
                    '8 hours of service',
                    '3 technicians',
                    'Premium decorations',
                    'Event planning consultation'
                ]
            ]
        ],
        'add_ons' => [
            'photo_booth' => [
                'name' => 'Photo Booth',
                'price' => 250,
                'description' => 'Fun photo booth with props and instant prints'
            ],
            'dj_service' => [
                'name' => 'DJ Service',
                'price' => 350,
                'description' => 'Professional DJ with extensive music library'
            ],
            'live_band' => [
                'name' => 'Live Band',
                'price' => 700,
                'description' => '3-piece live band for entertainment'
            ],
            'uplighting' => [
                'name' => 'Uplighting',
                'price' => 150,
                'description' => 'Colorful uplighting to enhance ambiance'
            ],
            'dance_floor' => [
                'name' => 'Dance Floor',
                'price' => 200,
                'description' => 'Portable dance floor (20x20 feet)'
            ],
            'catering' => [
                'name' => 'Catering Service',
                'price' => 450,
                'description' => 'Basic catering for up to 50 guests'
            ]
        ],
        'discounts' => [
            [
                'threshold' => 1200,
                'discount' => 0.1,
                'message' => '10% discount on orders over $1200'
            ],
            [
                'threshold' => 2000,
                'discount' => 0.15,
                'message' => '15% discount on orders over $2000'
            ]
        ]
    ],
    'dallas' => [
        'location_name' => 'Dallas',
        'packages' => [
            'basic' => [
                'name' => 'Basic Package',
                'price' => 550,
                'description' => 'Perfect for small gatherings and intimate events. Includes basic sound system and lighting.',
                'features' => [
                    'Basic sound system',
                    'Standard lighting',
                    '4 hours of service',
                    '1 technician'
                ]
            ],
            'standard' => [
                'name' => 'Standard Package',
                'price' => 1100,
                'description' => 'Our most popular package for medium-sized events with enhanced features.',
                'features' => [
                    'Premium sound system',
                    'Enhanced lighting effects',
                    '6 hours of service',
                    '2 technicians',
                    'Basic decorations'
                ]
            ],
            'premium' => [
                'name' => 'Premium Package',
                'price' => 2000,
                'description' => 'The ultimate experience for large events with all premium features.',
                'features' => [
                    'Professional sound system',
                    'Advanced lighting and effects',
                    '8 hours of service',
                    '3 technicians',
                    'Premium decorations',
                    'Event planning consultation'
                ]
            ]
        ],
        'add_ons' => [
            'photo_booth' => [
                'name' => 'Photo Booth',
                'price' => 350,
                'description' => 'Fun photo booth with props and instant prints'
            ],
            'dj_service' => [
                'name' => 'DJ Service',
                'price' => 450,
                'description' => 'Professional DJ with extensive music library'
            ],
            'live_band' => [
                'name' => 'Live Band',
                'price' => 900,
                'description' => '3-piece live band for entertainment'
            ],
            'uplighting' => [
                'name' => 'Uplighting',
                'price' => 250,
                'description' => 'Colorful uplighting to enhance ambiance'
            ],
            'dance_floor' => [
                'name' => 'Dance Floor',
                'price' => 300,
                'description' => 'Portable dance floor (20x20 feet)'
            ],
            'catering' => [
                'name' => 'Catering Service',
                'price' => 550,
                'description' => 'Basic catering for up to 50 guests'
            ]
        ],
        'discounts' => [
            [
                'threshold' => 1800,
                'discount' => 0.1,
                'message' => '10% discount on orders over $1800'
            ],
            [
                'threshold' => 3000,
                'discount' => 0.15,
                'message' => '15% discount on orders over $3000'
            ]
        ]
    ]
];

// Default location to use if none specified
$default_location = 'austin';