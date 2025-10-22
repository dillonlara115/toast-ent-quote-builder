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
		add_filter('manage_teqb_quote_posts_columns', array($this, 'register_columns'));
		add_action('manage_teqb_quote_posts_custom_column', array($this, 'render_columns'), 10, 2);
		add_filter('post_row_actions', array($this, 'row_actions'), 10, 2);
		add_action('admin_post_teqb_resend_quote', array($this, 'handle_resend_request'));
		add_action('admin_notices', array($this, 'render_admin_notices'));
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
			'message'              => get_post_meta($post_id, '_teqb_quote_message', true),
			'services'             => is_array(get_post_meta($post_id, '_teqb_quote_services', true)) ? get_post_meta($post_id, '_teqb_quote_services', true) : array(),
			'subtotal'             => floatval(get_post_meta($post_id, '_teqb_quote_subtotal', true)),
			'discount'             => floatval(get_post_meta($post_id, '_teqb_quote_discount', true)),
			'discount_label'       => get_post_meta($post_id, '_teqb_quote_discount_label', true),
			'final_total'          => floatval(get_post_meta($post_id, '_teqb_quote_final_total', true)),
			'admin_email_sent'     => get_post_meta($post_id, '_teqb_admin_email_sent', true),
			'customer_email_sent'  => get_post_meta($post_id, '_teqb_customer_email_sent', true),
		);
	}
}
