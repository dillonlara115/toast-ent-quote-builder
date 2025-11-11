<?php
/**
 * Admin functionality for Toast Entertainment Quote Builder.
 */

if (!defined('ABSPATH')) {
	exit;
}

class teqb_Admin {
	protected $config;
	protected $quote_builder;

	public function __construct($config, $quote_builder) {
		$this->config = $config;
		$this->quote_builder = $quote_builder;

		add_action('admin_menu', array($this, 'register_menus'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('add_meta_boxes_teqb_quote', array($this, 'add_quote_metabox'));
		add_action('add_meta_boxes_teqb_builder', array($this, 'add_builder_metabox'));
		add_action('save_post_teqb_builder', array($this, 'save_builder_config'), 10, 3);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_builder_admin_assets'));
		add_filter('manage_teqb_quote_posts_columns', array($this, 'register_columns'));
		add_action('manage_teqb_quote_posts_custom_column', array($this, 'render_columns'), 10, 2);
		add_filter('post_row_actions', array($this, 'row_actions'), 10, 2);
		add_action('admin_post_teqb_resend_quote', array($this, 'handle_resend_request'));
		add_action('admin_notices', array($this, 'render_admin_notices'));
		add_action('admin_notices', array($this, 'render_import_notices'));
		
		// Import/Export handlers
		add_action('admin_post_teqb_export_builder', array($this, 'handle_export_builder'));
		add_action('admin_post_teqb_import_builder', array($this, 'handle_import_builder'));
		add_filter('post_row_actions', array($this, 'builder_row_actions'), 10, 2);
		
		// Add import UI to builder list page
		add_action('manage_posts_extra_tablenav', array($this, 'add_import_ui_to_builder_list'), 10, 1);
		add_action('admin_footer-edit.php', array($this, 'render_import_hidden_form'));
		
		// Hardcoded data exporter
		add_action('admin_post_teqb_export_hardcoded', array($this, 'handle_export_hardcoded'));
	}

	public function register_menus() {
		add_menu_page(
			__('Quote Builder', 'teqb'),
			__('Quote Builder', 'teqb'),
			'manage_options',
			'teqb-settings',
			array($this, 'render_settings_page'),
			'dashicons-clipboard',
			58
		);

		add_submenu_page(
			'teqb-settings',
			__('Settings', 'teqb'),
			__('Settings', 'teqb'),
			'manage_options',
			'teqb-settings',
			array($this, 'render_settings_page')
		);

		add_submenu_page(
			'teqb-settings',
			__('Quote Builders', 'teqb'),
			__('Quote Builders', 'teqb'),
			'edit_teqb_builders',
			'edit.php?post_type=teqb_builder'
		);

		add_submenu_page(
			'teqb-settings',
			__('Quote Entries', 'teqb'),
			__('Quote Entries', 'teqb'),
			'edit_posts',
			'edit.php?post_type=teqb_quote'
		);
	}

	public function register_settings() {
		register_setting('teqb_settings_group', 'teqb_settings', array($this, 'sanitize_settings'));

		add_settings_section(
			'teqb_notifications_section',
			__('Notification Settings', 'teqb'),
			function () {
				echo '<p>' . esc_html__('Configure where quote submission notifications are delivered.', 'teqb') . '</p>';
			},
			'teqb-settings'
		);

		add_settings_field(
			'notification_email',
			__('Notification Email', 'teqb'),
			array($this, 'render_email_field'),
			'teqb-settings',
			'teqb_notifications_section'
		);
	}

	public function sanitize_settings($settings) {
		$settings = is_array($settings) ? $settings : array();
		$settings['notification_email'] = !empty($settings['notification_email'])
			? sanitize_email($settings['notification_email'])
			: '';
		return $settings;
	}

	public function render_email_field() {
		$settings = get_option('teqb_settings', array());
		$value = isset($settings['notification_email']) ? esc_attr($settings['notification_email']) : '';
		$placeholder = esc_attr(get_option('admin_email'));
		echo '<input type="email" name="teqb_settings[notification_email]" value="' . $value . '" class="regular-text" placeholder="' . $placeholder . '">';
		echo '<p class="description">' . esc_html__('Leave blank to use the default WordPress admin email.', 'teqb') . '</p>';
	}

	public function render_settings_page() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to access this page.', 'teqb'));
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Quote Builder Settings', 'teqb'); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('teqb_settings_group');
				do_settings_sections('teqb-settings');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function add_builder_metabox() {
		add_meta_box(
			'teqb-builder-config',
			__('Builder Configuration', 'teqb'),
			array($this, 'render_builder_metabox'),
			'teqb_builder',
			'normal',
			'high'
		);
	}

	public function render_builder_metabox($post) {
		wp_nonce_field('teqb_builder_config', 'teqb_builder_config_nonce');

		$config = $this->get_builder_config($post->ID);
		$json_value = wp_json_encode($config);
		if (!is_string($json_value)) {
			$json_value = '';
		}

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'teqb_export_builder',
					'builder_id' => $post->ID,
				),
				admin_url('admin-post.php')
			),
			'teqb_export_builder_' . $post->ID
		);

		?>
		<div style="margin-bottom: 12px;">
			<a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">
				<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span>
				<?php esc_html_e('Export Configuration', 'teqb'); ?>
			</a>
			<p class="description" style="margin-top: 8px;">
				<?php esc_html_e('Download this builder configuration as a JSON file for backup or import into another site.', 'teqb'); ?>
			</p>
		</div>
		<input type="hidden" id="teqb_builder_config" name="teqb_builder_config" value="<?php echo esc_attr($json_value); ?>">
		<div id="teqb-builder-app" class="teqb-builder-admin-root">
			<p><?php esc_html_e('Loading builder editor…', 'teqb'); ?></p>
		</div>
		<?php
	}

	public function add_quote_metabox() {
		add_meta_box(
			'teqb-quote-details',
			__('Quote Details', 'teqb'),
			array($this, 'render_quote_metabox'),
			'teqb_quote',
			'normal',
			'high'
		);
	}

	public function render_quote_metabox($post) {
		$meta = $this->get_quote_meta($post->ID);
		?>
		<div style="font-size:14px; line-height:1.6;">
			<h3><?php esc_html_e('Customer Information', 'teqb'); ?></h3>
			<ul>
				<li><strong><?php esc_html_e('Name:', 'teqb'); ?></strong> <?php echo esc_html($meta['name']); ?></li>
				<li><strong><?php esc_html_e('Email:', 'teqb'); ?></strong> <a href="<?php echo esc_url('mailto:' . $meta['email']); ?>"><?php echo esc_html($meta['email']); ?></a></li>
				<li><strong><?php esc_html_e('Phone:', 'teqb'); ?></strong> <?php echo esc_html($meta['phone']); ?></li>
				<?php if (!empty($meta['event_date'])) : ?>
					<li><strong><?php esc_html_e('Event Date:', 'teqb'); ?></strong> <?php echo esc_html($meta['event_date']); ?></li>
				<?php endif; ?>
				<?php if (!empty($meta['event_type'])) : ?>
					<li><strong><?php esc_html_e('Event Type:', 'teqb'); ?></strong> <?php echo esc_html($meta['event_type']); ?></li>
				<?php endif; ?>
				<?php if (!empty($meta['guests'])) : ?>
					<li><strong><?php esc_html_e('Guests:', 'teqb'); ?></strong> <?php echo esc_html($meta['guests']); ?></li>
				<?php endif; ?>
				<?php if (!empty($meta['referral_source'])) : ?>
					<li><strong><?php esc_html_e('How did you hear of us:', 'teqb'); ?></strong> <?php echo esc_html($meta['referral_source']); ?></li>
				<?php endif; ?>
				<?php if (!empty($meta['event_venue_location'])) : ?>
					<li><strong><?php esc_html_e('Event venue location:', 'teqb'); ?></strong> <?php echo esc_html($meta['event_venue_location']); ?></li>
				<?php endif; ?>
			</ul>

			<?php if (!empty($meta['message'])) : ?>
				<h3><?php esc_html_e('Message', 'teqb'); ?></h3>
				<p><?php echo nl2br(esc_html($meta['message'])); ?></p>
			<?php endif; ?>

			<h3><?php esc_html_e('Services', 'teqb'); ?></h3>
			<?php foreach ($meta['services'] as $service) : ?>
				<div style="border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:12px;">
					<h4 style="margin:0 0 6px;"><?php echo esc_html($service['serviceLabel']); ?></h4>
					<p style="margin:0 0 6px;">
						<strong><?php esc_html_e('Package:', 'teqb'); ?></strong>
						<?php
						$package_name = isset($service['package']['name']) && $service['package']['name'] !== ''
							? $service['package']['name']
							: __('Not selected', 'teqb');
						echo esc_html($package_name);
						if (!empty($service['package']['price'])) {
							echo ' — ' . esc_html($this->quote_builder->format_currency($service['package']['price']));
						}
						?>
					</p>
					<?php if (!empty($service['package']['bonuses'])) : ?>
						<p style="margin:0 0 6px;"><?php esc_html_e('Bonuses:', 'teqb'); ?> <?php echo esc_html(implode(', ', $service['package']['bonuses'])); ?></p>
					<?php endif; ?>
					<?php if (!empty($service['addOns'])) : ?>
						<p style="margin:0 0 6px;"><?php esc_html_e('Add-ons:', 'teqb'); ?></p>
						<ul style="margin:0 0 6px 0; padding:0; list-style:none;">
							<?php foreach ($service['addOns'] as $addon) : ?>
								<li style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:3px 0;">
									<div>
										<span><?php echo esc_html($addon['name']); ?></span>
										<?php if (!empty($addon['detail'])) : ?>
											<span class="description" style="display:block;"><?php echo esc_html($addon['detail']); ?></span>
										<?php endif; ?>
									</div>
									<span><?php echo esc_html($this->quote_builder->format_currency($addon['total'] ?? 0)); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<p style="margin:0;"><strong><?php esc_html_e('Subtotal:', 'teqb'); ?></strong> <?php echo esc_html($this->quote_builder->format_currency($service['subtotal'] ?? 0)); ?></p>
				</div>
			<?php endforeach; ?>

			<h3><?php esc_html_e('Pricing Summary', 'teqb'); ?></h3>
			<ul>
				<li><strong><?php esc_html_e('Subtotal:', 'teqb'); ?></strong> <?php echo esc_html($this->quote_builder->format_currency($meta['subtotal'])); ?></li>
				<?php if ($meta['discount'] > 0) : ?>
					<li><strong><?php esc_html_e('Discount:', 'teqb'); ?></strong> -<?php echo esc_html($this->quote_builder->format_currency($meta['discount'])); ?>
						<?php if (!empty($meta['discount_label'])) : ?>
							<br><small><?php echo esc_html($meta['discount_label']); ?></small>
						<?php endif; ?>
					</li>
				<?php endif; ?>
				<li><strong><?php esc_html_e('Final Total:', 'teqb'); ?></strong> <?php echo esc_html($this->quote_builder->format_currency($meta['final_total'])); ?></li>
			</ul>

			<h3><?php esc_html_e('Email Delivery', 'teqb'); ?></h3>
			<ul>
				<li><strong><?php esc_html_e('Admin email sent:', 'teqb'); ?></strong> <?php echo esc_html($meta['admin_email_sent']); ?></li>
				<li><strong><?php esc_html_e('Customer email sent:', 'teqb'); ?></strong> <?php echo esc_html($meta['customer_email_sent']); ?></li>
			</ul>

			<h3><?php esc_html_e('Resend Emails', 'teqb'); ?></h3>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="teqb_resend_quote">
				<input type="hidden" name="quote_id" value="<?php echo esc_attr($post->ID); ?>">
				<?php wp_nonce_field('teqb_resend_quote_' . $post->ID); ?>
				<p>
					<label for="teqb_resend_target"><strong><?php esc_html_e('Send To', 'teqb'); ?></strong></label><br>
					<select id="teqb_resend_target" name="teqb_resend_target">
						<option value="customer"><?php esc_html_e('Customer Email', 'teqb'); ?> (<?php echo esc_html($meta['email']); ?>)</option>
						<option value="admin"><?php esc_html_e('Admin Notification Email', 'teqb'); ?></option>
						<option value="custom"><?php esc_html_e('Custom Email Address…', 'teqb'); ?></option>
					</select>
				</p>
				<p>
					<label for="teqb_resend_custom"><strong><?php esc_html_e('Custom Email', 'teqb'); ?></strong></label><br>
					<input type="email" name="teqb_resend_custom" id="teqb_resend_custom" class="regular-text" placeholder="<?php esc_attr_e('you@example.com', 'teqb'); ?>">
					<span class="description"><?php esc_html_e('Only used if "Custom Email Address" is selected above.', 'teqb'); ?></span>
				</p>
				<p>
					<label><strong><?php esc_html_e('Include Emails', 'teqb'); ?></strong></label><br>
					<label><input type="checkbox" name="teqb_resend_admin_copy" value="1" checked> <?php esc_html_e('Send admin copy', 'teqb'); ?></label><br>
					<label><input type="checkbox" name="teqb_resend_customer_copy" value="1" checked> <?php esc_html_e('Send customer copy', 'teqb'); ?></label>
				</p>
				<?php submit_button(__('Resend Emails', 'teqb'), 'secondary', 'submit', false); ?>
			</form>
		</div>
		<?php
	}

	public function enqueue_builder_admin_assets($hook) {
		if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'teqb_builder') {
			return;
		}

		$post_id = $this->get_current_builder_post_id();
		$config = $this->get_builder_config($post_id);

		wp_enqueue_style(
			'teqb-admin-flowbite',
			'https://cdn.jsdelivr.net/npm/flowbite@2.2.0/dist/flowbite.min.css',
			array(),
			'2.2.0'
		);

		$css_path = plugin_dir_path(dirname(__FILE__)) . 'assets/css/admin-builder.css';
		$css_version = file_exists($css_path) ? filemtime($css_path) : $this->config['version'];
		wp_enqueue_style(
			'teqb-builder-admin',
			plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-builder.css',
			array('teqb-admin-flowbite'),
			$css_version
		);

		$script_path = plugin_dir_path(dirname(__FILE__)) . 'assets/js/admin-builder.js';
		$script_version = file_exists($script_path) ? filemtime($script_path) : $this->config['version'];
		wp_enqueue_script(
			'teqb-builder-admin',
			plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-builder.js',
			array('wp-element', 'wp-components', 'wp-i18n'),
			$script_version,
			true
		);

		wp_localize_script('teqb-builder-admin', 'teqbBuilderAdmin', array(
			'postId'   => $post_id,
			'postSlug' => $post_id ? get_post_field('post_name', $post_id) : '',
			'config'   => $config,
			'defaults' => $this->default_builder_config(),
		));
	}

	public function save_builder_config($post_id, $post, $update) {
		if (!isset($_POST['teqb_builder_config_nonce']) || !wp_verify_nonce($_POST['teqb_builder_config_nonce'], 'teqb_builder_config')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
			return;
		}

		if (!current_user_can('edit_teqb_builder', $post_id)) {
			return;
		}

		$raw = isset($_POST['teqb_builder_config']) ? wp_unslash($_POST['teqb_builder_config']) : '';
		if ($raw === '') {
			delete_post_meta($post_id, '_teqb_builder_config');
			return;
		}

		$data = json_decode($raw, true);
		if (!is_array($data)) {
			return;
		}

		$sanitized = $this->sanitize_builder_config($data);
		$encoded = $this->encode_builder_config($sanitized);
		update_post_meta($post_id, '_teqb_builder_config', $encoded);
	}

	protected function get_builder_config($post_id) {
		$default = $this->default_builder_config();
		if (!$post_id) {
			return $default;
		}

		$stored = get_post_meta($post_id, '_teqb_builder_config', true);
		if (empty($stored)) {
			return $default;
		}

		$decoded = json_decode($stored, true);
		if (!is_array($decoded)) {
			return $default;
		}

		return array_replace_recursive($default, $decoded);
	}

	protected function default_builder_config() {
		return array(
			'services' => array(),
			'bundles'  => array(
				'rules' => array(),
				'rewards' => array(
					'signature_touch' => array(
						'label'        => __('Signature Touch', 'teqb'),
						'pluralLabel'  => __('Signature Touches', 'teqb'),
						'optionsLabel' => __('Signature Touch Options', 'teqb'),
						'options'      => array(),
					),
					'luxury_enhancement' => array(
						'label'        => __('Luxury Enhancement', 'teqb'),
						'pluralLabel'  => __('Luxury Enhancements', 'teqb'),
						'optionsLabel' => __('Luxury Enhancement Options', 'teqb'),
						'options'      => array(),
					),
				),
			),
			'form' => array(
				'require_phone'      => true,
				'require_event_date' => false,
				'success_message'    => '',
				'confirmation_copy'  => '',
			),
			'notifications' => array(
				'email' => '',
			),
		);
	}

	protected function sanitize_builder_config($value) {
		if (is_array($value)) {
			$sanitized = array();
			foreach ($value as $key => $item) {
				$sanitized[$key] = $this->sanitize_builder_config($item);
			}
			return $sanitized;
		}

		if (is_bool($value)) {
			return $value;
		}

		if (is_numeric($value)) {
			return 0 + $value;
		}

		if (is_string($value)) {
			return sanitize_textarea_field($value);
		}

		return '';
	}

	/**
	 * Encode builder configuration safely for storage.
	 */
	protected function encode_builder_config($config) {
		$encoded = wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($encoded === false) {
			$encoded = wp_json_encode($config, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		if ($encoded === false) {
			wp_die(__('Failed to encode builder configuration for storage.', 'teqb'));
		}

		return $encoded;
	}

	/**
	 * Store a summary of the most recent import for user feedback.
	 */
	protected function store_import_summary($config) {
		$summary = array(
			'services' => 0,
			'packages' => 0,
			'addons' => 0,
			'bundle_rules' => 0,
			'rewards' => array(),
		);

		if (!empty($config['services']) && is_array($config['services'])) {
			$summary['services'] = count($config['services']);
			foreach ($config['services'] as $service) {
				if (is_array($service)) {
					$summary['packages'] += isset($service['packages']) && is_array($service['packages']) ? count($service['packages']) : 0;
					$summary['addons'] += isset($service['addons']) && is_array($service['addons']) ? count($service['addons']) : 0;
				}
			}
		}

		if (!empty($config['bundles']['rules']) && is_array($config['bundles']['rules'])) {
			$summary['bundle_rules'] = count($config['bundles']['rules']);
		}

		if (!empty($config['bundles']['rewards']) && is_array($config['bundles']['rewards'])) {
			foreach ($config['bundles']['rewards'] as $key => $reward) {
				if (is_array($reward)) {
					$summary['rewards'][$key] = isset($reward['options']) && is_array($reward['options'])
						? count($reward['options'])
						: 0;
				}
			}
		}

		set_transient(
			'teqb_import_summary_' . get_current_user_id(),
			$summary,
			MINUTE_IN_SECONDS * 10
		);
	}

	/**
	 * Normalize imported configuration structure so it aligns with the admin schema.
	 */
	protected function normalize_import_config($config) {
		if (!is_array($config)) {
			return array();
		}

		$defaults = $this->default_builder_config();

		// Handle legacy export structure that used quoteData at the root.
		if (!isset($config['services']) && isset($config['quoteData']) && is_array($config['quoteData'])) {
			$config['services'] = $config['quoteData'];
			unset($config['quoteData']);
		}

		$config['services'] = $this->normalize_services(isset($config['services']) ? $config['services'] : array());

		$config['form'] = isset($config['form']) && is_array($config['form'])
			? array_replace_recursive($defaults['form'], $config['form'])
			: $defaults['form'];

		$config['notifications'] = isset($config['notifications']) && is_array($config['notifications'])
			? array_replace_recursive($defaults['notifications'], $config['notifications'])
			: $defaults['notifications'];

		$bundles = isset($config['bundles']) && is_array($config['bundles'])
			? array_replace_recursive($defaults['bundles'], $config['bundles'])
			: $defaults['bundles'];
		$bundles['rules'] = $this->normalize_list(isset($bundles['rules']) ? $bundles['rules'] : array());
		foreach ($bundles['rules'] as &$rule) {
			if (!is_array($rule)) {
				$rule = array();
			}
			$rule['freebies'] = $this->normalize_list(isset($rule['freebies']) ? $rule['freebies'] : array());
		}
		unset($rule);

		if (!isset($bundles['rewards']) || !is_array($bundles['rewards'])) {
			$bundles['rewards'] = array();
		} else {
			foreach ($bundles['rewards'] as $reward_key => &$reward) {
				if (!is_array($reward)) {
					$reward = array();
				}
				$reward['options'] = $this->normalize_list(isset($reward['options']) ? $reward['options'] : array());
			}
			unset($reward);
		}

		$config['bundles'] = $bundles;

		return $config;
	}

	/**
	 * Normalize services array to ensure numeric indexes and expected nested arrays.
	 */
	protected function normalize_services($services) {
		if (!is_array($services)) {
			return array();
		}

		// Convert associative structures (serviceId => data) into list format.
		if ($this->is_associative_array($services)) {
			$normalized = array();
			foreach ($services as $service_id => $service) {
				if (!is_array($service)) {
					continue;
				}
				if (empty($service['id']) && is_string($service_id)) {
					$service['id'] = $service_id;
				}
				$normalized[] = $this->normalize_service_config($service);
			}
			return $normalized;
		}

		$normalized = array();
		foreach ($services as $service) {
			if (is_array($service)) {
				$normalized[] = $this->normalize_service_config($service);
			}
		}
		return $normalized;
	}

	/**
	 * Normalize individual service entry.
	 */
	protected function normalize_service_config($service) {
		if (!is_array($service)) {
			return array();
		}

		$service['paragraphs'] = $this->normalize_list(isset($service['paragraphs']) ? $service['paragraphs'] : array());
		$service['features'] = $this->normalize_list(isset($service['features']) ? $service['features'] : array());

		$packages = $this->normalize_list(isset($service['packages']) ? $service['packages'] : array());
		foreach ($packages as &$package) {
			if (!is_array($package)) {
				$package = array();
			}
			if (!isset($package['id'])) {
				$package['id'] = '';
			}
			if (!isset($package['name'])) {
				$package['name'] = '';
			}
			if (!isset($package['price'])) {
				$package['price'] = 0;
			}
			$package['includes'] = $this->normalize_list(isset($package['includes']) ? $package['includes'] : array());
			if (isset($package['bonusOptions'])) {
				$package['bonusOptions'] = $this->normalize_list($package['bonusOptions']);
			}
			if (isset($package['bundledServices'])) {
				$package['bundledServices'] = $this->normalize_list($package['bundledServices']);
				foreach ($package['bundledServices'] as &$bundled_service) {
					if (!is_array($bundled_service)) {
						$bundled_service = array();
					}
					if (isset($bundled_service['upgradePackages'])) {
						$bundled_service['upgradePackages'] = $this->normalize_list($bundled_service['upgradePackages']);
					}
				}
				unset($bundled_service);
			}
		}
		unset($package);
		$service['packages'] = $packages;

		$addons = $this->normalize_list(isset($service['addons']) ? $service['addons'] : array());
		foreach ($addons as &$addon) {
			if (!is_array($addon)) {
				$addon = array();
			}
			if (!isset($addon['id'])) {
				$addon['id'] = '';
			}
			if (!isset($addon['name'])) {
				$addon['name'] = '';
			}
			if (isset($addon['options'])) {
				$addon['options'] = $this->normalize_list($addon['options']);
			}
			if (!isset($addon['extras']) || !is_array($addon['extras'])) {
				$addon['extras'] = array();
			}
		}
		unset($addon);
		$service['addons'] = $addons;

		return $service;
	}

	/**
	 * Ensure repeated structures are always numerically indexed arrays.
	 */
	protected function normalize_list($value) {
		if (!is_array($value)) {
			return array();
		}

		if ($this->is_associative_array($value)) {
			return array_values($value);
		}

		return array_values($value);
	}

	/**
	 * Determine if an array is associative.
	 */
	protected function is_associative_array($array) {
		if (!is_array($array)) {
			return false;
		}

		return array_keys($array) !== range(0, count($array) - 1);
	}

	protected function get_current_builder_post_id() {
		if (isset($_GET['post'])) {
			return absint($_GET['post']);
		}

		if (isset($_POST['post_ID'])) {
			return absint($_POST['post_ID']);
		}

		return 0;
	}

	public function register_columns($columns) {
		$date = isset($columns['date']) ? $columns['date'] : '';
		unset($columns['date']);

		$columns['email']      = __('Email', 'teqb');
		$columns['event_date'] = __('Event Date', 'teqb');
		$columns['total']      = __('Total', 'teqb');

		if ($date) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	public function render_columns($column, $post_id) {
		$meta = $this->get_quote_meta($post_id);
		switch ($column) {
			case 'email':
				echo esc_html($meta['email']);
				break;
			case 'event_date':
				echo esc_html($meta['event_date']);
				break;
			case 'total':
				echo esc_html($this->quote_builder->format_currency($meta['final_total']));
				break;
		}
	}

	public function row_actions($actions, $post) {
		if ($post->post_type !== 'teqb_quote') {
			return $actions;
		}

		$resend_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'teqb_resend_quote',
					'quote_id' => $post->ID,
				),
				admin_url('admin-post.php')
			),
			'teqb_resend_quote_' . $post->ID
		);

		$actions['teqb-resend'] = '<a href="' . esc_url($resend_url) . '">' . esc_html__('Resend Emails', 'teqb') . '</a>';
		return $actions;
	}

	public function handle_resend_request() {
		$quote_id = isset($_REQUEST['quote_id']) ? absint($_REQUEST['quote_id']) : 0;
		if (!$quote_id) {
			wp_die(__('Missing quote entry.', 'teqb'));
		}
		if (!current_user_can('edit_post', $quote_id)) {
			wp_die(__('You do not have permission to resend this entry.', 'teqb'));
		}

		check_admin_referer('teqb_resend_quote_' . $quote_id);

		$target = isset($_POST['teqb_resend_target']) ? sanitize_text_field($_POST['teqb_resend_target']) : 'customer';
		$custom_email = isset($_POST['teqb_resend_custom']) ? sanitize_email($_POST['teqb_resend_custom']) : '';
		$send_admin_copy = !empty($_POST['teqb_resend_admin_copy']);
		$send_customer_copy = !empty($_POST['teqb_resend_customer_copy']);

		$result = $this->quote_builder->resend_quote_entry($quote_id, array(
			'target'             => $target,
			'custom_email'       => $custom_email,
			'send_admin_copy'    => $send_admin_copy,
			'send_customer_copy' => $send_customer_copy,
		));

		$redirect = remove_query_arg(array('teqb_resend'), wp_get_referer() ? wp_get_referer() : admin_url('edit.php?post_type=teqb_quote'));
		$redirect = add_query_arg(
			array(
				'teqb_resend' => $result ? 'success' : 'error',
			),
			$redirect
		);

		wp_safe_redirect($redirect);
		exit;
	}

	public function render_admin_notices() {
		if (empty($_GET['teqb_resend']) || empty($_GET['post_type']) || $_GET['post_type'] !== 'teqb_quote') {
			return;
		}

		if ($_GET['teqb_resend'] === 'success') {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Quote emails resent successfully.', 'teqb') . '</p></div>';
		} elseif ($_GET['teqb_resend'] === 'error') {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Quote emails could not be resent. Please review the entry details.', 'teqb') . '</p></div>';
		}
	}

	protected function get_quote_meta($post_id) {
		return array(
			'name'                 => get_post_meta($post_id, '_teqb_quote_name', true),
			'email'                => get_post_meta($post_id, '_teqb_quote_email', true),
			'phone'                => get_post_meta($post_id, '_teqb_quote_phone', true),
			'event_date'           => get_post_meta($post_id, '_teqb_quote_event_date', true),
			'event_type'           => get_post_meta($post_id, '_teqb_quote_event_type', true),
			'guests'               => get_post_meta($post_id, '_teqb_quote_guests', true),
			'referral_source'      => get_post_meta($post_id, '_teqb_quote_referral_source', true),
			'event_venue_location' => get_post_meta($post_id, '_teqb_quote_event_venue_location', true),
			'message'              => get_post_meta($post_id, '_teqb_quote_message', true),
			'services'             => is_array(get_post_meta($post_id, '_teqb_quote_services', true)) ? get_post_meta($post_id, '_teqb_quote_services', true) : array(),
			'subtotal'             => floatval(get_post_meta($post_id, '_teqb_quote_subtotal', true)),
			'discount'             => floatval(get_post_meta($post_id, '_teqb_quote_discount', true)),
			'discount_label'       => get_post_meta($post_id, '_teqb_quote_discount_label', true),
			'final_total'          => floatval(get_post_meta($post_id, '_teqb_quote_final_total', true)),
			'admin_email_sent'     => get_post_meta($post_id, '_teqb_admin_email_sent', true),
			'customer_email_sent'   => get_post_meta($post_id, '_teqb_customer_email_sent', true),
		);
	}

	/**
	 * Handle builder export request
	 */
	public function handle_export_builder() {
		$builder_id = isset($_GET['builder_id']) ? absint($_GET['builder_id']) : 0;
		if (!$builder_id) {
			wp_die(__('Invalid builder ID.', 'teqb'));
		}

		if (!current_user_can('edit_teqb_builder', $builder_id)) {
			wp_die(__('You do not have permission to export this builder.', 'teqb'));
		}

		check_admin_referer('teqb_export_builder_' . $builder_id);

		$post = get_post($builder_id);
		if (!$post || $post->post_type !== 'teqb_builder') {
			wp_die(__('Builder not found.', 'teqb'));
		}

		$config = $this->get_builder_config($builder_id);
		$json = wp_json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($json === false) {
			wp_die(__('Failed to encode builder configuration.', 'teqb'));
		}

		$filename = sanitize_file_name($post->post_name . '-quote-builder-' . date('Y-m-d') . '.json');

		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Length: ' . strlen($json));
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');

		echo $json;
		exit;
	}

	/**
	 * Handle builder import request
	 */
	public function handle_import_builder() {
		if (!current_user_can('edit_teqb_builders')) {
			wp_die(__('You do not have permission to import builders.', 'teqb'));
		}

		// Verify nonce - check both possible field names
		$nonce = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : (isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '');
		if (empty($nonce) || !wp_verify_nonce($nonce, 'teqb_import_builder')) {
			// For debugging - remove in production
			if (defined('WP_DEBUG') && WP_DEBUG) {
				$debug_info = array(
					'nonce_present' => !empty($nonce),
					'nonce_value' => substr($nonce, 0, 10) . '...',
					'post_keys' => array_keys($_POST),
					'request_keys' => array_keys($_REQUEST),
				);
				error_log('TEQB Import Debug: ' . print_r($debug_info, true));
			}
			wp_die(__('Security check failed. Please refresh the page and try again.', 'teqb'));
		}

		if (empty($_FILES['teqb_import_file']) || $_FILES['teqb_import_file']['error'] !== UPLOAD_ERR_OK) {
			$error_message = __('File upload failed.', 'teqb');
			if (!empty($_FILES['teqb_import_file']['error'])) {
				switch ($_FILES['teqb_import_file']['error']) {
					case UPLOAD_ERR_INI_SIZE:
					case UPLOAD_ERR_FORM_SIZE:
						$error_message .= ' ' . __('File is too large.', 'teqb');
						break;
					case UPLOAD_ERR_PARTIAL:
						$error_message .= ' ' . __('File was only partially uploaded.', 'teqb');
						break;
					case UPLOAD_ERR_NO_FILE:
						$error_message .= ' ' . __('No file was uploaded.', 'teqb');
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$error_message .= ' ' . __('Missing temporary folder.', 'teqb');
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$error_message .= ' ' . __('Failed to write file to disk.', 'teqb');
						break;
					case UPLOAD_ERR_EXTENSION:
						$error_message .= ' ' . __('File upload stopped by extension.', 'teqb');
						break;
				}
			}
			wp_die($error_message);
		}

		$file = $_FILES['teqb_import_file'];
		
		// Check file size (max 10MB should be plenty for JSON config)
		$max_size = 10 * 1024 * 1024; // 10MB
		if ($file['size'] > $max_size) {
			wp_die(__('File is too large. Maximum size is 10MB.', 'teqb'));
		}
		
		// Verify it's actually a JSON file
		$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		if ($file_ext !== 'json') {
			wp_die(__('Invalid file type. Please upload a JSON file.', 'teqb'));
		}
		
		$file_content = file_get_contents($file['tmp_name']);

		if ($file_content === false) {
			wp_die(__('Failed to read uploaded file.', 'teqb'));
		}

		$config = json_decode($file_content, true);

		if (!is_array($config)) {
			wp_die(__('Invalid JSON file. Please ensure the file is a valid quote builder configuration.', 'teqb'));
		}

		$config = $this->normalize_import_config($config);

		// Sanitize the config
		$sanitized = $this->sanitize_builder_config($config);

		// Record summary for user feedback
		$this->store_import_summary($sanitized);

		// Prepare JSON for storage
		$encoded_config = $this->encode_builder_config($sanitized);

		// Determine if we're updating existing or creating new
		$import_action = isset($_POST['teqb_import_action']) ? sanitize_text_field($_POST['teqb_import_action']) : 'create';
		$update_existing = ($import_action === 'update' && !empty($_POST['teqb_import_update_id']));
		$post_id = $update_existing ? absint($_POST['teqb_import_update_id']) : 0;

		if ($update_existing && $post_id) {
			// Update existing builder
			if (!current_user_can('edit_teqb_builder', $post_id)) {
				wp_die(__('You do not have permission to edit this builder.', 'teqb'));
			}

			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'teqb_builder') {
				wp_die(__('Builder not found.', 'teqb'));
			}

			update_post_meta($post_id, '_teqb_builder_config', $encoded_config);

			$redirect = add_query_arg(
				array(
					'post' => $post_id,
					'action' => 'edit',
					'teqb_import' => 'success',
				),
				admin_url('post.php')
			);
		} else {
			// Create new builder
			$title = __('Imported Quote Builder', 'teqb') . ' - ' . current_time('mysql');

			$post_id = wp_insert_post(array(
				'post_type' => 'teqb_builder',
				'post_title' => $title,
				'post_status' => 'publish',
			));

			if (is_wp_error($post_id)) {
				wp_die(__('Failed to create builder post.', 'teqb') . ' ' . $post_id->get_error_message());
			}

			update_post_meta($post_id, '_teqb_builder_config', $encoded_config);

			$redirect = add_query_arg(
				array(
					'post' => $post_id,
					'action' => 'edit',
					'teqb_import' => 'success',
				),
				admin_url('post.php')
			);
		}

		wp_safe_redirect($redirect);
		exit;
	}

	/**
	 * Add export action to builder row actions
	 */
	public function builder_row_actions($actions, $post) {
		if ($post->post_type !== 'teqb_builder') {
			return $actions;
		}

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'teqb_export_builder',
					'builder_id' => $post->ID,
				),
				admin_url('admin-post.php')
			),
			'teqb_export_builder_' . $post->ID
		);

		$actions['teqb-export'] = '<a href="' . esc_url($export_url) . '">' . esc_html__('Export', 'teqb') . '</a>';

		return $actions;
	}

	/**
	 * Render import notice
	 */
	public function render_import_notices() {
		if (empty($_GET['teqb_import']) || $_GET['teqb_import'] !== 'success') {
			return;
		}

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen || $screen->post_type !== 'teqb_builder') {
			return;
		}

		$summary_key = 'teqb_import_summary_' . get_current_user_id();
		$summary = get_transient($summary_key);
		if ($summary !== false) {
			delete_transient($summary_key);
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Builder configuration imported successfully!', 'teqb') . '</p>';

		if (is_array($summary)) {
			echo '<ul style="margin: 8px 0 0 20px; list-style: disc;">';
			echo '<li>' . sprintf(
				esc_html__('%d services imported', 'teqb'),
				intval($summary['services'])
			) . '</li>';
			echo '<li>' . sprintf(
				esc_html__('%d total packages and %d add-ons', 'teqb'),
				intval($summary['packages']),
				intval($summary['addons'])
			) . '</li>';
			echo '<li>' . sprintf(
				esc_html__('%d bundle rules', 'teqb'),
				intval($summary['bundle_rules'])
			) . '</li>';

			if (!empty($summary['rewards']) && is_array($summary['rewards'])) {
				foreach ($summary['rewards'] as $key => $count) {
					$label = ucwords(str_replace(array('_', '-'), ' ', $key));
					echo '<li>' . sprintf(
						esc_html__('%s reward options: %d', 'teqb'),
						esc_html($label),
						intval($count)
					) . '</li>';
				}
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * Add import UI to builder list page
	 */
	public function add_import_ui_to_builder_list($which) {
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'teqb_builder' || $which !== 'top') {
			return;
		}

		if (!current_user_can('edit_teqb_builders')) {
			return;
		}

		// Get all builders for update dropdown
		$builders = get_posts(array(
			'post_type' => 'teqb_builder',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'orderby' => 'title',
			'order' => 'ASC',
		));

		$export_hardcoded_url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'teqb_export_hardcoded',
				),
				admin_url('admin-post.php')
			),
			'teqb_export_hardcoded'
		);

		?>
		<div class="alignleft actions" style="margin-right: 10px;">
			<a href="<?php echo esc_url($export_hardcoded_url); ?>" class="button">
				<span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: 3px;"></span>
				<?php esc_html_e('Export Hardcoded Data', 'teqb'); ?>
			</a>
			<button type="button" class="button" id="teqb-toggle-import">
				<span class="dashicons dashicons-upload" style="vertical-align: middle; margin-top: 3px;"></span>
				<?php esc_html_e('Import Configuration', 'teqb'); ?>
			</button>
		</div>
		<?php $import_form_id = 'teqb-import-hidden-form'; ?>
		<div id="teqb-import-form" style="display: none; margin: 10px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
			<h3 style="margin-top: 0;"><?php esc_html_e('Import Quote Builder Configuration', 'teqb'); ?></h3>
			<p class="description" style="margin-bottom: 15px; color: #d63638;">
				<?php esc_html_e('Note: If you see a "link expired" error, please refresh this page first, then try importing again.', 'teqb'); ?>
			</p>
			<script type="text/javascript">
			(function() {
				var toggleBtn = document.getElementById('teqb-toggle-import');
				var importForm = document.getElementById('teqb-import-form');
				if (toggleBtn && importForm) {
					toggleBtn.addEventListener('click', function() {
						importForm.style.display = importForm.style.display === 'none' ? 'block' : 'none';
					});
				}
			})();
			</script>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="teqb_import_file"><?php esc_html_e('JSON File', 'teqb'); ?></label>
					</th>
					<td>
						<input type="file" name="teqb_import_file" id="teqb_import_file" accept=".json" required form="<?php echo esc_attr($import_form_id); ?>">
						<p class="description">
							<?php esc_html_e('Select a JSON file exported from another quote builder.', 'teqb'); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="teqb_import_action"><?php esc_html_e('Import Action', 'teqb'); ?></label>
					</th>
					<td>
						<select name="teqb_import_action" id="teqb_import_action" form="<?php echo esc_attr($import_form_id); ?>" onchange="document.getElementById('teqb_import_update_row').style.display = this.value === 'update' ? 'table-row' : 'none';">
							<option value="create"><?php esc_html_e('Create New Builder', 'teqb'); ?></option>
							<option value="update"><?php esc_html_e('Update Existing Builder', 'teqb'); ?></option>
						</select>
					</td>
				</tr>
				<tr id="teqb_import_update_row" style="display: none;">
					<th scope="row">
						<label for="teqb_import_update_id_select"><?php esc_html_e('Update Builder', 'teqb'); ?></label>
					</th>
					<td>
						<select name="teqb_import_update_id" id="teqb_import_update_id_select" form="<?php echo esc_attr($import_form_id); ?>">
							<?php foreach ($builders as $builder) : ?>
								<option value="<?php echo esc_attr($builder->ID); ?>">
									<?php echo esc_html($builder->post_title); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e('Warning: This will replace the existing builder configuration.', 'teqb'); ?>
						</p>
					</td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary" form="<?php echo esc_attr($import_form_id); ?>">
					<?php esc_html_e('Import Configuration', 'teqb'); ?>
				</button>
				<button type="button" class="button" onclick="document.getElementById('teqb-import-form').style.display = 'none';">
					<?php esc_html_e('Cancel', 'teqb'); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Render hidden form element used by the import UI to avoid nested forms.
	 */
	public function render_import_hidden_form() {
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'teqb_builder') {
			return;
		}

		if (!current_user_can('edit_teqb_builders')) {
			return;
		}

		?>
		<form id="teqb-import-hidden-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
			<input type="hidden" name="action" value="teqb_import_builder">
			<?php wp_nonce_field('teqb_import_builder', '_wpnonce', true, true); ?>
		</form>
		<?php
	}

	/**
	 * Handle export of hardcoded data
	 */
	public function handle_export_hardcoded() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to export hardcoded data.', 'teqb'));
		}

		check_admin_referer('teqb_export_hardcoded');

		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/hardcoded-exporter.php';
		$exporter = new teqb_Hardcoded_Data_Exporter();
		$exporter->export();
	}
}
