(function (wp) {
	if (!wp || !wp.element || !wp.components) {
		return;
	}

	const { __ } = wp.i18n || { __: (s) => s };
	const {
		createElement: el,
		Fragment,
		useState,
		useEffect,
		useMemo,
		useRef
	} = wp.element;
	const {
		TextControl,
		TextareaControl,
		Button,
		CheckboxControl,
		SelectControl,
		Notice
	} = wp.components;

	const data = window.teqbBuilderAdmin || {};

	const defaults = data.defaults || {};
	const initialConfig = data.config || {};

	const deepMerge = (target, source) => {
		if (Array.isArray(target)) {
			return Array.isArray(source) ? source : target;
		}

		if (target && typeof target === 'object') {
			const result = { ...target };
			if (source && typeof source === 'object') {
				Object.keys(source).forEach((key) => {
					result[key] = deepMerge(target[key], source[key]);
				});
				return result;
			}
			return result;
		}

		return source !== undefined ? source : target;
	};

	const clone = (value) => {
		if (typeof window.structuredClone === 'function') {
			return window.structuredClone(value);
		}
		return JSON.parse(JSON.stringify(value));
	};

	const slugify = (value) =>
		(value || '')
			.toString()
			.trim()
			.toLowerCase()
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');

	const defaultConfig = deepMerge({
		general: {
			headline: '',
			subheadline: '',
			description: '',
			pricing_note: '',
			hero_quote: {
				text: '',
				attribution: ''
			}
		},
		services: [],
		bundles: {
			rules: [],
			rewards: {
				signature_touch: {
					label: __('Signature Touch', 'teqb'),
					pluralLabel: __('Signature Touches', 'teqb'),
					optionsLabel: __('Signature Touch Options:', 'teqb'),
					options: []
				},
				luxury_enhancement: {
					label: __('Luxury Enhancement', 'teqb'),
					pluralLabel: __('Luxury Enhancements', 'teqb'),
					optionsLabel: __('Luxury Enhancement Options:', 'teqb'),
					options: []
				}
			}
		},
		form: {
			require_phone: true,
			require_event_date: false,
			success_message: '',
			confirmation_copy: ''
		},
		notifications: {
			email: ''
		}
	}, defaults);

	const initialState = deepMerge(defaultConfig, initialConfig);

	const createEmptyService = () => ({
		id: '',
		label: '',
		subtitle: '',
		paragraphs: [],
		features: [],
		quote: {
			text: '',
			attribution: ''
		},
		packages: [],
		addons: []
	});

	const createEmptyPackage = () => ({
		id: '',
		name: '',
		price: '',
		includes: [],
		bonusOptions: [],
		bonusLimit: ''
	});

	const createEmptyAddon = () => ({
		id: '',
		name: '',
		price: '',
		base: '',
		unit: '',
		min: '',
		options: []
	});

	const createEmptyBundleRule = () => ({
		minServices: 2,
		discount: 0,
		description: '',
		requiresAll: false,
		freebies: []
	});

	const createEmptyFreebie = () => ({
		type: 'signature_touch',
		quantity: 1
	});

	const RewardTypeOptions = [
		{ value: 'signature_touch', label: __('Signature Touch', 'teqb') },
		{ value: 'luxury_enhancement', label: __('Luxury Enhancement', 'teqb') }
	];

	const Section = ({ title, description, children }) =>
		el('section', { className: 'teqb-admin-section' },
			el('h2', null, title),
			description ? el('p', { className: 'section-description' }, description) : null,
			children
		);

	const TextListControl = ({ label, value, onChange, help, placeholder }) => {
		const currentValue = Array.isArray(value) ? value.join('\n') : '';
		return el(TextareaControl, {
			label,
			help: help || __('Enter one item per line.', 'teqb'),
			value: currentValue,
			placeholder: placeholder || '',
			onChange: (text) => {
				const items = text
					.split('\n')
					.map((line) => line.trim())
					.filter(Boolean);
				onChange(items);
			}
		});
	};

	const PackageEditor = ({ pkg, onChange, onRemove }) => {
		const handleUpdate = (field, fieldValue) => {
			const next = { ...pkg, [field]: fieldValue };
			if (field === 'name' && (!pkg.id || pkg.id === slugify(pkg.name))) {
				next.id = slugify(fieldValue);
			}
			if (field === 'price') {
				next.price = fieldValue.replace(/[^\d.]/g, '');
			}
			if (field === 'bonusLimit') {
				next.bonusLimit = fieldValue ? parseInt(fieldValue, 10) || '' : '';
			}
			onChange(next);
		};

		return el('div', { className: 'teqb-nested-card' },
			el('div', { className: 'teqb-nested-grid' },
				el(TextControl, {
					label: __('Package Name', 'teqb'),
					placeholder: __('e.g., Platinum Experience', 'teqb'),
					value: pkg.name || '',
					onChange: (value) => handleUpdate('name', value)
				}),
				el(TextControl, {
					label: __('Identifier (slug)', 'teqb'),
					help: __('Auto-filled from the name; adjust only if necessary.', 'teqb'),
					placeholder: __('e.g., platinum_experience', 'teqb'),
					value: pkg.id || '',
					onChange: (value) => onChange({ ...pkg, id: slugify(value) })
				}),
				el(TextControl, {
					label: __('Price', 'teqb'),
					type: 'number',
					placeholder: __('e.g., 1395', 'teqb'),
					value: pkg.price || '',
					onChange: (value) => handleUpdate('price', value)
				}),
				el(TextControl, {
					label: __('Bonus Limit', 'teqb'),
					type: 'number',
					help: __('How many bonus selections this package allows.', 'teqb'),
					placeholder: __('e.g., 2', 'teqb'),
					value: pkg.bonusLimit || '',
					onChange: (value) => handleUpdate('bonusLimit', value)
				})
			),
			TextListControl({
				label: __('Included Features', 'teqb'),
				placeholder: __('Enter each package inclusion on its own line.', 'teqb'),
				value: pkg.includes || [],
				onChange: (items) => onChange({ ...pkg, includes: items })
			}),
			TextListControl({
				label: __('Bonus Options', 'teqb'),
				placeholder: __('List the optional bonus selections available.', 'teqb'),
				value: pkg.bonusOptions || [],
				onChange: (items) => onChange({ ...pkg, bonusOptions: items })
			}),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, {
					variant: 'secondary',
					onClick: () => onRemove()
				}, __('Remove Package', 'teqb'))
			)
		);
	};

	const AddonEditor = ({ addon, onChange, onRemove }) => {
		const handleUpdate = (field, fieldValue) => {
			const next = { ...addon, [field]: fieldValue };
			if (field === 'name' && (!addon.id || addon.id === slugify(addon.name))) {
				next.id = slugify(fieldValue);
			}
			if (field === 'price' || field === 'base') {
				next[field] = fieldValue.replace(/[^\d.]/g, '');
			}
			if (field === 'min') {
				next.min = fieldValue ? parseInt(fieldValue, 10) || '' : '';
			}
			onChange(next);
		};

		return el('div', { className: 'teqb-nested-card' },
			el('div', { className: 'teqb-nested-grid' },
				el(TextControl, {
					label: __('Add-on Name', 'teqb'),
					placeholder: __('e.g., Cold Spark Fountains', 'teqb'),
					value: addon.name || '',
					onChange: (value) => handleUpdate('name', value)
				}),
				el(TextControl, {
					label: __('Identifier (slug)', 'teqb'),
					help: __('Auto-filled from the name; use lowercase with dashes/underscores.', 'teqb'),
					placeholder: __('e.g., cold_sparks', 'teqb'),
					value: addon.id || '',
					onChange: (value) => onChange({ ...addon, id: slugify(value) })
				}),
				el(TextControl, {
					label: __('Flat Price', 'teqb'),
					type: 'number',
					placeholder: __('e.g., 595', 'teqb'),
					value: addon.price || '',
					onChange: (value) => handleUpdate('price', value)
				}),
				el(TextControl, {
					label: __('Base Rate', 'teqb'),
					type: 'number',
					help: __('Per-unit rate when quantity applies (leave blank for flat price add-ons).', 'teqb'),
					placeholder: __('e.g., 200', 'teqb'),
					value: addon.base || '',
					onChange: (value) => handleUpdate('base', value)
				}),
				el(TextControl, {
					label: __('Unit', 'teqb'),
					help: __('Shown to visitors when base pricing is used (hours, letters, etc.).', 'teqb'),
					placeholder: __('e.g., hour', 'teqb'),
					value: addon.unit || '',
					onChange: (value) => handleUpdate('unit', value)
				}),
				el(TextControl, {
					label: __('Minimum Quantity', 'teqb'),
					type: 'number',
					help: __('Only relevant when a base rate is set.', 'teqb'),
					placeholder: __('e.g., 4', 'teqb'),
					value: addon.min || '',
					onChange: (value) => handleUpdate('min', value)
				})
			),
			TextListControl({
				label: __('Options', 'teqb'),
				placeholder: __('Optional variations (e.g., Flower Wall, Greenery Wall).', 'teqb'),
				value: addon.options || [],
				onChange: (items) => onChange({ ...addon, options: items })
			}),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, {
					variant: 'secondary',
					onClick: () => onRemove()
				}, __('Remove Add-on', 'teqb'))
			)
		);
	};

	const ServiceEditor = ({ service, onChange, onRemove }) => {
		const updateField = (field, value) => {
			const next = { ...service, [field]: value };
			if (field === 'label' && (!service.id || service.id === slugify(service.label))) {
				next.id = slugify(value);
			}
			onChange(next);
		};

		const updateNested = (path, value) => {
			const next = { ...service };
			let pointer = next;
			while (path.length > 1) {
				const key = path.shift();
				pointer[key] = { ...pointer[key] };
				pointer = pointer[key];
			}
			pointer[path[0]] = value;
			onChange(next);
		};

		const packages = Array.isArray(service.packages) ? service.packages : [];
		const addons = Array.isArray(service.addons) ? service.addons : [];

		return el('div', { className: 'teqb-service-card' },
			el('div', { className: 'teqb-nested-grid' },
				el(TextControl, {
					label: __('Service Label', 'teqb'),
					help: __('Public name shown to visitors.', 'teqb'),
					placeholder: __('e.g., DJ / MC', 'teqb'),
					value: service.label || '',
					onChange: (value) => updateField('label', value)
				}),
				el(TextControl, {
					label: __('Identifier (slug)', 'teqb'),
					help: __('Used internally; auto-generated from the label when left blank.', 'teqb'),
					placeholder: __('e.g., djmc', 'teqb'),
					value: service.id || '',
					onChange: (value) => updateField('id', slugify(value))
				}),
				el(TextControl, {
					label: __('Subtitle', 'teqb'),
					help: __('Brief supporting phrase shown on the service card.', 'teqb'),
					placeholder: __('e.g., Crafting Unforgettable Celebrations', 'teqb'),
					value: service.subtitle || '',
					onChange: (value) => updateField('subtitle', value)
				})
			),
			TextListControl({
				label: __('Marketing Paragraphs', 'teqb'),
				placeholder: __('Explain the service benefits and experience.', 'teqb'),
				value: service.paragraphs || [],
				onChange: (items) => updateField('paragraphs', items)
			}),
			TextListControl({
				label: __('Feature Bullets', 'teqb'),
				placeholder: __('Include the core inclusions guests receive.', 'teqb'),
				value: service.features || [],
				onChange: (items) => updateField('features', items)
			}),
			el(TextareaControl, {
				label: __('Quote Text', 'teqb'),
				placeholder: __('“The dance floor was packed all night long!”', 'teqb'),
				value: (service.quote && service.quote.text) || '',
				onChange: (value) => updateNested(['quote', 'text'], value)
			}),
			el(TextControl, {
				label: __('Quote Attribution', 'teqb'),
				placeholder: __('e.g., Sarah M., Houston bride', 'teqb'),
				value: (service.quote && service.quote.attribution) || '',
				onChange: (value) => updateNested(['quote', 'attribution'], value)
			}),
			el('div', { className: 'teqb-admin-multi' },
				el('h3', null, __('Packages', 'teqb')),
				packages.map((pkg, idx) =>
					el(PackageEditor, {
						key: `pkg-${idx}`,
						pkg,
						onChange: (next) => {
							const nextPackages = packages.slice();
							nextPackages[idx] = next;
							updateField('packages', nextPackages);
						},
						onRemove: () => {
							const nextPackages = packages.slice();
							nextPackages.splice(idx, 1);
							updateField('packages', nextPackages);
						}
					})
				),
				el(Button, {
					variant: 'primary',
					onClick: () => {
						const nextPackages = packages.slice();
						nextPackages.push(createEmptyPackage());
						updateField('packages', nextPackages);
					}
				}, __('Add Package', 'teqb'))
			),
			el('div', { className: 'teqb-admin-multi' },
				el('h3', null, __('Add-ons', 'teqb')),
				addons.map((addon, idx) =>
					el(AddonEditor, {
						key: `add-${idx}`,
						addon,
						onChange: (next) => {
							const nextAddons = addons.slice();
							nextAddons[idx] = next;
							updateField('addons', nextAddons);
						},
						onRemove: () => {
							const nextAddons = addons.slice();
							nextAddons.splice(idx, 1);
							updateField('addons', nextAddons);
						}
					})
				),
				el(Button, {
					variant: 'secondary',
					onClick: () => {
						const nextAddons = addons.slice();
						nextAddons.push(createEmptyAddon());
						updateField('addons', nextAddons);
					}
				}, __('Add Add-on', 'teqb'))
			),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, {
					isDestructive: true,
					onClick: () => onRemove()
				}, __('Remove Service', 'teqb'))
			)
		);
	};

	const FreebieEditor = ({ freebie, onChange, onRemove, rewardTypes }) =>
		el('div', { className: 'teqb-nested-card' },
			el('div', { className: 'teqb-nested-grid' },
				el(SelectControl, {
					label: __('Reward Type', 'teqb'),
					help: __('Select which reward catalog this freebie pulls from.', 'teqb'),
					value: freebie.type,
					options: rewardTypes,
					onChange: (value) => onChange({ ...freebie, type: value })
				}),
				el(TextControl, {
					label: __('Quantity', 'teqb'),
					type: 'number',
					min: 1,
					placeholder: __('e.g., 1', 'teqb'),
					value: freebie.quantity,
					onChange: (value) => onChange({ ...freebie, quantity: parseInt(value, 10) || 1 })
				})
			),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, { variant: 'secondary', onClick: onRemove }, __('Remove Reward', 'teqb'))
			)
		);

	const BundleRuleEditor = ({ rule, onChange, onRemove, rewardTypes }) => {
		const freebies = Array.isArray(rule.freebies) ? rule.freebies : [];
		return el('div', { className: 'teqb-service-card' },
			el('div', { className: 'teqb-nested-grid' },
				el(TextControl, {
					label: __('Minimum Services', 'teqb'),
					type: 'number',
					help: __('Number of booked services required to trigger this tier.', 'teqb'),
					placeholder: __('e.g., 3', 'teqb'),
					value: rule.minServices || 0,
					onChange: (value) => onChange({ ...rule, minServices: parseInt(value, 10) || 0 })
				}),
				el(TextControl, {
					label: __('Discount Amount', 'teqb'),
					type: 'number',
					help: __('Flat discount applied when the tier is met.', 'teqb'),
					placeholder: __('e.g., 200', 'teqb'),
					value: rule.discount || 0,
					onChange: (value) => onChange({ ...rule, discount: parseFloat(value) || 0 })
				}),
				el(CheckboxControl, {
					label: __('Requires all services', 'teqb'),
					checked: !!rule.requiresAll,
					onChange: (checked) => onChange({ ...rule, requiresAll: !!checked })
				})
			),
			el(TextControl, {
				label: __('Description', 'teqb'),
				help: __('Shown in the summary (e.g., “Book 3 services: $200 off + 1 Luxury Enhancement”).', 'teqb'),
				placeholder: __('Describe the offer unlocked at this tier.', 'teqb'),
				value: rule.description || '',
				onChange: (value) => onChange({ ...rule, description: value })
			}),
			el('div', { className: 'teqb-admin-multi' },
				el('h4', null, __('Rewards', 'teqb')),
				freebies.map((item, idx) =>
					el(FreebieEditor, {
						key: `freebie-${idx}`,
						freebie: item,
						rewardTypes,
						onChange: (next) => {
							const updated = freebies.slice();
							updated[idx] = next;
							onChange({ ...rule, freebies: updated });
						},
						onRemove: () => {
							const updated = freebies.slice();
							updated.splice(idx, 1);
							onChange({ ...rule, freebies: updated });
						}
					})
				),
				el(Button, {
					variant: 'secondary',
					onClick: () => {
						const updated = freebies.slice();
						updated.push(createEmptyFreebie());
						onChange({ ...rule, freebies: updated });
					}
				}, __('Add Reward', 'teqb'))
			),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, {
					isDestructive: true,
					onClick: () => onRemove()
				}, __('Remove Tier', 'teqb'))
			)
		);
	};

	const RewardCatalogEditor = ({ rewardKey, rewardConfig, onChange }) =>
		el('div', { className: 'teqb-service-card' },
			el('h3', null, rewardConfig.label || rewardKey),
			el('div', { className: 'teqb-nested-grid' },
				el(TextControl, {
					label: __('Label (singular)', 'teqb'),
					placeholder: __('e.g., Signature Touch', 'teqb'),
					value: rewardConfig.label || '',
					onChange: (value) => onChange({ ...rewardConfig, label: value })
				}),
				el(TextControl, {
					label: __('Label (plural)', 'teqb'),
					placeholder: __('e.g., Signature Touches', 'teqb'),
					value: rewardConfig.pluralLabel || '',
					onChange: (value) => onChange({ ...rewardConfig, pluralLabel: value })
				}),
				el(TextControl, {
					label: __('Options Heading', 'teqb'),
					placeholder: __('e.g., Signature Touch Options:', 'teqb'),
					value: rewardConfig.optionsLabel || '',
					onChange: (value) => onChange({ ...rewardConfig, optionsLabel: value })
				})
			),
			TextListControl({
				label: __('Available Options', 'teqb'),
				placeholder: __('List each perk guests can choose (Photo Booth Guest Album, Glow Sticks, etc.).', 'teqb'),
				value: rewardConfig.options || [],
				onChange: (items) => onChange({ ...rewardConfig, options: items }),
				help: __('List each bonus item on a separate line.', 'teqb')
			})
		);

	const GeneralSection = ({ general, onUpdate }) =>
		el(Section, {
			title: __('General Information', 'teqb'),
			description: __('Set the headline, supporting copy, and default pricing note for this builder.', 'teqb')
		},
		el('div', { className: 'teqb-nested-grid' },
			el(TextControl, {
				label: __('Headline', 'teqb'),
				help: __('Displayed prominently above the builder.', 'teqb'),
				placeholder: __('e.g., Craft Your Perfect Toast Experience', 'teqb'),
				value: general.headline || '',
				onChange: (value) => onUpdate('headline', value)
			}),
			el(TextControl, {
				label: __('Subheadline', 'teqb'),
				help: __('Short supporting line beneath the headline.', 'teqb'),
				placeholder: __('e.g., Choose your services and unlock exclusive rewards', 'teqb'),
				value: general.subheadline || '',
				onChange: (value) => onUpdate('subheadline', value)
			})
		),
		el(TextareaControl, {
			label: __('Description / Intro Copy', 'teqb'),
			help: __('Long-form introduction that appears before step one.', 'teqb'),
			placeholder: __('Welcome guests with a brief overview of how the quote builder works.', 'teqb'),
			value: general.description || '',
			onChange: (value) => onUpdate('description', value)
		}),
		el(TextareaControl, {
			label: __('Pricing Note', 'teqb'),
			help: __('Displayed in the pricing notes modal. Include seasonal surcharges or disclaimers.', 'teqb'),
			placeholder: __('All packages include tax. Add $200 for October Saturday events.', 'teqb'),
			value: general.pricing_note || '',
			onChange: (value) => onUpdate('pricing_note', value)
		}),
		el(TextareaControl, {
			label: __('Hero Quote', 'teqb'),
			help: __('Inspirational quote or testimonial displayed near the top.', 'teqb'),
			placeholder: __('“Our wedding was unforgettable thanks to the Toast team!”', 'teqb'),
			value: (general.hero_quote && general.hero_quote.text) || '',
			onChange: (value) => {
				const next = { ...(general.hero_quote || {}) };
				next.text = value;
				onUpdate('hero_quote', next);
			}
		}),
		el(TextControl, {
			label: __('Hero Quote Attribution', 'teqb'),
			placeholder: __('e.g., Jordan & Casey, Austin, TX', 'teqb'),
			value: (general.hero_quote && general.hero_quote.attribution) || '',
			onChange: (value) => {
				const next = { ...(general.hero_quote || {}) };
				next.attribution = value;
				onUpdate('hero_quote', next);
			}
		})
	);

	const ServicesSection = ({ services, onChange }) =>
		el(Section, {
			title: __('Services', 'teqb'),
			description: __('Define services, packages, and add-ons that visitors can select.', 'teqb')
		},
		el('div', { className: 'teqb-admin-multi' },
			services.map((service, idx) =>
				el(ServiceEditor, {
					key: `service-${idx}`,
					service,
					onChange: (next) => {
						const nextServices = services.slice();
						nextServices[idx] = next;
						onChange(nextServices);
					},
					onRemove: () => {
						const nextServices = services.slice();
						nextServices.splice(idx, 1);
						onChange(nextServices);
					}
				})
			),
			el(Button, {
				variant: 'primary',
				onClick: () => {
					const nextServices = services.slice();
					nextServices.push(createEmptyService());
					onChange(nextServices);
				}
			}, __('Add Service', 'teqb'))
		)
	);

	const BundlesSection = ({ bundles, onChange }) => {
		const rules = Array.isArray(bundles.rules) ? bundles.rules : [];
		const rewards = bundles.rewards || {};
		const updateRules = (value) => onChange({ ...bundles, rules: value });
		const updateRewards = (key, value) => onChange({
			...bundles,
			rewards: {
				...rewards,
				[key]: value
			}
		});

		const rewardTypeOptions = RewardTypeOptions.map((option) => ({
			...option,
			label: (rewards[option.value] && rewards[option.value].label) || option.label
		}));

		return el(Section, {
			title: __('Bundle Discounts & Rewards', 'teqb'),
			description: __('Configure tiered discounts and the complimentary rewards they unlock.', 'teqb')
		},
		el('div', { className: 'teqb-admin-multi' },
			rules.map((rule, idx) =>
				el(BundleRuleEditor, {
					key: `rule-${idx}`,
					rule,
					rewardTypes: rewardTypeOptions,
					onChange: (next) => {
						const nextRules = rules.slice();
						nextRules[idx] = next;
						updateRules(nextRules);
					},
					onRemove: () => {
						const nextRules = rules.slice();
						nextRules.splice(idx, 1);
						updateRules(nextRules);
					}
				})
			),
			el(Button, {
				variant: 'secondary',
				onClick: () => {
					const nextRules = rules.slice();
					nextRules.push(createEmptyBundleRule());
					updateRules(nextRules);
				}
			}, __('Add Bundle Tier', 'teqb'))
		),
		el('div', { className: 'teqb-admin-multi' },
			Object.keys(rewards).map((key) =>
				el(RewardCatalogEditor, {
					key,
					rewardKey: key,
					rewardConfig: rewards[key],
					onChange: (next) => updateRewards(key, next)
				})
			)
		));
	};

	const FormSection = ({ form, notifications, onUpdateForm, onUpdateNotifications }) =>
		el(Section, {
			title: __('Form & Notifications', 'teqb'),
			description: __('Control required fields, confirmation messages, and notification routing.', 'teqb')
		},
		el('div', { className: 'teqb-nested-grid' },
			el(CheckboxControl, {
				label: __('Require phone number', 'teqb'),
				help: __('When enabled, visitors must supply a phone number before submitting.', 'teqb'),
				checked: !!form.require_phone,
				onChange: (checked) => onUpdateForm('require_phone', !!checked)
			}),
			el(CheckboxControl, {
				label: __('Require event date', 'teqb'),
				help: __('Ask visitors to confirm their event date before completing the form.', 'teqb'),
				checked: !!form.require_event_date,
				onChange: (checked) => onUpdateForm('require_event_date', !!checked)
			})
		),
		el(TextareaControl, {
			label: __('Success message', 'teqb'),
			placeholder: __('Thank you! Our team will reach out with a personalized quote within 24 hours.', 'teqb'),
			value: form.success_message || '',
			onChange: (value) => onUpdateForm('success_message', value)
		}),
		el(TextareaControl, {
			label: __('Confirmation copy (optional)', 'teqb'),
			help: __('Display additional next steps or expectations beneath the success message.', 'teqb'),
			placeholder: __('Keep an eye on your inbox for a confirmation email with next steps.', 'teqb'),
			value: form.confirmation_copy || '',
			onChange: (value) => onUpdateForm('confirmation_copy', value)
		}),
		el(TextControl, {
			label: __('Notification email override', 'teqb'),
			help: __('Leave blank to use the global setting.', 'teqb'),
			placeholder: __('e.g., quotes@toastent.com', 'teqb'),
			value: notifications.email || '',
			onChange: (value) => onUpdateNotifications('email', value)
		})
	);

	const App = () => {
		const [config, setConfig] = useState(initialState);
		const [dirty, setDirty] = useState(false);
		const firstRender = useRef(true);

		useEffect(() => {
			const input = document.getElementById('teqb_builder_config');
			if (input) {
				input.value = JSON.stringify(config);
			}
			if (firstRender.current) {
				firstRender.current = false;
			} else {
				setDirty(true);
			}
		}, [config]);

		const general = config.general || {};
		const services = Array.isArray(config.services) ? config.services : [];
		const bundles = config.bundles || {};
		const form = config.form || {};
		const notifications = config.notifications || {};

		return el('div', { className: 'teqb-builder-admin' },
			el(Notice, {
				status: 'info',
				isDismissible: false
			}, __('Use this editor to configure the quote builder experience. Changes are stored as post meta and will be wired to the front-end in a later phase.', 'teqb')),
			GeneralSection({
				general,
				onUpdate: (field, value) => {
					const next = { ...general, [field]: value };
					setConfig({ ...config, general: next });
				}
			}),
			ServicesSection({
				services,
				onChange: (next) => setConfig({ ...config, services: next })
			}),
			BundlesSection({
				bundles,
				onChange: (next) => setConfig({ ...config, bundles: next })
			}),
			FormSection({
				form,
				notifications,
				onUpdateForm: (field, value) => {
					const next = { ...form, [field]: value };
					setConfig({ ...config, form: next });
				},
				onUpdateNotifications: (field, value) => {
					const next = { ...notifications, [field]: value };
					setConfig({ ...config, notifications: next });
				}
			}),
			el('div', { className: 'teqb-admin-footer' },
				el('span', null,
					dirty
						? __('Changes will be saved when you update the builder post.', 'teqb')
						: __('No changes yet.', 'teqb')
				),
				el('span', null,
					__('Shortcode example:', 'teqb'),
					' ',
					el('code', null, '[quote-builder=builder-' + ((data.postSlug && data.postSlug.length) ? data.postSlug : 'slug') + ']')
				)
			)
		);
	};

	document.addEventListener('DOMContentLoaded', () => {
		const rootElement = document.getElementById('teqb-builder-app');
		if (!rootElement) {
			return;
		}

		if (typeof wp.element.createRoot === 'function') {
			wp.element.createRoot(rootElement).render(el(App));
		} else if (typeof wp.element.render === 'function') {
			wp.element.render(el(App), rootElement);
		}
	});
})(window.wp);
