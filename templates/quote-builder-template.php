<?php
/**
 * Quote Builder Template
 *
 * Multi-service quote builder powered by Alpine.js
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div x-data="quoteBuilder()" x-ref="builderRoot" class="toast-quote-builder" x-cloak tabindex="-1">
    <div class="quote-layout">
        <div class="quote-main">
    <!-- Step Indicators -->
    <div class="step-indicators">
        <div class="step-indicator"
             :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }"
             :aria-current="currentStep === 1 ? 'step' : null">
            <div class="step-number">
                <span class="step-index">1</span>
                <svg class="step-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="step-label">
                Services
                <span class="visually-hidden" x-show="currentStep > 1" x-cloak>completed</span>
            </div>
        </div>
        <div class="step-indicator"
             :class="{ 'active': currentStep === 2, 'completed': serviceProgressCount > 0 }"
             :aria-current="currentStep === 2 ? 'step' : null">
            <div class="step-number">
                <span class="step-index">2</span>
                <svg class="step-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="step-label">
                Packages
                <span class="visually-hidden" x-show="serviceProgressCount > 0" x-cloak>completed</span>
            </div>
        </div>
        <div class="step-indicator"
             :class="{ 'active': currentStep === 3 || currentStep === 4, 'completed': serviceProgressCount > 0 && currentStep >= 5 }"
             :aria-current="(currentStep === 3 || currentStep === 4) ? 'step' : null">
            <div class="step-number">
                <span class="step-index">3</span>
                <svg class="step-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="step-label">
                Enhancements &amp; Add-ons
                <span class="visually-hidden" x-show="serviceProgressCount > 0 && currentStep >= 5" x-cloak>completed</span>
            </div>
        </div>
        <div class="step-indicator"
             :class="{ 'active': currentStep >= 5, 'completed': currentStep > 5 }"
             :aria-current="currentStep >= 5 && currentStep < 6 ? 'step' : null">
            <div class="step-number">
                <span class="step-index">4</span>
                <svg class="step-check" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="step-label">
                Review
                <span class="visually-hidden" x-show="currentStep > 5" x-cloak>completed</span>
            </div>
        </div>
    </div>

    <!-- Alert / Helper -->
    <template x-if="stepError">
        <div class="mb-4 px-4 py-3 rounded-md bg-red-50 text-red-700" x-text="stepError"></div>
    </template>

    <!-- Step 1: Service Selection -->
    <div class="form-step" :class="{ 'active': currentStep === 1 }">
        <h2 class="text-2xl font-bold mb-3">What services are you inquiring about?</h2>
        <p class="mb-6 text-gray-600">Choose one or more services to build your perfect celebration.</p>

        <div class="service-cards-stack">
            <template x-for="service in availableServices" :key="service.id">
                <button type="button"
                        class="service-card"
                        :class="{ 'selected': selectedServices.includes(service.id), 'locked': isServiceLocked(service.id) }"
                        :disabled="isServiceLocked(service.id)"
                        :aria-disabled="isServiceLocked(service.id) ? 'true' : null"
                        :title="isServiceLocked(service.id) ? getServiceLockMessage(service.id) : null"
                        @click="toggleService(service.id)">
                    <div class="service-card-banner">
                        <div>
                            <p class="service-card-heading" x-text="service.title"></p>
                            <p class="service-card-subheading" x-text="service.subtitle"></p>
                        </div>
                        <span class="service-starting" x-text="'Starting at ' + formatCurrency(service.startingPrice)"></span>
                    </div>
                    <div class="service-card-body">
                        <div class="service-card-copy">
                            <template x-for="paragraph in service.paragraphs" :key="paragraph">
                                <p x-text="paragraph"></p>
                            </template>
                            <template x-if="service.quote">
                                <blockquote class="service-card-quote">
                                    
                                    <span class="service-card-quote-text" x-text="service.quote.text"></span>
                                    <cite class="service-card-quote-attribution" x-text="service.quote.attribution"></cite>
                                </blockquote>
                            </template>
                        </div>
                        <template x-if="service.features.length">
                            <div class="service-card-features">
                                <h4 x-text="service.featuresTitle"></h4>
                                <ul>
                                    <template x-for="feature in service.features" :key="feature">
                                        <li x-text="feature"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                    <div class="service-card-footer" :class="{ 'locked': isServiceLocked(service.id) }">
                        <span class="select-indicator" :class="{ 'active': selectedServices.includes(service.id) }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <template x-if="isServiceLocked(service.id)">
                            <div class="service-card-locked-note">
                                <span x-text="getServiceLockMessage(service.id)"></span>
                                <span class="service-card-locked-link"
                                      role="button"
                                      tabindex="0"
                                      x-show="hasBundledServiceDetails(service.id)"
                                      @click.stop="openBundledServiceDetails(service.id)"
                                      @keydown.enter.stop.prevent="openBundledServiceDetails(service.id)"
                                      @keydown.space.stop.prevent="openBundledServiceDetails(service.id)">
                                    View included details
                                </span>
                            </div>
                        </template>
                    </div>
                </button>
            </template>
        </div>

        <div class="service-selection-summary mt-4 flex items-center justify-between">
            <p class="text-sm text-gray-600">
                <span class="font-semibold" x-text="selectedServices.length"></span>
                service<span x-text="selectedServices.length === 1 ? '' : 's'"></span> selected
            </p>
            <div class="navigation-buttons">
                <button @click="resetServices"
                        class="  font-medium py-2 px-4 rounded"
                        x-show="selectedServices.length"
                        type="button">
                    Clear Selection
                </button>
                <button @click="proceedToFirstService"
                        class="text-white font-bold py-2 px-4 rounded"
                        style="background-color: var(--qb-color);"
                        :disabled="selectedServices.length === 0"
                        type="button">
                    Next: Choose Packages
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: Package Selection -->
    <div class="form-step" :class="{ 'active': currentStep === 2 }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm  font-semibold uppercase tracking-wide">
                    Service <span x-text="currentServiceDisplayIndex"></span> of <span x-text="selectedServices.length"></span>
                </p>
                <h2 class="text-2xl font-bold">
                    Choose a package for <span x-text="currentServiceLabel"></span>
                </h2>
                <p class="text-gray-600 mt-1">
                    Select the option that best matches your vision. You can always go back to adjust.
                </p>
            </div>
            <div class="text-right text-sm text-gray-500" x-show="selectedServices.length > 1">
                <p>Next service: <span x-text="nextServiceLabel"></span></p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <template x-for="packageOption in currentServicePackages" :key="packageOption.id">
                <div class="package-card"
                     :class="{ 'selected': serviceSelections[currentServiceId].selectedPackage === packageOption.id }"
                     @click="selectPackage(packageOption)">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="package-name" x-text="packageOption.name"></h3>
                            <p class="package-price" x-text="formatCurrency(packageOption.price)"></p>
                        </div>
                    </div>
                    <ul class="package-features">
                        <template x-for="item in packageOption.includes" :key="item">
                            <li>
                                <svg class="h-4 w-4  mr-2" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span x-text="item"></span>
                            </li>
                        </template>
                    </ul>

                    <template x-if="packageOption.bonusOptions">
                        <div class="mt-4">
                            <p class="text-sm font-semibold  mb-2">
                                Choose up to <span x-text="packageOption.bonusLimit"></span> luxury enhancements
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="bonus in packageOption.bonusOptions" :key="bonus">
                                    <button type="button"
                                            class="bonus-chip"
                                            :class="{ 'active': packageBonusSelected(packageOption.id, bonus) }"
                                            @click.stop="togglePackageBonus(packageOption, bonus)">
                                        <span x-text="bonus"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="navigation-buttons mt-6">
            <button @click="backToServices"
                    class="  font-medium py-2 px-4 rounded"
                    type="button">
                Back to Services
            </button>
            <button @click="goToAddOns"
                    class="text-white font-bold py-2 px-4 rounded"
                    style="background-color: var(--qb-color);"
                    :disabled="!serviceSelections[currentServiceId].selectedPackage"
                    type="button">
                <span x-text="currentPackageHasUpgradeFlow ? 'Next: Included Enhancements' : 'Next: Add-ons'"></span>
            </button>
        </div>
    </div>

    <!-- Step 3: Included Enhancements -->
    <div class="form-step" :class="{ 'active': currentStep === 3 }" x-show="upgradeFlowActive">
        <div class="upgrade-step-panel">
            <p class="bundled-details-kicker" x-text="upgradeKickerText"></p>
            <h2 class="bundled-details-title" x-text="upgradeInfoTitle"></h2>
            <p class="bundled-details-subtitle" x-show="upgradeInfoDescription" x-text="upgradeInfoDescription"></p>
            <template x-if="upgradeIncludedPackage">
                <div class="bundled-details-package upgrade-step-package">
                    <p class="bundled-details-price" x-text="formatCurrency(upgradeIncludedPackage.price)"></p>
                    <ul class="bundled-details-list" x-show="upgradeIncludedPackage.includes && upgradeIncludedPackage.includes.length">
                        <template x-for="item in upgradeIncludedPackage.includes" :key="item">
                            <li x-text="item"></li>
                        </template>
                    </ul>
                    <p class="bundled-details-note">Already included at no additional cost.</p>
                </div>
            </template>
            <template x-if="upgradeInfoLink">
                <a class="upgrade-step-link" :href="upgradeInfoLink" target="_blank" rel="noopener noreferrer">
                    Learn more about this experience
                </a>
            </template>
            <template x-if="upgradeUpgradePackages.length">
                <div class="bundled-upgrades upgrade-step-upgrades">
                    <h3 class="bundled-upgrades-title">Explore Upgrade Possibilities</h3>
                    <div class="upgrade-option-grid">
                        <template x-for="upgrade in upgradeUpgradePackages" :key="upgrade.id">
                            <button type="button"
                                    class="upgrade-option-card"
                                    :class="{ 'selected': isUpgradeSelected(upgrade.id) }"
                                    @click="selectUpgradePackage(upgrade)">
                                <div class="upgrade-option-header">
                                    <h5 x-text="upgrade.name"></h5>
                                    <span class="upgrade-option-delta"
                                          x-text="formatCurrency(Math.max(Number(upgrade.price || 0) - (upgradeIncludedPackage ? Number(upgradeIncludedPackage.price || 0) : 0), 0))"></span>
                                </div>
                                <p class="upgrade-option-price" x-text="formatCurrency(upgrade.price)"></p>
                                <ul class="upgrade-option-list" x-show="upgrade.includes && upgrade.includes.length">
                                    <template x-for="item in upgrade.includes" :key="item">
                                        <li x-text="item"></li>
                                    </template>
                                </ul>
                                <span class="upgrade-option-status"
                                      x-text="isUpgradeSelected(upgrade.id) ? 'Selected' : 'Tap to select'"></span>
                            </button>
                        </template>
                    </div>
                    <button type="button"
                            class="upgrade-keep-button"
                            @click="clearUpgradeSelection">
                        Keep the included option
                    </button>
                    <p class="bundled-upgrades-note">Ready for an upgrade? Mention your preferred option when we follow up and we’ll tailor your quote.</p>
                </div>
            </template>
            <div class="upgrade-step-actions">
                <button type="button"
                        class="  font-medium py-2 px-4 rounded"
                        @click="skipUpgradeFlow">
                    No Thanks
                </button>
                <button type="button"
                        class="text-white font-bold py-2 px-4 rounded"
                        style="background-color: var(--qb-color);"
                        @click="completeUpgradeFlow">
                    Continue to Add-ons
                </button>
            </div>
        </div>
    </div>

    <!-- Step 4: Add-on Selection -->
    <div class="form-step" :class="{ 'active': currentStep === 4 }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm  font-semibold uppercase tracking-wide">
                    Customize <span x-text="currentServiceLabel"></span>
                </p>
                <h2 class="text-2xl font-bold">Enhance with Add-ons</h2>
                <p class="text-gray-600 mt-1">
                    Optional enhancements to make your experience unforgettable.
                </p>
            </div>
            <div class="text-right text-sm text-gray-500">
                <p>Current subtotal: <strong x-text="formatCurrency(currentServiceSubtotal)"></strong></p>
            </div>
        </div>

        <div class="space-y-4">
            <template x-for="addOn in currentServiceAddOns" :key="addOn.id">
                <div class="add-on-card">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h3 class="add-on-name" x-text="addOn.name"></h3>
                            <p class="text-sm text-gray-500" x-text="describeAddOn(addOn)"></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <!-- Quantity based -->
                            <template x-if="addOn.base">
                                <div class="flex items-center">
                                    <template x-if="!addOn.unit || addOn.unit.toLowerCase() !== 'hour'">
                                        <label class="text-sm text-gray-600 mr-3">
                                            <span x-text="addOn.unit ? addOn.unit : 'Quantity'"></span>
                                        </label>
                                    </template>
                                    <input type="number"
                                           min="1"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded"
                                           :min="addOn.min ? addOn.min : 1"
                                           :value="getAddOnQuantity(addOn.id)"
                                           @input="updateAddOnQuantity(addOn, $event.target.value)">
                                    <template x-if="addOn.unit && addOn.unit.toLowerCase() === 'hour'">
                                        <span class="ml-2 text-sm text-gray-600" x-text="addOn.unit"></span>
                                    </template>
                                </div>
                            </template>
                            <!-- Flat price -->
                            <template x-if="!addOn.base">
                                <button type="button"
                                        class="toggle-button"
                                        :class="{ 'active': isAddOnSelected(addOn.id) }"
                                        @click="toggleFlatAddOn(addOn)">
                                    <span x-text="isAddOnSelected(addOn.id) ? 'Remove' : 'Add'"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <template x-if="addOn.extras">
                        <div class="mt-4 border-t pt-4">
                            <p class="text-sm font-semibold  mb-2">Optional extras</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(price, key) in addOn.extras" :key="key">
                                    <button type="button"
                                            class="bonus-chip"
                                            :class="{ 'active': addOnExtraSelected(addOn.id, key) }"
                                            @click="toggleAddOnExtra(addOn, key, price)">
                                        <span x-text="key + ' (' + formatCurrency(price) + ')'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="addOn.options">
                        <div class="mt-4 border-t pt-4">
                            <label class="text-sm font-semibold  mb-2 block">
                                Choose an option
                            </label>
                            <select class="w-full md:w-1/2 px-3 py-2 border border-gray-300 rounded"
                                    @change="setAddOnOption(addOn, $event.target.value)"
                                    :value="getAddOnOption(addOn.id)">
                                <option value="">Select an option</option>
                                <template x-for="option in addOn.options" :key="option">
                                    <option :value="option" x-text="option"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-lg font-semibold mb-2">Service Summary</h3>
            <p class="flex justify-between text-sm ">
                <span>Package</span>
                <span x-text="formatCurrency(currentPackagePrice)"></span>
            </p>
            <template x-for="line in currentAddOnLines" :key="line.id">
                <p class="flex justify-between text-sm text-gray-500">
                    <span>
                        <span x-text="line.name"></span>
                        <template x-if="line.detail">
                            <span class="block text-xs text-gray-400" x-text="line.detail"></span>
                        </template>
                    </span>
                    <span x-text="formatCurrency(line.total)"></span>
                </p>
            </template>
            <template x-if="currentBundleUpgradeLines.length">
                <div class="current-upgrade-summary">
                    <template x-for="upgrade in currentBundleUpgradeLines" :key="upgrade.packageId">
                        <p class="flex justify-between text-sm text-gray-500">
                            <span>
                                <span x-text="upgrade.packageName"></span>
                                <template x-if="upgrade.includedName">
                                    <span class="block text-xs text-gray-400" x-text="'Replaces ' + upgrade.includedName"></span>
                                </template>
                            </span>
                            <span x-text="upgrade.delta > 0 ? '+ ' + formatCurrency(upgrade.delta) : 'Included'"></span>
                        </p>
                    </template>
                </div>
            </template>
            <p class="flex justify-between text-base font-semibold text-gray-900 border-t border-gray-200 pt-2 mt-2">
                <span>Service Subtotal</span>
                <span x-text="formatCurrency(currentServiceSubtotal)"></span>
            </p>
        </div>

        <div class="navigation-buttons mt-6">
            <button @click="backToPackages"
                    class="  font-medium py-2 px-4 rounded"
                    type="button">
                Back to Packages
            </button>
            <button @click="completeService"
                    class="text-white font-bold py-2 px-4 rounded"
                    style="background-color: var(--qb-color);"
                    type="button">
                <span x-text="currentServiceIndex === selectedServices.length - 1 ? 'Review Quote' : 'Next Service'"></span>
            </button>
        </div>
    </div>

    <!-- Step 5: Review & Submit -->
    <div class="form-step" :class="{ 'active': currentStep === 5 }">
        <div class="grid gap-6">
            <div class="space-y-4">
                <h2 class="text-2xl font-bold mb-3">Tell Us About Your Event</h2>
                <p class="text-gray-600 mb-4">Share your contact details and event info so we can follow up quickly.</p>
                <form class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="name" class="form-label">Your Name *</label>
                            <input type="text" id="name" x-model="formData.name" required class="form-input">
                        </div>
                        <div>
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" x-model="formData.email" required class="form-input">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" id="phone" x-model="formData.phone" required class="form-input">
                        </div>
                        <div>
                            <label for="event-date" class="form-label">Event Date *</label>
                            <input type="date" id="event-date" x-model="formData.eventDate" required class="form-input">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="event-type" class="form-label">Event Type *</label>
                            <select id="event-type" x-model="formData.eventType" required class="form-input">
                                <option value="">Select Event Type</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Corporate Event">Corporate Event</option>
                                <option value="Birthday Party">Birthday Party</option>
                                <option value="Anniversary">Anniversary</option>
                                <option value="Graduation Party">Graduation Party</option>
                                <option value="Holiday Party">Holiday Party</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="guests" class="form-label">Number of Guests *</label>
                            <input type="number" id="guests" x-model="formData.guests" min="1" required class="form-input">
                        </div>
                    </div>
                    <div>
                        <label for="message" class="form-label">Additional Message (Optional)</label>
                        <textarea id="message" x-model="formData.message" rows="4" class="form-input"></textarea>
                    </div>
                </form>

                <div class="navigation-buttons mt-6">
                    <button @click="returnToLastService"
                            class="  font-medium py-2 px-4 rounded"
                            type="button">
                        Back to Add-ons
                    </button>
                    <button @click="submitForm"
                            class="text-white font-bold py-2 px-4 rounded"
                            style="background-color: var(--qb-color);"
                            :disabled="isSubmitting || !isContactValid"
                            type="button">
                        <span x-show="!isSubmitting">Submit Quote Request</span>
                        <span x-show="isSubmitting">Submitting...</span>
                    </button>
                </div>

                <div x-show="submitMessage" class="mt-4 p-4 rounded-md"
                     :class="submitSuccess ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                    <p x-text="submitMessage"></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Step 6: Success -->
    <div class="form-step" :class="{ 'active': currentStep === 6 }">
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h2 class="text-2xl font-bold mb-4">Quote Request Submitted!</h2>
            <p class="mb-6">Thank you, <span x-text="formData.name"></span>! We’re excited to start planning with you. A confirmation email with your quote summary has been sent to <strong x-text="formData.email"></strong>.</p>
            <button @click="resetAll"
                    class="text-white font-bold py-2 px-4 rounded">
                Build Another Quote
            </button>
        </div>
    </div>
</div><!-- /.quote-main -->

    <aside class="quote-sidebar">
            <h2 class="summary-title">Quote Summary</h2>
            <template x-if="selectedServices.length === 0">
                <p class="summary-empty-state">
                    Select a service to start building your quote. Your selections will appear here with live pricing.
                </p>
            </template>
            <div class="summary-service-alerts" x-show="serviceNotifications.length" x-cloak>
                <template x-for="note in serviceNotifications" :key="note.id">
                    <div class="summary-service-alert"
                         x-transition:enter="fade-slide-enter"
                         x-transition:enter-start="fade-slide-enter-start"
                         x-transition:enter-end="fade-slide-enter-end"
                         x-transition:leave="fade-slide-leave"
                         x-transition:leave-start="fade-slide-leave-start"
                         x-transition:leave-end="fade-slide-leave-end">
                        <div>
                            <p class="summary-service-alert-message" x-text="note.message"></p>
                            <button type="button"
                                    class="summary-service-alert-link"
                                    x-show="note.serviceId"
                                    @click="openBundledServiceDetails(note.serviceId)">
                                View included details
                            </button>
                        </div>
                        <button type="button"
                                class="summary-service-alert-dismiss"
                                @click="dismissServiceNotification(note.id)"
                                aria-label="Dismiss notice">
                            &times;
                        </button>
                    </div>
                </template>
            </div>
            <div class="summary-services" x-show="selectedServices.length">
                <template x-for="serviceId in selectedServices" :key="serviceId">
                    <div class="summary-service"
                         x-data="{ snapshot: getServiceSnapshot(serviceId) }"
                         x-effect="snapshot = getServiceSnapshot(serviceId)"
                         x-show="snapshot">
                        <div class="summary-service-header">
                            <h3 x-text="snapshot.serviceLabel"></h3>
                            <button type="button"
                                    class="summary-edit"
                                    @click.prevent="editService(serviceId)"
                                    x-show="snapshot.package">
                                Edit
                            </button>
                        </div>
                        <template x-if="snapshot.package">
                            <div class="summary-service-body">
                                <p class="summary-package-line">
                                    <span x-text="snapshot.package.name"></span>
                                    <span x-text="formatCurrency(snapshot.package.price)"></span>
                                </p>
                                <template x-if="snapshot.package.bonuses.length">
                                    <p class="summary-bonuses">
                                        Bonuses:
                                        <span x-text="snapshot.package.bonuses.join(', ')"></span>
                                    </p>
                                </template>
                                <template x-if="snapshot.addOns.length">
                                    <ul class="summary-addons">
                                        <template x-for="addon in snapshot.addOns" :key="addon.id">
                                            <li class="summary-addon-item">
                                                <div>
                                                    <span x-text="addon.name"></span>
                                                    <template x-if="addon.detail">
                                                        <span class="summary-addon-detail" x-text="addon.detail"></span>
                                                    </template>
                                                </div>
                                                <span x-text="formatCurrency(addon.total)"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                                <template x-if="snapshot.bundleUpgrades && snapshot.bundleUpgrades.length">
                                    <ul class="summary-upgrades">
                                        <template x-for="upgrade in snapshot.bundleUpgrades" :key="upgrade.packageId">
                                            <li class="summary-upgrade-item">
                                                <div>
                                                    <span class="summary-upgrade-name" x-text="upgrade.packageName"></span>
                                                    <template x-if="upgrade.includedName">
                                                        <span class="summary-upgrade-detail" x-text="'Replaces ' + upgrade.includedName"></span>
                                                    </template>
                                                </div>
                                                <span class="summary-upgrade-price" x-text="upgrade.delta > 0 ? '+ ' + formatCurrency(upgrade.delta) : 'Included'"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </template>
                                <p class="summary-service-total">
                                    <span>Service Subtotal</span>
                                    <span x-text="formatCurrency(snapshot.subtotal)"></span>
                                </p>
                            </div>
                        </template>
                        <template x-if="!snapshot.package">
                            <p class="summary-incomplete">
                                Select a package to begin pricing this service.
                            </p>
                        </template>
                    </div>
                </template>
            </div>
            <div class="summary-totals">
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span x-text="formatCurrency(subtotal)"></span>
                </div>
                <div class="summary-line discount" x-show="discount > 0">
                    <span>Bundle Discount</span>
                    <span>-<span x-text="formatCurrency(discount)"></span></span>
                </div>
                <p class="summary-discount-label" x-show="discountLabel" x-text="discountLabel"></p>
                <div class="bundle-rewards" x-show="bundleRewards.length" x-cloak>
                    <p class="bundle-rewards-title">Bundle Rewards</p>
                    <template x-for="(reward, index) in bundleRewards" :key="reward.type + '-' + index">
                        <div class="bundle-reward-item">
                            <div class="bundle-reward-copy" x-show="reward.headline || reward.subline">
                                <p class="bundle-rewards-headline" x-show="reward.headline" x-text="reward.headline"></p>
                                <p class="bundle-rewards-subline" x-show="reward.subline" x-text="reward.subline"></p>
                            </div>
                            <p class="bundle-reward-heading" x-show="reward.quantityText" x-text="reward.quantityText"></p>
                            <template x-if="reward.options.length">
                                <ul class="bundle-reward-list">
                                    <template x-for="option in reward.options" :key="option">
                                        <li x-text="option"></li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="summary-line total">
                    <span>Estimated Total</span>
                    <span x-text="formatCurrency(finalTotal)"></span>
                </div>
                <button type="button" class="summary-notes-link" @click="showPricingNotes = true">
                    Details &amp; Pricing Notes
                </button>
            </div>
        </aside>
    </div><!-- /.quote-layout -->

    <div x-show="showBundledDetailsModal"
         x-cloak
         class="bundled-details-overlay"
         @keydown.escape.window="closeBundledServiceDetails()">
        <div class="bundled-details-modal" role="dialog" aria-modal="true">
            <button type="button" class="bundled-details-close" @click="closeBundledServiceDetails()" aria-label="Close">
                &times;
            </button>
            <template x-if="activeBundledDetail">
                <div>
                    <p class="bundled-details-kicker"
                       x-text="'Included with your ' + activeBundledDetail.sourcePackageName + ' ' + activeBundledDetail.sourceServiceLabel + ' package'"></p>
                    <h3 class="bundled-details-title"
                        x-text="activeBundledDetail.infoTitle ? activeBundledDetail.infoTitle : (activeBundledIncludedPackage ? activeBundledIncludedPackage.name : getServiceLabel(activeBundledServiceId))"></h3>
                    <p class="bundled-details-subtitle"
                       x-show="activeBundledDetail.infoDescription"
                       x-text="activeBundledDetail.infoDescription"></p>

                    <template x-if="activeBundledIncludedPackage">
                        <div class="bundled-details-package">
                            <p class="bundled-details-price" x-text="formatCurrency(activeBundledIncludedPackage.price)"></p>
                            <ul class="bundled-details-list" x-show="activeBundledIncludedPackage.includes && activeBundledIncludedPackage.includes.length">
                                <template x-for="item in activeBundledIncludedPackage.includes" :key="item">
                                    <li x-text="item"></li>
                                </template>
                            </ul>
                            <p class="bundled-details-note">Already included at no additional cost.</p>
                        </div>
                    </template>

                    <template x-if="activeBundledUpgradePackages.length">
                        <div class="bundled-upgrades">
                            <h4 class="bundled-upgrades-title">Upgrade Possibilities</h4>
                            <div class="bundled-upgrade-grid">
                                <template x-for="upgrade in activeBundledUpgradePackages" :key="upgrade.id">
                                    <div class="bundled-upgrade-card">
                                        <h5 x-text="upgrade.name"></h5>
                                        <p class="bundled-upgrade-price" x-text="formatCurrency(upgrade.price)"></p>
                                        <ul class="bundled-upgrade-list" x-show="upgrade.includes && upgrade.includes.length">
                                            <template x-for="item in upgrade.includes" :key="item">
                                                <li x-text="item"></li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                            <p class="bundled-upgrades-note">Interested in an upgrade? Mention your preferred booth when we follow up and we’ll tailor your quote.</p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
        <div class="bundled-details-backdrop" @click="closeBundledServiceDetails()"></div>
    </div>

    <div x-show="showPricingNotes" x-cloak class="pricing-notes-overlay" @keydown.escape.window="showPricingNotes = false">
        <div class="pricing-notes-modal" role="dialog" aria-modal="true" aria-labelledby="pricing-notes-title">
            <button type="button" class="pricing-notes-close" @click="showPricingNotes = false" aria-label="Close">
                &times;
            </button>
            <h3 class="pricing-notes-kicker"><?php esc_html_e('Build a Quote', 'teqb'); ?></h3>
            <h2 class="pricing-notes-title" id="pricing-notes-title"><?php esc_html_e('Some Notes on Pricing', 'teqb'); ?></h2>
            <div class="pricing-notes-content">
                <p>The total shown in your instant quote is an initial estimate. Once we review your event details, your final proposal may include applicable travel fees, peak date adjustments, and state/local sales tax. We’ll confirm all pricing and details with you before finalizing your booking.</p>
                <p>If you do not see a package that works for you, please contact us and we would be happy to arrange a package more closely suited to your needs and budget!</p>
                <p>All applicable taxes are already included in the price listed. Peak dates may affect pricing.</p>
                <p>For Saturday events in March, April, May, September, November, and December, please add $100 to the package price. For Saturday events in October, please add $200 to the package price.</p>
                <p>National holiday rates may differ. Please inquire for accurate quote.</p>
            </div>
        </div>
        <div class="pricing-notes-backdrop" @click="showPricingNotes = false"></div>
    </div>
</div><!-- /.toast-quote-builder -->
