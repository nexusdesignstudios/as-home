# Coral Mirage Hotel - Field Editability Issues - Fixes Applied

## Issues Identified

1. **Hotel VAT field not editable in user dashboard** - Field was hardcoded as `disabled={true}` and `readOnly={true}`
2. **Hotel VAT field not showing in admin panel** - Field was in `price-field` class but should be in `hotel-fields` section
3. **City field not editable** - Needs investigation (may be related to pending request check)

## Property Information

- **Property ID:** 239
- **Title:** Coral Mirage Hotel
- **Property Classification:** hotel_booking (5)
- **City:** hurghada
- **State:** Red Sea Governorate
- **Country:** Egypt
- **Hotel VAT:** NULL
- **Request Status:** approved

## Fixes Applied

### 1. Hotel VAT Field - Frontend (User Dashboard) ✅

**File:** `new_ashome_front/as-home-web-nexus/src/Components/EditPropertyTabs/EditPropertyTabs.jsx`

**Issue:** Hotel VAT field was hardcoded as `disabled={true}` and `readOnly={true}`

**Fix:** Changed to use `getDisabledAttribute()` which only disables when there's a pending edit request:

```jsx
// Before:
disabled={true}
readOnly={true}

// After:
disabled={getDisabledAttribute()}
readOnly={getDisabledAttribute()}
```

**Location:** Line ~5031-5033

### 2. Hotel VAT Field - Admin Panel ✅

**File:** `as-home-dashboard-Admin/resources/views/property/edit.blade.php`

**Issue:** Hotel VAT field was in `price-field` class, which might not always be visible for hotels

**Fix:** Moved Hotel VAT field into the `hotel-fields` section so it's guaranteed to show for hotels (property_classification = 5)

**Location:** Moved from line ~332 to inside `hotel-fields` div (line ~258-322)

**JavaScript:** The `handlePropertyClassification()` function shows `hotel-fields` when `propertyClassification == 5`, so Hotel VAT will now be visible for hotels.

### 3. City Field Editability - Investigation Needed ⚠️

**File:** `new_ashome_front/as-home-web-nexus/src/Components/EditPropertyTabs/EditPropertyTabs.jsx`

**Current Behavior:**
- City field uses `shouldDisableFields()` which checks for pending edit requests
- When `!shouldDisableFields()`, it shows Google Autocomplete component
- When `shouldDisableFields()`, it shows a regular input with `disabled` and `readOnly` attributes

**Possible Issues:**
1. There might be a pending edit request for this property (check `property_edit_requests` table)
2. The Autocomplete component might not be loading properly
3. There might be a JavaScript error preventing the field from being editable

**To Check:**
```sql
SELECT * FROM property_edit_requests 
WHERE property_id = 239 AND status = 'pending';
```

**Note:** Location fields (city, state, country, address) were removed from approval-required fields in previous work, so they should be editable without approval. However, if there's a pending edit request for other fields, ALL fields are disabled.

## Testing Steps

1. **Test Hotel VAT in User Dashboard:**
   - Login as property owner
   - Go to Edit Property for Coral Mirage Hotel
   - Navigate to Hotel Details tab
   - Verify Hotel VAT field is now editable (not grayed out)
   - Try entering a VAT number and saving

2. **Test Hotel VAT in Admin Panel:**
   - Login as admin
   - Go to Properties → Edit Coral Mirage Hotel
   - Verify Hotel VAT field is visible in the Hotel Specific Fields section
   - Try editing and saving the Hotel VAT value

3. **Test City Field:**
   - Login as property owner
   - Go to Edit Property for Coral Mirage Hotel
   - Navigate to Location tab
   - Check if city field is editable
   - If not, check browser console for JavaScript errors
   - Check database for pending edit requests

## Database Queries for Verification

```sql
-- Check for pending edit requests
SELECT * FROM property_edit_requests 
WHERE property_id = 239 AND status = 'pending';

-- Check property classification
SELECT id, title, property_classification, hotel_vat, city, request_status 
FROM propertys 
WHERE id = 239;

-- Check if hotel_vat column exists
SHOW COLUMNS FROM propertys LIKE 'hotel_vat';
```

## Files Modified

1. `new_ashome_front/as-home-web-nexus/src/Components/EditPropertyTabs/EditPropertyTabs.jsx`
   - Fixed Hotel VAT field to use `getDisabledAttribute()` instead of hardcoded `true`

2. `as-home-dashboard-Admin/resources/views/property/edit.blade.php`
   - Moved Hotel VAT field into `hotel-fields` section

## Next Steps

1. Test the fixes in both user dashboard and admin panel
2. If city field is still not editable, check for pending edit requests
3. If no pending requests exist, check browser console for JavaScript errors
4. Verify that location fields are not in the approval-required list

