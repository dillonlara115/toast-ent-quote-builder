# Long Island Quote Builder Updates

## Overview

This document outlines the changes needed based on client feedback for the Long Island quote builder.

---

## Architecture Decision: Builder-Level Service Overrides

Instead of creating duplicate services for Long Island (which would require duplicating add-ons), we will implement **builder-level service overrides**. This allows:

- The same DJ/MC service CPT to be used across all builders
- Each builder can customize features title, features list, subtitle, description, package screen text, etc.
- Add-ons stay linked to the original service and work everywhere
- No duplication of content or relationships

---

## Code Changes Required

### 1. Add `serviceOverrides` to Builder Config Structure

**Files to modify:**
- `classes/admin.php` - Add to `default_builder_config()` and `sanitize_builder_config()`
- `assets/js/admin-builder.js` - Add to `defaultConfig`

**Config structure:**
```javascript
serviceOverrides: {
  [service_post_id]: {
    features_title: "",      // Override "What's Included" title
    features: [],            // Override features bullet list (array)
    subtitle: "",            // Override service subtitle
    paragraphs: [],          // Override description paragraphs (array)
    package_screen_title: "",       // Custom title for package selection screen
    package_screen_description: "", // Custom intro text for package selection screen
    hide_hourly_breakdown: false    // If true, show only total price, not hourly rate
  }
}
```

---

### 2. Add Service Override UI in Admin Builder

**File:** `assets/js/admin-builder.js`

**Location:** Inside `ServicesSection` component, after the service checkbox when `isServiceSelected` is true

**UI Elements to add:**
- Collapsible "Override Service Display" panel
- TextControl for `features_title`
- TextareaControl for `features` (one per line)
- TextControl for `subtitle`
- TextareaControl for `paragraphs` (one per line)
- TextControl for `package_screen_title`
- TextareaControl for `package_screen_description`
- CheckboxControl for `hide_hourly_breakdown`

**Behavior:**
- Empty fields = use service defaults from CPT
- Only non-empty values override the service defaults

---

### 3. Apply Overrides in PHP Backend

**File:** `classes/quote-builder.php`

**Location:** `build_quote_data_from_cpt()` method (around line 1335)

**Changes:**
1. Load `serviceOverrides` from config
2. After building base service data, check for overrides
3. Apply any non-empty override values

```php
// After building base quote_data for a service:
$service_overrides = $config['serviceOverrides'][$service['post_id']] ?? [];

if (!empty($service_overrides['features_title'])) {
    $quote_data[$service_id]['featuresTitle'] = $service_overrides['features_title'];
}
if (!empty($service_overrides['features'])) {
    $quote_data[$service_id]['features'] = $service_overrides['features'];
}
// ... etc for other fields
```

---

### 4. Update Frontend Template

**File:** `templates/quote-builder-template.php`

**Changes:**

#### 4a. Package Screen Title (line 206-207)
```html
<!-- Current -->
<h2>Choose a package for <span x-text="currentServiceLabel"></span></h2>

<!-- Updated -->
<h2 x-text="currentServiceData?.packageScreenTitle || 'Choose a package for ' + currentServiceLabel"></h2>
```

#### 4b. Package Screen Description (line 209-211)
```html
<!-- Current -->
<p>Select the option that best matches your vision. You can always go back to adjust.</p>

<!-- Updated -->
<p x-text="currentServiceData?.packageScreenDescription || 'Select the option that best matches your vision. You can always go back to adjust.'"></p>
```

#### 4c. Hourly Pricing Display (lines 226-236)
```html
<!-- Add condition to hide hourly breakdown -->
<template x-if="packageOption.hourlyRate && packageOption.minimumHours && !currentServiceData?.hideHourlyBreakdown">
    <!-- existing hourly display -->
</template>
<template x-if="!packageOption.hourlyRate || !packageOption.minimumHours || currentServiceData?.hideHourlyBreakdown">
    <p class="package-price" x-text="formatCurrency(packageOption.price)"></p>
</template>
```

---

### 5. Update Frontend JavaScript

**File:** `assets/js/quote-builder.js`

**Changes:**
- Add getter for `currentServiceData` that returns the full service object including override fields
- Ensure override fields are accessible in the template

---

## WordPress Admin Updates (After Code Changes)

Once the service override feature is implemented, the Long Island-specific content will be configured in the **Quote Builder editor** (not the Service editor), keeping the base service unchanged for other locations.

### 6. Configure Long Island Builder Service Overrides

**Location:** Quote Builders → [Long Island Builder] → DJ/MC Service → Override Settings

**Override values to set:**

| Field | Value |
|-------|-------|
| Features Title | `Essential services included in your Toast DJ/MC Package` |
| Package Screen Title | `Your DJ/MC package includes the first 4 hours` |
| Package Screen Description | *(see below)* |
| Hide Hourly Breakdown | ✅ Checked |

**Features (one per line):**
```
4 hours of DJ & MC service
Unlimited consultation & personalized planning
Online planning tools
Premium sound system (up to 300 guests)
Wireless handheld microphone
Dance floor lighting
Extra speaker for ceremony
Sleek DJ façade
Full backup coverage
No hidden fees (setup, breakdown, and travel within 50 miles included)
```

**Package Screen Description:**
```
Your DJ/MC package is designed to make the night feel effortless and unforgettable. A professional DJ and MC will follow your timeline, read the room, and keep the energy moving naturally from your first dance through the final song. Everything you need for a polished, seamless celebration is already included, with no hidden fees or surprises.

On the next screen, you'll have the opportunity to add additional hours and select enhancements to further personalize your event.
```

> **Note:** This uses a **50 mile radius** (not 30 miles) as specified by the Long Island location owner.

---

## Summary

| # | Task | Type | Status |
|---|------|------|--------|
| 1 | Add `serviceOverrides` to config structure | PHP + JS | ✅ Complete |
| 2 | Add service override UI in admin builder | JS (React) | ✅ Complete |
| 3 | Apply overrides in PHP backend | PHP | ✅ Complete |
| 4 | Update frontend template for custom text | PHP Template | ✅ Complete |
| 5 | Update frontend JS for override data access | JS | ✅ Complete |
| 6 | Configure Long Island builder overrides | WordPress Admin | ⬜ Ready to configure |

### Implementation Order

1. **Config structure** (PHP default + sanitize, JS default)
2. **PHP backend** (apply overrides when building quote data)
3. **Admin UI** (service override panel in builder editor)
4. **Frontend template** (display override values)
5. **Frontend JS** (getter for current service data)
6. **WordPress Admin** (configure Long Island overrides)

---

## Benefits of This Approach

| Benefit | Description |
|---------|-------------|
| **No duplicate services** | DJ/MC service remains a single CPT used by all builders |
| **No duplicate add-ons** | Add-ons stay linked to the original service |
| **Location-specific customization** | Each builder can have unique text, features, pricing display |
| **Easy maintenance** | Changes to base service apply everywhere unless overridden |
| **Scalable** | Any future location can have its own overrides |

---

## Notes

- The Long Island location has different requirements than other locations (e.g., 50 mile radius vs 30 mile radius)
- Empty override fields will fall back to the service CPT defaults
- The `featuresTitle` field needs to be added to the frontend data (currently not passed from backend)
