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
        add_shortcode('quote-builder', [$this, 'render_builder_shortcode']);
        add_shortcode('quote', [$this, 'render_builder_shortcode']); // legacy support
        
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
        add_action('wp_print_styles', function() {
            global $wp_styles;
            echo '<!-- Enqueued Styles -->';
            foreach ($wp_styles->queue as $handle) {
                echo '<!-- ' . $handle . ' -->';
            }
            echo '<!-- End Enqueued Styles -->';
        });

        $css_path = plugin_dir_path(dirname(__FILE__)) . 'assets/css/quote-builder.css';
        $css_version = file_exists($css_path) ? filemtime($css_path) : $this->config['version'];
        $js_path = plugin_dir_path(dirname(__FILE__)) . 'assets/js/quote-builder.js';
        $js_version = file_exists($js_path) ? filemtime($js_path) : $this->config['version'];

        // Custom CSS - Load after Flowbite CSS and Fusion styles
        wp_enqueue_style(
            'quote-builder-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/quote-builder.css',
            ['flowbite-css', 'fusion-dynamic-css'],
            $css_version,
            'all'
        );
        // Custom JS - Load in header with Alpine.js as dependency so the component is available
        wp_enqueue_script(
            'quote-builder-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/quote-builder.js',
            ['alpinejs'],
            $js_version,
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
            'builder' => '',
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
     * Render alias shortcodes like [quote=builder-slug]
     */
    public function render_builder_shortcode($atts) {
        $builder_slug = $this->extract_builder_slug($atts);
        if (!$builder_slug) {
            return '';
        }

        return $this->render_quote_builder([
            'builder' => $builder_slug,
        ]);
    }

    /**
     * Normalize shortcode attributes to obtain the builder slug.
     */
    protected function extract_builder_slug($atts) {
        $builder_slug = '';

        if (is_array($atts)) {
            if (!empty($atts['builder'])) {
                $builder_slug = $atts['builder'];
            } elseif (!empty($atts['quote-builder'])) {
                $builder_slug = $atts['quote-builder'];
            } elseif (!empty($atts['slug'])) {
                $builder_slug = $atts['slug'];
            } elseif (isset($atts[0]) && is_string($atts[0])) {
                $builder_slug = $atts[0];
            } else {
                foreach ($atts as $key => $value) {
                    if (is_string($key) && $value === '' && strpos($key, 'builder-') === 0) {
                        $builder_slug = $key;
                        break;
                    }
                    if (is_string($value) && strpos($value, 'builder-') === 0) {
                        $builder_slug = $value;
                        break;
                    }
                }
            }
        }

        if (!$builder_slug) {
            return '';
        }

        $builder_slug = trim($builder_slug);

        if (strpos($builder_slug, 'builder-') === 0) {
            $builder_slug = substr($builder_slug, strlen('builder-'));
        }

        return sanitize_title($builder_slug);
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

        $bundle_rewards_raw = wp_unslash($_POST['bundle_rewards'] ?? '[]');
        $bundle_rewards_decoded = json_decode($bundle_rewards_raw, true);
        $bundle_rewards = $this->sanitize_bundle_rewards($bundle_rewards_decoded);

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
            'bundle_rewards' => $bundle_rewards,
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
        
        $entry_id = $this->store_quote_entry($submission_data);
        if ($entry_id) {
            $submission_data['entry_id'] = $entry_id;
        }

        $send_result = $this->send_quote_notifications($submission_data);

        if ($entry_id) {
            update_post_meta($entry_id, '_teqb_admin_email_sent', $send_result['admin'] ? current_time('mysql') : '');
            update_post_meta($entry_id, '_teqb_customer_email_sent', $send_result['customer'] ? current_time('mysql') : '');
        }

        // Filter for modifying success response
        $success_message = apply_filters('teqb_success_message', 'Your quote request has been submitted successfully! We will contact you soon.', $submission_data);
        $error_message = apply_filters('teqb_error_message', 'There was an error submitting your quote request. Please try again.', $submission_data);

        if ($send_result['admin'] && $send_result['customer']) {
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
                    <?php if (!empty($service['bonusSavings'])) : ?>
                        <p style="margin: 0 0 8px; color: #2563eb;">
                            <strong>Included Enhancements Value:</strong> <?php echo $this->format_currency($service['bonusSavings']); ?>
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
                    <?php if (!empty($service['bundleUpgrades'])) : ?>
                        <p style="margin: 0 0 6px;"><strong>Upgrades:</strong></p>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['bundleUpgrades'] as $upgrade) : ?>
                                <?php $delta = isset($upgrade['delta']) ? max(0, floatval($upgrade['delta'])) : 0; ?>
                                <li>
                                    <?php echo esc_html($upgrade['packageName']); ?>
                                    <?php if (!empty($upgrade['includedName'])) : ?>
                                        <br><small>Replaces: <?php echo esc_html($upgrade['includedName']); ?></small>
                                    <?php endif; ?>
                                    <br><strong>
                                        <?php echo $delta > 0 ? $this->format_currency($delta) : 'Included'; ?>
                                    </strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($service['bundleUpgrades'])) : ?>
                        <p style="margin: 0 0 6px;"><strong>Upgrades:</strong></p>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['bundleUpgrades'] as $upgrade) : ?>
                                <?php $delta = isset($upgrade['delta']) ? max(0, floatval($upgrade['delta'])) : 0; ?>
                                <li>
                                    <?php echo esc_html($upgrade['packageName']); ?>
                                    <?php if (!empty($upgrade['includedName'])) : ?>
                                        <br><small>Replaces: <?php echo esc_html($upgrade['includedName']); ?></small>
                                    <?php endif; ?>
                                    <br><strong>
                                        <?php echo $delta > 0 ? $this->format_currency($delta) : 'Included'; ?>
                                    </strong>
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
        <h3 style="color: #555; margin-top: 32px;">Details &amp; Pricing Notes</h3>
        <h5 style="color: #222; margin: 3px 0 12px 0; font-size: 15px;">Some Notes on Pricing</h5>
        <ul style="margin: 0 0 18px 22px; padding: 0 0 0 0; color: #444; font-size: 15px;">
            <li style="margin-bottom: 10px;">
                If you do not see a package that works for you, please contact us and we would be happy to arrange a package more closely suited to your needs and budget!
            </li>
            <li style="margin-bottom: 10px;">
                All applicable taxes are already included in the price listed. Peak dates may affect pricing.
            </li>
            <li style="margin-bottom: 10px;">
                For Saturday events in March, April, May, September, November, and December, please add $100 to the package price. For Saturday events in October, please add $200 to the package price.
            </li>
            <li>
                National holiday rates may differ. Please inquire for accurate quote.
            </li>
        </ul>
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
        $bundle_rewards = isset($data['bundle_rewards']) && is_array($data['bundle_rewards']) ? $data['bundle_rewards'] : [];
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
                    <?php if (!empty($service['package']['bonuses'])) : ?>
                        <p style="margin: 0 0 8px;">
                            <strong>Selected Bonuses:</strong>
                            <?php echo esc_html(implode(', ', $service['package']['bonuses'])); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($service['bonusSavings'])) : ?>
                        <p style="margin: 0 0 8px; color: #2563eb;">
                            <strong>Included Enhancements Value:</strong> <?php echo $this->format_currency($service['bonusSavings']); ?>
                        </p>
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
                    <?php if (!empty($service['bundleUpgrades'])) : ?>
                        <p style="margin: 0 0 6px;"><strong>Upgrades:</strong></p>
                        <ul style="margin: 0 0 12px 18px; padding: 0;">
                            <?php foreach ($service['bundleUpgrades'] as $upgrade) : ?>
                                <?php $delta = isset($upgrade['delta']) ? max(0, floatval($upgrade['delta'])) : 0; ?>
                                <li>
                                    <?php echo esc_html($upgrade['packageName']); ?>
                                    <?php if (!empty($upgrade['includedName'])) : ?>
                                        <br><small>Replaces: <?php echo esc_html($upgrade['includedName']); ?></small>
                                    <?php endif; ?>
                                    <br><strong>
                                        <?php echo $delta > 0 ? $this->format_currency($delta) : 'Included'; ?>
                                    </strong>
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
            <?php if (!empty($bundle_rewards)) : ?>
                <div style="border: 1px solid #fcd34d; background-color: #fffbeb; border-radius: 8px; padding: 16px; margin: 24px 0;">
                    <p style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #d97706;">Bundle Rewards</p>
                    <?php foreach ($bundle_rewards as $reward) : ?>
                        <div style="margin-bottom: 16px;">
                            <?php if (!empty($reward['headline'])) : ?>
                                <p style="margin: 0 0 4px; font-weight: 600; color: #1f2937;"><?php echo esc_html($reward['headline']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($reward['subline'])) : ?>
                                <p style="margin: 0 0 8px; color: #4b5563;"><?php echo esc_html($reward['subline']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($reward['quantityText'])) : ?>
                                <p style="margin: 0 0 6px; font-weight: 600; color: #1f2937;"><?php echo esc_html($reward['quantityText']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($reward['options'])) : ?>
                                <ul style="margin: 0 0 12px 18px; padding: 0; color: #1f2937;">
                                    <?php foreach ($reward['options'] as $option) : ?>
                                        <li style="margin: 0 0 4px;"><?php echo esc_html($option); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
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

            $bundle_upgrades = [];
            if (!empty($service['bundleUpgrades']) && is_array($service['bundleUpgrades'])) {
                foreach ($service['bundleUpgrades'] as $upgrade) {
                    if (empty($upgrade['packageId'])) {
                        continue;
                    }

                    $bundle_upgrades[] = [
                        'packageId' => sanitize_text_field($upgrade['packageId']),
                        'packageName' => sanitize_text_field($upgrade['packageName'] ?? ''),
                        'includedName' => sanitize_text_field($upgrade['includedName'] ?? ''),
                        'delta' => isset($upgrade['delta']) ? floatval($upgrade['delta']) : 0,
                        'includedPrice' => isset($upgrade['includedPrice']) ? floatval($upgrade['includedPrice']) : 0,
                        'upgradePrice' => isset($upgrade['upgradePrice']) ? floatval($upgrade['upgradePrice']) : 0,
                        'upgradeServiceId' => sanitize_text_field($upgrade['upgradeServiceId'] ?? ''),
                        'serviceLabel' => sanitize_text_field($upgrade['serviceLabel'] ?? ''),
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
                'bundleUpgrades' => $bundle_upgrades,
                'bonusSelections' => array_map('sanitize_text_field', $service['bonusSelections'] ?? []),
                'bonusSavings' => isset($service['bonusSavings']) ? floatval($service['bonusSavings']) : 0,
                'subtotal' => isset($service['subtotal']) ? floatval($service['subtotal']) : 0,
            ];
        }

        return $sanitized;
    }

    /**
     * Sanitize bundle rewards payload from the front-end.
     *
     * @param mixed $rewards
     * @return array
     */
    private function sanitize_bundle_rewards($rewards) {
        $sanitized = [];

        if (!is_array($rewards)) {
            return $sanitized;
        }

        foreach ($rewards as $reward) {
            if (!is_array($reward)) {
                continue;
            }

            $type = isset($reward['type']) ? sanitize_text_field($reward['type']) : '';
            $quantity = isset($reward['quantity']) ? intval($reward['quantity']) : 0;
            $label = isset($reward['label']) ? sanitize_text_field($reward['label']) : '';
            $quantity_text = isset($reward['quantityText']) ? sanitize_text_field($reward['quantityText']) : '';
            $headline = isset($reward['headline']) ? sanitize_text_field($reward['headline']) : '';
            $subline = isset($reward['subline']) ? sanitize_text_field($reward['subline']) : '';

            $options = [];
            if (!empty($reward['options']) && is_array($reward['options'])) {
                foreach ($reward['options'] as $option) {
                    $options[] = sanitize_text_field($option);
                }
            }

            $sanitized[] = [
                'type' => $type,
                'quantity' => $quantity,
                'label' => $label,
                'quantityText' => $quantity_text,
                'headline' => $headline,
                'subline' => $subline,
                'options' => $options,
            ];
        }

        return $sanitized;
    }

    /**
     * Persist the submitted quote to the database.
     */
    private function store_quote_entry($data) {
        $post_title_parts = array_filter([
            __('Quote', 'teqb'),
            !empty($data['name']) ? '– ' . $data['name'] : '',
            get_bloginfo('name'),
        ]);

        $post_id = wp_insert_post([
            'post_type'   => 'teqb_quote',
            'post_status' => 'publish',
            'post_title'  => implode(' ', $post_title_parts),
        ], true);

        if (is_wp_error($post_id)) {
            error_log('TEQB: Failed to store quote entry - ' . $post_id->get_error_message());
            return 0;
        }

        update_post_meta($post_id, '_teqb_quote_name', $data['name']);
        update_post_meta($post_id, '_teqb_quote_email', $data['email']);
        update_post_meta($post_id, '_teqb_quote_phone', $data['phone']);
        update_post_meta($post_id, '_teqb_quote_event_date', $data['event_date']);
        update_post_meta($post_id, '_teqb_quote_event_type', $data['event_type']);
        update_post_meta($post_id, '_teqb_quote_guests', $data['guests']);
        update_post_meta($post_id, '_teqb_quote_message', $data['message']);
        update_post_meta($post_id, '_teqb_quote_services', $data['services']);
        update_post_meta($post_id, '_teqb_quote_subtotal', $data['subtotal']);
        update_post_meta($post_id, '_teqb_quote_discount', $data['discount']);
        update_post_meta($post_id, '_teqb_quote_discount_label', $data['discount_label']);
        update_post_meta($post_id, '_teqb_quote_final_total', $data['final_total']);
        update_post_meta($post_id, '_teqb_quote_bundle_rewards', isset($data['bundle_rewards']) ? $data['bundle_rewards'] : []);

        return $post_id;
    }

    /**
     * Send admin and customer notifications for the provided submission data.
     */
    private function send_quote_notifications($submission_data, $args = array()) {
        $defaults = array(
            'target'             => 'customer',
            'custom_email'       => '',
            'send_admin_copy'    => true,
            'send_customer_copy' => true,
        );
        $args = wp_parse_args($args, $defaults);

        $settings = get_option('teqb_settings', []);
        $notification_email = '';
        if (!empty($settings['notification_email'])) {
            $sanitized = sanitize_email($settings['notification_email']);
            if (!empty($sanitized)) {
                $notification_email = $sanitized;
            }
        }
        if (empty($notification_email)) {
            $notification_email = get_option('admin_email');
        }

        $email_subject = apply_filters('teqb_admin_email_subject', 'New Quote Request from ' . $submission_data['name'], $submission_data);
        $email_headers = apply_filters('teqb_email_headers', ['Content-Type: text/html; charset=UTF-8'], 'admin');

        $admin_email = apply_filters('teqb_admin_email', $notification_email, $submission_data);
        $admin_message = $this->generate_admin_email_html($submission_data);
        $admin_message = apply_filters('teqb_admin_email_content', $admin_message, $submission_data);

        $customer_subject = apply_filters('teqb_customer_email_subject', 'Your Quote Request from Toast Entertainment', $submission_data);
        $customer_headers = apply_filters('teqb_email_headers', ['Content-Type: text/html; charset=UTF-8'], 'customer');
        $customer_message = $this->generate_customer_email_html($submission_data);
        $customer_message = apply_filters('teqb_customer_email_content', $customer_message, $submission_data);

        do_action('teqb_before_send_emails', $submission_data);

        $admin_sent = false;
        $customer_sent = false;

        if ($args['send_admin_copy']) {
            $target_admin_email = $admin_email;
            if ($args['target'] === 'admin' && !empty($args['custom_email'])) {
                $target_admin_email = $args['custom_email'];
            }
            if (!empty($target_admin_email)) {
                $admin_sent = wp_mail($target_admin_email, $email_subject, $admin_message, $email_headers);
            }
        }

        if ($args['send_customer_copy']) {
            $target_customer_email = $submission_data['email'];
            if ($args['target'] === 'customer' && !empty($args['custom_email'])) {
                $target_customer_email = $args['custom_email'];
            }
            if (!empty($target_customer_email)) {
                $customer_sent = wp_mail($target_customer_email, $customer_subject, $customer_message, $customer_headers);
            }
        }

        if ($args['target'] === 'custom' && !empty($args['custom_email'])) {
            $custom_sent = wp_mail($args['custom_email'], $customer_subject, $customer_message, $customer_headers);
            $customer_sent = $customer_sent || $custom_sent;
        }

        do_action('teqb_after_send_emails', $submission_data, $admin_sent, $customer_sent);

        return [
            'admin'    => $admin_sent,
            'customer' => $customer_sent,
        ];
    }

    /**
     * Resend notifications for a stored entry.
     */
    public function resend_quote_entry($entry_id, $args = array()) {
        $submission_data = $this->build_submission_data_from_entry($entry_id);
        if (!$submission_data) {
            return false;
        }

        $result = $this->send_quote_notifications($submission_data, $args);

        update_post_meta($entry_id, '_teqb_admin_email_sent', $result['admin'] ? current_time('mysql') : '');
        update_post_meta($entry_id, '_teqb_customer_email_sent', $result['customer'] ? current_time('mysql') : '');

        return ($result['admin'] && $result['customer']);
    }

    private function build_submission_data_from_entry($entry_id) {
        $post = get_post($entry_id);
        if (!$post || $post->post_type !== 'teqb_quote') {
            return null;
        }

        $services = get_post_meta($entry_id, '_teqb_quote_services', true);
        if (!is_array($services)) {
            $services = array();
        }

        $bundle_rewards = get_post_meta($entry_id, '_teqb_quote_bundle_rewards', true);
        if (!is_array($bundle_rewards)) {
            $bundle_rewards = array();
        }

        return array(
            'name'           => get_post_meta($entry_id, '_teqb_quote_name', true),
            'email'          => get_post_meta($entry_id, '_teqb_quote_email', true),
            'phone'          => get_post_meta($entry_id, '_teqb_quote_phone', true),
            'event_date'     => get_post_meta($entry_id, '_teqb_quote_event_date', true),
            'event_type'     => get_post_meta($entry_id, '_teqb_quote_event_type', true),
            'guests'         => get_post_meta($entry_id, '_teqb_quote_guests', true),
            'message'        => get_post_meta($entry_id, '_teqb_quote_message', true),
            'services'       => $services,
            'subtotal'       => floatval(get_post_meta($entry_id, '_teqb_quote_subtotal', true)),
            'discount'       => floatval(get_post_meta($entry_id, '_teqb_quote_discount', true)),
            'discount_label' => get_post_meta($entry_id, '_teqb_quote_discount_label', true),
            'final_total'    => floatval(get_post_meta($entry_id, '_teqb_quote_final_total', true)),
            'bundle_rewards' => $bundle_rewards,
        );
    }

    /**
     * Helper to format currency consistently
     */
    public function format_currency($amount) {
        $amount = floatval($amount);
        return '$' . number_format($amount, 2);
    }
}
