<?php
/**
 * Quote Builder Class
 * 
 * Handles the shortcode registration, asset loading, and core functionality
 * for the Toast Entertainment Quote Builder.
 */

if (!defined('ABSPATH')) {
    exit;
}

class teqb_Quote_Builder extends teqb_Base {
    
    /**
     * Constructor
     */
    public function __construct($config) {
        parent::__construct($config);
        $this->config = $config;
        
        // Register shortcode
        add_shortcode('toast_quote_builder', [$this, 'render_quote_builder']);
        
        // Register AJAX handlers
        add_action('wp_ajax_submit_quote', [$this, 'handle_quote_submission']);
        add_action('wp_ajax_nopriv_submit_quote', [$this, 'handle_quote_submission']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        // Flowbite CSS
        wp_enqueue_style(
            'flowbite-css',
            'https://cdn.jsdelivr.net/npm/flowbite@2.2.0/dist/flowbite.min.css',
            [],
            '2.2.0'
        );
        
        // Alpine.js - Load in header to ensure it's available before our script
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            [],
            '3.15.0',
            false
        );
        wp_add_inline_script(
            'alpinejs',
            'window.deferLoadingAlpine = function (init) { document.addEventListener("DOMContentLoaded", init); };',
            'before'
        );
        wp_script_add_data('alpinejs', 'defer', true);
        
        // Custom CSS
        wp_enqueue_style(
            'quote-builder-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/quote-builder.css',
            ['flowbite-css'],
            $this->config['version']
        );
        
        // Custom JS - Load in header with Alpine.js as dependency so the component is available
        wp_enqueue_script(
            'quote-builder-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/quote-builder.js',
            ['alpinejs'],
            $this->config['version'],
            false
        );
        wp_script_add_data('quote-builder-js', 'defer', true);
        
        // Localize script for AJAX URL
        wp_localize_script(
            'quote-builder-js',
            'quoteBuilderConfig',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('quote_builder_nonce'),
            ]
        );
    }
    
    /**
     * Render the quote builder shortcode
     */
    public function render_quote_builder($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts([
            'location' => 'austin',
        ], $atts, 'toast_quote_builder');
        
        // Filter for modifying shortcode attributes
        $atts = apply_filters('teqb_shortcode_attributes', $atts, 'toast_quote_builder');
        
        // Allow developers to hook before the template is rendered
        do_action('teqb_before_template', $atts);
        
        // Load template
        ob_start();
        require plugin_dir_path(dirname(__FILE__)) . 'templates/quote-builder-template.php';
        $template_content = ob_get_clean();
        
        // Allow filters on the rendered content
        return apply_filters('teqb_template_content', $template_content, $atts);
    }
    
    /**
     * Handle quote submission via AJAX
     */
    public function handle_quote_submission() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'quote_builder_nonce')) {
            wp_die('Security check failed');
        }
        
        // Sanitize and validate form data
        $name       = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email      = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $phone      = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $event_date = sanitize_text_field(wp_unslash($_POST['event_date'] ?? ''));
        $event_type = sanitize_text_field(wp_unslash($_POST['event_type'] ?? ''));
        $guests     = intval($_POST['guests'] ?? 0);
        $message    = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

        $services_raw = wp_unslash($_POST['services'] ?? '[]');
        $services_decoded = json_decode($services_raw, true);
        if (!is_array($services_decoded)) {
            wp_send_json_error(['message' => 'Quote details are missing or invalid. Please try again.']);
        }

        $services = $this->sanitize_services_payload($services_decoded);
        if (empty($services)) {
            wp_send_json_error(['message' => 'Please select at least one service to continue.']);
        }

        $subtotal       = floatval($_POST['subtotal'] ?? 0);
        $discount       = floatval($_POST['discount'] ?? 0);
        $discount_label = sanitize_text_field(wp_unslash($_POST['discount_label'] ?? ''));
        $final_total    = floatval($_POST['final_total'] ?? 0);
        
        // Filter for modifying submitted data
        $submission_data = apply_filters('teqb_submission_data', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'event_date' => $event_date,
            'event_type' => $event_type,
            'guests' => $guests,
            'services' => $services,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_label' => $discount_label,
            'final_total' => $final_total,
            'message' => $message,
        ]);
        
        // Extract filtered data
        extract($submission_data);
        
        // Custom validation filter
        $validation_result = apply_filters('teqb_validate_submission', true, $submission_data);
        if ($validation_result !== true) {
            wp_send_json_error(['message' => $validation_result]);
        }
        
        // Default validation
        if (empty($name) || empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Please fill in all required fields with valid information.']);
        }
        
        // Prepare email content
        $email_subject = apply_filters('teqb_admin_email_subject', 'New Quote Request from ' . $name, $submission_data);
        $email_headers = apply_filters('teqb_email_headers', ['Content-Type: text/html; charset=UTF-8'], 'admin');
        
        // Email to admin
        $admin_email = apply_filters('teqb_admin_email', get_option('admin_email'), $submission_data);
        $admin_message = $this->generate_admin_email_html($submission_data);
        
        // Filter for modifying admin email content
        $admin_message = apply_filters('teqb_admin_email_content', $admin_message, $submission_data);
        
        // Email to customer
        $customer_subject = apply_filters('teqb_customer_email_subject', 'Your Quote Request from Toast Entertainment', $submission_data);
        $customer_headers = apply_filters('teqb_email_headers', ['Content-Type: text/html; charset=UTF-8'], 'customer');
        $customer_message = $this->generate_customer_email_html($submission_data);
        
        // Filter for modifying customer email content
        $customer_message = apply_filters('teqb_customer_email_content', $customer_message, $submission_data);
        
        // Action before sending emails
        do_action('teqb_before_send_emails', $submission_data);
        
        // Send emails
        $admin_sent = wp_mail($admin_email, $email_subject, $admin_message, $email_headers);
        $customer_sent = wp_mail($email, $customer_subject, $customer_message, $customer_headers);
        
        // Action after sending emails
        do_action('teqb_after_send_emails', $submission_data, $admin_sent, $customer_sent);
        
        // Filter for modifying success response
        $success_message = apply_filters('teqb_success_message', 'Your quote request has been submitted successfully! We will contact you soon.', $submission_data);
        $error_message = apply_filters('teqb_error_message', 'There was an error submitting your quote request. Please try again.', $submission_data);
        
        if ($admin_sent && $customer_sent) {
            wp_send_json_success(['message' => $success_message]);
        } else {
            wp_send_json_error(['message' => $error_message]);
        }
    }
    
    /**
     * Generate HTML email for admin
     */
    private function generate_admin_email_html($data) {
        $services = $data['services'];
        ob_start();
        ?>
        <html>
        <body style="font-family: Arial, sans-serif; max-width: 720px; margin: 0 auto; padding: 24px;">
            <h2 style="color: #333;">New Quote Request</h2>
            
            <h3 style="color: #555; margin-top: 24px;">Customer Information</h3>
            <p><strong>Name:</strong> <?php echo esc_html($data['name']); ?></p>
            <p><strong>Email:</strong> <?php echo esc_html($data['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo esc_html($data['phone']); ?></p>
            <?php if (!empty($data['event_type'])) : ?>
                <p><strong>Event Type:</strong> <?php echo esc_html($data['event_type']); ?></p>
            <?php endif; ?>
            <?php if (!empty($data['event_date'])) : ?>
                <p><strong>Event Date:</strong> <?php echo esc_html($data['event_date']); ?></p>
            <?php endif; ?>
            <?php if (!empty($data['guests'])) : ?>
                <p><strong>Guests:</strong> <?php echo esc_html($data['guests']); ?></p>
            <?php endif; ?>

            <h3 style="color: #555; margin-top: 24px;">Requested Services</h3>
            <?php foreach ($services as $service) : ?>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <h4 style="margin: 0 0 8px; color: #1f2937;">
                        <?php echo esc_html($service['serviceLabel']); ?>
                    </h4>
                    <p style="margin: 0 0 8px;">
                        <strong>Package:</strong>
                        <?php echo esc_html($service['package']['name']); ?>
                        (<?php echo $this->format_currency($service['package']['price']); ?>)
                    </p>
                    <?php if (!empty($service['package']['bonuses'])) : ?>
                        <p style="margin: 0 0 8px;">
                            <strong>Selected Bonuses:</strong>
                            <?php echo esc_html(implode(', ', $service['package']['bonuses'])); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($service['package']['includes'])) : ?>
                        <p style="margin: 0 0 8px;"><strong>Includes:</strong></p>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['package']['includes'] as $include) : ?>
                                <li><?php echo esc_html($include); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($service['addOns'])) : ?>
                        <p style="margin: 0 0 6px;"><strong>Add-ons:</strong></p>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['addOns'] as $addon) : ?>
                                <li>
                                    <?php echo esc_html($addon['name']); ?>
                                    <?php if (!empty($addon['quantity']) && !empty($addon['unit'])) : ?>
                                        (<?php echo esc_html($addon['quantity']) . ' ' . esc_html($addon['unit']); ?>)
                                    <?php elseif (!empty($addon['quantity'])) : ?>
                                        (<?php echo esc_html($addon['quantity']); ?>)
                                    <?php endif; ?>
                                    – <?php echo $this->format_currency($addon['total']); ?>
                                    <?php if (!empty($addon['extras'])) : ?>
                                        <br><small>Extras: <?php echo esc_html(implode(', ', $addon['extras'])); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($addon['options'])) : ?>
                                        <br><small>Options: <?php echo esc_html(implode(', ', (array) $addon['options'])); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <p style="margin: 0;"><strong>Service Subtotal:</strong> <?php echo $this->format_currency($service['subtotal']); ?></p>
                </div>
            <?php endforeach; ?>

            <h3 style="color: #555; margin-top: 24px;">Pricing Summary</h3>
            <p><strong>Subtotal:</strong> <?php echo $this->format_currency($data['subtotal']); ?></p>
            <?php if ($data['discount'] > 0) : ?>
                <p><strong>Discount:</strong> -<?php echo $this->format_currency($data['discount']); ?>
                    <?php if (!empty($data['discount_label'])) : ?>
                        <br><small><?php echo esc_html($data['discount_label']); ?></small>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <p><strong>Final Total:</strong> <?php echo $this->format_currency($data['final_total']); ?></p>

            <?php if (!empty($data['message'])) : ?>
                <h3 style="color: #555; margin-top: 24px;">Client Message</h3>
                <p><?php echo nl2br(esc_html($data['message'])); ?></p>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate HTML email for customer
     */
    private function generate_customer_email_html($data) {
        $services = $data['services'];
        ob_start();
        ?>
        <html>
        <body style="font-family: Arial, sans-serif; max-width: 720px; margin: 0 auto; padding: 24px;">
            <h2 style="color: #333;">Thank You for Your Quote Request!</h2>
            
            <p>Hi <?php echo esc_html($data['name']); ?>,</p>
            <p>We’ve received your request and our team is already reviewing the details. Below is a summary of the services you selected.</p>
            
            <?php foreach ($services as $service) : ?>
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <h3 style="margin: 0 0 8px; color: #1f2937;"><?php echo esc_html($service['serviceLabel']); ?></h3>
                    <p style="margin: 0 0 8px;">
                        <strong>Package:</strong>
                        <?php echo esc_html($service['package']['name']); ?>
                        (<?php echo $this->format_currency($service['package']['price']); ?>)
                    </p>
                    <?php if (!empty($service['package']['includes'])) : ?>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['package']['includes'] as $include) : ?>
                                <li><?php echo esc_html($include); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($service['addOns'])) : ?>
                        <p style="margin: 0 0 6px;"><strong>Add-ons:</strong></p>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['addOns'] as $addon) : ?>
                                <li>
                                    <?php echo esc_html($addon['name']); ?>
                                    <?php if (!empty($addon['quantity']) && !empty($addon['unit'])) : ?>
                                        (<?php echo esc_html($addon['quantity']) . ' ' . esc_html($addon['unit']); ?>)
                                    <?php elseif (!empty($addon['quantity'])) : ?>
                                        (<?php echo esc_html($addon['quantity']); ?>)
                                    <?php endif; ?>
                                    – <?php echo $this->format_currency($addon['total']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <p style="margin: 0;"><strong>Service Subtotal:</strong> <?php echo $this->format_currency($service['subtotal']); ?></p>
                </div>
            <?php endforeach; ?>

            <h3 style="color: #555; margin-top: 24px;">Pricing Summary</h3>
            <p><strong>Subtotal:</strong> <?php echo $this->format_currency($data['subtotal']); ?></p>
            <?php if ($data['discount'] > 0) : ?>
                <p><strong>Discount:</strong> -<?php echo $this->format_currency($data['discount']); ?>
                    <?php if (!empty($data['discount_label'])) : ?>
                        <br><small><?php echo esc_html($data['discount_label']); ?></small>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <p><strong>Estimated Total:</strong> <?php echo $this->format_currency($data['final_total']); ?></p>

            <p style="margin-top: 24px;">We’ll reach out soon to confirm availability and next steps. Feel free to reply to this email if you have questions in the meantime.</p>
            
            <p>With gratitude,<br>The Toast Entertainment Team</p>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Sanitize services payload from the front-end
     */
    private function sanitize_services_payload($services) {
        $sanitized = [];

        foreach ($services as $service) {
            if (empty($service['serviceId'])) {
                continue;
            }

            $package = $service['package'] ?? [];
            $package_includes = [];
            if (!empty($package['includes']) && is_array($package['includes'])) {
                $package_includes = array_map('sanitize_text_field', $package['includes']);
            }
            $package_bonuses = [];
            if (!empty($package['bonuses']) && is_array($package['bonuses'])) {
                $package_bonuses = array_map('sanitize_text_field', $package['bonuses']);
            }

            $add_ons = [];
            if (!empty($service['addOns']) && is_array($service['addOns'])) {
                foreach ($service['addOns'] as $addon) {
                    if (empty($addon['id'])) {
                        continue;
                    }

                    $extras = [];
                    if (!empty($addon['extras']) && is_array($addon['extras'])) {
                        $extras = array_map('sanitize_text_field', $addon['extras']);
                    }

                    $options = [];
                    if (!empty($addon['options']) && is_array($addon['options'])) {
                        $options = array_map('sanitize_text_field', $addon['options']);
                    } elseif (!empty($addon['options']) && is_string($addon['options'])) {
                        $options = [sanitize_text_field($addon['options'])];
                    }

                    $add_ons[] = [
                        'id' => sanitize_text_field($addon['id']),
                        'name' => sanitize_text_field($addon['name'] ?? ''),
                        'quantity' => isset($addon['quantity']) ? floatval($addon['quantity']) : null,
                        'unit' => sanitize_text_field($addon['unit'] ?? ''),
                        'price' => isset($addon['price']) ? floatval($addon['price']) : 0,
                        'total' => isset($addon['total']) ? floatval($addon['total']) : 0,
                        'extras' => $extras,
                        'options' => $options,
                        'detail' => isset($addon['detail']) ? sanitize_text_field($addon['detail']) : '',
                    ];
                }
            }

            $sanitized[] = [
                'serviceId' => sanitize_text_field($service['serviceId']),
                'serviceLabel' => sanitize_text_field($service['serviceLabel'] ?? ''),
                'package' => [
                    'id' => sanitize_text_field($package['id'] ?? ''),
                    'name' => sanitize_text_field($package['name'] ?? ''),
                    'price' => isset($package['price']) ? floatval($package['price']) : 0,
                    'includes' => $package_includes,
                    'bonuses' => $package_bonuses,
                ],
                'addOns' => $add_ons,
                'subtotal' => isset($service['subtotal']) ? floatval($service['subtotal']) : 0,
            ];
        }

        return $sanitized;
    }

    /**
     * Helper to format currency consistently
     */
    private function format_currency($amount) {
        $amount = floatval($amount);
        return '$' . number_format($amount, 2);
    }
}
