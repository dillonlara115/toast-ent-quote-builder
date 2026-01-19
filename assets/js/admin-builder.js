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
		FormTokenField,
		Notice
	} = wp.components;

	const data = window.teqbBuilderAdmin || {};

	const defaults = data.defaults || {};
	const initialConfig = data.config || {};
	const cptData = data.cptData || { allServices: [], locations: [] };

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
		selectedServices: [],
		selectedPackages: [],
		selectedAddons: [],
		location: '',
		skipPackages: false, // If true, skip package selection and go directly to add-ons
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
				},
				combo_perks: {
					label: __('Combo Perk', 'teqb'),
					pluralLabel: __('Combo Perks', 'teqb'),
					optionsLabel: __('Enhancement Options:', 'teqb'),
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

	// Ensure arrays are initialized even if initialConfig has old format
	const normalizedInitialConfig = {
		...initialConfig,
		selectedServices: Array.isArray(initialConfig.selectedServices) ? initialConfig.selectedServices : [],
		selectedPackages: Array.isArray(initialConfig.selectedPackages) 
			? initialConfig.selectedPackages.map(p => ({
				...p,
				// Clean up: if price_override is 0, null, or empty string, set to null
				price_override: (p.price_override && p.price_override !== '' && p.price_override !== 0) 
					? parseFloat(p.price_override) 
					: null
			}))
			: [],
		selectedAddons: Array.isArray(initialConfig.selectedAddons)
			? initialConfig.selectedAddons.map(a => ({
				...a,
				// Clean up: if price_override is 0, null, or empty string, set to null
				price_override: (a.price_override && a.price_override !== '' && a.price_override !== 0)
					? parseFloat(a.price_override)
					: null
			}))
			: [],
		location: initialConfig.location || '',
	};
	
	const initialState = deepMerge(defaultConfig, normalizedInitialConfig);

	const createEmptyService = () => ({
		id: '',
		label: '',
		subtitle: '',
		paragraphs: [],
		features: [],
		packages: [],
		addons: []
	});

	const createEmptyPackage = () => ({
		id: '',
		name: '',
		price: '',
		includes: [],
		bonusOptions: [],
		bonusLimit: '',
		bundledServices: []
	});

	const createEmptyAddon = () => ({
		id: '',
		name: '',
		price: '',
		base: '',
		unit: '',
		min: '',
		options: [],
		extras: {}
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
		{ value: 'luxury_enhancement', label: __('Luxury Enhancement', 'teqb') },
		{ value: 'combo_perks', label: __('Combo Perks', 'teqb') }
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

	const createEmptyBundledService = () => ({
		serviceId: '',
		packageId: '',
		upgradePackages: [],
		message: '',
		removalMessage: '',
		upgradeHint: '',
		infoTitle: '',
		infoDescription: '',
		infoLink: ''
	});

	const BundledServiceEditor = ({ bundledService, onChange, onRemove, allServices }) => {
		const handleUpdate = (field, fieldValue) => {
			const next = { ...bundledService, [field]: fieldValue };
			onChange(next);
		};

		// Get available service IDs from allServices
		const serviceOptions = allServices.map((svc) => ({
			value: svc.id || '',
			label: svc.label || svc.id || __('Select service', 'teqb')
		}));

		// Get packages for the selected service
		const selectedService = allServices.find((svc) => svc.id === bundledService.serviceId);
		const packageOptions = selectedService && Array.isArray(selectedService.packages)
			? selectedService.packages.map((pkg) => ({
				value: pkg.id || '',
				label: pkg.name || pkg.id || __('Select package', 'teqb')
			}))
			: [];

		const upgradePackagesValue = Array.isArray(bundledService.upgradePackages)
			? bundledService.upgradePackages
			: [];
		const upgradeSuggestions = selectedService && Array.isArray(selectedService.packages)
			? selectedService.packages
				.map((pkg) => pkg.id || '')
				.filter(Boolean)
			: [];

		return el('div', { className: 'teqb-nested-card', style: { border: '1px solid #ddd', padding: '12px', marginBottom: '12px' } },
			el('h4', { style: { marginTop: 0 } }, __('Bundled Service', 'teqb')),
			el('div', { className: 'teqb-nested-grid' },
				el(SelectControl, {
					label: __('Service ID', 'teqb'),
					help: __('The service ID that is bundled with this package.', 'teqb'),
					value: bundledService.serviceId || '',
					options: [{ value: '', label: __('Select a service', 'teqb') }, ...serviceOptions],
					onChange: (value) => {
						const next = { ...bundledService, serviceId: value, packageId: '', upgradePackages: [] };
						onChange(next);
					}
				}),
				el(SelectControl, {
					label: __('Package ID', 'teqb'),
					help: __('The default package included for the bundled service.', 'teqb'),
					value: bundledService.packageId || '',
					options: [{ value: '', label: __('Select a package', 'teqb') }, ...packageOptions],
					onChange: (value) => handleUpdate('packageId', value),
					disabled: !bundledService.serviceId
				})
			),
			el(FormTokenField, {
				label: __('Upgradeable Packages', 'teqb'),
				help: __('Pick which packages can replace the bundled option (type to add custom IDs).', 'teqb'),
				value: upgradePackagesValue,
				suggestions: upgradeSuggestions,
				disabled: !bundledService.serviceId,
				onChange: (tokens) => {
					const formatted = Array.isArray(tokens)
						? tokens.map((token) => (token || '').trim()).filter(Boolean)
						: [];
					handleUpdate('upgradePackages', formatted);
				},
				placeholder: upgradeSuggestions.length
					? __('Start typing a package ID…', 'teqb')
					: __('Select a service to choose packages…', 'teqb')
			}),
			el(TextControl, {
				label: __('Message', 'teqb'),
				help: __('Message shown when this bundled service is detected.', 'teqb'),
				value: bundledService.message || '',
				onChange: (value) => handleUpdate('message', value)
			}),
			el(TextareaControl, {
				label: __('Removal Message', 'teqb'),
				help: __('Message shown when the bundled service is removed from selections.', 'teqb'),
				value: bundledService.removalMessage || '',
				onChange: (value) => handleUpdate('removalMessage', value)
			}),
			el(TextControl, {
				label: __('Upgrade Hint', 'teqb'),
				help: __('Hint text about available upgrades.', 'teqb'),
				value: bundledService.upgradeHint || '',
				onChange: (value) => handleUpdate('upgradeHint', value)
			}),
			el(TextControl, {
				label: __('Info Title', 'teqb'),
				help: __('Title for the bundled service info display.', 'teqb'),
				value: bundledService.infoTitle || '',
				onChange: (value) => handleUpdate('infoTitle', value)
			}),
			el(TextareaControl, {
				label: __('Info Description', 'teqb'),
				help: __('Description text for the bundled service.', 'teqb'),
				value: bundledService.infoDescription || '',
				onChange: (value) => handleUpdate('infoDescription', value)
			}),
			el(TextControl, {
				label: __('Info Link', 'teqb'),
				help: __('Optional link URL for more information.', 'teqb'),
				value: bundledService.infoLink || '',
				onChange: (value) => handleUpdate('infoLink', value)
			}),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, {
					variant: 'secondary',
					onClick: () => onRemove()
				}, __('Remove Bundled Service', 'teqb'))
			)
		);
	};

	const ExtrasEditor = ({ extras, onChange }) => {
		const extrasArray = extras && typeof extras === 'object'
			? Object.entries(extras).map(([key, value]) => ({ key, value }))
			: [];

		const handleExtrasChange = (newExtras) => {
			const extrasObj = {};
			newExtras.forEach((item) => {
				if (item.key && item.key.trim()) {
					extrasObj[item.key.trim()] = parseFloat(item.value) || 0;
				}
			});
			onChange(extrasObj);
		};

		return el('div', { className: 'teqb-nested-card', style: { border: '1px solid #ddd', padding: '12px', marginTop: '12px' } },
			el('h4', { style: { marginTop: 0 } }, __('Extras (Additional Options)', 'teqb')),
			el('p', { style: { fontSize: '13px', color: '#666' } },
				__('Add additional options for this add-on (e.g., "Blast" option for Cold Sparks).', 'teqb')
			),
			extrasArray.map((item, idx) =>
				el('div', { key: `extra-${idx}`, className: 'teqb-nested-grid', style: { marginBottom: '8px' } },
					el(TextControl, {
						label: __('Option Name', 'teqb'),
						placeholder: __('e.g., Blast', 'teqb'),
						value: item.key || '',
						onChange: (value) => {
							const updated = extrasArray.slice();
							updated[idx] = { ...item, key: value };
							handleExtrasChange(updated);
						}
					}),
					el(TextControl, {
						label: __('Price', 'teqb'),
						type: 'number',
						placeholder: __('e.g., 200', 'teqb'),
						value: item.value || '',
						onChange: (value) => {
							const updated = extrasArray.slice();
							updated[idx] = { ...item, value: value.replace(/[^\d.]/g, '') };
							handleExtrasChange(updated);
						}
					}),
					el('div', { style: { display: 'flex', alignItems: 'flex-end' } },
						el(Button, {
							variant: 'secondary',
							onClick: () => {
								const updated = extrasArray.slice();
								updated.splice(idx, 1);
								handleExtrasChange(updated);
							}
						}, __('Remove', 'teqb'))
					)
				)
			),
			el(Button, {
				variant: 'secondary',
				onClick: () => {
					const updated = extrasArray.slice();
					updated.push({ key: '', value: '' });
					handleExtrasChange(updated);
				}
			}, __('Add Extra Option', 'teqb'))
		);
	};

	const PackageEditor = ({ pkg, onChange, onRemove, allServices }) => {
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

		const bundledServices = Array.isArray(pkg.bundledServices) ? pkg.bundledServices : [];

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
			el('div', { className: 'teqb-admin-multi' },
				el('h4', null, __('Bundled Services', 'teqb')),
				el('p', { style: { fontSize: '13px', color: '#666', marginBottom: '12px' } },
					__('Services that are automatically included with this package.', 'teqb')
				),
				bundledServices.map((bundled, idx) =>
					el(BundledServiceEditor, {
						key: `bundled-${idx}`,
						bundledService: bundled,
						allServices: allServices || [],
						onChange: (next) => {
							const nextBundled = bundledServices.slice();
							nextBundled[idx] = next;
							onChange({ ...pkg, bundledServices: nextBundled });
						},
						onRemove: () => {
							const nextBundled = bundledServices.slice();
							nextBundled.splice(idx, 1);
							onChange({ ...pkg, bundledServices: nextBundled });
						}
					})
				),
				el(Button, {
					variant: 'secondary',
					onClick: () => {
						const nextBundled = bundledServices.slice();
						nextBundled.push(createEmptyBundledService());
						onChange({ ...pkg, bundledServices: nextBundled });
					}
				}, __('Add Bundled Service', 'teqb'))
			),
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
			ExtrasEditor({
				extras: addon.extras || {},
				onChange: (extrasObj) => onChange({ ...addon, extras: extrasObj })
			}),
			el('div', { className: 'teqb-inline-actions' },
				el(Button, {
					variant: 'secondary',
					onClick: () => onRemove()
				}, __('Remove Add-on', 'teqb'))
			)
		);
	};

	const ServiceEditor = ({ service, onChange, onRemove, allServices }) => {
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
			el('div', { className: 'teqb-admin-multi' },
				el('h3', null, __('Packages', 'teqb')),
				packages.map((pkg, idx) =>
					el(PackageEditor, {
						key: `pkg-${idx}`,
						pkg,
						allServices: allServices || [],
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

	const ServicesSection = ({ config, onChange, cptData }) => {
		const allServices = cptData.allServices || [];
		const locations = cptData.locations || [];
		const selectedServiceIds = config.selectedServices || [];
		const selectedPackages = config.selectedPackages || [];
		const selectedAddons = config.selectedAddons || [];
		const locationFilter = config.location || '';
		
		// Filter services by location if specified
		const availableServices = locationFilter
			? allServices.filter(service => {
				// Check if service has packages/addons for this location
				const hasPackages = service.packages && service.packages.length > 0;
				const hasAddons = service.addons && service.addons.length > 0;
				return hasPackages || hasAddons;
			})
			: allServices;
		
		const toggleService = (servicePostId) => {
			const newSelected = selectedServiceIds.includes(servicePostId)
				? selectedServiceIds.filter(id => id !== servicePostId)
				: [...selectedServiceIds, servicePostId];
			onChange({ ...config, selectedServices: newSelected });
		};
		
		const togglePackage = (packagePostId, basePrice) => {
			const existing = selectedPackages.find(p => p.post_id === packagePostId);
			const newSelected = existing
				? selectedPackages.filter(p => p.post_id !== packagePostId)
				: [...selectedPackages, { post_id: packagePostId, price_override: null }];
			onChange({ ...config, selectedPackages: newSelected });
		};
		
		const toggleAddon = (addonPostId) => {
			const existing = selectedAddons.find(a => a.post_id === addonPostId);
			const newSelected = existing
				? selectedAddons.filter(a => a.post_id !== addonPostId)
				: [...selectedAddons, { post_id: addonPostId, price_override: null }];
			onChange({ ...config, selectedAddons: newSelected });
		};
		
		const updatePackagePriceOverride = (packagePostId, priceOverride) => {
			const newSelected = selectedPackages.map(p => 
				p.post_id === packagePostId
					? { ...p, price_override: (priceOverride && priceOverride !== '' && !isNaN(parseFloat(priceOverride))) ? parseFloat(priceOverride) : null }
					: p
			);
			onChange({ ...config, selectedPackages: newSelected });
		};
		
		const updateAddonPriceOverride = (addonPostId, priceOverride) => {
			const newSelected = selectedAddons.map(a => 
				a.post_id === addonPostId
					? { ...a, price_override: (priceOverride && priceOverride !== '' && !isNaN(parseFloat(priceOverride))) ? parseFloat(priceOverride) : null }
					: a
			);
			onChange({ ...config, selectedAddons: newSelected });
		};
		
		const getPackageOverride = (packagePostId) => {
			const found = selectedPackages.find(p => p.post_id === packagePostId);
			return found ? found.price_override : null;
		};
		
		const getAddonOverride = (addonPostId) => {
			const found = selectedAddons.find(a => a.post_id === addonPostId);
			return found ? found.price_override : null;
		};
		
		const isPackageSelected = (packagePostId) => {
			return selectedPackages.some(p => p.post_id === packagePostId);
		};
		
		const isAddonSelected = (addonPostId) => {
			return selectedAddons.some(a => a.post_id === addonPostId);
		};
		
		return el(Section, {
			title: __('Services, Packages & Add-ons', 'teqb'),
			description: __('Select services, packages, and add-ons from your catalog. You can override pricing per builder.', 'teqb')
		},
			locations.length > 0 ? el(SelectControl, {
				label: __('Filter by Location', 'teqb'),
				help: __('Filter available services by location. Leave blank to show all.', 'teqb'),
				value: locationFilter,
				options: [
					{ label: __('All Locations', 'teqb'), value: '' },
					...locations.map(loc => ({ label: loc.name, value: loc.slug }))
				],
				onChange: (value) => onChange({ ...config, location: value })
			}) : null,
			
			availableServices.length === 0 ? el(Notice, {
				status: 'warning'
			}, __('No services found. Please create services, packages, and add-ons first.', 'teqb')) : null,
			
			el('div', { className: 'teqb-cpt-services-list', style: { marginTop: '20px' } },
				availableServices.map(service => {
					const isServiceSelected = selectedServiceIds.includes(service.post_id);
					const servicePackages = service.packages || [];
					const serviceAddons = service.addons || [];
					
					return el('div', {
						key: `service-${service.post_id}`,
						className: 'teqb-cpt-service-card',
						style: {
							border: '1px solid #ddd',
							borderRadius: '8px',
							padding: '16px',
							marginBottom: '16px',
							backgroundColor: isServiceSelected ? '#f0f9ff' : '#fff'
						}
					},
						el('div', { style: { display: 'flex', alignItems: 'center', marginBottom: '12px' } },
							el(CheckboxControl, {
								checked: isServiceSelected,
								onChange: () => toggleService(service.post_id),
								label: '' // Add empty label to prevent WordPress from rendering differently
							}),
							el('div', { style: { marginLeft: '12px', flex: 1 } },
								el('h3', { style: { margin: 0 } }, service.label),
								service.subtitle ? el('p', { style: { margin: '4px 0 0', color: '#666' } }, service.subtitle) : null
							)
						),
						
						isServiceSelected ? el('div', { style: { marginLeft: '32px', marginTop: '16px' } },
							servicePackages.length > 0 ? el('div', { style: { marginBottom: '20px' } },
								el('h4', { style: { marginBottom: '12px' } }, __('Packages', 'teqb')),
								servicePackages.map(pkg => {
									const pkgSelected = isPackageSelected(pkg.post_id);
									const priceOverride = getPackageOverride(pkg.post_id);
									const basePrice = parseFloat(pkg.price) || 0;
									const displayPriceNum = priceOverride !== null && priceOverride !== undefined 
										? (parseFloat(priceOverride) || 0) 
										: basePrice;
									const displayPrice = typeof displayPriceNum === 'number' && !isNaN(displayPriceNum) ? displayPriceNum : 0;
									
									return el('div', {
										key: `pkg-${pkg.post_id}`,
										style: {
											border: '1px solid #e5e7eb',
											borderRadius: '4px',
											padding: '12px',
											marginBottom: '8px',
											backgroundColor: pkgSelected ? '#f9fafb' : '#fff'
										}
									},
										el('div', { style: { display: 'flex', alignItems: 'center', gap: '12px' } },
											el(CheckboxControl, {
												checked: pkgSelected,
												onChange: () => togglePackage(pkg.post_id, basePrice),
												label: '' // Add empty label to prevent WordPress from rendering differently
											}),
											el('div', { style: { flex: 1 } },
												el('strong', null, pkg.name),
												el('span', { style: { marginLeft: '8px', color: '#666' } },
													`$${basePrice.toFixed(2)}`
												)
											),
											pkgSelected ? el(TextControl, {
												type: 'number',
												label: __('Price Override', 'teqb'),
												help: __('Leave blank to use default price', 'teqb'),
												value: priceOverride !== null && priceOverride !== undefined ? String(priceOverride) : '',
												onChange: (value) => updatePackagePriceOverride(pkg.post_id, value),
												style: { width: '150px' }
											}) : null
										),
										pkgSelected && priceOverride !== null && priceOverride !== undefined ? el('p', {
											style: { margin: '4px 0 0 32px', fontSize: '12px', color: '#059669' }
										}, `Using override: $${displayPrice.toFixed(2)}`) : null
									);
								})
							) : null,
							
							serviceAddons.length > 0 ? el('div', null,
								el('h4', { style: { marginBottom: '12px' } }, __('Add-ons', 'teqb')),
								serviceAddons.map(addon => {
									const addonSelected = isAddonSelected(addon.post_id);
									const priceOverride = getAddonOverride(addon.post_id);
									const basePrice = parseFloat(addon.price || addon.base || 0) || 0;
									const displayPriceNum = priceOverride !== null && priceOverride !== undefined 
										? (parseFloat(priceOverride) || 0) 
										: basePrice;
									const displayPrice = typeof displayPriceNum === 'number' && !isNaN(displayPriceNum) ? displayPriceNum : 0;
									
									return el('div', {
										key: `addon-${addon.post_id}`,
										style: {
											border: '1px solid #e5e7eb',
											borderRadius: '4px',
											padding: '12px',
											marginBottom: '8px',
											backgroundColor: addonSelected ? '#f9fafb' : '#fff'
										}
									},
										el('div', { style: { display: 'flex', alignItems: 'center', gap: '12px' } },
											el(CheckboxControl, {
												checked: addonSelected,
												onChange: () => toggleAddon(addon.post_id),
												label: '' // Add empty label to prevent WordPress from rendering differently
											}),
											el('div', { style: { flex: 1 } },
												el('strong', null, addon.name),
												el('span', { style: { marginLeft: '8px', color: '#666' } },
													addon.base && addon.unit
														? `$${basePrice.toFixed(2)}/${addon.unit}`
														: `$${basePrice.toFixed(2)}`
												)
											),
											addonSelected ? el(TextControl, {
												type: 'number',
												label: __('Price Override', 'teqb'),
												help: __('Leave blank to use default price', 'teqb'),
												value: priceOverride !== null && priceOverride !== undefined ? String(priceOverride) : '',
												onChange: (value) => updateAddonPriceOverride(addon.post_id, value),
												style: { width: '150px' }
											}) : null
										),
										addonSelected && priceOverride !== null && priceOverride !== undefined ? el('p', {
											style: { margin: '4px 0 0 32px', fontSize: '12px', color: '#059669' }
										}, `Using override: $${displayPrice.toFixed(2)}`) : null
									);
								})
							) : null
						) : null
					);
				})
			)
		);
	};

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

		const bundles = config.bundles || {};
		const form = config.form || {};
		const notifications = config.notifications || {};

		return el('div', { className: 'teqb-builder-admin' },
			el(Notice, {
				status: 'info',
				isDismissible: false
			}, __('Select services, packages, and add-ons from your catalog. Configure bundle discounts and form settings below.', 'teqb')),
			ServicesSection({
				config,
				onChange: (next) => setConfig(next),
				cptData: cptData
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
