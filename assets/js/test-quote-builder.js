/**
 * Test Quote Builder JavaScript
 * Simple test to debug data flow
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Test Quote Builder loaded');
    
    // Test 1: Check if Alpine.js is loaded
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded');
        return;
    }
    
    console.log('Alpine.js is loaded');
    
    // Test 2: Register a simple component
    Alpine.data('testComponent', () => ({
        message: 'Test Component Working',
        data: null,
        
        init() {
            console.log('Test component initialized');
            
            // Try to get data from the data attribute
            const element = document.querySelector('[x-data*="quoteBuilder"]');
            if (element) {
                const initialData = element.getAttribute('data-initial');
                console.log('Found initial data:', initialData);
                
                if (initialData && initialData !== '{}') {
                    try {
                        this.data = JSON.parse(initialData);
                        console.log('Parsed data:', this.data);
                    } catch (e) {
                        console.error('Failed to parse initial data:', e);
                    }
                }
            }
        }
    }));
    
    console.log('Test component registered');
});