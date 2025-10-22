<?php
/**
 * Quote Builder Template
 * 
 * Template file for the Toast Entertainment Quote Builder form
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div x-data="quoteBuilder()" class="toast-quote-builder" x-init="initData(<?php echo htmlspecialchars(json_encode(['location' => $location, 'data' => $location_data]), ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Step Indicators -->
    <div class="step-indicators">
        <div class="step-indicator" :class="{ 'active': currentStep === 1, 'completed': currentStep > 1 }">
            <div class="step-number">1</div>
            <div class="step-label">Package</div>
        </div>
        <div class="step-indicator" :class="{ 'active': currentStep === 2, 'completed': currentStep > 2 }">
            <div class="step-number">2</div>
            <div class="step-label">Add-ons</div>
        </div>
        <div class="step-indicator" :class="{ 'active': currentStep === 3, 'completed': currentStep > 3 }">
            <div class="step-number">3</div>
            <div class="step-label">Details</div>
        </div>
        <div class="step-indicator" :class="{ 'active': currentStep === 4, 'completed': currentStep > 4 }">
            <div class="step-number">4</div>
            <div class="step-label">Review</div>
        </div>
    </div>

    <!-- Step 1: Package Selection -->
    <div class="form-step" :class="{ 'active': currentStep === 1 }">
        <h2 class="text-2xl font-bold mb-6">Choose Your Package</h2>
        
        <template x-for="(packageData, packageId) in quoteData.data.packages" :key="packageId">
            <div class="package-card" 
                 :class="{ 'selected': formData.selectedPackage === packageId }"
                 @click="selectPackage(packageId)">
                <h3 class="package-name" x-text="packageData.name"></h3>
                <div class="package-price" x-text="formatCurrency(packageData.price)"></div>
                <p class="package-description" x-text="packageData.description"></p>
                <ul class="package-features">
                    <template x-for="feature in packageData.features" :key="feature">
                        <li x-text="feature"></li>
                    </template>
                </ul>
            </div>
        </template>
        
        <div class="navigation-buttons">
            <div></div> <!-- Empty div for flex spacing -->
            <button @click="nextStep" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    :disabled="!formData.selectedPackage">
                Next Step
            </button>
        </div>
    </div>

    <!-- Step 2: Add-ons Selection -->
    <div class="form-step" :class="{ 'active': currentStep === 2 }">
        <h2 class="text-2xl font-bold mb-6">Enhance Your Event</h2>
        <p class="mb-6 text-gray-600">Select additional services to customize your event (optional)</p>
        
        <template x-for="(addOnData, addOnId) in quoteData.data.add_ons" :key="addOnId">
            <div class="add-on-card" 
                 :class="{ 'selected': formData.selectedAddOns.includes(addOnId) }"
                 @click="toggleAddOn(addOnId)">
                <div class="add-on-info">
                    <div class="add-on-name" x-text="addOnData.name"></div>
                    <div class="add-on-description" x-text="addOnData.description"></div>
                </div>
                <div class="add-on-price" x-text="formatCurrency(addOnData.price)"></div>
            </div>
        </template>
        
        <!-- Pricing Summary -->
        <div class="pricing-summary">
            <h3 class="text-lg font-semibold mb-3">Pricing Summary</h3>
            <div class="pricing-row">
                <span>Package:</span>
                <span x-text="formatCurrency(getSelectedPackage()?.price || 0)"></span>
            </div>
            <template x-for="addOn in getSelectedAddOns()" :key="addOn.name">
                <div class="pricing-row">
                    <span x-text="addOn.name"></span>
                    <span x-text="formatCurrency(addOn.price)"></span>
                </div>
            </template>
            <div class="pricing-row discount" x-show="formData.discount > 0">
                <span>Discount:</span>
                <span x-text="'-' + formatCurrency(formData.discount)"></span>
            </div>
            <div class="pricing-row total">
                <span>Total:</span>
                <span x-text="formatCurrency(formData.total)"></span>
            </div>
            <div class="discount-message" x-show="formData.discountMessage" x-text="formData.discountMessage"></div>
        </div>
        
        <div class="navigation-buttons">
            <button @click="prevStep" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Previous
            </button>
            <button @click="nextStep" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Next Step
            </button>
        </div>
    </div>

    <!-- Step 3: Contact Information -->
    <div class="form-step" :class="{ 'active': currentStep === 3 }">
        <h2 class="text-2xl font-bold mb-6">Event Details</h2>
        
        <form class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name *</label>
                    <input type="text" id="name" x-model="formData.name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" x-model="formData.email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                    <input type="tel" id="phone" x-model="formData.phone" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="event-date" class="block text-sm font-medium text-gray-700 mb-1">Event Date *</label>
                    <input type="date" id="event-date" x-model="formData.eventDate" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="event-type" class="block text-sm font-medium text-gray-700 mb-1">Event Type *</label>
                    <select id="event-type" x-model="formData.eventType" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    <label for="guests" class="block text-sm font-medium text-gray-700 mb-1">Number of Guests *</label>
                    <input type="number" id="guests" x-model="formData.guests" min="1" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Additional Message (Optional)</label>
                <textarea id="message" x-model="formData.message" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
        </form>
        
        <div class="navigation-buttons">
            <button @click="prevStep" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Previous
            </button>
            <button @click="nextStep" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    :disabled="!validateContactInfo()">
                Review Quote
            </button>
        </div>
    </div>

    <!-- Step 4: Review -->
    <div class="form-step" :class="{ 'active': currentStep === 4 }">
        <h2 class="text-2xl font-bold mb-6">Review Your Quote</h2>
        
        <div class="review-section">
            <h3 class="review-title">Contact Information</h3>
            <div class="review-item"><span class="review-label">Name:</span> <span x-text="formData.name"></span></div>
            <div class="review-item"><span class="review-label">Email:</span> <span x-text="formData.email"></span></div>
            <div class="review-item"><span class="review-label">Phone:</span> <span x-text="formData.phone"></span></div>
        </div>
        
        <div class="review-section">
            <h3 class="review-title">Event Details</h3>
            <div class="review-item"><span class="review-label">Event Type:</span> <span x-text="formData.eventType"></span></div>
            <div class="review-item"><span class="review-label">Event Date:</span> <span x-text="formData.eventDate"></span></div>
            <div class="review-item"><span class="review-label">Number of Guests:</span> <span x-text="formData.guests"></span></div>
            <div class="review-item"><span class="review-label">Location:</span> <span x-text="quoteData.data.location_name"></span></div>
        </div>
        
        <div class="review-section">
            <h3 class="review-title">Selected Package</h3>
            <div class="review-item"><span x-text="getSelectedPackage()?.name"></span> - <span x-text="formatCurrency(getSelectedPackage()?.price || 0)"></span></div>
        </div>
        
        <div class="review-section" x-show="getSelectedAddOns().length > 0">
            <h3 class="review-title">Selected Add-ons</h3>
            <template x-for="addOn in getSelectedAddOns()" :key="addOn.name">
                <div class="review-item"><span x-text="addOn.name"></span> - <span x-text="formatCurrency(addOn.price)"></span></div>
            </template>
        </div>
        
        <div class="pricing-summary">
            <h3 class="text-lg font-semibold mb-3">Pricing Summary</h3>
            <div class="pricing-row">
                <span>Subtotal:</span>
                <span x-text="formatCurrency(formData.subtotal)"></span>
            </div>
            <div class="pricing-row discount" x-show="formData.discount > 0">
                <span>Discount:</span>
                <span x-text="'-' + formatCurrency(formData.discount)"></span>
            </div>
            <div class="pricing-row total">
                <span>Total:</span>
                <span x-text="formatCurrency(formData.total)"></span>
            </div>
            <div class="discount-message" x-show="formData.discountMessage" x-text="formData.discountMessage"></div>
        </div>
        
        <div class="navigation-buttons">
            <button @click="prevStep" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Previous
            </button>
            <button @click="submitForm" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                    :disabled="isSubmitting">
                <span x-show="!isSubmitting">Submit Quote Request</span>
                <span x-show="isSubmitting">Submitting...</span>
            </button>
        </div>
        
        <div x-show="submitMessage" class="mt-4 p-4 rounded-md" 
             :class="submitSuccess ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
            <p x-text="submitMessage"></p>
        </div>
    </div>

    <!-- Step 5: Success -->
    <div class="form-step" :class="{ 'active': currentStep === 5 }">
        <div class="success-message">
            <div class="success-icon">✓</div>
            <h2 class="text-2xl font-bold mb-4">Quote Request Submitted!</h2>
            <p class="mb-6">Thank you for your interest in our event services. We have received your quote request and will contact you soon to discuss the details.</p>
            <p class="mb-6">A confirmation email has been sent to <strong x-text="formData.email"></strong> with your quote details.</p>
            <button @click="resetForm" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create Another Quote
            </button>
        </div>
    </div>
</div>