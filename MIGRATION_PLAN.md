# Quote Builder Backend Migration & Import/Export Plan

## Current State Analysis

### ✅ Already Implemented
1. **Admin UI** (`admin-builder.js`): React-based editor for managing quote builder configurations
2. **Backend Storage**: Config stored as `_teqb_builder_config` post meta on `teqb_builder` CPT
3. **Admin Metabox**: Builder configuration editor integrated into WordPress admin
4. **Basic Structure**: Services, packages, addons, bundles, rewards can be edited

### ❌ Missing/Incomplete Features
1. **Frontend Data Loading**: Frontend still uses hardcoded `quoteData` in `quote-builder.js`
2. **Recent Feature Support**: Admin UI missing support for:
   - `bundledServices` (packages that include other services)
   - `extras` on addons (e.g., Cold Sparks "Blast" option)
   - `rewardCatalog` (currently hardcoded)
   - `bundleDiscounts` (currently hardcoded)
3. **Import/Export**: No functionality to export/import configurations
4. **Data Transformation**: No bridge between admin config format and frontend format

---

## Migration Plan

### Phase 1: Complete Admin UI Feature Support

#### 1.1 Add `bundledServices` Support to Package Editor
- **File**: `assets/js/admin-builder.js`
- **Changes**:
  - Add `bundledServices` array to `createEmptyPackage()`
  - Create `BundledServiceEditor` component
  - Add bundled services section to `PackageEditor`
  - Fields needed:
    - `serviceId` (select dropdown of available services)
    - `packageId` (select dropdown of packages for that service)
    - `upgradePackages` (multi-select of upgrade packages)
    - `message`, `removalMessage`, `upgradeHint` (text fields)
    - `infoTitle`, `infoDescription`, `infoLink` (text fields)

#### 1.2 Add `extras` Support to Addon Editor
- **File**: `assets/js/admin-builder.js`
- **Changes**:
  - Add `extras` object to `createEmptyAddon()`
  - Create `ExtrasEditor` component (key-value pairs)
  - Add extras section to `AddonEditor`
  - Format: `{ "Blast": 200 }` → key-value editor

#### 1.3 Add `rewardCatalog` Management
- **File**: `assets/js/admin-builder.js`
- **Changes**:
  - Already exists in `BundlesSection` but needs verification
  - Ensure all fields are editable: `label`, `pluralLabel`, `optionsLabel`, `options`

#### 1.4 Add `bundleDiscounts` Management
- **File**: `assets/js/admin-builder.js`
- **Changes**:
  - Currently only `bundles.rules` exist
  - Need to verify `bundleDiscounts` array structure matches frontend
  - Frontend expects: `{ minServices, discount, description, requiresAll, freebies }`

---

### Phase 2: Frontend Data Loading

#### 2.1 Create Backend Data Transformer
- **File**: `classes/quote-builder.php`
- **New Method**: `get_quote_data_for_frontend($builder_id)`
- **Purpose**: Transform admin config format to frontend `quoteData` format
- **Transformations**:
  - Convert `services` array to object keyed by service `id`
  - Ensure all fields match frontend expectations
  - Include `rewardCatalog` and `bundleDiscounts` in output

#### 2.2 Update Shortcode to Load Builder Data
- **File**: `classes/quote-builder.php`
- **Method**: `render_quote_builder()`
- **Changes**:
  - If `builder` attribute provided, load config from that builder post
  - Pass builder ID to data transformer
  - Localize transformed data to frontend script

#### 2.3 Update Frontend Script to Use Dynamic Data
- **File**: `assets/js/quote-builder.js`
- **Changes**:
  - Check for `window.quoteBuilderData` (localized from backend)
  - If exists, use it; otherwise fall back to hardcoded `quoteData`
  - Ensure all references use the dynamic data
  - Update `rewardCatalog` and `bundleDiscounts` to use localized data

#### 2.4 Localize Data in `enqueue_assets()`
- **File**: `classes/quote-builder.php`
- **Method**: `enqueue_assets()`
- **Changes**:
  - Detect builder from shortcode attributes (via global or filter)
  - Load builder config
  - Transform to frontend format
  - Localize as `quoteBuilderData` to `quote-builder-js`

---

### Phase 3: Import/Export Functionality

#### 3.1 Export Function
- **File**: `classes/admin.php`
- **New Method**: `handle_export_builder()`
- **Endpoint**: `admin-post.php?action=teqb_export_builder&builder_id=X`
- **Output**: JSON file download
- **Content**: Full builder config JSON (same format as stored in post meta)
- **UI**: Add "Export" button to builder edit screen

#### 3.2 Import Function
- **File**: `classes/admin.php`
- **New Method**: `handle_import_builder()`
- **Endpoint**: `admin-post.php?action=teqb_import_builder`
- **Process**:
  1. Accept JSON file upload
  2. Validate JSON structure
  3. Sanitize data using `sanitize_builder_config()`
  4. Create new builder post OR update existing
  5. Save config as post meta
- **UI**: Add "Import" button/modal to builder list screen

#### 3.3 Import/Export UI Components
- **File**: `assets/js/admin-builder.js` (or separate admin page)
- **Features**:
  - Export button in builder edit screen
  - Import button in builder list screen
  - File upload modal for import
  - Success/error notifications

---

### Phase 4: Data Migration & Testing

#### 4.1 Export Current Hardcoded Data
- Create JSON export of current `quoteData` from `quote-builder.js`
- Use this as baseline for import testing

#### 4.2 Migration Script (Optional)
- **File**: `classes/migrate.php` (temporary)
- **Purpose**: One-time script to import current hardcoded data into a builder post
- **Process**:
  1. Parse `quoteData` from `quote-builder.js`
  2. Transform to admin config format
  3. Create builder post
  4. Save config

#### 4.3 Testing Checklist
- [ ] Admin UI can edit all features (bundledServices, extras, etc.)
- [ ] Frontend loads data from backend correctly
- [ ] All services/packages/addons display correctly
- [ ] Bundle discounts calculate correctly
- [ ] Bundled services work correctly
- [ ] Addon extras work correctly
- [ ] Export produces valid JSON
- [ ] Import creates/updates builder correctly
- [ ] Imported builder works on frontend

---

## Implementation Order

### Priority 1 (Critical Path)
1. ✅ Complete admin UI feature support (bundledServices, extras)
2. ✅ Backend data transformer
3. ✅ Frontend data loading
4. ✅ Test end-to-end flow

### Priority 2 (Nice to Have)
5. ✅ Export functionality
6. ✅ Import functionality
7. ✅ Migration script

---

## Technical Notes

### Data Format Differences

**Admin Config Format** (stored in post meta):
```json
{
  "services": [
    {
      "id": "djmc",
      "label": "DJ / MC",
      "packages": [...],
      "addons": [...]
    }
  ],
  "bundles": {
    "rules": [...],
    "rewards": {...}
  }
}
```

**Frontend Format** (used in quote-builder.js):
```javascript
{
  djmc: {
    label: 'DJ / MC',
    packages: [...],
    addons: [...]
  }
}
```

**Transformation Needed**:
- Convert services array to object keyed by `id`
- Ensure all nested structures match
- Include `rewardCatalog` and `bundleDiscounts` at top level

### Backward Compatibility
- Keep hardcoded `quoteData` as fallback if no builder specified
- Support `[quote-builder]` without attribute (uses default/hardcoded)
- Support `[quote-builder=builder-slug]` (loads from backend)

---

## Questions to Clarify

1. **Default Builder**: Should there be a "default" builder, or require explicit builder slug?
2. **Multiple Builders**: Can multiple builders exist? How to choose which one?
3. **Versioning**: Should import/export include version info for compatibility?
4. **Validation**: What validation rules for imported data?
5. **Rollback**: Should we keep hardcoded data as permanent fallback or remove after migration?


