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
		
		// Database seeder
		add_action('admin_post_teqb_seed_database', array($this, 'handle_seed_database'));
		add_action('admin_post_teqb_clear_seeded_data', array($this, 'handle_clear_seeded_data'));
		
		// Service, Package, Add-on metaboxes
		add_action('add_meta_boxes_teqb_service', array($this, 'add_service_metaboxes'));
		add_action('add_meta_boxes_teqb_package', array($this, 'add_package_metaboxes'));
		add_action('add_meta_boxes_teqb_addon', array($this, 'add_addon_metaboxes'));
		
		// Save handlers
		add_action('save_post_teqb_service', array($this, 'save_service_meta'), 10, 2);
		add_action('save_post_teqb_package', array($this, 'save_package_meta'), 10, 2);
		add_action('save_post_teqb_addon', array($this, 'save_addon_meta'), 10, 2);
		
		// Admin columns
		add_filter('manage_teqb_service_posts_columns', array($this, 'service_columns'));
		add_action('manage_teqb_service_posts_custom_column', array($this, 'render_service_columns'), 10, 2);
		add_filter('manage_edit-teqb_service_sortable_columns', array($this, 'service_sortable_columns'));
		add_filter('manage_teqb_package_posts_columns', array($this, 'package_columns'));
		add_action('manage_teqb_package_posts_custom_column', array($this, 'render_package_columns'), 10, 2);
		add_filter('manage_edit-teqb_package_sortable_columns', array($this, 'package_sortable_columns'));
		add_filter('manage_teqb_addon_posts_columns', array($this, 'addon_columns'));
		add_action('manage_teqb_addon_posts_custom_column', array($this, 'render_addon_columns'), 10, 2);
		add_filter('manage_edit-teqb_addon_sortable_columns', array($this, 'addon_sortable_columns'));
		add_action('restrict_manage_posts', array($this, 'add_addon_service_filter'));
		add_action('parse_query', array($this, 'filter_addons_by_service'));
		add_filter('posts_clauses', array($this, 'sort_addons_by_service_name'), 10, 2);
		
		// Bulk actions
		add_filter('bulk_actions-edit-teqb_service', array($this, 'service_bulk_actions'));
		add_filter('bulk_actions-edit-teqb_package', array($this, 'package_bulk_actions'));
		add_filter('bulk_actions-edit-teqb_addon', array($this, 'addon_bulk_actions'));
		add_action('handle_bulk_actions-edit-teqb_service', array($this, 'handle_service_bulk_action'), 10, 3);
		add_action('handle_bulk_actions-edit-teqb_package', array($this, 'handle_package_bulk_action'), 10, 3);
		add_action('handle_bulk_actions-edit-teqb_addon', array($this, 'handle_addon_bulk_action'), 10, 3);
		
		// Bulk action UI
		add_action('admin_footer-edit.php', array($this, 'add_bulk_action_ui'));
		add_action('admin_notices', array($this, 'render_bulk_action_notices'));
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
		
		add_submenu_page(
			'teqb-settings',
			__('Services', 'teqb'),
			__('Services', 'teqb'),
			'edit_posts',
			'edit.php?post_type=teqb_service'
		);
		
		add_submenu_page(
			'teqb-settings',
			__('Packages', 'teqb'),
			__('Packages', 'teqb'),
			'edit_posts',
			'edit.php?post_type=teqb_package'
		);
		
		add_submenu_page(
			'teqb-settings',
			__('Add-ons', 'teqb'),
			__('Add-ons', 'teqb'),
			'edit_posts',
			'edit.php?post_type=teqb_addon'
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
		
		// Check for seeder messages
		$seeder_message = get_transient('teqb_seeder_message');
		$seeder_message_type = get_transient('teqb_seeder_message_type');
		if ($seeder_message) {
			delete_transient('teqb_seeder_message');
			delete_transient('teqb_seeder_message_type');
		}
		
		// Get location terms for seeder
		$locations = get_terms(array(
			'taxonomy' => 'teqb_location',
			'hide_empty' => false,
		));
		
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Quote Builder Settings', 'teqb'); ?></h1>
			
			<?php if ($seeder_message) : ?>
				<div class="notice notice-<?php echo esc_attr($seeder_message_type); ?> is-dismissible">
					<p><?php echo esc_html($seeder_message); ?></p>
				</div>
			<?php endif; ?>
			
			<h2><?php esc_html_e('Notification Settings', 'teqb'); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields('teqb_settings_group');
				do_settings_sections('teqb-settings');
				submit_button();
				?>
			</form>
			
			<hr>
			
			<h2><?php esc_html_e('Database Seeding', 'teqb'); ?></h2>
			<p><?php esc_html_e('Import default services, packages, and add-ons from the JSON configuration file.', 'teqb'); ?></p>
			
			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
				<h3><?php esc_html_e('Seed Database', 'teqb'); ?></h3>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<?php wp_nonce_field('teqb_seed_database', '_wpnonce'); ?>
					<input type="hidden" name="action" value="teqb_seed_database">
					
					<?php if (!empty($locations) && !is_wp_error($locations)) : ?>
						<p>
							<label>
								<input type="checkbox" name="assign_all_locations" value="1" checked>
								<?php esc_html_e('Assign to all locations', 'teqb'); ?>
							</label>
						</p>
						<p>
							<label><?php esc_html_e('Or select specific locations:', 'teqb'); ?></label><br>
							<?php foreach ($locations as $location) : ?>
								<label style="display: inline-block; margin-right: 15px;">
									<input type="checkbox" name="locations[]" value="<?php echo esc_attr($location->term_id); ?>">
									<?php echo esc_html($location->name); ?>
								</label>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
					
					<p>
						<label>
							<input type="checkbox" name="update_existing" value="1" checked>
							<?php esc_html_e('Update existing items if they already exist', 'teqb'); ?>
						</label>
					</p>
					
					<p class="submit">
						<?php submit_button(__('Seed Database', 'teqb'), 'primary', 'submit', false); ?>
					</p>
				</form>
			</div>
			
			<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-left-color: #dc3232;">
				<h3><?php esc_html_e('Clear Seeded Data', 'teqb'); ?></h3>
				<p><?php esc_html_e('Warning: This will permanently delete all services, packages, and add-ons. This action cannot be undone.', 'teqb'); ?></p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete all seeded data? This cannot be undone.', 'teqb'); ?>');">
					<?php wp_nonce_field('teqb_clear_seeded_data', '_wpnonce'); ?>
					<input type="hidden" name="action" value="teqb_clear_seeded_data">
					<p class="submit">
						<?php submit_button(__('Clear All Seeded Data', 'teqb'), 'delete', 'submit', false); ?>
					</p>
				</form>
			</div>
			
			<hr>
			
			<h2><?php esc_html_e('Configuration Diagnostics', 'teqb'); ?></h2>
			<?php $this->render_diagnostics_section(); ?>
		</div>
		<?php
	}
	
	/**
	 * Render diagnostics section showing builder configurations
	 */
	private function render_diagnostics_section() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/cpt-loader.php';
		
		// Get all quote builders
		$builders = get_posts(array(
			'post_type' => 'teqb_builder',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'orderby' => 'title',
			'order' => 'ASC',
		));
		
		// Check videography service in database
		$all_services = teqb_CPT_Loader::get_all_services(null);
		$videography_service_id = null;
		$videography_addons = [];
		
		foreach ($all_services as $service) {
			$service_id_meta = get_post_meta($service['post_id'], '_teqb_service_id', true);
			if ($service_id_meta === 'videography') {
				$videography_service_id = $service['post_id'];
				$videography_addons = teqb_CPT_Loader::get_addons_for_service($service['post_id']);
				break;
			}
		}
		
		$cpt_builders = [];
		$hardcoded_builders = [];
		$videography_builders = [];
		
		?>
		<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
			<h3><?php esc_html_e('Videography Service Status', 'teqb'); ?></h3>
			<?php if ($videography_service_id) : ?>
				<p style="color: #46b450;">
					<strong>✓</strong> <?php esc_html_e('Videography service found in database', 'teqb'); ?> 
					(<?php esc_html_e('Post ID', 'teqb'); ?>: <?php echo esc_html($videography_service_id); ?>)
				</p>
				<p>
					<?php esc_html_e('Videography add-ons in database', 'teqb'); ?>: <strong><?php echo esc_html(count($videography_addons)); ?></strong>
				</p>
				<?php if (!empty($videography_addons)) : ?>
					<ul style="margin-left: 20px;">
						<?php foreach ($videography_addons as $addon) : ?>
							<li><?php echo esc_html($addon['name']); ?> (ID: <?php echo esc_html($addon['id']); ?>)</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p style="color: #dc3232;">
						<strong>⚠</strong> <?php esc_html_e('No videography add-ons found in database. Video package add-ons are currently hardcoded in JavaScript.', 'teqb'); ?>
					</p>
				<?php endif; ?>
			<?php else : ?>
				<p style="color: #dc3232;">
					<strong>✗</strong> <?php esc_html_e('Videography service NOT found in database. All videography data is currently hardcoded.', 'teqb'); ?>
				</p>
			<?php endif; ?>
		</div>
		
		<div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0;">
			<h3><?php esc_html_e('Quote Builder Configurations', 'teqb'); ?></h3>
			<?php if (empty($builders)) : ?>
				<p><?php esc_html_e('No quote builders found.', 'teqb'); ?></p>
			<?php else : ?>
				<p><?php echo esc_html(sprintf(__('Found %d quote builder(s):', 'teqb'), count($builders))); ?></p>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e('Builder Name', 'teqb'); ?></th>
							<th><?php esc_html_e('Type', 'teqb'); ?></th>
							<th><?php esc_html_e('Videography', 'teqb'); ?></th>
							<th><?php esc_html_e('Status', 'teqb'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($builders as $builder) : 
							$config_raw = get_post_meta($builder->ID, '_teqb_builder_config', true);
							$config = !empty($config_raw) ? json_decode($config_raw, true) : null;
							$is_cpt = false;
							$is_hardcoded = false;
							$has_videography = false;
							$status = 'unknown';
							
							if (is_array($config)) {
								if (!empty($config['selectedServices']) && is_array($config['selectedServices'])) {
									$is_cpt = true;
									$cpt_builders[] = $builder->ID;
									$status = 'cpt';
									if ($videography_service_id && in_array($videography_service_id, $config['selectedServices'])) {
										$has_videography = true;
										$videography_builders[] = $builder->ID;
									}
								} elseif (!empty($config['services']) && is_array($config['services'])) {
									$is_hardcoded = true;
									$hardcoded_builders[] = $builder->ID;
									$status = 'hardcoded';
									foreach ($config['services'] as $service) {
										if (isset($service['id']) && $service['id'] === 'videography') {
											$has_videography = true;
											$videography_builders[] = $builder->ID;
											break;
										}
									}
								} else {
									$status = 'empty';
								}
							} else {
								$status = 'no-config';
							}
							?>
							<tr>
								<td>
									<strong><?php echo esc_html($builder->post_title); ?></strong><br>
									<small style="color: #666;">ID: <?php echo esc_html($builder->ID); ?></small>
								</td>
								<td>
									<?php if ($is_cpt) : ?>
										<span style="color: #46b450;">✓ CPT-based</span>
									<?php elseif ($is_hardcoded) : ?>
										<span style="color: #dc3232;">✗ Hardcoded</span>
									<?php else : ?>
										<span style="color: #ffb900;">⚠ <?php esc_html_e('Unknown', 'teqb'); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ($has_videography) : ?>
										<span style="color: #46b450;">✓ <?php esc_html_e('Included', 'teqb'); ?></span>
										<?php if ($is_hardcoded) : ?>
											<br><small style="color: #666;"><?php esc_html_e('(using hardcoded add-ons)', 'teqb'); ?></small>
										<?php elseif ($is_cpt) : ?>
											<br><small style="color: #666;"><?php esc_html_e('(using database add-ons)', 'teqb'); ?></small>
										<?php endif; ?>
									<?php else : ?>
										<span style="color: #666;">✗ <?php esc_html_e('Not included', 'teqb'); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ($status === 'cpt') : ?>
										<?php esc_html_e('Using database', 'teqb'); ?>
									<?php elseif ($status === 'hardcoded') : ?>
										<?php esc_html_e('Using hardcoded data', 'teqb'); ?>
									<?php elseif ($status === 'empty') : ?>
										<?php esc_html_e('Empty config', 'teqb'); ?>
									<?php elseif ($status === 'no-config') : ?>
										<?php esc_html_e('No config', 'teqb'); ?>
									<?php else : ?>
										<?php esc_html_e('Unknown', 'teqb'); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
					<h4><?php esc_html_e('Summary', 'teqb'); ?></h4>
					<ul>
						<li><strong><?php esc_html_e('Total builders', 'teqb'); ?>:</strong> <?php echo esc_html(count($builders)); ?></li>
						<li><strong><?php esc_html_e('CPT-based builders', 'teqb'); ?>:</strong> <?php echo esc_html(count($cpt_builders)); ?></li>
						<li><strong><?php esc_html_e('Hardcoded builders', 'teqb'); ?>:</strong> <?php echo esc_html(count($hardcoded_builders)); ?></li>
						<li><strong><?php esc_html_e('Builders with videography', 'teqb'); ?>:</strong> <?php echo esc_html(count($videography_builders)); ?></li>
					</ul>
					
					<?php if ($videography_service_id && empty($videography_addons)) : ?>
						<p style="margin-top: 15px; color: #dc3232;">
							<strong>⚠ <?php esc_html_e('Recommendation', 'teqb'); ?>:</strong> 
							<?php esc_html_e('Videography service exists but has NO add-ons in database. Video package add-ons are currently hardcoded in JavaScript. To use database system, add videography add-ons via WordPress admin.', 'teqb'); ?>
						</p>
					<?php elseif ($videography_service_id && !empty($videography_addons) && !empty($hardcoded_builders)) : ?>
						<p style="margin-top: 15px; color: #2271b1;">
							<strong>ℹ <?php esc_html_e('Note', 'teqb'); ?>:</strong> 
							<?php esc_html_e('Videography service and add-ons exist in database. Consider migrating hardcoded builders to use CPT system.', 'teqb'); ?>
						</p>
					<?php elseif (!$videography_service_id) : ?>
						<p style="margin-top: 15px; color: #dc3232;">
							<strong>⚠ <?php esc_html_e('Recommendation', 'teqb'); ?>:</strong> 
							<?php esc_html_e('Videography service not found in database. All videography data is currently hardcoded.', 'teqb'); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
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
		
		// Load CPT data
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/cpt-loader.php';
		$all_services = teqb_CPT_Loader::get_all_services();
		$locations = teqb_CPT_Loader::get_locations();

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
			'cptData'  => array(
				'allServices' => $all_services,
				'locations' => $locations,
			),
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
			'selectedServices' => array(), // Array of service post IDs
			'selectedPackages' => array(), // Array of package post IDs with price overrides: { post_id: 123, price_override: 1500 }
			'selectedAddons' => array(),   // Array of addon post IDs with price overrides: { post_id: 456, price_override: 200 }
			'location' => '',              // Location slug filter
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
	
	/**
	 * Handle database seeding
	 */
	public function handle_seed_database() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to seed the database.', 'teqb'));
		}
		
		check_admin_referer('teqb_seed_database');
		
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/database-seeder.php';
		
		// Determine locations
		$locations = null;
		if (isset($_POST['assign_all_locations']) && $_POST['assign_all_locations']) {
			// Use all locations (null = all)
			$locations = null;
		} elseif (isset($_POST['locations']) && is_array($_POST['locations'])) {
			$locations = array_map('absint', $_POST['locations']);
		}
		
		$results = teqb_Database_Seeder::seed(null, $locations);
		
		if ($results['success']) {
			$message = sprintf(
				__('Database seeded successfully! Created: %d services, %d packages, %d add-ons. Updated: %d services, %d packages, %d add-ons.', 'teqb'),
				$results['services_created'],
				$results['packages_created'],
				$results['addons_created'],
				$results['services_updated'],
				$results['packages_updated'],
				$results['addons_updated']
			);
			
			if (!empty($results['errors'])) {
				$message .= ' ' . __('Some errors occurred:', 'teqb') . ' ' . implode(', ', $results['errors']);
			}
			
			set_transient('teqb_seeder_message', $message, 30);
			set_transient('teqb_seeder_message_type', 'success', 30);
		} else {
			set_transient('teqb_seeder_message', __('Error seeding database: ', 'teqb') . ($results['error'] ?? 'Unknown error'), 30);
			set_transient('teqb_seeder_message_type', 'error', 30);
		}
		
		wp_safe_redirect(admin_url('admin.php?page=teqb-settings'));
		exit;
	}
	
	/**
	 * Handle clearing seeded data
	 */
	public function handle_clear_seeded_data() {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to clear seeded data.', 'teqb'));
		}
		
		check_admin_referer('teqb_clear_seeded_data');
		
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/database-seeder.php';
		
		$results = teqb_Database_Seeder::clear_all();
		
		if ($results['success']) {
			$message = sprintf(__('Cleared %d items from the database.', 'teqb'), $results['deleted']);
			set_transient('teqb_seeder_message', $message, 30);
			set_transient('teqb_seeder_message_type', 'success', 30);
		} else {
			set_transient('teqb_seeder_message', __('Error clearing data.', 'teqb'), 30);
			set_transient('teqb_seeder_message_type', 'error', 30);
		}
		
		wp_safe_redirect(admin_url('admin.php?page=teqb-settings'));
		exit;
	}
	
	// ============================================
	// SERVICE METHODS
	// ============================================
	
	/**
	 * Add metaboxes for Service CPT
	 */
	public function add_service_metaboxes($post) {
		add_meta_box(
			'teqb-service-details',
			__('Service Details', 'teqb'),
			array($this, 'render_service_metabox'),
			'teqb_service',
			'normal',
			'high'
		);
	}
	
	/**
	 * Render Service metabox
	 */
	public function render_service_metabox($post) {
		wp_nonce_field('teqb_service_meta', 'teqb_service_meta_nonce');
		
		$service_id = get_post_meta($post->ID, '_teqb_service_id', true);
		$subtitle = get_post_meta($post->ID, '_teqb_subtitle', true);
		$starting_price = get_post_meta($post->ID, '_teqb_starting_price', true);
		$features_title = get_post_meta($post->ID, '_teqb_features_title', true);
		$features = get_post_meta($post->ID, '_teqb_features', true);
		$paragraphs = get_post_meta($post->ID, '_teqb_paragraphs', true);
		
		if (empty($service_id)) {
			$service_id = 'service-' . $post->ID;
		}
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="teqb_service_id"><?php esc_html_e('Service ID', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_service_id" name="teqb_service_id" value="<?php echo esc_attr($service_id); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e('Unique identifier for this service (e.g., "photo-booth", "dj")', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_subtitle"><?php esc_html_e('Subtitle', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_subtitle" name="teqb_subtitle" value="<?php echo esc_attr($subtitle); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label for="teqb_starting_price"><?php esc_html_e('Starting Price', 'teqb'); ?></label></th>
				<td>
					<input type="number" id="teqb_starting_price" name="teqb_starting_price" value="<?php echo esc_attr($starting_price); ?>" step="0.01" min="0" class="small-text">
					<p class="description"><?php esc_html_e('Lowest package price for this service', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_features_title"><?php esc_html_e('Features Title', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_features_title" name="teqb_features_title" value="<?php echo esc_attr($features_title); ?>" class="regular-text" placeholder="<?php esc_attr_e('What\'s Included', 'teqb'); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="teqb_features"><?php esc_html_e('Features', 'teqb'); ?></label></th>
				<td>
					<textarea id="teqb_features" name="teqb_features" rows="5" class="large-text"><?php echo esc_textarea($features); ?></textarea>
					<p class="description"><?php esc_html_e('One feature per line', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_paragraphs"><?php esc_html_e('Description Paragraphs', 'teqb'); ?></label></th>
				<td>
					<textarea id="teqb_paragraphs" name="teqb_paragraphs" rows="5" class="large-text"><?php echo esc_textarea($paragraphs); ?></textarea>
					<p class="description"><?php esc_html_e('One paragraph per line', 'teqb'); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Save Service meta
	 */
	public function save_service_meta($post_id, $post) {
		if (!isset($_POST['teqb_service_meta_nonce']) || !wp_verify_nonce($_POST['teqb_service_meta_nonce'], 'teqb_service_meta')) {
			return;
		}
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		
		$fields = array(
			'teqb_service_id' => 'sanitize_text_field',
			'teqb_subtitle' => 'sanitize_text_field',
			'teqb_starting_price' => 'floatval',
			'teqb_features_title' => 'sanitize_text_field',
			'teqb_features' => 'sanitize_textarea_field',
			'teqb_paragraphs' => 'sanitize_textarea_field',
		);
		
		foreach ($fields as $field => $sanitize) {
			$value = isset($_POST[$field]) ? $_POST[$field] : '';
			if ($sanitize === 'floatval') {
				$value = floatval($value);
			} else {
				$value = call_user_func($sanitize, $value);
			}
			update_post_meta($post_id, '_' . $field, $value);
		}
	}
	
	/**
	 * Service admin columns
	 */
	public function service_columns($columns) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['menu_order'] = __('Order', 'teqb');
		$new_columns['title'] = __('Service Name', 'teqb');
		$new_columns['service_id'] = __('Service ID', 'teqb');
		$new_columns['starting_price'] = __('Starting Price', 'teqb');
		$new_columns['locations'] = __('Locations', 'teqb');
		$new_columns['date'] = $columns['date'];
		return $new_columns;
	}
	
	/**
	 * Render Service columns
	 */
	public function render_service_columns($column, $post_id) {
		switch ($column) {
			case 'menu_order':
				$order = get_post($post_id)->menu_order;
				echo '<strong>' . esc_html($order) . '</strong>';
				break;
			case 'service_id':
				echo esc_html(get_post_meta($post_id, '_teqb_service_id', true));
				break;
			case 'starting_price':
				$price = get_post_meta($post_id, '_teqb_starting_price', true);
				echo $price ? '$' . number_format($price, 2) : '—';
				break;
			case 'locations':
				$terms = get_the_terms($post_id, 'teqb_location');
				if ($terms && !is_wp_error($terms)) {
					$location_names = array_map(function($term) {
						return $term->name;
					}, $terms);
					echo esc_html(implode(', ', $location_names));
				} else {
					echo '—';
				}
				break;
		}
	}
	
	/**
	 * Service sortable columns
	 */
	public function service_sortable_columns($columns) {
		$columns['menu_order'] = 'menu_order';
		return $columns;
	}
	
	/**
	 * Service bulk actions
	 */
	public function service_bulk_actions($actions) {
		$actions['teqb_assign_location'] = __('Assign Location', 'teqb');
		$actions['teqb_remove_location'] = __('Remove Location', 'teqb');
		$actions['teqb_update_starting_price'] = __('Update Starting Price', 'teqb');
		return $actions;
	}
	
	/**
	 * Handle Service bulk action
	 */
	public function handle_service_bulk_action($redirect_to, $action, $post_ids) {
		check_admin_referer('bulk-posts');
		
		if ($action === 'teqb_assign_location' || $action === 'teqb_remove_location') {
			if (isset($_GET['teqb_location'])) {
				$location_id = intval($_GET['teqb_location']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					if ($action === 'teqb_assign_location') {
						wp_set_post_terms($post_id, array($location_id), 'teqb_location', true);
					} else {
						wp_remove_object_terms($post_id, $location_id, 'teqb_location');
					}
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		} elseif ($action === 'teqb_update_starting_price') {
			if (isset($_GET['teqb_starting_price'])) {
				$price = floatval($_GET['teqb_starting_price']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					update_post_meta($post_id, '_teqb_starting_price', $price);
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		}
		return $redirect_to;
	}
	
	// ============================================
	// PACKAGE METHODS
	// ============================================
	
	/**
	 * Add metaboxes for Package CPT
	 */
	public function add_package_metaboxes($post) {
		add_meta_box(
			'teqb-package-details',
			__('Package Details', 'teqb'),
			array($this, 'render_package_metabox'),
			'teqb_package',
			'normal',
			'high'
		);
	}
	
	/**
	 * Render Package metabox
	 */
	public function render_package_metabox($post) {
		wp_nonce_field('teqb_package_meta', 'teqb_package_meta_nonce');
		
		$package_id = get_post_meta($post->ID, '_teqb_package_id', true);
		$service_id = get_post_meta($post->ID, '_teqb_service_id', true);
		$price = get_post_meta($post->ID, '_teqb_price', true);
		$includes = get_post_meta($post->ID, '_teqb_includes', true);
		$bonus_options = get_post_meta($post->ID, '_teqb_bonus_options', true);
		$bonus_limit = get_post_meta($post->ID, '_teqb_bonus_limit', true);
		$additional_time_message = get_post_meta($post->ID, '_teqb_additional_time_message', true);
		
		if (empty($package_id)) {
			$package_id = 'package-' . $post->ID;
		}
		
		// Get available services
		$services = get_posts(array(
			'post_type' => 'teqb_service',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'orderby' => 'title',
			'order' => 'ASC',
		));
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="teqb_package_id"><?php esc_html_e('Package ID', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_package_id" name="teqb_package_id" value="<?php echo esc_attr($package_id); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e('Unique identifier for this package', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_service_id"><?php esc_html_e('Service', 'teqb'); ?></label></th>
				<td>
					<select id="teqb_service_id" name="teqb_service_id" class="regular-text" required>
						<option value=""><?php esc_html_e('— Select Service —', 'teqb'); ?></option>
						<?php foreach ($services as $service) : 
							$service_meta_id = get_post_meta($service->ID, '_teqb_service_id', true);
							$selected = ($service_id == $service->ID || $service_meta_id == $service_id) ? 'selected' : '';
						?>
							<option value="<?php echo esc_attr($service->ID); ?>" <?php echo $selected; ?>>
								<?php echo esc_html($service->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e('The service this package belongs to', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_price"><?php esc_html_e('Price', 'teqb'); ?></label></th>
				<td>
					<input type="number" id="teqb_price" name="teqb_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="small-text" required>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_includes"><?php esc_html_e('Includes', 'teqb'); ?></label></th>
				<td>
					<textarea id="teqb_includes" name="teqb_includes" rows="5" class="large-text"><?php echo esc_textarea($includes); ?></textarea>
					<p class="description"><?php esc_html_e('One item per line', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_bonus_options"><?php esc_html_e('Bonus Options', 'teqb'); ?></label></th>
				<td>
					<textarea id="teqb_bonus_options" name="teqb_bonus_options" rows="5" class="large-text"><?php echo esc_textarea($bonus_options); ?></textarea>
					<p class="description"><?php esc_html_e('One bonus option per line', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_bonus_limit"><?php esc_html_e('Bonus Limit', 'teqb'); ?></label></th>
				<td>
					<input type="number" id="teqb_bonus_limit" name="teqb_bonus_limit" value="<?php echo esc_attr($bonus_limit); ?>" min="0" class="small-text">
					<p class="description"><?php esc_html_e('Maximum number of bonuses that can be selected', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_additional_time_message"><?php esc_html_e('Additional Time Message', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_additional_time_message" name="teqb_additional_time_message" value="<?php echo esc_attr($additional_time_message); ?>" class="large-text">
					<p class="description"><?php esc_html_e('Optional message displayed when package includes hours (e.g., "Additional time can be added on the next screen."). Leave blank to hide.', 'teqb'); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Save Package meta
	 */
	public function save_package_meta($post_id, $post) {
		if (!isset($_POST['teqb_package_meta_nonce']) || !wp_verify_nonce($_POST['teqb_package_meta_nonce'], 'teqb_package_meta')) {
			return;
		}
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		
		$fields = array(
			'teqb_package_id' => 'sanitize_text_field',
			'teqb_service_id' => 'absint',
			'teqb_price' => 'floatval',
			'teqb_includes' => 'sanitize_textarea_field',
			'teqb_bonus_options' => 'sanitize_textarea_field',
			'teqb_bonus_limit' => 'absint',
			'teqb_additional_time_message' => 'sanitize_text_field',
		);
		
		foreach ($fields as $field => $sanitize) {
			$value = isset($_POST[$field]) ? $_POST[$field] : '';
			if ($sanitize === 'floatval') {
				$value = floatval($value);
			} elseif ($sanitize === 'absint') {
				$value = absint($value);
			} else {
				$value = call_user_func($sanitize, $value);
			}
			update_post_meta($post_id, '_' . $field, $value);
		}
	}
	
	/**
	 * Package admin columns
	 */
	public function package_columns($columns) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['menu_order'] = __('Order', 'teqb');
		$new_columns['title'] = __('Package Name', 'teqb');
		$new_columns['package_id'] = __('Package ID', 'teqb');
		$new_columns['service'] = __('Service', 'teqb');
		$new_columns['price'] = __('Price', 'teqb');
		$new_columns['locations'] = __('Locations', 'teqb');
		$new_columns['date'] = $columns['date'];
		return $new_columns;
	}
	
	/**
	 * Render Package columns
	 */
	public function render_package_columns($column, $post_id) {
		switch ($column) {
			case 'menu_order':
				$order = get_post($post_id)->menu_order;
				echo '<strong>' . esc_html($order) . '</strong>';
				break;
			case 'package_id':
				echo esc_html(get_post_meta($post_id, '_teqb_package_id', true));
				break;
			case 'service':
				$service_id = get_post_meta($post_id, '_teqb_service_id', true);
				if ($service_id) {
					$service = get_post($service_id);
					echo $service ? esc_html($service->post_title) : '—';
				} else {
					echo '—';
				}
				break;
			case 'price':
				$price = get_post_meta($post_id, '_teqb_price', true);
				echo $price ? '$' . number_format($price, 2) : '—';
				break;
			case 'locations':
				$terms = get_the_terms($post_id, 'teqb_location');
				if ($terms && !is_wp_error($terms)) {
					$location_names = array_map(function($term) {
						return $term->name;
					}, $terms);
					echo esc_html(implode(', ', $location_names));
				} else {
					echo '—';
				}
				break;
		}
	}
	
	/**
	 * Package sortable columns
	 */
	public function package_sortable_columns($columns) {
		$columns['menu_order'] = 'menu_order';
		return $columns;
	}
	
	/**
	 * Package bulk actions
	 */
	public function package_bulk_actions($actions) {
		$actions['teqb_assign_location'] = __('Assign Location', 'teqb');
		$actions['teqb_remove_location'] = __('Remove Location', 'teqb');
		$actions['teqb_update_price'] = __('Update Price', 'teqb');
		return $actions;
	}
	
	/**
	 * Handle Package bulk action
	 */
	public function handle_package_bulk_action($redirect_to, $action, $post_ids) {
		check_admin_referer('bulk-posts');
		
		if ($action === 'teqb_assign_location' || $action === 'teqb_remove_location') {
			if (isset($_GET['teqb_location'])) {
				$location_id = intval($_GET['teqb_location']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					if ($action === 'teqb_assign_location') {
						wp_set_post_terms($post_id, array($location_id), 'teqb_location', true);
					} else {
						wp_remove_object_terms($post_id, $location_id, 'teqb_location');
					}
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		} elseif ($action === 'teqb_update_price') {
			if (isset($_GET['teqb_price'])) {
				$price = floatval($_GET['teqb_price']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					update_post_meta($post_id, '_teqb_price', $price);
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		}
		return $redirect_to;
	}
	
	// ============================================
	// ADD-ON METHODS
	// ============================================
	
	/**
	 * Add metaboxes for Add-on CPT
	 */
	public function add_addon_metaboxes($post) {
		add_meta_box(
			'teqb-addon-details',
			__('Add-on Details', 'teqb'),
			array($this, 'render_addon_metabox'),
			'teqb_addon',
			'normal',
			'high'
		);
	}
	
	/**
	 * Render Add-on metabox
	 */
	public function render_addon_metabox($post) {
		wp_nonce_field('teqb_addon_meta', 'teqb_addon_meta_nonce');
		
		$addon_id = get_post_meta($post->ID, '_teqb_addon_id', true);
		$service_id = get_post_meta($post->ID, '_teqb_service_id', true);
		$price = get_post_meta($post->ID, '_teqb_price', true);
		$base = get_post_meta($post->ID, '_teqb_base', true);
		$unit = get_post_meta($post->ID, '_teqb_unit', true);
		$min = get_post_meta($post->ID, '_teqb_min', true);
		$options = get_post_meta($post->ID, '_teqb_options', true);
		$extras = get_post_meta($post->ID, '_teqb_extras', true);
		
		if (empty($addon_id)) {
			$addon_id = 'addon-' . $post->ID;
		}
		
		// Get available services
		$services = get_posts(array(
			'post_type' => 'teqb_service',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'orderby' => 'title',
			'order' => 'ASC',
		));
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="teqb_addon_id"><?php esc_html_e('Add-on ID', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_addon_id" name="teqb_addon_id" value="<?php echo esc_attr($addon_id); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e('Unique identifier for this add-on', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_service_id"><?php esc_html_e('Service', 'teqb'); ?></label></th>
				<td>
					<select id="teqb_service_id" name="teqb_service_id" class="regular-text" required>
						<option value=""><?php esc_html_e('— Select Service —', 'teqb'); ?></option>
						<?php foreach ($services as $service) : 
							$service_meta_id = get_post_meta($service->ID, '_teqb_service_id', true);
							$selected = ($service_id == $service->ID || $service_meta_id == $service_id) ? 'selected' : '';
						?>
							<option value="<?php echo esc_attr($service->ID); ?>" <?php echo $selected; ?>>
								<?php echo esc_html($service->post_title); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e('The service this add-on belongs to', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_price"><?php esc_html_e('Price (Flat)', 'teqb'); ?></label></th>
				<td>
					<input type="number" id="teqb_price" name="teqb_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="small-text">
					<p class="description"><?php esc_html_e('Flat price (leave blank if using base price)', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_base"><?php esc_html_e('Base Price (Per Unit)', 'teqb'); ?></label></th>
				<td>
					<input type="number" id="teqb_base" name="teqb_base" value="<?php echo esc_attr($base); ?>" step="0.01" min="0" class="small-text">
					<p class="description"><?php esc_html_e('Base price per unit (leave blank if using flat price)', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_unit"><?php esc_html_e('Unit', 'teqb'); ?></label></th>
				<td>
					<input type="text" id="teqb_unit" name="teqb_unit" value="<?php echo esc_attr($unit); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., hour, uplight', 'teqb'); ?>">
				</td>
			</tr>
			<tr>
				<th><label for="teqb_min"><?php esc_html_e('Minimum Quantity', 'teqb'); ?></label></th>
				<td>
					<input type="number" id="teqb_min" name="teqb_min" value="<?php echo esc_attr($min); ?>" min="0" class="small-text">
				</td>
			</tr>
			<tr>
				<th><label for="teqb_options"><?php esc_html_e('Options', 'teqb'); ?></label></th>
				<td>
					<textarea id="teqb_options" name="teqb_options" rows="5" class="large-text"><?php echo esc_textarea($options); ?></textarea>
					<p class="description"><?php esc_html_e('One option per line', 'teqb'); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_tiered"><?php esc_html_e('Tiered Pricing (JSON)', 'teqb'); ?></label></th>
				<td>
					<?php 
					$tiered = get_post_meta($post->ID, '_teqb_tiered', true);
					$tiered_display = '';
					if ($tiered) {
						$tiered_decoded = json_decode($tiered, true);
						if (is_array($tiered_decoded)) {
							$tiered_display = wp_json_encode($tiered_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
						} else {
							$tiered_display = $tiered;
						}
					}
					?>
					<textarea id="teqb_tiered" name="teqb_tiered" rows="5" class="large-text"><?php echo esc_textarea($tiered_display); ?></textarea>
					<p class="description">
						<?php esc_html_e('JSON format mapping option names to prices: {"Tier 3": 200, "Tier 2": 300, "Tier 1": 400}', 'teqb'); ?><br>
						<?php esc_html_e('Leave blank if not using tiered pricing. Options must match the option names above.', 'teqb'); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="teqb_extras"><?php esc_html_e('Extras (JSON)', 'teqb'); ?></label></th>
				<td>
					<textarea id="teqb_extras" name="teqb_extras" rows="5" class="large-text"><?php echo esc_textarea($extras); ?></textarea>
					<p class="description"><?php esc_html_e('JSON format: {"Option Name": 100, "Another Option": 200}', 'teqb'); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Save Add-on meta
	 */
	public function save_addon_meta($post_id, $post) {
		if (!isset($_POST['teqb_addon_meta_nonce']) || !wp_verify_nonce($_POST['teqb_addon_meta_nonce'], 'teqb_addon_meta')) {
			return;
		}
		
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		
		$fields = array(
			'teqb_addon_id' => 'sanitize_text_field',
			'teqb_service_id' => 'absint',
			'teqb_price' => 'floatval',
			'teqb_base' => 'floatval',
			'teqb_unit' => 'sanitize_text_field',
			'teqb_min' => 'absint',
			'teqb_options' => 'sanitize_textarea_field',
			'teqb_tiered' => 'sanitize_textarea_field',
			'teqb_extras' => 'sanitize_textarea_field',
		);
		
		foreach ($fields as $field => $sanitize) {
			$value = isset($_POST[$field]) ? $_POST[$field] : '';
			if ($sanitize === 'floatval') {
				$value = floatval($value);
			} elseif ($sanitize === 'absint') {
				$value = absint($value);
			} else {
				$value = call_user_func($sanitize, $value);
			}
			
			// Special handling for JSON fields (tiered and extras)
			if ($field === 'teqb_tiered' || $field === 'teqb_extras') {
				// Validate JSON before saving
				if (!empty($value)) {
					$decoded = json_decode($value, true);
					if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
						// Save as valid JSON
						update_post_meta($post_id, '_' . $field, wp_json_encode($decoded, JSON_UNESCAPED_UNICODE));
					} else {
						// Invalid JSON - save as-is but log error
						update_post_meta($post_id, '_' . $field, $value);
						if (defined('WP_DEBUG') && WP_DEBUG) {
							error_log('TEQB: Invalid JSON for ' . $field . ' on post ' . $post_id . ': ' . json_last_error_msg());
						}
					}
				} else {
					delete_post_meta($post_id, '_' . $field);
				}
			} else {
				update_post_meta($post_id, '_' . $field, $value);
			}
		}
	}
	
	/**
	 * Add-on admin columns
	 */
	public function addon_columns($columns) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['menu_order'] = __('Order', 'teqb');
		$new_columns['title'] = __('Add-on Name', 'teqb');
		$new_columns['addon_id'] = __('Add-on ID', 'teqb');
		$new_columns['service'] = __('Service', 'teqb');
		$new_columns['price'] = __('Price', 'teqb');
		$new_columns['locations'] = __('Locations', 'teqb');
		$new_columns['date'] = $columns['date'];
		return $new_columns;
	}
	
	/**
	 * Render Add-on columns
	 */
	public function render_addon_columns($column, $post_id) {
		switch ($column) {
			case 'menu_order':
				$order = get_post($post_id)->menu_order;
				echo '<strong>' . esc_html($order) . '</strong>';
				break;
			case 'addon_id':
				echo esc_html(get_post_meta($post_id, '_teqb_addon_id', true));
				break;
			case 'service':
				$service_id = get_post_meta($post_id, '_teqb_service_id', true);
				if ($service_id) {
					$service = get_post($service_id);
					echo $service ? esc_html($service->post_title) : '—';
				} else {
					echo '—';
				}
				break;
			case 'price':
				$price = get_post_meta($post_id, '_teqb_price', true);
				$base = get_post_meta($post_id, '_teqb_base', true);
				if ($price) {
					echo '$' . number_format($price, 2) . ' (flat)';
				} elseif ($base) {
					echo '$' . number_format($base, 2) . ' (per unit)';
				} else {
					echo '—';
				}
				break;
			case 'locations':
				$terms = get_the_terms($post_id, 'teqb_location');
				if ($terms && !is_wp_error($terms)) {
					$location_names = array_map(function($term) {
						return $term->name;
					}, $terms);
					echo esc_html(implode(', ', $location_names));
				} else {
					echo '—';
				}
				break;
		}
	}
	
	/**
	 * Add-on sortable columns
	 */
	public function addon_sortable_columns($columns) {
		$columns['menu_order'] = 'menu_order';
		$columns['service'] = 'service'; // Make Service column sortable
		return $columns;
	}
	
	/**
	 * Add-on bulk actions
	 */
	public function addon_bulk_actions($actions) {
		$actions['teqb_assign_location'] = __('Assign Location', 'teqb');
		$actions['teqb_remove_location'] = __('Remove Location', 'teqb');
		$actions['teqb_update_price'] = __('Update Price (Flat)', 'teqb');
		$actions['teqb_update_base_price'] = __('Update Base Price (Per Unit)', 'teqb');
		return $actions;
	}
	
	/**
	 * Handle Add-on bulk action
	 */
	public function handle_addon_bulk_action($redirect_to, $action, $post_ids) {
		check_admin_referer('bulk-posts');
		
		if ($action === 'teqb_assign_location' || $action === 'teqb_remove_location') {
			if (isset($_GET['teqb_location'])) {
				$location_id = intval($_GET['teqb_location']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					if ($action === 'teqb_assign_location') {
						wp_set_post_terms($post_id, array($location_id), 'teqb_location', true);
					} else {
						wp_remove_object_terms($post_id, $location_id, 'teqb_location');
					}
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		} elseif ($action === 'teqb_update_price') {
			if (isset($_GET['teqb_price'])) {
				$price = floatval($_GET['teqb_price']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					update_post_meta($post_id, '_teqb_price', $price);
					// Clear base price if setting flat price
					update_post_meta($post_id, '_teqb_base', '');
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		} elseif ($action === 'teqb_update_base_price') {
			if (isset($_GET['teqb_base_price'])) {
				$base_price = floatval($_GET['teqb_base_price']);
				$updated = 0;
				foreach ($post_ids as $post_id) {
					update_post_meta($post_id, '_teqb_base', $base_price);
					// Clear flat price if setting base price
					update_post_meta($post_id, '_teqb_price', '');
					$updated++;
				}
				$redirect_to = add_query_arg('teqb_bulk_updated', $updated, $redirect_to);
				$redirect_to = add_query_arg('teqb_bulk_action', $action, $redirect_to);
			}
		}
		return $redirect_to;
	}
	
	/**
	 * Add bulk action UI (location selector, price inputs)
	 */
	public function add_bulk_action_ui($hook) {
		$screen = get_current_screen();
		if (!$screen || !in_array($screen->post_type, array('teqb_service', 'teqb_package', 'teqb_addon'))) {
			return;
		}
		
		$locations = get_terms(array(
			'taxonomy' => 'teqb_location',
			'hide_empty' => false,
		));
		
		if (is_wp_error($locations)) {
			$locations = array();
		}
		?>
		<script type="text/javascript">
		(function($) {
			$(document).ready(function() {
				var postType = '<?php echo esc_js($screen->post_type); ?>';
				var $bulkActions = $('select[name="action"], select[name="action2"]');
				var $bulkActionsTop = $('select[name="action"]');
				var $bulkActionsBottom = $('select[name="action2"]');
				
				// Create UI container
				var $bulkUI = $('<div id="teqb-bulk-ui" style="display:none; padding:10px; background:#fff; border:1px solid #ccd0d4; margin:10px 0; border-left:4px solid #2271b1;"></div>');
				$bulkActionsTop.after($bulkUI);
				
				function showBulkUI(action) {
					$bulkUI.empty().show();
					
					if (action === 'teqb_assign_location' || action === 'teqb_remove_location') {
						var actionLabel = action === 'teqb_assign_location' ? 'Assign Location' : 'Remove Location';
						var html = '<strong>' + actionLabel + ':</strong><br>';
						html += '<select name="teqb_location" id="teqb_location_select" style="margin-top:5px; min-width:200px;">';
						html += '<option value=""><?php esc_html_e('— Select Location —', 'teqb'); ?></option>';
						<?php foreach ($locations as $location) : ?>
						html += '<option value="<?php echo esc_js($location->term_id); ?>"><?php echo esc_js($location->name); ?></option>';
						<?php endforeach; ?>
						html += '</select>';
						html += '<p class="description" style="margin-top:5px;"><?php esc_html_e('Select a location and click "Apply" to execute the bulk action.', 'teqb'); ?></p>';
						$bulkUI.html(html);
					} else if (action === 'teqb_update_starting_price' && postType === 'teqb_service') {
						var html = '<strong><?php esc_html_e('Update Starting Price:', 'teqb'); ?></strong><br>';
						html += '<input type="number" name="teqb_starting_price" id="teqb_starting_price" step="0.01" min="0" style="margin-top:5px; width:150px;" placeholder="0.00">';
						html += '<p class="description" style="margin-top:5px;"><?php esc_html_e('Enter the new starting price for all selected services.', 'teqb'); ?></p>';
						$bulkUI.html(html);
					} else if (action === 'teqb_update_price' && (postType === 'teqb_package' || postType === 'teqb_addon')) {
						var html = '<strong><?php esc_html_e('Update Price:', 'teqb'); ?></strong><br>';
						html += '<input type="number" name="teqb_price" id="teqb_price" step="0.01" min="0" style="margin-top:5px; width:150px;" placeholder="0.00">';
						html += '<p class="description" style="margin-top:5px;"><?php esc_html_e('Enter the new flat price for all selected items.', 'teqb'); ?></p>';
						$bulkUI.html(html);
					} else if (action === 'teqb_update_base_price' && postType === 'teqb_addon') {
						var html = '<strong><?php esc_html_e('Update Base Price (Per Unit):', 'teqb'); ?></strong><br>';
						html += '<input type="number" name="teqb_base_price" id="teqb_base_price" step="0.01" min="0" style="margin-top:5px; width:150px;" placeholder="0.00">';
						html += '<p class="description" style="margin-top:5px;"><?php esc_html_e('Enter the new base price per unit for all selected add-ons.', 'teqb'); ?></p>';
						$bulkUI.html(html);
					}
				}
				
				function hideBulkUI() {
					$bulkUI.hide().empty();
				}
				
				// Watch for bulk action changes
				$bulkActions.on('change', function() {
					var action = $(this).val();
					if (action && action.indexOf('teqb_') === 0) {
						showBulkUI(action);
					} else {
						hideBulkUI();
					}
				});
				
				// Intercept form submission to add parameters
				$('form#posts-filter').on('submit', function(e) {
					var action = $bulkActionsTop.val() || $bulkActionsBottom.val();
					if (action && action.indexOf('teqb_') === 0) {
						if (action === 'teqb_assign_location' || action === 'teqb_remove_location') {
							var locationId = $('#teqb_location_select').val();
							if (!locationId) {
								alert('<?php esc_html_e('Please select a location.', 'teqb'); ?>');
								e.preventDefault();
								return false;
							}
							$(this).append('<input type="hidden" name="teqb_location" value="' + locationId + '">');
						} else if (action === 'teqb_update_starting_price') {
							var price = $('#teqb_starting_price').val();
							if (price === '') {
								alert('<?php esc_html_e('Please enter a starting price.', 'teqb'); ?>');
								e.preventDefault();
								return false;
							}
							$(this).append('<input type="hidden" name="teqb_starting_price" value="' + price + '">');
						} else if (action === 'teqb_update_price') {
							var price = $('#teqb_price').val();
							if (price === '') {
								alert('<?php esc_html_e('Please enter a price.', 'teqb'); ?>');
								e.preventDefault();
								return false;
							}
							$(this).append('<input type="hidden" name="teqb_price" value="' + price + '">');
						} else if (action === 'teqb_update_base_price') {
							var basePrice = $('#teqb_base_price').val();
							if (basePrice === '') {
								alert('<?php esc_html_e('Please enter a base price.', 'teqb'); ?>');
								e.preventDefault();
								return false;
							}
							$(this).append('<input type="hidden" name="teqb_base_price" value="' + basePrice + '">');
						}
					}
				});
			});
		})(jQuery);
		</script>
		<?php
	}
	
	/**
	 * Render bulk action success notices
	 */
	public function render_bulk_action_notices() {
		if (!isset($_GET['teqb_bulk_updated']) || !isset($_GET['teqb_bulk_action'])) {
			return;
		}
		
		$updated = intval($_GET['teqb_bulk_updated']);
		$action = sanitize_text_field($_GET['teqb_bulk_action']);
		
		if ($updated === 0) {
			return;
		}
		
		$messages = array(
			'teqb_assign_location' => sprintf(_n('%d item assigned to location.', '%d items assigned to location.', $updated, 'teqb'), $updated),
			'teqb_remove_location' => sprintf(_n('%d item removed from location.', '%d items removed from location.', $updated, 'teqb'), $updated),
			'teqb_update_starting_price' => sprintf(_n('Starting price updated for %d service.', 'Starting price updated for %d services.', $updated, 'teqb'), $updated),
			'teqb_update_price' => sprintf(_n('Price updated for %d item.', 'Price updated for %d items.', $updated, 'teqb'), $updated),
			'teqb_update_base_price' => sprintf(_n('Base price updated for %d add-on.', 'Base price updated for %d add-ons.', $updated, 'teqb'), $updated),
		);
		
		$message = isset($messages[$action]) ? $messages[$action] : sprintf(__('%d items updated.', 'teqb'), $updated);
		
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html($message); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Add service filter dropdown for add-ons
	 */
	public function add_addon_service_filter($post_type) {
		if ($post_type !== 'teqb_addon') {
			return;
		}
		
		// Get all services
		$services = get_posts(array(
			'post_type' => 'teqb_service',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'orderby' => 'title',
			'order' => 'ASC',
		));
		
		$selected_service = isset($_GET['teqb_filter_service']) ? intval($_GET['teqb_filter_service']) : 0;
		
		?>
		<select name="teqb_filter_service" id="teqb_filter_service">
			<option value="0"><?php esc_html_e('All Services', 'teqb'); ?></option>
			<?php foreach ($services as $service) : ?>
				<option value="<?php echo esc_attr($service->ID); ?>" <?php selected($selected_service, $service->ID); ?>>
					<?php echo esc_html($service->post_title); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}
	
	/**
	 * Filter add-ons by service
	 */
	public function filter_addons_by_service($query) {
		global $pagenow;
		
		if (!is_admin() || $pagenow !== 'edit.php') {
			return;
		}
		
		if (!function_exists('get_current_screen')) {
			return;
		}
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen || !isset($screen->post_type) || $screen->post_type !== 'teqb_addon') {
			return;
		}
		// Handle service filter
		if (isset($_GET['teqb_filter_service']) && $_GET['teqb_filter_service'] != '0') {
			$service_id = intval($_GET['teqb_filter_service']);
			$query->set('meta_key', '_teqb_service_id');
			$query->set('meta_value', $service_id);
		}
		
		// Handle service column sorting - we'll use posts_clauses filter for proper JOIN
		if (isset($_GET['orderby']) && $_GET['orderby'] === 'service') {
			$query->set('meta_key', '_teqb_service_id');
		}
	}
	
	/**
	 * Sort add-ons by service name using JOIN
	 */
	public function sort_addons_by_service_name($clauses, $query) {
		global $wpdb;
		
		if (!is_admin() || !$query->is_main_query()) {
			return $clauses;
		}
		
		$screen = get_current_screen();
		if (!$screen || $screen->post_type !== 'teqb_addon') {
			return $clauses;
		}
		
		// Only apply when sorting by service
		if (!isset($_GET['orderby']) || $_GET['orderby'] !== 'service') {
			return $clauses;
		}
		
		$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
		
		// Add JOIN to get service post title
		$clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS service_meta ON {$wpdb->posts}.ID = service_meta.post_id AND service_meta.meta_key = '_teqb_service_id'";
		$clauses['join'] .= " LEFT JOIN {$wpdb->posts} AS service_posts ON service_meta.meta_value = service_posts.ID";
		
		// Order by service post title
		$clauses['orderby'] = "service_posts.post_title {$order}, {$wpdb->posts}.menu_order ASC";
		
		return $clauses;
	}
}
