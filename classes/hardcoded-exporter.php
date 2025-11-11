<?php
/**
 * Migration Script: Export Hardcoded Quote Builder Data
 * 
 * This script exports the hardcoded data from quote-builder.js
 * into the admin config format for import into WordPress.
 * 
 * Usage:
 * 1. Access via: /wp-admin/admin.php?page=teqb-export-hardcoded
 * 2. Click "Export Hardcoded Data" button
 * 3. Import the downloaded JSON file using the Import feature
 */

if (!defined('ABSPATH')) {
	exit;
}

class teqb_Hardcoded_Data_Exporter {
	
	/**
	 * Get hardcoded quote data structure
	 * This mirrors the structure from assets/js/quote-builder.js
	 */
	protected function get_hardcoded_quote_data() {
		return array(
			'djmc' => array(
				'label' => 'DJ / MC',
				'subtitle' => 'Crafting Unforgettable Celebrations',
				'paragraphs' => array(
					'Our DJs are true artists, blending live mixing with flawless hosting to keep your night flowing and your dance floor full. Whether it\'s the subtle background during dinner or the all-out energy of the last song, we read the room and adapt to every moment.'
				),
				'features' => array(
					'Professional DJ & MC service',
					'Unlimited consultation & personalized planning',
					'Premium sound system',
					'Wireless handheld mic',
					'Dance floor lighting that turns your reception into a celebration',
					'Extra speaker for ceremony',
					'Sleek DJ façade',
					'Full backup coverage',
					'Online planning tools',
					'No hidden fees – travel within 30 miles included'
				),
				'packages' => array(
					array(
						'id' => 'essential_experience',
						'name' => 'Essential Experience',
						'price' => 795,
						'includes' => array('4 hours of entertainment')
					),
					array(
						'id' => 'platinum_experience',
						'name' => 'Platinum Experience',
						'price' => 1395,
						'includes' => array('Ceremony to midnight', '10 LED uplights', 'Ceremony mic')
					),
					array(
						'id' => 'diamond_combo',
						'name' => 'Diamond Combo "Celebration"',
						'price' => 1995,
						'includes' => array(
							'Ceremony to midnight',
							'10 LED uplights',
							'Ceremony mic',
							'Ultimate Photo Booth'
						),
						'bundledServices' => array(
							array(
								'serviceId' => 'photobooth',
								'packageId' => 'strike_a_pose',
								'upgradePackages' => array('all_around_the_world', 'mirror_mirror'),
								'message' => 'Already included with the Diamond Combo DJ / MC package.',
								'removalMessage' => 'Photo Booth is already included with your Diamond Combo DJ / MC package, so we removed it from your service list.',
								'upgradeHint' => 'You can still explore booth upgrades below.',
								'infoTitle' => 'The Ultimate Photo Booth',
								'infoDescription' => 'This open-air booth delivers instant prints, premium backdrops, a sleek design, and a professional host—perfect for keeping guests entertained all night.',
								'infoLink' => ''
							)
						)
					),
					array(
						'id' => 'diamond_deluxe',
						'name' => 'Diamond Deluxe "Simply the Best"',
						'price' => 2995,
						'includes' => array(
							'Ceremony to midnight',
							'10 LED uplights',
							'Ceremony mic',
							'Ultimate Photo Booth',
							'Choice of 2 luxury enhancements'
						),
						'bonusOptions' => array(
							'Cold Spark Fountains',
							'Dancing on a Cloud',
							'Monogram Projection',
							'LOVE Marquee Letters'
						),
						'bonusLimit' => 2,
						'bundledServices' => array(
							array(
								'serviceId' => 'photobooth',
								'packageId' => 'strike_a_pose',
								'upgradePackages' => array('all_around_the_world', 'mirror_mirror'),
								'message' => 'Already included with the Diamond Deluxe DJ / MC package.',
								'removalMessage' => 'Photo Booth is already included with your Diamond Deluxe DJ / MC package, so we removed it from your service list.',
								'upgradeHint' => 'You can still explore booth upgrades below.',
								'infoTitle' => 'The Ultimate Photo Booth',
								'infoDescription' => 'This open-air booth delivers instant prints, premium backdrops, a sleek design, and a professional host—perfect for keeping guests entertained all night.',
								'infoLink' => ''
							)
						)
					)
				),
				'addons' => array(
					array('id' => 'extra_hour', 'name' => 'Extra Hour', 'base' => 200, 'unit' => 'hour'),
					array('id' => 'lapel_mic', 'name' => 'Lapel Microphone', 'price' => 95),
					array('id' => 'cold_sparks', 'name' => 'Cold Spark Fountains', 'base' => 595, 'min' => 2, 'extras' => array('Blast' => 200)),
					array('id' => 'cloud', 'name' => 'Dancing on a Cloud', 'price' => 595),
					array('id' => 'uplighting', 'name' => 'Uplighting', 'base' => 395, 'unit' => 'light'),
					array('id' => 'monogram', 'name' => 'Monogram Projection', 'price' => 595),
					array('id' => 'mashup', 'name' => 'Custom Mashup', 'price' => 95),
					array('id' => 'karaoke', 'name' => 'Karaoke Experience', 'price' => 595),
					array('id' => 'guestbook', 'name' => 'Audio Guestbook Phone', 'price' => 295),
					array('id' => 'glow', 'name' => 'Glow Sticks', 'price' => 295),
					array('id' => 'letters', 'name' => 'Marquee Letters', 'base' => 150, 'min' => 4, 'unit' => 'letter'),
					array('id' => 'tv_booth', 'name' => 'TV Booth', 'price' => 795),
					array('id' => 'tower_booth', 'name' => 'Tower DJ Booth', 'price' => 795),
					array('id' => 'request_dj', 'name' => 'Request Specific DJ', 'price' => 200)
				)
			),
			'photography' => array(
				'label' => 'Photography',
				'subtitle' => 'Capturing the Story of Your Day',
				'paragraphs' => array(
					'Our photographers blend artistry and authenticity to capture both the big moments and the subtle details that tell your story. The result is a collection of images you\'ll treasure for a lifetime.'
				),
				'features' => array(
					'Professional photographer',
					'Unlimited consultation',
					'Unlimited locations',
					'High-quality professional editing',
					'Free online gallery',
					'Full print rights',
					'Fast turnaround',
					'Online customizable shot list',
					'No hidden fees – travel within 30 miles included',
					'Bonus: One free 16×24 fine art print'
				),
				'packages' => array(
					array(
						'id' => 'picture_perfect',
						'name' => 'Picture Perfect',
						'price' => 1195,
						'includes' => array('4 hours of coverage')
					),
					array(
						'id' => 'from_this_moment',
						'name' => 'From This Moment On',
						'price' => 1995,
						'includes' => array('5 hours of coverage', 'Engagement or Bridal Session ($495 value)')
					),
					array(
						'id' => 'unforgettable',
						'name' => 'Unforgettable',
						'price' => 3495,
						'includes' => array(
							'8 hours of coverage',
							'Engagement or Bridal Session ($495 value)',
							'Additional photographer'
						)
					),
					array(
						'id' => 'miss_a_thing',
						'name' => 'I Do Not Want to Miss a Thing',
						'price' => 4995,
						'includes' => array(
							'Full-day coverage',
							'Engagement or Bridal Session',
							'Additional photographer',
							'Priority editing',
							'Luxury Wedding Album ($695 value)'
						)
					)
				),
				'addons' => array(
					array('id' => 'bridal_session', 'name' => 'Bridal or Engagement Photo Session', 'price' => 445, 'options' => array('Bridal Session', 'Engagement Session')),
					array('id' => 'expedited_editing', 'name' => 'Expedited Editing', 'price' => 300),
					array('id' => 'lead_extra_hour', 'name' => 'Lead Photographer Extra Hour', 'base' => 300, 'unit' => 'hour'),
					array('id' => 'assistant_photographer', 'name' => 'Assistant Photographer', 'base' => 125, 'unit' => 'hour', 'min' => 4),
					array('id' => 'specific_photographer', 'name' => 'Request Specific Photographer', 'price' => 200)
				)
			),
			'videography' => array(
				'label' => 'Videography',
				'subtitle' => 'Turning Moments into Motion',
				'paragraphs' => array(
					'We see wedding videography as an art, blending authentic moments with cinematic style for films you\'ll want to watch again and again.'
				),
				'features' => array(
					'Professional videographer',
					'1-minute social media highlight film',
					'Multiple cameras',
					'Unlimited consultations',
					'Unlimited locations',
					'Fast turnaround',
					'Full HD digital delivery',
					'Online hosting',
					'No hidden fees – travel within 30 miles included'
				),
				'packages' => array(
					array(
						'id' => 'love_story',
						'name' => 'Love Story',
						'price' => 1495,
						'includes' => array('4 hours', '4-6 min highlight film', '10-20 min extended film')
					),
					array(
						'id' => 'come_fly',
						'name' => 'Come Fly With Me',
						'price' => 2195,
						'includes' => array(
							'6 hours',
							'5-7 min highlight film',
							'20-30 min extended film',
							'Drone footage'
						)
					),
					array(
						'id' => 'endless_love',
						'name' => 'Endless Love',
						'price' => 3995,
						'includes' => array(
							'Full-day coverage',
							'10-15 min highlight film',
							'60-90 min extended film',
							'Drone footage',
							'Pro audio recording'
						)
					)
				),
				'addons' => array(
					array('id' => 'extra_hours', 'name' => 'Additional Hours', 'base' => 350, 'unit' => 'hour'),
					array('id' => 'raw_pre', 'name' => 'Raw Footage (Pre-event)', 'price' => 300),
					array('id' => 'raw_post', 'name' => 'Raw Footage (Post-event)', 'price' => 500),
					array('id' => 'drone', 'name' => 'Drone Coverage', 'price' => 200),
					array('id' => 'second_videographer', 'name' => 'Second Videographer', 'base' => 150, 'unit' => 'hour', 'min' => 4),
					array('id' => 'love_story_film', 'name' => 'Love Story Film', 'price' => 495),
					array('id' => 'specific_videographer', 'name' => 'Request Specific Videographer', 'price' => 200),
					array('id' => 'custom_editing', 'name' => 'Custom Editing Services', 'price' => 250)
				)
			),
			'coordination' => array(
				'label' => 'Coordination',
				'subtitle' => 'Expertly Crafting Your Event',
				'paragraphs' => array(
					'Our coordination team makes sure every detail is perfect, every timeline is on track, and every moment is yours to enjoy.'
				),
				'features' => array(
					'Dedicated lead coordinator & assistant',
					'Unlimited pre-wedding communication',
					'Exclusive access to our trusted vendor network',
					'1-hour in-person or virtual consultation',
					'1-hour venue walkthrough',
					'Finalization of layout, timeline & checklists',
					'Vendor management & confirmation',
					'Last-minute troubleshooting',
					'Set up & breakdown supervision',
					'Flawless ceremony & reception flow',
					'No hidden fees – travel within 30 miles included'
				),
				'packages' => array(
					array(
						'id' => 'essential_4month',
						'name' => 'Essential 4-Month Coordination - Can\'t Stop the Feeling',
						'price' => 1995,
						'includes' => array('Up to 8 hours of event-day coverage')
					),
					array(
						'id' => 'unlimited_4month',
						'name' => 'Unlimited 4-Month Coordination - All You Need Is Love',
						'price' => 2495,
						'includes' => array('Unlimited event-day coverage')
					),
					array(
						'id' => 'ultimate_weekend',
						'name' => '4-Month Ultimate Weekend - Best Day of My Life',
						'price' => 2995,
						'includes' => array('Unlimited event-day coverage', 'Rehearsal coverage')
					)
				),
				'addons' => array(
					array('id' => 'coord_extra_hours', 'name' => 'Additional Hours', 'base' => 200, 'unit' => 'hour'),
					array('id' => 'rehearsal', 'name' => 'Rehearsal Coverage', 'price' => 500),
					array('id' => 'specific_coordinator', 'name' => 'Request Specific Coordinator', 'price' => 200)
				)
			),
			'photobooth' => array(
				'label' => 'Photo Booth',
				'subtitle' => 'Fun That Guests Take Home',
				'paragraphs' => array(
					'Our photo booths are designed for fun — with unlimited prints, awesome props, and instant sharing that guests of all ages love.'
				),
				'features' => array(
					'Unlimited sessions',
					'Professional photo booth operator',
					'Fun & hilarious prop collection',
					'Full digital gallery after the event'
				),
				'packages' => array(
					array(
						'id' => 'strike_a_pose',
						'name' => 'The Ultimate Photo Booth - "Strike a Pose"',
						'price' => 745,
						'includes' => array(
							'Instant prints',
							'Choice of 4 premium backdrops',
							'Sleek open-air booth design'
						)
					),
					array(
						'id' => 'all_around_the_world',
						'name' => 'The 360 Experience - "All Around the World"',
						'price' => 895,
						'includes' => array(
							'360 video booth setup',
							'Instant video sharing',
							'High-quality lighting and slow-motion effects'
						)
					),
					array(
						'id' => 'mirror_mirror',
						'name' => 'Magic Mirror Booth Experience - "Mirror, Mirror"',
						'price' => 1095,
						'includes' => array(
							'Interactive full-length mirror booth',
							'Instant prints',
							'Choice of 4 premium backdrops',
							'On-screen signing and emoji features'
						)
					)
				),
				'addons' => array(
					array('id' => 'extra_hour', 'name' => 'Additional Hour', 'base' => 100, 'unit' => 'hour'),
					array('id' => 'guest_album', 'name' => 'Photo Booth Guest Album', 'price' => 125),
					array('id' => 'photo_strip', 'name' => 'Custom Photo Strip Template', 'price' => 100),
					array('id' => 'upgraded_backdrops', 'name' => 'Upgraded Backdrops', 'price' => 495, 'options' => array('Greenery Wall', 'Flower Wall', 'Custom Backdrop'))
				)
			)
		);
	}
	
	/**
	 * Get hardcoded reward catalog
	 */
	protected function get_hardcoded_reward_catalog() {
		return array(
			'signature_touch' => array(
				'label' => 'Signature Touch',
				'pluralLabel' => 'Signature Touches',
				'optionsLabel' => 'Signature Touch Options:',
				'options' => array(
					'Photo Booth Hours Match Other Service Hours',
					'Lapel Microphone ($95 value)',
					'Custom DJ Mashup ($95 value)',
					'Audio Guestbook Phone ($295 value)',
					'Glow Sticks ($295 value)',
					'Photo Booth Guest Album ($125 value)',
					'Custom Photo Strip Templates ($100 value)'
				)
			),
			'luxury_enhancement' => array(
				'label' => 'Luxury Enhancement',
				'pluralLabel' => 'Luxury Enhancements',
				'optionsLabel' => 'Luxury Enhancement Options:',
				'options' => array(
					'Cold Spark Fountains (2 sparks, one use; $595 value)',
					'Dancing on a Cloud ($595 value)',
					'Uplighting ($395 value)',
					'Monogram Projection ($595 value)',
					'Karaoke Experience ($595 value)',
					'Love Letters',
					'Upgraded Backdrops ($495 value)',
					'Mirror Me or 360 Photo Booth Upgrade'
				)
			)
		);
	}
	
	/**
	 * Get hardcoded bundle discounts
	 */
	protected function get_hardcoded_bundle_discounts() {
		return array(
			array(
				'minServices' => 2,
				'discount' => 100,
				'description' => 'Book Any 2 Services: $100 Off + 1 Free Signature Touch',
				'freebies' => array(array('type' => 'signature_touch', 'quantity' => 1))
			),
			array(
				'minServices' => 3,
				'discount' => 200,
				'description' => 'Book Any 3 Services: $200 Off + 1 Free Luxury Enhancement',
				'freebies' => array(array('type' => 'luxury_enhancement', 'quantity' => 1))
			),
			array(
				'minServices' => 4,
				'discount' => 300,
				'description' => 'Book Any 4 Services: $300 Off + 1 Free Signature Touch + 1 Free Luxury Enhancement',
				'freebies' => array(
					array('type' => 'signature_touch', 'quantity' => 1),
					array('type' => 'luxury_enhancement', 'quantity' => 1)
				)
			),
			array(
				'minServices' => 5,
				'discount' => 400,
				'description' => 'Book All 5 Services: $400 Off + 2 Signature Touches + 2 Luxury Enhancements',
				'freebies' => array(
					array('type' => 'signature_touch', 'quantity' => 2),
					array('type' => 'luxury_enhancement', 'quantity' => 2)
				),
				'requiresAll' => true
			)
		);
	}
	
	/**
	 * Transform frontend format to admin config format
	 */
	protected function transform_to_admin_format($quote_data, $reward_catalog, $bundle_discounts) {
		// Convert services object to array
		$services = array();
		foreach ($quote_data as $service_id => $service_data) {
			$service = array(
				'id' => $service_id,
				'label' => $service_data['label'] ?? '',
				'subtitle' => $service_data['subtitle'] ?? '',
				'paragraphs' => $service_data['paragraphs'] ?? array(),
				'features' => $service_data['features'] ?? array(),
				'quote' => $service_data['quote'] ?? array('text' => '', 'attribution' => ''),
				'packages' => array(),
				'addons' => array(),
			);
			
			// Transform packages
			if (!empty($service_data['packages']) && is_array($service_data['packages'])) {
				foreach ($service_data['packages'] as $pkg) {
					$package = array(
						'id' => $pkg['id'] ?? '',
						'name' => $pkg['name'] ?? '',
						'price' => $pkg['price'] ?? 0,
						'includes' => $pkg['includes'] ?? array(),
					);
					
					if (!empty($pkg['bonusOptions'])) {
						$package['bonusOptions'] = $pkg['bonusOptions'];
					}
					
					if (!empty($pkg['bonusLimit'])) {
						$package['bonusLimit'] = $pkg['bonusLimit'];
					}
					
					if (!empty($pkg['bundledServices'])) {
						$package['bundledServices'] = $pkg['bundledServices'];
					}
					
					$service['packages'][] = $package;
				}
			}
			
			// Transform addons
			if (!empty($service_data['addons']) && is_array($service_data['addons'])) {
				foreach ($service_data['addons'] as $addon) {
					$addon_data = array(
						'id' => $addon['id'] ?? '',
						'name' => $addon['name'] ?? '',
					);
					
					if (isset($addon['price'])) {
						$addon_data['price'] = $addon['price'];
					}
					
					if (isset($addon['base'])) {
						$addon_data['base'] = $addon['base'];
					}
					
					if (!empty($addon['unit'])) {
						$addon_data['unit'] = $addon['unit'];
					}
					
					if (isset($addon['min'])) {
						$addon_data['min'] = $addon['min'];
					}
					
					if (!empty($addon['options'])) {
						$addon_data['options'] = $addon['options'];
					}
					
					if (!empty($addon['extras'])) {
						$addon_data['extras'] = $addon['extras'];
					}
					
					$service['addons'][] = $addon_data;
				}
			}
			
			$services[] = $service;
		}
		
		// Transform reward catalog (already in correct format)
		$rewards = array();
		foreach ($reward_catalog as $key => $reward) {
			$rewards[$key] = array(
				'label' => $reward['label'] ?? '',
				'pluralLabel' => $reward['pluralLabel'] ?? '',
				'optionsLabel' => $reward['optionsLabel'] ?? '',
				'options' => $reward['options'] ?? array(),
			);
		}
		
		// Transform bundle discounts (already in correct format)
		$rules = array();
		foreach ($bundle_discounts as $rule) {
			$rule_data = array(
				'minServices' => $rule['minServices'] ?? 0,
				'discount' => $rule['discount'] ?? 0,
				'description' => $rule['description'] ?? '',
				'requiresAll' => !empty($rule['requiresAll']),
				'freebies' => $rule['freebies'] ?? array(),
			);
			$rules[] = $rule_data;
		}
		
		return array(
			'services' => $services,
			'bundles' => array(
				'rules' => $rules,
				'rewards' => $rewards,
			),
			'form' => array(
				'require_phone' => true,
				'require_event_date' => false,
				'success_message' => '',
				'confirmation_copy' => '',
			),
			'notifications' => array(
				'email' => '',
			),
		);
	}
	
	/**
	 * Export hardcoded data
	 */
	public function export() {
		$quote_data = $this->get_hardcoded_quote_data();
		$reward_catalog = $this->get_hardcoded_reward_catalog();
		$bundle_discounts = $this->get_hardcoded_bundle_discounts();
		
		$admin_config = $this->transform_to_admin_format($quote_data, $reward_catalog, $bundle_discounts);
		
		$json = wp_json_encode($admin_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		
		if ($json === false) {
			wp_die(__('Failed to encode configuration.', 'teqb'));
		}
		
		$filename = 'hardcoded-quote-builder-' . date('Y-m-d') . '.json';
		
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Content-Length: ' . strlen($json));
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		
		echo $json;
		exit;
	}
}

