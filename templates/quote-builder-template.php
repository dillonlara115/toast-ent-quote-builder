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

<div x-data="quoteBuilder()" class="toast-quote-builder">
    <!-- Step Indicators -->
    <div class="step-indicators">
        <div class="step-indicator" :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }">
            <div class="step-number">1</div>
            <div class="step-label">Services</div>
        </div>
        <div class="step-indicator" :class="{ 'active': currentStep === 2, 'completed': serviceProgress > 0 }">
            <div class="step-number">2</div>
            <div class="step-label">Packages</div>
        </div>
        <div class="step-indicator" :class="{ 'active': currentStep === 3, 'completed': serviceProgress > 0 && currentStep > 3 }">
            <div class="step-number">3</div>
            <div class="step-label">Add-ons</div>
        </div>
        <div class="step-indicator" :class="{ 'active': currentStep >= 4 }">
            <div class="step-number">4</div>
            <div class="step-label">Review</div>
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

        <div class="grid gap-4 md:grid-cols-2">
            <template x-for="service in availableServices" :key="service.id">
                <button type="button"
                        class="service-card"
                        :class="{ 'selected': selectedServices.includes(service.id) }"
                        @click="toggleService(service.id)">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="service.label"></h3>
                        <span class="select-indicator" :class="{ 'active': selectedServices.includes(service.id) }">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">Explore curated packages, enhancements, and combos.</p>
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
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded"
                        x-show="selectedServices.length"
                        type="button">
                    Clear Selection
                </button>
                <button @click="proceedToFirstService"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
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
                <p class="text-sm text-blue-600 font-semibold uppercase tracking-wide">
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
                                <svg class="h-4 w-4 text-blue-500 mr-2" fill="none" stroke="currentColor"
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
                            <p class="text-sm font-semibold text-gray-700 mb-2">
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
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded"
                    type="button">
                Back to Services
            </button>
            <button @click="goToAddOns"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    :disabled="!serviceSelections[currentServiceId].selectedPackage"
                    type="button">
                Next: Add-ons
            </button>
        </div>
    </div>

    <!-- Step 3: Add-on Selection -->
    <div class="form-step" :class="{ 'active': currentStep === 3 }">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-blue-600 font-semibold uppercase tracking-wide">
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
                        <div class="flex items-center gap-4">
                            <!-- Quantity based -->
                            <template x-if="addOn.base">
                                <div class="flex items-center">
                                    <label class="text-sm text-gray-600 mr-2">
                                        <span x-text="addOn.unit ? addOn.unit : 'Qty'"></span>
                                    </label>
                                    <input type="number"
                                           min="1"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded"
                                           :min="addOn.min ? addOn.min : 1"
                                           :value="getAddOnQuantity(addOn.id)"
                                           @input="updateAddOnQuantity(addOn, $event.target.value)">
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
                            <p class="text-sm font-semibold text-gray-700 mb-2">Optional extras</p>
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
                            <label class="text-sm font-semibold text-gray-700 mb-2 block">
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
            <p class="flex justify-between text-sm text-gray-700">
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
            <p class="flex justify-between text-base font-semibold text-gray-900 border-t border-gray-200 pt-2 mt-2">
                <span>Service Subtotal</span>
                <span x-text="formatCurrency(currentServiceSubtotal)"></span>
            </p>
        </div>

        <div class="navigation-buttons mt-6">
            <button @click="backToPackages"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded"
                    type="button">
                Back to Packages
            </button>
            <button @click="completeService"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    type="button">
                <span x-text="currentServiceIndex === selectedServices.length - 1 ? 'Review Quote' : 'Next Service'"></span>
            </button>
        </div>
    </div>

    <!-- Step 4: Review & Submit -->
    <div class="form-step" :class="{ 'active': currentStep === 4 }">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-4">
                <h2 class="text-2xl font-bold">Review Your Selections</h2>
                <template x-for="service in orderedServiceSummaries" :key="service.serviceId">
                    <div class="review-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900" x-text="service.serviceLabel"></h3>
                                <p class="text-sm text-gray-500">Package: <span x-text="service.package.name"></span></p>
                            </div>
                            <button type="button"
                                    class="text-sm text-blue-600 hover:text-blue-700"
                                    @click="editService(service.serviceId)">
                                Edit
                            </button>
                        </div>
                        <p class="text-sm font-semibold text-gray-700 mt-3">Included:</p>
                        <ul class="review-list" x-show="service.package.includes.length">
                            <template x-for="item in service.package.includes" :key="item">
                                <li x-text="item"></li>
                            </template>
                        </ul>
                        <p class="text-sm text-gray-700 mt-2">Package Price: <strong x-text="formatCurrency(service.package.price)"></strong></p>
                        <template x-if="service.addOns.length">
                            <div class="mt-3">
                                <p class="text-sm font-semibold text-gray-700 mb-1">Add-ons:</p>
                                <ul class="review-list">
                                    <template x-for="addon in service.addOns" :key="addon.id">
                                        <li>
                                            <span x-text="addon.name"></span>
                                            <template x-if="addon.detail">
                                                <span class="block text-xs text-gray-400" x-text="addon.detail"></span>
                                            </template>
                                            <span class="float-right" x-text="formatCurrency(addon.total)"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                        <p class="flex justify-between text-sm font-semibold text-gray-900 border-t border-gray-200 mt-3 pt-2">
                            <span>Service Total</span>
                            <span x-text="formatCurrency(service.subtotal)"></span>
                        </p>
                    </div>
                </template>

                <div class="pricing-summary">
                    <h3 class="text-lg font-semibold mb-3 text-gray-900">Pricing Summary</h3>
                    <div class="pricing-row">
                        <span>Subtotal</span>
                        <span x-text="formatCurrency(subtotal)"></span>
                    </div>
                    <div class="pricing-row discount" x-show="discount > 0">
                        <span>Combo Discount</span>
                        <span>-<span x-text="formatCurrency(discount)"></span></span>
                    </div>
                    <div class="discount-message" x-show="discountLabel" x-text="discountLabel"></div>
                    <div class="pricing-row total">
                        <span>Final Total</span>
                        <span x-text="formatCurrency(finalTotal)"></span>
                    </div>
                </div>
            </div>

            <div>
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
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded"
                            type="button">
                        Back to Add-ons
                    </button>
                    <button @click="submitForm"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
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

    <!-- Step 5: Success -->
    <div class="form-step" :class="{ 'active': currentStep === 5 }">
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h2 class="text-2xl font-bold mb-4">Quote Request Submitted!</h2>
            <p class="mb-6">Thank you, <span x-text="formData.name"></span>! We’re excited to start planning with you. A confirmation email with your quote summary has been sent to <strong x-text="formData.email"></strong>.</p>
            <button @click="resetAll"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Build Another Quote
            </button>
        </div>
    </div>
</div>
