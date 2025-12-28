# Property Edit Rules - Complete Documentation

## Overview

This document provides a comprehensive guide to the property edit rules system, explaining how users can edit properties, what fields are editable, and how admin approval works for all property types.

## Property Classifications

The system supports 5 property classifications:

1. **Classification 1** = Sell/Rent (Long Term)
2. **Classification 2** = Commercial
3. **Classification 3** = New Project
4. **Classification 4** = Vacation Homes
5. **Classification 5** = Hotel Booking

## Allowed Editable Fields (Same for ALL Property Types)

Users can edit the following fields, which require admin approval:

### Text Fields
- `title` - Property title (English)
- `title_ar` - Property title (Arabic)
- `description` - Property description (English)
- `description_ar` - Property description (Arabic)
- `area_description` - Area description (English)
- `area_description_ar` - Area description (Arabic)

### Image Fields
- `title_image` - Main property image
- `three_d_image` - 3D tour image
- `gallery_images` - Gallery images array

### Location Fields
- `address` - Street address
- `city` - City name
- `state` - State/Province name
- `country` - Country name
- `latitude` - GPS latitude coordinate
- `longitude` - GPS longitude coordinate

### Special Fields
- `hotel_rooms` - Only the `description` field for hotel room objects (Classification 5 only)

## Disabled Fields (Non-editable by Users)

These fields are greyed out and disabled in the frontend interface for owner properties:

### General Fields
- **Price** - Property price
- **Category** - Property category selection
- **Property Type** - Sell/Rent radio buttons
- **Rent Duration** - Daily/Monthly/Yearly selection
- **Is Premium** - Premium package toggle
- **Slug** - URL slug identifier

### Commission & Pricing
- **Weekend Commission** - Weekend pricing commission

### Hotel-Specific Fields (Classification 5)
- **Hotel VAT** - VAT number
- **Hotel Available Rooms** - Number of available rooms
- **Apartment Type** - Apartment type selection
- **Check-in Time** - Check-in time picker
- **Check-out Time** - Check-out time picker
- **Non-Refundable Toggle** - Non-refundable option
- **Hotel Room Fields** (except description):
  - Room Type
  - Price per Night
  - Maximum Guests
  - Availability Dates
  - Discount Percentage
  - Non-refundable Percentage
  - Availability Type

### Vacation Home-Specific Fields (Classification 4)
- **Check-in Time** - Check-in time picker
- **Check-out Time** - Check-out time picker
- **Availability Type** - Availability type selection
- **Instant Booking Toggle** - Instant booking option
- **Vacation Apartments** - All apartment fields (NOT editable)

### All Property Types
- **Parameters Tab (Tab 2)** - All parameter inputs (number, checkbox, radio, textbox, textarea, dropdown, file)
- **Facilities Tab (Tab 3)** - All facility distance inputs

## User Workflow

### For Property Owners (added_by != 0)

1. **Navigate to Edit Property Page**
   - User goes to `/user/edit-property/{slug}`
   - System loads property data

2. **Field Display**
   - **Editable fields**: Normal appearance, fully functional
   - **Disabled fields**: Grey background (#f5f5f5), reduced opacity (0.7), cursor: not-allowed

3. **Make Edits**
   - User can only modify allowed fields
   - Disabled fields are visually greyed out and cannot be changed

4. **Submit Changes**
   - User clicks "Update Property" or "Submit"
   - Frontend sends all form data to backend

5. **Backend Processing**
   - Backend checks: `property.added_by != 0` (owner property)
   - Checks setting: `auto_approve_edited_listings`
   - If auto-approve is **OFF**:
     - Filters data to only include allowed fields
     - Compares with original data to detect changes
     - If changes exist: Creates `PropertyEditRequest` with status `pending`
     - Sets property `request_status = 'pending'`
     - User sees success message, but changes await approval
   - If auto-approve is **ON**:
     - Changes are applied immediately
     - Property `request_status = 'approved'`
     - No edit request is created

6. **Wait for Admin Approval**
   - Edit request appears in admin panel at `/property-edit-requests`
   - Property shows as "pending" until approved/rejected

### For Admin Properties (added_by == 0)

1. **All fields are enabled** - No restrictions
2. **Changes save immediately** - No approval needed
3. **No edit requests created** - Direct save to database

## Admin Panel Workflow

### Viewing Edit Requests

1. **Navigate to Edit Requests Page**
   - Admin goes to `/property-edit-requests`
   - Page shows table of all edit requests

2. **Filter by Status**
   - **Pending** - Awaiting review (default)
   - **Approved** - Previously approved requests
   - **Rejected** - Previously rejected requests
   - **All** - All requests regardless of status

3. **View Request Details**
   - Click "View" button on any request
   - Modal opens showing:
     - Request information (property, owner, date, status)
     - Review information (changes count, reviewer, review date)
     - Detailed comparison table:
       - Field name
       - Original value (red/highlighted)
       - New value (green/highlighted)
       - Special formatting for JSON, images, hotel rooms

4. **Approve Request**
   - Click "Approve" button
   - System calls `applyEditRequest()`:
     - Applies all field changes to property
     - For hotel rooms: Updates only descriptions
     - Sets property `request_status = 'approved'`, `status = 1`
     - Updates edit request status to `approved`
     - Records reviewer and review timestamp
   - Property changes go live immediately

5. **Reject Request**
   - Click "Reject" button
   - Modal opens requesting rejection reason (required)
   - Submit rejection:
     - Updates edit request status to `rejected`
     - Stores rejection reason
     - Records reviewer and review timestamp
     - Property remains unchanged

## Workflow by Property Classification

### Classification 1: Sell/Rent

**Editable Fields:**
- Title (EN/AR)
- Description (EN/AR)
- Area Description (EN/AR)
- Images (title, 3D, gallery)
- Address location (city, state, country, lat, lng)

**Disabled Fields:**
- Price, Category, Property Type, Rent Duration, Premium status, Slug, Weekend Commission
- Parameters Tab, Facilities Tab

**Backend Flow:**
- Standard edit request workflow
- No special handling needed

### Classification 2: Commercial

**Editable Fields:** (Same as Sell/Rent)
- Title (EN/AR)
- Description (EN/AR)
- Area Description (EN/AR)
- Images (title, 3D, gallery)
- Address location (city, state, country, lat, lng)

**Disabled Fields:** (Same as Sell/Rent)
- Price, Category, Property Type, Rent Duration, Premium status, Slug, Weekend Commission
- Parameters Tab, Facilities Tab

**Backend Flow:**
- Standard edit request workflow
- No special handling needed

### Classification 3: New Project

**Editable Fields:** (Same as Sell/Rent)
- Title (EN/AR)
- Description (EN/AR)
- Area Description (EN/AR)
- Images (title, 3D, gallery)
- Address location (city, state, country, lat, lng)

**Disabled Fields:** (Same as Sell/Rent)
- Price, Category, Property Type, Rent Duration, Premium status, Slug, Weekend Commission
- Parameters Tab, Facilities Tab

**Backend Flow:**
- Standard edit request workflow
- No special handling needed

### Classification 4: Vacation Homes

**Editable Fields:**
- Title (EN/AR)
- Description (EN/AR)
- Area Description (EN/AR)
- Images (title, 3D, gallery)
- Address location (city, state, country, lat, lng)

**Disabled Fields:**
- Price, Category, Property Type, Instant Booking, Check-in/out times, Availability Type
- Parameters Tab, Facilities Tab
- **Vacation Apartments** - NOT editable (all fields disabled)

**Backend Flow:**
- Standard edit request workflow
- Vacation apartments are NOT included in edit requests
- Note: VacationApartmentsManager component is not disabled in frontend, but changes are filtered out by backend

### Classification 5: Hotel Booking

**Editable Fields:**
- Title (EN/AR)
- Description (EN/AR)
- Area Description (EN/AR)
- Images (title, 3D, gallery)
- Address location (city, state, country, lat, lng)
- **Hotel Room Descriptions ONLY** - Only the description field for each hotel room

**Disabled Fields:**
- Price, Category, Property Type, Hotel VAT, Available Rooms, Apartment Type
- Check-in/out times, Non-refundable toggle
- **Hotel Room Fields** (except description):
  - Room Type, Price, Guests, Availability Dates, Discount Percentage, etc.
- Parameters Tab, Facilities Tab

**Backend Flow:**
- Standard edit request workflow
- Special handling for `hotel_rooms`:
  - Only `description` field is extracted from each room
  - When approved, only room descriptions are updated
  - All other room fields remain unchanged

## Technical Implementation Details

### Frontend Logic

**File:** `new_ashome_front/as-home-web-nexus/src/Components/EditPropertyTabs/EditPropertyTabs.jsx`

**Key Functions:**
- `shouldDisableFields()` - Checks if `propertyData.added_by != 0` (owner property)
- `getDisabledFieldStyle()` - Returns grey styling for disabled fields
- `getDisabledAttribute()` - Returns disabled/readonly attributes

**Field Disabling:**
- All non-editable fields use `disabled={getDisabledAttribute()}` and `readOnly={getDisabledAttribute()}`
- Visual styling applied via `style={getDisabledFieldStyle()}`
- Category selection: `pointerEvents: 'none'` and `opacity: 0.6`
- Toggle switches: `opacity: 0.6` and `cursor: 'not-allowed'`

### Backend Logic

**File:** `as-home-dashboard-Admin/app/Http/Controllers/PropertController.php`

**Update Method Flow:**
1. Check `$UpdateProperty->added_by != 0` → `$isOwnerEdit = true`
2. Check `auto_approve_edited_listings` setting
3. If owner edit AND auto-approve is OFF:
   - Filter edited data: `PropertyEditRequestService::filterAllowedFields()`
   - Compare filtered data with original to detect changes
   - If changes exist: Create `PropertyEditRequest` with status `pending`
4. If admin edit OR auto-approve is ON:
   - Save directly, no approval needed

**File:** `as-home-dashboard-Admin/app/Services/PropertyEditRequestService.php`

**Key Methods:**
- `getAllowedEditableFields()` - Returns array of allowed field names
- `filterAllowedFields()` - Filters data to only include allowed fields
- `saveEditRequest()` - Creates/updates PropertyEditRequest record
- `applyEditRequest()` - Applies approved changes to property
- `rejectEditRequest()` - Marks request as rejected

**Special Handling:**
- `hotel_rooms`: Only extracts `description` field from each room object
- All other fields: Direct property field updates

### Admin Panel

**File:** `as-home-dashboard-Admin/resources/views/property/edit-requests.blade.php`

**Features:**
- Status-filtered table view
- Detailed comparison modal
- Approve/Reject actions
- Special formatting for:
  - Hotel rooms (shows room ID and description)
  - Vacation apartments (if present in old requests)
  - JSON fields (pretty formatted)
  - Images (file paths)
  - Long text (truncated with length)

## Important Notes

1. **Vacation Apartments**: While the VacationApartmentsManager component is not disabled in the frontend, vacation apartments are NOT editable by users. The backend filters them out, so any changes won't be saved in edit requests.

2. **Auto-Approve Setting**: If `auto_approve_edited_listings` is enabled, owner edits are applied immediately without creating edit requests.

3. **Admin vs Owner**: Admin properties (`added_by == 0`) have no restrictions. All fields are editable and changes save immediately.

4. **Hotel Rooms**: Only the description field can be edited. All other room fields (price, type, guests, availability, etc.) are disabled and cannot be changed.

5. **Change Detection**: The system only creates edit requests if there are actual changes in the allowed fields. If a user submits the form with no changes to editable fields, no edit request is created.

## Testing Checklist

### Frontend Testing
- [x] Sell/Rent properties - fields disabled correctly
- [x] Commercial properties - fields disabled correctly
- [x] New Project properties - fields disabled correctly
- [x] Vacation Homes - fields disabled correctly
- [x] Hotel Bookings - fields disabled correctly, only room description editable
- [x] Admin properties (added_by == 0) - all fields enabled
- [x] Owner properties (added_by != 0) - restricted fields disabled with grey styling

### Backend Testing
- [x] Edit requests created only for owner edits (added_by != 0)
- [x] Edit requests NOT created for admin edits (added_by == 0)
- [x] Only allowed fields saved in edit requests
- [x] Hotel room descriptions saved correctly
- [x] Vacation apartments NOT included in edit requests
- [x] Change detection works correctly
- [x] Empty edit requests not created

### Admin Panel Testing
- [x] Edit requests list displays correctly
- [x] Status filtering works
- [x] Detailed view shows all changes correctly
- [x] Hotel room changes display correctly
- [x] Approve action applies changes correctly
- [x] Reject action works correctly
- [x] Already-processed requests cannot be modified

## Files Modified

### Backend Files
- `as-home-dashboard-Admin/app/Services/PropertyEditRequestService.php`
  - Fixed `array_key_exists` for hotel room descriptions
- `as-home-dashboard-Admin/app/Http/Controllers/PropertController.php`
  - Removed vacation_apartments from filteredEditedData
  - Fixed change detection logic (removed vacation_apartments from special handling)

### Frontend Files
- `new_ashome_front/as-home-web-nexus/src/Components/EditPropertyTabs/EditPropertyTabs.jsx`
  - Added `shouldDisableFields()` function
  - Added `getDisabledFieldStyle()` function
  - Added `getDisabledAttribute()` function
  - Applied disabled attributes to all non-editable fields
- `new_ashome_front/as-home-web-nexus/src/Components/HotelRoomsManager/HotelRoomsManager.jsx`
  - Added `disableNonEditableFields` prop
  - Disabled all hotel room fields except description

## Future Improvements

1. **VacationApartmentsManager**: Add `disableNonEditableFields` prop support for consistency (currently not disabled but backend filters it out)

2. **Visual Indicators**: Consider adding tooltips explaining why fields are disabled

3. **Bulk Actions**: Add ability to approve/reject multiple edit requests at once

4. **Email Notifications**: Notify users when their edit requests are approved/rejected

5. **Edit History**: Track all approved/rejected edit requests for audit purposes

