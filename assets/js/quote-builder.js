/**
 * Toast Entertainment Quote Builder
 * Alpine.js component for multi-service quoting with combo discounts.
 */

(function () {
    const quoteData = {
       
        
        djmc: {
            label: 'DJ / MC',
            packages: [
                {
                    id: 'essential_experience',
                    name: 'Essential Experience',
                    price: 795,
                    includes: ['4 hours of entertainment', 'Ceremony mic']
                },
                {
                    id: 'platinum_experience',
                    name: 'Platinum Experience',
                    price: 1395,
                    includes: ['Ceremony to midnight', '10 LED uplights', 'Ceremony mic']
                },
                {
                    id: 'diamond_combo',
                    name: 'Diamond Combo "Celebration"',
                    price: 1995,
                    includes: [
                        'Ceremony to midnight',
                        '10 LED uplights',
                        'Ceremony mic',
                        'Ultimate Photo Booth'
                    ]
                },
                {
                    id: 'diamond_deluxe',
                    name: 'Diamond Deluxe "Simply the Best"',
                    price: 2995,
                    includes: [
                        'Ceremony to midnight',
                        '10 LED uplights',
                        'Ceremony mic',
                        'Ultimate Photo Booth',
                        'Choice of 2 luxury enhancements'
                    ],
                    bonusOptions: [
                        'Cold Spark Fountains',
                        'Dancing on a Cloud',
                        'Monogram Projection',
                        'LOVE Marquee Letters'
                    ],
                    bonusLimit: 2
                }
            ],
            addons: [
                { id: 'extra_hour', name: 'Extra Hour', base: 200, unit: 'hour' },
                { id: 'lapel_mic', name: 'Lapel Microphone', price: 95 },
                { id: 'cold_sparks', name: 'Cold Spark Fountains', base: 595, extras: { Blast: 200 } },
                { id: 'cloud', name: 'Dancing on a Cloud', price: 595 },
                { id: 'uplighting', name: 'Uplighting', price: 395 },
                { id: 'monogram', name: 'Monogram Projection', price: 595 },
                { id: 'mashup', name: 'Custom Mashup', price: 95 },
                { id: 'karaoke', name: 'Karaoke Experience', price: 595 },
                { id: 'guestbook', name: 'Audio Guestbook Phone', price: 295 },
                { id: 'glow', name: 'Glow Sticks', price: 295 },
                { id: 'letters', name: 'Marquee Letters', base: 150, min: 4, unit: 'letter' },
                { id: 'tv_booth', name: 'TV Booth', price: 795 },
                { id: 'tower_booth', name: 'Tower DJ Booth', price: 795 },
                { id: 'request_dj', name: 'Request Specific DJ', price: 200 },
                { id: 'reservation', name: 'Special Reservation', price: 200 }
            ]
        },
        photography: {
            label: 'Photography',
            packages: [
                {
                    id: 'picture_perfect',
                    name: 'Picture Perfect',
                    price: 1195,
                    includes: ['4 hours of coverage']
                },
                {
                    id: 'from_this_moment',
                    name: 'From This Moment On',
                    price: 1995,
                    includes: ['5 hours of coverage', 'Engagement or Bridal Session ($495 value)']
                },
                {
                    id: 'unforgettable',
                    name: 'Unforgettable',
                    price: 3495,
                    includes: [
                        '8 hours of coverage',
                        'Engagement or Bridal Session ($495 value)',
                        'Additional photographer'
                    ]
                },
                {
                    id: 'miss_a_thing',
                    name: 'I Do Not Want to Miss a Thing',
                    price: 4995,
                    includes: [
                        'Full-day coverage',
                        'Engagement or Bridal Session',
                        'Additional photographer',
                        'Priority editing',
                        'Luxury Wedding Album ($695 value)'
                    ]
                }
            ],
            addons: [
                { id: 'bridal_session', name: 'Bridal or Engagement Photo Session', price: 445 },
                { id: 'expedited_editing', name: 'Expedited Editing', price: 300 },
                { id: 'lead_extra_hour', name: 'Lead Photographer Extra Hour', base: 300, unit: 'hour' },
                { id: 'assistant_photographer', name: 'Assistant Photographer', base: 125, unit: 'hour', min: 4 },
                { id: 'specific_photographer', name: 'Request Specific Photographer', price: 200 }
            ]
        },
        videography: {
            label: 'Videography',
            packages: [
                {
                    id: 'love_story',
                    name: 'Love Story',
                    price: 1495,
                    includes: ['4 hours', '4-6 min highlight film', '10-20 min extended film']
                },
                {
                    id: 'come_fly',
                    name: 'Come Fly With Me',
                    price: 2195,
                    includes: [
                        '6 hours',
                        '5-7 min highlight film',
                        '20-30 min extended film',
                        'Drone footage'
                    ]
                },
                {
                    id: 'endless_love',
                    name: 'Endless Love',
                    price: 3995,
                    includes: [
                        'Full-day coverage',
                        '10-15 min highlight film',
                        '60-90 min extended film',
                        'Drone footage',
                        'Pro audio recording'
                    ]
                }
            ],
            addons: [
                { id: 'extra_hours', name: 'Additional Hours', base: 350, unit: 'hour' },
                { id: 'raw_pre', name: 'Raw Footage (Pre-event)', price: 300 },
                { id: 'raw_post', name: 'Raw Footage (Post-event)', price: 500 },
                { id: 'drone', name: 'Drone Coverage', price: 200 },
                { id: 'second_videographer', name: 'Second Videographer', base: 150, unit: 'hour', min: 4 },
                { id: 'love_story_film', name: 'Love Story Film', price: 495 },
                { id: 'specific_videographer', name: 'Request Specific Videographer', price: 200 },
                { id: 'custom_editing', name: 'Custom Editing Services', price: 250 }
            ]
        },
        coordination: {
            label: 'Coordination',
            packages: [
                {
                    id: 'essential_4month',
                    name: 'Essential 4-Month Coordination - Can\'t Stop the Feeling',
                    price: 1995,
                    includes: ['Up to 8 hours of event-day coverage']
                },
                {
                    id: 'unlimited_4month',
                    name: 'Unlimited 4-Month Coordination - All You Need Is Love',
                    price: 2495,
                    includes: ['Unlimited event-day coverage']
                },
                {
                    id: 'ultimate_weekend',
                    name: '4-Month Ultimate Weekend - Best Day of My Life',
                    price: 2995,
                    includes: ['Unlimited event-day coverage', 'Rehearsal coverage']
                }
            ],
            addons: [
                { id: 'coord_extra_hours', name: 'Additional Hours', base: 200, unit: 'hour' },
                { id: 'rehearsal', name: 'Rehearsal Coverage', price: 500 },
                { id: 'specific_coordinator', name: 'Request Specific Coordinator', price: 200 }
            ]
        },
        photobooth: {
            label: 'Photo Booth',
            packages: [
                {
                    id: 'strike_a_pose',
                    name: 'The Ultimate Photo Booth - "Strike a Pose"',
                    price: 745,
                    includes: [
                        'Instant prints',
                        'Choice of 4 premium backdrops',
                        'Sleek open-air booth design'
                    ]
                },
                {
                    id: 'all_around_the_world',
                    name: 'The 360 Experience - "All Around the World"',
                    price: 895,
                    includes: [
                        '360 video booth setup',
                        'Instant video sharing',
                        'High-quality lighting and slow-motion effects'
                    ]
                },
                {
                    id: 'mirror_mirror',
                    name: 'Magic Mirror Booth Experience - "Mirror, Mirror"',
                    price: 1095,
                    includes: [
                        'Interactive full-length mirror booth',
                        'Instant prints',
                        'Choice of 4 premium backdrops',
                        'On-screen signing and emoji features'
                    ]
                }
            ],
            addons: [
                { id: 'extra_hour', name: 'Additional Hour', base: 100, unit: 'hour' },
                { id: 'guest_album', name: 'Photo Booth Guest Album', price: 125 },
                { id: 'photo_strip', name: 'Custom Photo Strip Template', price: 100 },
                {
                    id: 'upgraded_backdrops',
                    name: 'Upgraded Backdrops',
                    price: 495,
                    options: ['Greenery Wall', 'Flower Wall', 'Custom Backdrop']
                }
            ]
        }
    };

    const comboRules = [
        { services: ['djmc', 'photography'], type: 'percent', value: 0.05, label: 'DJ + Photography Combo (5% off)' },
        { services: ['djmc', 'photography', 'videography'], type: 'percent', value: 0.1, label: 'Dream Wedding Combo (10% off)' },
        { services: ['djmc', 'photography', 'photobooth'], type: 'flat', value: 150, label: 'DJ + Photo Booth Combo ($150 off)' },
        { services: ['djmc', 'photography', 'photobooth', 'coordination'], type: 'percent', value: 0.15, label: 'Fairy Tale Combo (15% off)' }
    ];

    const formatCurrencyValue = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount || 0);
    };

    const createInitialSelections = () => {
        const selections = {};
        Object.keys(quoteData).forEach((serviceId) => {
            selections[serviceId] = {
                selectedPackage: '',
                selectedBonuses: [],
                addOns: {}
            };
        });
        return selections;
    };

    const defaultFormData = () => ({
        name: '',
        email: '',
        phone: '',
        eventDate: '',
        eventType: '',
        guests: '',
        message: ''
    });

    const serviceContent = {
        djmc: {
            title: 'DJ/MC Packages',
            subtitle: 'Crafting Unforgettable Celebrations',
            paragraphs: [
                'Our DJs are true artists, blending live mixing with flawless hosting to keep your night flowing and your dance floor full. Whether it’s the subtle background during dinner or the all-out energy of the last song, we read the room and adapt to every moment.'
            ],
            quote: {
                text: 'From the moment the music started, the dance floor was packed and the energy never dropped. He read the room perfectly and made sure every guest had a reason to dance.',
                attribution: 'Jordan Dean'
            },
            featuresTitle: 'Essential services included in ALL Toast DJ/MC Packages:',
            features: [
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
            ]
        },
        photography: {
            title: 'Photography Packages',
            subtitle: 'Capturing the Story of Your Day',
            paragraphs: [
                'Our photographers blend artistry and authenticity to capture both the big moments and the subtle details that tell your story. The result is a collection of images you’ll treasure for a lifetime.'
            ],
            quote: {
                text: 'The pictures came out amazing and our photographer made us feel so comfortable the entire time.',
                attribution: 'Brittany Chapman'
            },
            featuresTitle: 'Essential services included in ALL Toast Photography Packages:',
            features: [
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
            ]
        },
        videography: {
            title: 'Videography Packages',
            subtitle: 'Turning Moments into Motion',
            paragraphs: [
                'We see wedding videography as an art, blending authentic moments with cinematic style for films you’ll want to watch again and again.'
            ],
            quote: {
                text: 'Our video was absolutely perfect. It captured all of the best moments and was edited beautifully.',
                attribution: 'Meghan & Kyle'
            },
            featuresTitle: 'Essential services included in ALL Toast Videography Packages:',
            features: [
                'Professional videographer',
                '1-minute social media highlight film',
                'Multiple cameras',
                'Unlimited consultations',
                'Unlimited locations',
                'Fast turnaround',
                'Full HD digital delivery',
                'Online hosting',
                'No hidden fees – travel within 30 miles included'
            ]
        },
        coordination: {
            title: 'Coordination Packages',
            subtitle: 'Expertly Crafting Your Event',
            paragraphs: [
                'Our coordination team makes sure every detail is perfect, every timeline is on track, and every moment is yours to enjoy.'
            ],
            quote: {
                text: 'Our coordinator was incredible. She made sure everything ran smoothly and on time, and we didn’t have to worry about a single thing.',
                attribution: 'Michelle Ramirez'
            },
            featuresTitle: 'Essential services included in ALL Toast Coordination Packages:',
            features: [
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
            ]
        },
        photobooth: {
            title: 'Photo Booth Packages',
            subtitle: 'Fun That Guests Take Home',
            paragraphs: [
                'Our photo booths are designed for fun — with unlimited prints, awesome props, and instant sharing that guests of all ages love.'
            ],
            quote: {
                text: 'The photo booth was such a hit at our wedding! Everyone had so much fun and the pictures were hilarious.',
                attribution: 'Nicole L.'
            },
            featuresTitle: 'Essential services included in ALL Toast Photo Booth Packages:',
            features: [
                'Unlimited sessions',
                'Professional photo booth operator',
                'Fun & hilarious prop collection',
                'Full digital gallery after the event'
            ]
        }
    };

    const buildAvailableServices = () =>
        Object.entries(quoteData).map(([serviceId, service]) => {
            const packages = Array.isArray(service.packages) ? service.packages : [];
            const priceCandidates = packages
                .map((pkg) => Number(pkg.price))
                .filter((value) => !Number.isNaN(value) && value > 0);
            const startingPrice = priceCandidates.length ? Math.min(...priceCandidates) : 0;
            const description = serviceContent[serviceId] || {};

            return {
                id: serviceId,
                label: service.label,
                startingPrice,
                title: description.title || service.label,
                subtitle: description.subtitle || '',
                paragraphs: Array.isArray(description.paragraphs) ? description.paragraphs : [],
                quote: description.quote || null,
                featuresTitle: description.featuresTitle || '',
                features: Array.isArray(description.features) ? description.features : []
            };
        });

    let isRegistered = false;

    const registerComponent = () => {
        if (isRegistered || typeof Alpine === 'undefined') {
            return;
        }

        Alpine.data('quoteBuilder', () => ({
            currentStep: 1,
            selectedServices: [],
            currentServiceIndex: 0,
            serviceSelections: createInitialSelections(),
            serviceSummaries: {},
            orderedServiceSummaries: [],
            subtotal: 0,
            discount: 0,
            discountLabel: '',
            finalTotal: 0,
            stepError: '',
            serviceProgressCount: 0,
            editingService: false,
            isSubmitting: false,
            submitMessage: '',
            submitSuccess: false,
            formData: defaultFormData(),
            availableServices: [],
            showPricingNotes: false,

            init() {
                this.availableServices = buildAvailableServices();
                this.setEventDateMin();
            },

            get currentServiceId() {
                return this.selectedServices[this.currentServiceIndex] || null;
            },

            get currentServiceLabel() {
                const id = this.currentServiceId;
                return id ? quoteData[id].label : '';
            },

            get currentServiceDisplayIndex() {
                return this.currentServiceIndex + 1;
            },

            get nextServiceLabel() {
                const nextId = this.selectedServices[this.currentServiceIndex + 1];
                return nextId ? quoteData[nextId].label : '';
            },

            get currentServicePackages() {
                const id = this.currentServiceId;
                return id ? quoteData[id].packages : [];
            },

            get currentServiceAddOns() {
                const id = this.currentServiceId;
                return id ? quoteData[id].addons : [];
            },

            get currentPackagePrice() {
                const pkg = this.getSelectedPackageForService(this.currentServiceId);
                return pkg ? pkg.price : 0;
            },

            get currentAddOnLines() {
                return this.buildAddOnLines(this.currentServiceId);
            },

            get currentServiceSubtotal() {
                return this.calculateServiceSubtotal(this.currentServiceId);
            },

            get serviceProgress() {
                return Object.keys(this.serviceSummaries).length;
            },

            get orderedServiceSummaries() {
                return this.selectedServices
                    .map((id) => this.serviceSummaries[id])
                    .filter(Boolean);
            },

            get isContactValid() {
                return (
                    this.formData.name.trim() !== '' &&
                    this.validateEmail(this.formData.email) &&
                    this.formData.phone.trim() !== '' &&
                    this.formData.eventDate !== '' &&
                    this.formData.eventType.trim() !== '' &&
                    this.formData.guests !== ''
                );
            },

            toggleService(serviceId) {
                this.stepError = '';
                const index = this.selectedServices.indexOf(serviceId);
                if (index === -1) {
                    this.selectedServices.push(serviceId);
                    this.recalculateTotals();
                } else {
                    this.selectedServices.splice(index, 1);
                    delete this.serviceSummaries[serviceId];
                    this.serviceSelections[serviceId] = createInitialSelections()[serviceId];
                    this.recalculateTotals();
                }
            },

            resetServices() {
                this.selectedServices = [];
                this.currentServiceIndex = 0;
                this.serviceSelections = createInitialSelections();
                this.serviceSummaries = {};
                this.subtotal = 0;
                this.discount = 0;
                this.discountLabel = '';
                this.finalTotal = 0;
                this.stepError = '';
                this.editingService = false;
                this.currentStep = 1;
                this.recalculateTotals();
            },

            proceedToFirstService() {
                if (!this.selectedServices.length) {
                    this.stepError = 'Please select at least one service to continue.';
                    return;
                }
                this.currentServiceIndex = 0;
                this.currentStep = 2;
                this.stepError = '';
                this.editingService = false;
            },

            packageBonusSelected(packageId, bonus) {
                const serviceSelection = this.serviceSelections[this.currentServiceId];
                return (
                    serviceSelection &&
                    serviceSelection.selectedPackage === packageId &&
                    serviceSelection.selectedBonuses.includes(bonus)
                );
            },

            togglePackageBonus(packageOption, bonus) {
                const serviceSelection = this.serviceSelections[this.currentServiceId];
                if (!serviceSelection || serviceSelection.selectedPackage !== packageOption.id) {
                    return;
                }

                const existing = serviceSelection.selectedBonuses;
                const idx = existing.indexOf(bonus);
                if (idx !== -1) {
                    existing.splice(idx, 1);
                } else if (existing.length < (packageOption.bonusLimit || 0)) {
                    existing.push(bonus);
                }
                this.recalculateTotals();
            },

            selectPackage(packageOption) {
                if (!this.currentServiceId) {
                    return;
                }
                const selection = this.serviceSelections[this.currentServiceId];
                selection.selectedPackage = packageOption.id;
                selection.selectedBonuses = selection.selectedBonuses.slice(0, packageOption.bonusLimit || 0);
                this.stepError = '';
                this.recalculateTotals();
            },

            backToServices() {
                this.currentStep = 1;
                this.stepError = '';
            },

            goToAddOns() {
                const selection = this.serviceSelections[this.currentServiceId];
                if (!selection || !selection.selectedPackage) {
                    this.stepError = 'Please select a package to continue.';
                    return;
                }
                this.currentStep = 3;
                this.stepError = '';
            },

            backToPackages() {
                this.currentStep = 2;
                this.stepError = '';
            },

            describeAddOn(addOn) {
                if (addOn.base && addOn.unit) {
                    const minText = addOn.min ? ` (min ${addOn.min})` : '';
                    return `${this.formatCurrency(addOn.base)} per ${addOn.unit}${minText}`;
                }
                if (addOn.base) {
                    return `${this.formatCurrency(addOn.base)} each`;
                }
                if (addOn.price) {
                    return `${this.formatCurrency(addOn.price)} flat`;
                }
                return 'Custom enhancement';
            },

            getAddOnQuantity(addOnId) {
                const selection = this.serviceSelections[this.currentServiceId];
                if (!selection) {
                    return '';
                }
                const stored = selection.addOns[addOnId];
                return stored && typeof stored.quantity !== 'undefined' ? stored.quantity : '';
            },

            updateAddOnQuantity(addOn, value) {
                const serviceId = this.currentServiceId;
                if (!serviceId) {
                    return;
                }
                const selection = this.serviceSelections[serviceId];
                const min = addOn.min ? Number(addOn.min) : 1;
                const quantity = Math.max(parseFloat(value) || 0, 0);

                if (quantity <= 0) {
                    delete selection.addOns[addOn.id];
                    this.recalculateTotals();
                    return;
                }

                selection.addOns[addOn.id] = selection.addOns[addOn.id] || {
                    extras: {},
                    selectedOption: ''
                };
                selection.addOns[addOn.id].quantity = Math.max(quantity, min);
                this.recalculateTotals();
            },

            toggleFlatAddOn(addOn) {
                const serviceId = this.currentServiceId;
                if (!serviceId) {
                    return;
                }
                const selection = this.serviceSelections[serviceId];
                if (selection.addOns[addOn.id]) {
                    delete selection.addOns[addOn.id];
                } else {
                    selection.addOns[addOn.id] = {
                        quantity: 1,
                        extras: {},
                        selectedOption: ''
                    };
                }
                this.recalculateTotals();
            },

            isAddOnSelected(addOnId) {
                const selection = this.serviceSelections[this.currentServiceId];
                return Boolean(selection && selection.addOns[addOnId]);
            },

            addOnExtraSelected(addOnId, key) {
                const selection = this.serviceSelections[this.currentServiceId];
                return Boolean(
                    selection &&
                    selection.addOns[addOnId] &&
                    selection.addOns[addOnId].extras &&
                    Object.prototype.hasOwnProperty.call(selection.addOns[addOnId].extras, key)
                );
            },

            toggleAddOnExtra(addOn, key, price) {
                const serviceId = this.currentServiceId;
                if (!serviceId) {
                    return;
                }
                const selection = this.serviceSelections[serviceId];
                const entry = selection.addOns[addOn.id] || {
                    quantity: addOn.base ? (addOn.min || 1) : 1,
                    extras: {},
                    selectedOption: ''
                };

                if (entry.extras && Object.prototype.hasOwnProperty.call(entry.extras, key)) {
                    delete entry.extras[key];
                } else {
                    entry.extras[key] = Number(price) || 0;
                }

                selection.addOns[addOn.id] = entry;
                this.recalculateTotals();
            },

            getAddOnOption(addOnId) {
                const selection = this.serviceSelections[this.currentServiceId];
                if (!selection || !selection.addOns[addOnId]) {
                    return '';
                }
                return selection.addOns[addOnId].selectedOption || '';
            },

            setAddOnOption(addOn, value) {
                const serviceId = this.currentServiceId;
                if (!serviceId) {
                    return;
                }
                const selection = this.serviceSelections[serviceId];
                selection.addOns[addOn.id] = selection.addOns[addOn.id] || {
                    quantity: addOn.base ? (addOn.min || 1) : 1,
                    extras: {},
                    selectedOption: ''
                };
                selection.addOns[addOn.id].selectedOption = value;
                this.recalculateTotals();
            },

            getSelectedPackageForService(serviceId) {
                if (!serviceId) {
                    return null;
                }
                const selection = this.serviceSelections[serviceId];
                if (!selection || !selection.selectedPackage) {
                    return null;
                }
                return quoteData[serviceId].packages.find(
                    (pkg) => pkg.id === selection.selectedPackage
                ) || null;
            },

            buildAddOnLines(serviceId) {
                if (!serviceId) {
                    return [];
                }
                const serviceData = quoteData[serviceId];
                const selection = this.serviceSelections[serviceId];
                if (!serviceData || !selection) {
                    return [];
                }

                const lines = [];
                Object.entries(selection.addOns).forEach(([addOnId, stored]) => {
                    const addOnDef = serviceData.addons.find((item) => item.id === addOnId);
                    if (!addOnDef) {
                        return;
                    }

                    const quantity = stored.quantity || (addOnDef.base ? (addOnDef.min || 1) : 1);
                    let total = 0;
                    const detailParts = [];

                    if (addOnDef.price) {
                        total += Number(addOnDef.price);
                    }
                    if (addOnDef.base) {
                        total += Number(addOnDef.base) * quantity;
                        detailParts.push(`${quantity} ${addOnDef.unit || 'unit'}`);
                    }

                    const extras = stored.extras || {};
                    const extrasLabels = Object.keys(extras);
                    if (extrasLabels.length) {
                   const extrasAmount = Object.values(extras).reduce((sum, val) => sum + Number(val || 0), 0);
                   total += extrasAmount;
                   detailParts.push(`Extras: ${extrasLabels.join(', ')}`);
                }

                    if (stored.selectedOption) {
                        detailParts.push(`Option: ${stored.selectedOption}`);
                    }

                    lines.push({
                        id: addOnId,
                        name: addOnDef.name,
                        quantity: addOnDef.base ? quantity : null,
                        unit: addOnDef.base ? (addOnDef.unit || '') : '',
                        price: addOnDef.price || addOnDef.base || 0,
                        total,
                        extras: extrasLabels,
                        options: stored.selectedOption ? [stored.selectedOption] : [],
                        detail: detailParts.length ? detailParts.join(' • ') : null
                    });
                });
                return lines;
            },

            calculateServiceSubtotal(serviceId) {
                if (!serviceId) {
                    return 0;
                }
                const pkg = this.getSelectedPackageForService(serviceId);
                const packagePrice = pkg ? pkg.price : 0;
                const addOnTotal = this.buildAddOnLines(serviceId).reduce((sum, line) => sum + line.total, 0);
                return packagePrice + addOnTotal;
            },

            getServiceSnapshot(serviceId) {
                if (!serviceId || !quoteData[serviceId]) {
                    return null;
                }

                const selection = this.serviceSelections[serviceId];
                if (!selection) {
                    return null;
                }

                const serviceDef = quoteData[serviceId];
                const packageDef = selection.selectedPackage
                    ? serviceDef.packages.find((pkg) => pkg.id === selection.selectedPackage)
                    : null;

                if (!packageDef) {
                    return {
                        serviceId,
                        serviceLabel: serviceDef.label,
                        package: null,
                        addOns: [],
                        subtotal: 0,
                        inProgress: true
                    };
                }

                const addOns = this.buildAddOnLines(serviceId);
                const subtotal = this.calculateServiceSubtotal(serviceId);

                return {
                    serviceId,
                    serviceLabel: serviceDef.label,
                    package: {
                        id: packageDef.id,
                        name: packageDef.name,
                        price: packageDef.price,
                        includes: Array.isArray(packageDef.includes) ? packageDef.includes.slice() : [],
                        bonuses: selection.selectedBonuses.slice()
                    },
                    addOns,
                    subtotal,
                    inProgress: !this.serviceSummaries[serviceId]
                };
            },

            completeService() {
                const serviceId = this.currentServiceId;
                if (!serviceId) {
                    return;
                }
                const selection = this.serviceSelections[serviceId];
                if (!selection.selectedPackage) {
                    this.stepError = 'Please choose a package to continue.';
                    return;
                }

                const snapshot = this.getServiceSnapshot(serviceId);
                if (snapshot) {
                    snapshot.inProgress = false;
                    this.serviceSummaries[serviceId] = snapshot;
                }

                this.recalculateTotals();

                if (this.editingService) {
                    this.editingService = false;
                    this.currentStep = 4;
                    this.stepError = '';
                    return;
                }

                if (this.currentServiceIndex < this.selectedServices.length - 1) {
                    this.currentServiceIndex++;
                    this.currentStep = 2;
                } else {
                    this.finishQuote();
                }

                this.stepError = '';
            },

            finishQuote() {
                this.recalculateTotals();
                this.editingService = false;
                this.currentStep = 4;
            },

            recalculateTotals() {
                this.orderedServiceSummaries = this.selectedServices
                    .map((id) => this.getServiceSnapshot(id))
                    .filter((snapshot) => snapshot !== null);

                this.subtotal = this.orderedServiceSummaries.reduce((sum, snapshot) => {
                    return sum + (snapshot.package ? snapshot.subtotal : 0);
                }, 0);

                this.serviceProgressCount = this.orderedServiceSummaries.filter(
                    (snapshot) => snapshot.package
                ).length;

                this.calculateDiscount();
                this.finalTotal = Math.max(this.subtotal - this.discount, 0);
            },

            calculateDiscount() {
                let best = { amount: 0, label: '' };
                const selected = this.selectedServices;

                comboRules.forEach((rule) => {
                    const matches = rule.services.every((svc) => selected.includes(svc));
                    if (!matches) {
                        return;
                    }
                    let amount = 0;
                    if (rule.type === 'percent') {
                        amount = this.subtotal * rule.value;
                    } else if (rule.type === 'flat') {
                        amount = rule.value;
                    }
                    if (amount > best.amount) {
                        best = { amount, label: rule.label };
                    }
                });

                this.discount = best.amount;
                this.discountLabel = best.label;
            },

            editService(serviceId) {
                const index = this.selectedServices.indexOf(serviceId);
                if (index === -1) {
                    return;
                }
                this.editingService = true;
                if (this.serviceSummaries[serviceId]) {
                    delete this.serviceSummaries[serviceId];
                }
                this.currentServiceIndex = index;
                this.currentStep = 2;
                this.stepError = '';
                this.recalculateTotals();
            },

            returnToLastService() {
                if (!this.selectedServices.length) {
                    return;
                }
                this.editingService = true;
                this.currentServiceIndex = this.selectedServices.length - 1;
                this.currentStep = 3;
                this.stepError = '';
                this.recalculateTotals();
            },

            resetAll() {
                this.currentStep = 1;
                this.selectedServices = [];
                this.currentServiceIndex = 0;
                this.serviceSelections = createInitialSelections();
                this.serviceSummaries = {};
                this.orderedServiceSummaries = [];
                this.subtotal = 0;
                this.discount = 0;
                this.discountLabel = '';
                this.finalTotal = 0;
                this.stepError = '';
                this.editingService = false;
                this.serviceProgressCount = 0;
                this.isSubmitting = false;
                this.submitMessage = '';
                this.submitSuccess = false;
                this.formData = defaultFormData();
                this.setEventDateMin();
                this.recalculateTotals();
            },

            submitForm() {
                if (!this.isContactValid) {
                    this.stepError = 'Please complete the contact information so we can reach you.';
                    return;
                }

                this.stepError = '';
                this.submitMessage = '';
                this.submitSuccess = false;
                this.isSubmitting = true;

                this.recalculateTotals();

                if (typeof quoteBuilderConfig === 'undefined') {
                    console.error('quoteBuilderConfig is not defined.');
                    this.submitSuccess = false;
                    this.submitMessage = 'Configuration error. Please refresh and try again.';
                    this.isSubmitting = false;
                    return;
                }

                const servicePayload = this.orderedServiceSummaries
                    .filter((snapshot) => snapshot && snapshot.package)
                    .map((snapshot) => ({
                        serviceId: snapshot.serviceId,
                        serviceLabel: snapshot.serviceLabel,
                        package: snapshot.package,
                        addOns: snapshot.addOns,
                        subtotal: snapshot.subtotal
                    }));

                const payload = {
                    action: 'submit_quote',
                    nonce: quoteBuilderConfig.nonce,
                    name: this.formData.name,
                    email: this.formData.email,
                    phone: this.formData.phone,
                    event_date: this.formData.eventDate,
                    event_type: this.formData.eventType,
                    guests: this.formData.guests,
                    message: this.formData.message,
                    services: JSON.stringify(servicePayload),
                    subtotal: this.subtotal,
                    discount: this.discount,
                    discount_label: this.discountLabel,
                    final_total: this.finalTotal
                };

                fetch(quoteBuilderConfig.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(payload)
                })
                    .then((response) => response.json())
                    .then((response) => {
                        if (response.success) {
                            this.submitSuccess = true;
                            this.submitMessage = response.data.message;
                            this.currentStep = 5;
                        } else {
                            this.submitSuccess = false;
                            this.submitMessage = response.data.message || 'An error occurred. Please try again.';
                        }
                    })
                    .catch((error) => {
                        console.error('Quote submission failed:', error);
                        this.submitSuccess = false;
                        this.submitMessage = 'An unexpected error occurred. Please try again.';
                    })
                    .finally(() => {
                        this.isSubmitting = false;
                    });
            },

            setEventDateMin() {
                this.$nextTick(() => {
                    const eventDateInput = document.getElementById('event-date');
                    if (eventDateInput) {
                        const today = new Date().toISOString().split('T')[0];
                        eventDateInput.setAttribute('min', today);
                    }
                });
            },

            validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(String(email).toLowerCase());
            },

            formatCurrency(amount) {
                return formatCurrencyValue(amount);
            }
        }));

        isRegistered = true;
    };

    if (typeof document !== 'undefined') {
        document.addEventListener('alpine:init', registerComponent);
        if (typeof window !== 'undefined' && window.Alpine) {
            registerComponent();
        }
    }

     // Function to set primary color based on URL
     function setPrimaryColorFromURL() {
        const url = window.location.href.toLowerCase();
        const root = document.documentElement;
        
        if (url.includes('austin')) {
        console.log(root.style.getPropertyValue('--qb-color'));
            root.style.setProperty('--qb-color', '#bf5700');
        } else if (url.includes('dallas')) {
            root.style.setProperty('--qb-color', '#3286ba');
        } else if (url.includes('houston')) {
            root.style.setProperty('--qb-color', '#b22222');
        } else if (url.includes('san-antonio')) {
            root.style.setProperty('--qb-color', '#708e76');
        } else if (url.includes('little-rock')) {
            root.style.setProperty('--qb-color', '#6e7a1f');
        } else if (url.includes('long-island')) {
            root.style.setProperty('--qb-color', '#008080');
        } else if (url.includes('new-orleans')) {
            root.style.setProperty('--qb-color', '#734c7b');
        } else if (url.includes('texas')) {
            root.style.setProperty('--qb-color', '#c91250');
        } else if (url.includes('washington')) {
            root.style.setProperty('--qb-color', '#4763a5');
        } else if (url.includes('worth')) {
            root.style.setProperty('--qb-color', '#036a3b');
        } else {
            root.style.setProperty('--qb-color', '#eee');
        }

        console.log(root.style.getPropertyValue('--qb-color'));
    }
    
    // Call the function when the page loads
    setPrimaryColorFromURL();
})();