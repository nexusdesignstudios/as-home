# Property Edit Code References

## Overview
Complete reference guide to all code locations involved in the property edit approval system.

## Backend Code References

### API Controller (`ApiController.php`)

#### Approval-Required Fields Definition
**Location:** Lines 2981-2988  
**Purpose:** Defines which fields require admin approval  
**Code:**
```php
$approvalRequiredFields = [
    'title', 'title_ar',
    'description', 'description_ar',
    'area_description', 'area_description_ar',
    'title_image', 'gallery_images', 'three_d_image', 'og_images',
    'address', 'latitude', 'longitude', 'state', 'city', 'country',
    'hotel_rooms' // Only description field within rooms
];
```

#### Owner Edit Check
**Location:** Lines 2969-2974  
**Purpose:** Determines if edit is from owner or admin  
**Code:**
```php
$isOwnerEdit = $property->added_by != 0;
$autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;
```

#### Approval Check Method
**Location:** Lines 12537-12609  
**Method:** `hasApprovalRequiredChanges()`  
**Purpose:** Checks if any approval-required fields have changed  
**Returns:** `true` if approval required, `false` otherwise

**Key Checks:**
- Title fields (lines 12540-12544)
- Description fields (lines 12548-12552)
- Area description fields (lines 12556-12560)
- Images (lines 12564-12568)
- Address/location fields (lines 12571-12589)
- Hotel room descriptions (lines 12591-12606)

#### Edit Request Creation
**Location:** Lines 2999-3109  
**Purpose:** Creates edit request when approval is required  
**Key Steps:**
1. Get edited data (line 3002)
2. Get original data (line 3023)
3. Include vacation apartments if applicable (lines 3008-3015)
4. Include facilities if applicable (lines 3041-3048)
5. Create edit request (line 3052)
6. Return response with edit_request object (lines 3101-3109)

#### Direct Save (No Approval)
**Location:** Lines 3110-3136  
**Purpose:** Saves changes directly when no approval required  
**Key Steps:**
1. Save facilities immediately (lines 3113-3126)
2. Set request_status to approved (lines 3128-3130)
3. Save property (line 3131)
4. Log direct save (lines 3132-3135)

#### Auto-Approve Path
**Location:** Lines 3137-3178  
**Purpose:** Handles owner edits when auto-approve is ON  
**Key Steps:**
1. Save facilities immediately (lines 3143-3158)
2. Set request_status to approved (lines 3163-3176)
3. Save property (line 3178)

#### Field Assignment (Auto-Approval Fields)
**Location:** Lines 2527-2664  
**Purpose:** Assigns values to property fields  
**Key Fields:**
- Price (line 2555)
- Property type (line 2551)
- Facilities (saved separately, lines 2014-2020)
- Parameters (saved separately, lines 2023-2040)
- Hotel fields (lines 2599-2641)
- Contact details (lines 2515-2661)

### Property Edit Request Service (`PropertyEditRequestService.php`)

#### Allowed Editable Fields
**Location:** Lines 17-36  
**Method:** `getAllowedEditableFields()`  
**Purpose:** Returns list of fields that can be edited (require approval)  
**Returns:** Array of field names

**Fields Included:**
- `title`, `title_ar`
- `description`, `description_ar`
- `area_description`, `area_description_ar`
- `title_image`, `three_d_image`, `gallery_images`
- `address`, `city`, `state`, `country`, `latitude`, `longitude`
- `hotel_rooms` (special handling)

#### Field Filtering
**Location:** Lines 46-74  
**Method:** `filterAllowedFields()`  
**Purpose:** Filters edited data to only include allowed fields  
**Parameters:**
- `$editedData`: Array of all edited fields
- `$property`: Property model instance
**Returns:** Filtered array with only approval-required fields

**Special Handling:**
- Hotel rooms: Only keeps description field (lines 53-66)
- Other fields: Direct assignment (line 68)

#### Save Edit Request
**Location:** Lines 84-146  
**Method:** `saveEditRequest()`  
**Purpose:** Creates or updates property edit request  
**Parameters:**
- `$property`: Property model
- `$editedData`: Filtered edited data
- `$requestedBy`: User ID who requested edit
- `$originalData`: Original property data (optional)

**Key Steps:**
1. Begin transaction (line 87)
2. Get original data if not provided (lines 90-94)
3. Check for existing pending request (lines 97-99)
4. Update existing or create new request (lines 101-136)
5. Commit transaction (line 108 or 127)
6. Log action (lines 110-114 or 129-133)

#### Apply Edit Request
**Location:** Lines 155-222  
**Method:** `applyEditRequest()`  
**Purpose:** Applies approved edit request to property  
**Parameters:**
- `$editRequest`: PropertyEditRequest model
- `$reviewedBy`: Admin user ID (optional)

**Key Steps:**
1. Begin transaction (line 158)
2. Get property and edited data (lines 160-161)
3. Handle hotel rooms separately (lines 164-176)
4. Apply other fields (lines 179-191)
5. Set request_status to approved (line 194)
6. Update edit request status (lines 200-203)
7. Commit transaction (line 205)

### Admin Panel Controller (`PropertController.php`)

#### Owner Edit Check
**Location:** Lines 665-670  
**Purpose:** Checks if edit is from owner  
**Code:**
```php
$isOwnerEdit = $UpdateProperty->added_by != 0;
$autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;
```

#### Edit Request Creation
**Location:** Lines 870-943  
**Purpose:** Creates edit request for owner edits  
**Key Steps:**
1. Filter allowed fields (line 875)
2. Check for changes (lines 887-915)
3. Create edit request if changes exist (lines 917-936)
4. Set request_status to pending (line 928)
5. Log edit request creation (lines 931-936)

#### Direct Save (Admin/Auto-Approve)
**Location:** Lines 944-950  
**Purpose:** Saves directly when no approval needed  
**Key Steps:**
1. Set request_status to approved if owner with auto-approve (line 948)
2. Save property (line 953)

## Frontend Code References

### Edit Property Tabs Component (`EditPropertyTabs.jsx`)

#### Approval-Required Fields Constant
**Location:** Lines 539-546  
**Purpose:** Defines approval-required fields in frontend  
**Code:**
```javascript
const APPROVAL_REQUIRED_FIELDS = [
  'title', 'title_ar',
  'description', 'description_ar',
  'area_description', 'area_description_ar',
  'titleImage', 'galleryImages', '_3DImages', 'ogImages',
  'address', 'latitude', 'longitude', 'state', 'city', 'country',
  'hotelRooms'
];
```

#### Approval Check Function
**Location:** Lines 581-637  
**Function:** `hasApprovalRequiredChanges()`  
**Purpose:** Checks if approval-required fields have changed  
**Returns:** `true` if approval required, `false` otherwise

**Key Checks:**
- Title fields (lines 585-588)
- Description fields (lines 591-594)
- Area description fields (lines 597-600)
- Images (lines 603-608)
- Address/location fields (lines 611-618)
- Hotel room descriptions (lines 621-634)

#### Get Changed Approval Fields
**Location:** Lines 639-700  
**Function:** `getChangedApprovalFields()`  
**Purpose:** Returns list of specific approval-required fields that changed  
**Returns:** Array of field names (e.g., ['Property Title (English)', 'Title Image'])

**Key Checks:**
- Title fields (lines 644-651)
- Description fields (lines 654-661)
- Area description fields (lines 664-671)
- Images (lines 674-687)
- Hotel room descriptions (lines 690-703)

#### Approval Check Before Submit
**Location:** Line 2574  
**Purpose:** Checks if approval is required before submission  
**Code:**
```javascript
const requiresApproval = hasApprovalRequiredChanges();
```

#### Success Popup with Hints
**Location:** Lines 2794-2852  
**Purpose:** Shows detailed success message with approval information  
**Key Features:**
- Gets changed approval fields (line 2795)
- Builds detailed HTML message (lines 2803-2828)
- Shows field list, "What happens next?", and "Saved immediately" sections
- Uses Swal.fire for popup (lines 2830-2852)

#### Pending Request Check
**Location:** Lines 903-921  
**Purpose:** Checks for pending edit requests on component load  
**Function:** `CheckPendingEditRequestApi()`  
**Behavior:**
- Disables form if pending request exists
- Shows message to user
- Prevents new edits until approval

## Database Schema

### Properties Table (`propertys`)
**Key Fields:**
- `id`: Primary key
- `added_by`: User ID (0 = admin, >0 = owner)
- `request_status`: 'pending', 'approved', 'rejected'
- `status`: Active/inactive flag
- All property fields (title, description, price, etc.)

### Property Edit Requests Table (`property_edit_requests`)
**Key Fields:**
- `id`: Primary key
- `property_id`: Foreign key to properties
- `requested_by`: User ID who requested edit
- `status`: 'pending', 'approved', 'rejected'
- `edited_data`: JSON of edited fields
- `original_data`: JSON of original fields
- `reviewed_by`: Admin user ID (when approved/rejected)
- `reviewed_at`: Timestamp
- `reject_reason`: Text reason for rejection

### Assigned Outdoor Facilities Table (`assigned_outdoor_facilities`)
**Purpose:** Stores facilities (auto-approval field)  
**Key Fields:**
- `property_id`: Foreign key
- `facility_id`: Facility reference
- `distance`: Distance value

### Assign Parameters Table (`assign_parameters`)
**Purpose:** Stores parameters (auto-approval field)  
**Key Fields:**
- `modal_id`: Property ID
- `modal_type`: Model type
- `parameter_id`: Parameter reference
- `value`: Parameter value

## Key Constants and Settings

### System Settings
**Setting Name:** `auto_approve_edited_listings`  
**Location:** Settings table  
**Values:**
- `0`: OFF (requires approval for owner edits)
- `1`: ON (auto-approves all owner edits)

**Access:**
```php
HelperService::getSettingData('auto_approve_edited_listings')
```

### Request Status Values
- `'pending'`: Waiting for admin approval
- `'approved'`: Changes approved and applied
- `'rejected'`: Changes rejected

### Property Classification Values
- `1`: Sell/Rent (Residential)
- `2`: Commercial
- `3`: New Project
- `4`: Vacation Homes
- `5`: Hotel Booking

## API Endpoints

### Update Property
**Endpoint:** `POST /api/update-post-property`  
**Controller:** `ApiController@updatePostProperty`  
**Location:** `ApiController.php:2307+`  
**Purpose:** Handles property updates from frontend

### Get Property Details
**Endpoint:** `GET /api/get-property-details`  
**Controller:** `ApiController@getPropertyDetails`  
**Purpose:** Retrieves property data for editing

### Check Pending Edit Request
**Endpoint:** `GET /api/check-pending-edit-request/{propertyId}`  
**Controller:** `ApiController@checkPendingEditRequest`  
**Purpose:** Checks if property has pending edit request

### Get Edit Requests (Admin)
**Endpoint:** `GET /admin/property-edit-requests`  
**Controller:** `PropertController@getEditRequests`  
**Location:** `PropertController.php:1828-1852`  
**Purpose:** Lists all edit requests for admin review

### Approve/Reject Edit Request (Admin)
**Endpoint:** `POST /admin/property-edit-requests/update-status`  
**Controller:** `PropertController@updateEditRequestStatus`  
**Location:** `PropertController.php:1898-2052`  
**Purpose:** Approves or rejects edit request

## Logging Locations

### Edit Request Created
**Location:** `PropertyEditRequestService.php:129-133`  
**Log Level:** Info  
**Message:** "Property edit request created"

### Edit Request Updated
**Location:** `PropertyEditRequestService.php:110-114`  
**Log Level:** Info  
**Message:** "Property edit request updated"

### Direct Save (No Approval)
**Location:** `ApiController.php:3132-3135`  
**Log Level:** Info  
**Message:** "Property saved directly (only non-approval fields changed)"

### Facilities Saved
**Location:** `ApiController.php:3122-3125`  
**Log Level:** Info  
**Message:** "Facilities saved directly (no approval required)"

### Auto-Approved
**Location:** `ApiController.php:3172-3175`  
**Log Level:** Info  
**Message:** "Owner edit auto-approved (bypassing edit request)"

## Testing Key Points

### Test Scenarios
1. **Owner + Auto-Approve OFF + Approval Fields:** Should create edit request
2. **Owner + Auto-Approve OFF + Auto Fields:** Should save directly
3. **Owner + Auto-Approve ON + Any Fields:** Should save directly
4. **Admin + Any Fields:** Should save directly
5. **Mixed Fields:** Should create edit request + save auto fields

### Key Assertions
- Edit request created when approval required
- Property status set correctly
- Non-approval fields saved immediately
- Approval fields pending until approved
- Frontend popup shows correct information

## File Structure

```
as-home-dashboard-Admin/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ApiController.php (Main API logic)
│   │       └── PropertController.php (Admin panel logic)
│   └── Services/
│       └── PropertyEditRequestService.php (Edit request service)
└── database/
    └── migrations/
        └── 2025_01_25_000000_create_property_edit_requests_table.php

new_ashome_front/
└── as-home-web-nexus/
    └── src/
        └── Components/
            └── EditPropertyTabs/
                └── EditPropertyTabs.jsx (Frontend component)
```

