# Property Edit Flow Scenarios

## Overview
This document describes all possible scenarios when editing a property, showing the complete flow from user action to final result.

## Scenario Matrix

| User Type | Auto-Approve Setting | Fields Changed | Result | Edit Request Created? |
|-----------|---------------------|----------------|--------|----------------------|
| Owner | OFF | Approval-required only | Pending approval | ✅ Yes |
| Owner | OFF | Auto-approve only | Immediate save | ❌ No |
| Owner | OFF | Mixed (both types) | Partial immediate + Pending | ✅ Yes (for approval fields) |
| Owner | ON | Any fields | Immediate save (all) | ❌ No |
| Admin | N/A | Any fields | Immediate save (all) | ❌ No |

## Detailed Scenarios

### Scenario 1: Owner Edit - Auto-Approve OFF - Approval Fields Changed

**User:** Property Owner (`added_by != 0`)  
**Setting:** `auto_approve_edited_listings = 0` (OFF)  
**Fields Changed:** Title, Description, Images, or Hotel Room Descriptions

#### Flow:
```
1. User edits property form
   └─> Changes: title, description, title_image
   
2. User clicks "Submit"
   └─> Frontend: hasApprovalRequiredChanges() returns true
   └─> Frontend: getChangedApprovalFields() returns list
   
3. API Request sent to backend
   └─> Backend: Checks isOwnerEdit = true
   └─> Backend: Checks autoApproveEdited = false
   └─> Backend: hasApprovalRequiredChanges() returns true
   
4. Backend Processing
   └─> Creates PropertyEditRequest
   └─> Sets property.request_status = 'pending'
   └─> Saves property (with pending status)
   └─> Saves facilities/parameters immediately (if changed)
   
5. Response to Frontend
   └─> Returns edit_request object with status 'pending_approval'
   └─> Returns property data
   
6. Frontend Display
   └─> Shows success popup with:
       • List of fields requiring approval
       • "What happens next?" information
       • "Saved immediately" section (if applicable)
   └─> Redirects to dashboard
```

#### Result:
- ✅ **Edit Request Created:** Yes
- ✅ **Property Status:** `request_status = 'pending'`
- ✅ **Changes Visible:** No (pending approval)
- ✅ **Non-Approval Fields:** Saved immediately if changed
- ✅ **User Notification:** Detailed popup showing approval requirements

#### Code Path:
- Frontend: `EditPropertyTabs.jsx:2574, 2794-2852`
- Backend: `ApiController.php:2978-3109`

---

### Scenario 2: Owner Edit - Auto-Approve OFF - Only Non-Approval Fields Changed

**User:** Property Owner (`added_by != 0`)  
**Setting:** `auto_approve_edited_listings = 0` (OFF)  
**Fields Changed:** Price, Facilities, Parameters, Property Type, etc.

#### Flow:
```
1. User edits property form
   └─> Changes: price, facilities, parameters
   
2. User clicks "Submit"
   └─> Frontend: hasApprovalRequiredChanges() returns false
   
3. API Request sent to backend
   └─> Backend: Checks isOwnerEdit = true
   └─> Backend: Checks autoApproveEdited = false
   └─> Backend: hasApprovalRequiredChanges() returns false
   
4. Backend Processing
   └─> Skips edit request creation
   └─> Saves facilities directly
   └─> Sets property.request_status = 'approved'
   └─> Saves property with all changes
   
5. Response to Frontend
   └─> Returns success message
   └─> Returns updated property data
   
6. Frontend Display
   └─> Shows success popup: "Property updated successfully"
   └─> Redirects to dashboard
```

#### Result:
- ❌ **Edit Request Created:** No
- ✅ **Property Status:** `request_status = 'approved'`
- ✅ **Changes Visible:** Yes (immediately)
- ✅ **All Fields:** Saved immediately
- ✅ **User Notification:** Simple success message

#### Code Path:
- Frontend: `EditPropertyTabs.jsx:2574, 2798-2800`
- Backend: `ApiController.php:3110-3136`

---

### Scenario 3: Owner Edit - Auto-Approve OFF - Mixed Fields Changed

**User:** Property Owner (`added_by != 0`)  
**Setting:** `auto_approve_edited_listings = 0` (OFF)  
**Fields Changed:** Title (approval) + Price (auto-approve) + Facilities (auto-approve)

#### Flow:
```
1. User edits property form
   └─> Changes: title, price, facilities
   
2. User clicks "Submit"
   └─> Frontend: hasApprovalRequiredChanges() returns true
   └─> Frontend: getChangedApprovalFields() returns ['Property Title (English)']
   
3. API Request sent to backend
   └─> Backend: Checks isOwnerEdit = true
   └─> Backend: Checks autoApproveEdited = false
   └─> Backend: hasApprovalRequiredChanges() returns true
   
4. Backend Processing
   └─> Creates PropertyEditRequest (for title only)
   └─> Sets property.request_status = 'pending'
   └─> Saves facilities directly (immediate)
   └─> Saves price directly (immediate)
   └─> Saves property (title pending approval)
   
5. Response to Frontend
   └─> Returns edit_request object
   └─> Returns property data (with price/facilities updated)
   
6. Frontend Display
   └─> Shows success popup with:
       • "Property Title (English)" requires approval
       • Price and Facilities saved immediately
       • "What happens next?" information
   └─> Redirects to dashboard
```

#### Result:
- ✅ **Edit Request Created:** Yes (for approval fields only)
- ⚠️ **Property Status:** `request_status = 'pending'` (but some fields already saved)
- ✅ **Approval Fields Visible:** No (pending)
- ✅ **Auto-Approval Fields Visible:** Yes (immediately)
- ✅ **User Notification:** Detailed popup showing both behaviors

#### Code Path:
- Frontend: `EditPropertyTabs.jsx:2574, 2794-2852`
- Backend: `ApiController.php:2978-3109, 3113-3126`

---

### Scenario 4: Owner Edit - Auto-Approve ON

**User:** Property Owner (`added_by != 0`)  
**Setting:** `auto_approve_edited_listings = 1` (ON)  
**Fields Changed:** Any fields (approval-required or not)

#### Flow:
```
1. User edits property form
   └─> Changes: title, description, price, facilities
   
2. User clicks "Submit"
   └─> Frontend: hasApprovalRequiredChanges() returns true
   └─> Frontend: requiresApproval = true (but will be bypassed)
   
3. API Request sent to backend
   └─> Backend: Checks isOwnerEdit = true
   └─> Backend: Checks autoApproveEdited = true
   └─> Skips approval check (auto-approve bypasses all)
   
4. Backend Processing
   └─> Skips edit request creation
   └─> Saves facilities directly
   └─> Sets property.request_status = 'approved'
   └─> Saves property with ALL changes (including title, description, etc.)
   
5. Response to Frontend
   └─> Returns success message
   └─> Returns updated property data
   
6. Frontend Display
   └─> Shows success popup: "Property updated successfully"
   └─> Redirects to dashboard
```

#### Result:
- ❌ **Edit Request Created:** No
- ✅ **Property Status:** `request_status = 'approved'`
- ✅ **Changes Visible:** Yes (immediately, all fields)
- ✅ **All Fields:** Saved immediately (bypasses approval)
- ✅ **User Notification:** Simple success message

#### Code Path:
- Frontend: `EditPropertyTabs.jsx:2574, 2798-2800`
- Backend: `ApiController.php:3137-3178`

---

### Scenario 5: Admin Edit

**User:** Admin (`added_by == 0`)  
**Setting:** N/A (admins bypass all approval)  
**Fields Changed:** Any fields

#### Flow:
```
1. Admin edits property form
   └─> Changes: any fields
   
2. Admin clicks "Submit"
   └─> Frontend: Same validation as owner
   
3. API Request sent to backend
   └─> Backend: Checks isOwnerEdit = false
   └─> Skips all approval checks
   
4. Backend Processing
   └─> Skips edit request creation
   └─> Saves all fields directly
   └─> Sets property.request_status = 'approved'
   └─> Saves property with all changes
   
5. Response to Frontend
   └─> Returns success message
   └─> Returns updated property data
   
6. Frontend Display
   └─> Shows success popup: "Property updated successfully"
   └─> Redirects to dashboard
```

#### Result:
- ❌ **Edit Request Created:** No
- ✅ **Property Status:** `request_status = 'approved'`
- ✅ **Changes Visible:** Yes (immediately)
- ✅ **All Fields:** Saved immediately (no restrictions)
- ✅ **User Notification:** Simple success message

#### Code Path:
- Frontend: `EditPropertyTabs.jsx:2574, 2798-2800`
- Backend: `ApiController.php:3137-3168`

---

## Decision Tree

```
User Submits Property Edit
│
├─ Is Admin? (added_by == 0)
│  └─ YES → Save All Fields Immediately → Approved Status
│
└─ NO (Owner)
   │
   ├─ Auto-Approve ON? (auto_approve_edited_listings == 1)
   │  └─ YES → Save All Fields Immediately → Approved Status
   │
   └─ NO (Auto-Approve OFF)
      │
      ├─ Approval-Required Fields Changed?
      │  │
      │  ├─ YES → Create Edit Request → Pending Status
      │  │        └─ Save Non-Approval Fields Immediately
      │  │
      │  └─ NO → Save All Fields Immediately → Approved Status
```

## Key Behaviors

### 1. Selective Approval
- Only specific fields trigger approval
- Other fields save immediately even when approval-required fields are changed
- User sees mixed behavior: some fields pending, some saved

### 2. Auto-Approve Bypass
- When auto-approve is ON, ALL fields bypass approval
- No edit requests created regardless of field type
- All changes applied immediately

### 3. Admin Privileges
- Admins bypass all approval checks
- All fields save immediately
- No restrictions on any field

### 4. Mixed Field Changes
- When both types are changed:
  - Approval-required → Edit request created
  - Auto-approval → Saved immediately
  - User sees detailed popup explaining both

## User Experience Summary

| Scenario | User Sees | Fields Saved | Fields Pending |
|----------|-----------|--------------|----------------|
| Owner + OFF + Approval fields | Detailed approval popup | Non-approval only | Approval fields |
| Owner + OFF + Auto fields | Simple success | All fields | None |
| Owner + OFF + Mixed | Detailed approval popup | Auto fields | Approval fields |
| Owner + ON + Any | Simple success | All fields | None |
| Admin + Any | Simple success | All fields | None |

## Code References

### Backend Decision Points
- **Owner Check:** `ApiController.php:2971` - `$isOwnerEdit = $property->added_by != 0;`
- **Auto-Approve Check:** `ApiController.php:2974` - `$autoApproveEdited = HelperService::getSettingData('auto_approve_edited_listings') == 1;`
- **Approval Check:** `ApiController.php:2991` - `hasApprovalRequiredChanges()`
- **Edit Request Creation:** `ApiController.php:3051` - `saveEditRequest()`
- **Direct Save:** `ApiController.php:3131` - `$property->save()`

### Frontend Decision Points
- **Approval Check:** `EditPropertyTabs.jsx:2574` - `requiresApproval = hasApprovalRequiredChanges()`
- **Changed Fields List:** `EditPropertyTabs.jsx:2795` - `getChangedApprovalFields()`
- **Success Message:** `EditPropertyTabs.jsx:2798-2852` - Swal.fire popup

