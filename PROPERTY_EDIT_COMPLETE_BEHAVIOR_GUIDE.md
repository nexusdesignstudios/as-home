# Property Edit Complete Behavior Guide

## Overview
This comprehensive guide documents the complete behavior of the property editing system, including field classifications, edit flows, code references, and user experience.

## Table of Contents

1. [Quick Reference](#quick-reference)
2. [Field Classification](#field-classification)
3. [Edit Flow Scenarios](#edit-flow-scenarios)
4. [Visual Diagrams](#visual-diagrams)
5. [Code References](#code-references)
6. [User Experience](#user-experience)
7. [Implementation Details](#implementation-details)

## Quick Reference

### Fields Requiring Approval
- Property Title (English & Arabic)
- Property Description (English & Arabic)
- Area Description (English & Arabic)
- Images (Title, Gallery, 3D, Meta)
- Hotel Room Descriptions

### Fields with Automatic Approval (Live Update)
- Price
- Facilities
- Parameters
- Property Type
- Rent Duration
- Hotel/Vacation operational settings
- Contact information

### Key Behaviors
- **Selective Approval:** Only specific fields trigger approval
- **Auto-Approve Setting:** When ON, all fields bypass approval
- **Admin Privileges:** Admins bypass all approval
- **Mixed Changes:** Approval fields pending, auto fields saved immediately

## Field Classification

### Approval-Required Fields

These fields require admin approval before changes are visible:

| Field | Database Field | Notes |
|-------|---------------|-------|
| Property Title (English) | `title` | Both title fields checked separately |
| Property Title (Arabic) | `title_ar` | Both title fields checked separately |
| Property Description (English) | `description` | Both description fields checked separately |
| Property Description (Arabic) | `description_ar` | Both description fields checked separately |
| Area Description (English) | `area_description` | Both area description fields checked separately |
| Area Description (Arabic) | `area_description_ar` | Both area description fields checked separately |
| Title Image | `title_image` | Only new uploads trigger approval |
| Gallery Images | `gallery_images` | Only new uploads trigger approval |
| 3D Image | `three_d_image` | Only new uploads trigger approval |
| Meta Image | `meta_image` | Only new uploads trigger approval |
| Hotel Room Descriptions | `hotel_rooms[].description` | Only description field, not other room properties |

### Automatic Approval Fields (Live Update)

These fields save immediately without approval:

**Basic Information:**
- Price, Property Type, Rent Duration, Category, Classification, Status, Is Premium

**Facilities & Parameters:**
- Outdoor Facilities, Custom Parameters

**Hotel-Specific:**
- Check-in/out times, Available rooms, Hotel VAT, Instant booking, Non-refundable, Agent addons

**Vacation Home:**
- Availability type, Available dates, Apartment quantities

**Contact Information:**
- Company employee details, Revenue contacts, Reservation contacts

**Other:**
- Video link, Meta tags, Slug, Documents, Certificates

**For complete list, see:** [PROPERTY_EDIT_FIELD_CLASSIFICATION.md](PROPERTY_EDIT_FIELD_CLASSIFICATION.md)

## Edit Flow Scenarios

### Scenario 1: Owner + Auto-Approve OFF + Approval Fields Changed

**Flow:**
1. User edits approval-required fields
2. Submits form
3. System creates edit request
4. Property status: `pending`
5. Non-approval fields saved immediately
6. User sees detailed approval popup

**Result:**
- ✅ Edit request created
- ⏳ Approval fields pending
- ✅ Auto-approval fields saved immediately

### Scenario 2: Owner + Auto-Approve OFF + Only Auto Fields Changed

**Flow:**
1. User edits only auto-approval fields
2. Submits form
3. System saves directly
4. Property status: `approved`
5. User sees simple success popup

**Result:**
- ❌ No edit request
- ✅ All fields saved immediately
- ✅ Changes visible right away

### Scenario 3: Owner + Auto-Approve ON + Any Fields

**Flow:**
1. User edits any fields
2. Submits form
3. System saves directly (bypasses approval)
4. Property status: `approved`
5. User sees simple success popup

**Result:**
- ❌ No edit request
- ✅ All fields saved immediately
- ✅ Changes visible right away

### Scenario 4: Admin + Any Fields

**Flow:**
1. Admin edits any fields
2. Submits form
3. System saves directly (no restrictions)
4. Property status: `approved`
5. Admin sees simple success popup

**Result:**
- ❌ No edit request
- ✅ All fields saved immediately
- ✅ Changes visible right away

**For detailed flows, see:** [PROPERTY_EDIT_FLOW_SCENARIOS.md](PROPERTY_EDIT_FLOW_SCENARIOS.md)

## Visual Diagrams

### Main Decision Tree

```
User Submits Edit
│
├─ Is Admin? → Save All Immediately
│
└─ Is Owner?
   │
   ├─ Auto-Approve ON? → Save All Immediately
   │
   └─ Auto-Approve OFF?
      │
      ├─ Approval Fields Changed? → Create Edit Request
      │
      └─ Only Auto Fields? → Save Directly
```

**For complete diagrams, see:** [PROPERTY_EDIT_FLOW_DIAGRAMS.md](PROPERTY_EDIT_FLOW_DIAGRAMS.md)

## Code References

### Backend

**Main Files:**
- `ApiController.php`: Main API logic (lines 2981-3136, 12537-12609)
- `PropertyEditRequestService.php`: Edit request service (lines 17-74, 84-146)
- `PropertController.php`: Admin panel logic (lines 870-943)

**Key Methods:**
- `hasApprovalRequiredChanges()`: Checks if approval required
- `getAllowedEditableFields()`: Returns approval-required fields
- `filterAllowedFields()`: Filters edited data
- `saveEditRequest()`: Creates edit request
- `applyEditRequest()`: Applies approved changes

### Frontend

**Main File:**
- `EditPropertyTabs.jsx`: Property edit component

**Key Functions:**
- `hasApprovalRequiredChanges()`: Checks form state (lines 581-637)
- `getChangedApprovalFields()`: Lists changed fields (lines 639-700)
- Success popup: Shows detailed information (lines 2794-2852)

**For complete code references, see:** [PROPERTY_EDIT_CODE_REFERENCES.md](PROPERTY_EDIT_CODE_REFERENCES.md)

## User Experience

### What Users See

**During Editing:**
- Form with all fields editable
- No visual distinction between field types
- No warnings or indicators

**After Submission (Approval Required):**
- Detailed popup showing:
  - List of fields requiring approval
  - "What happens next?" information
  - "Saved immediately" section
- Redirect to dashboard

**After Submission (No Approval):**
- Simple success popup
- Redirect to dashboard

**Pending Approval:**
- Property shows old values (approval fields)
- New values visible (auto-approval fields)
- Cannot make new edits until approved

**After Approval:**
- Notification received
- All changes visible on listing

**For complete user experience, see:** [PROPERTY_EDIT_USER_EXPERIENCE_FLOW.md](PROPERTY_EDIT_USER_EXPERIENCE_FLOW.md)

## Implementation Details

### Approval Logic

**Backend Decision Process:**
1. Check if owner edit (`added_by != 0`)
2. Check auto-approve setting
3. If owner + auto-approve OFF:
   - Check if approval-required fields changed
   - If yes: Create edit request
   - If no: Save directly
4. If owner + auto-approve ON: Save directly
5. If admin: Save directly

**Frontend Decision Process:**
1. Check form state vs. original data
2. Determine if approval required
3. Show appropriate popup based on result

### Field Change Detection

**Backend:**
- Compares request values with current property values
- For images: Checks `hasFile()` for new uploads
- For hotel rooms: Compares only description field

**Frontend:**
- Compares form state with `originalPropertyData`
- For images: Checks for File objects
- For hotel rooms: Compares description fields

### Edit Request Lifecycle

1. **Creation:** When owner edits approval-required fields
2. **Storage:** Saved in `property_edit_requests` table
3. **Status:** `pending` until admin action
4. **Review:** Admin approves/rejects via admin panel
5. **Application:** If approved, changes applied to property
6. **Notification:** User notified of result

### Database Tables

**`propertys` Table:**
- `request_status`: 'pending', 'approved', 'rejected'
- `added_by`: 0 = admin, >0 = owner

**`property_edit_requests` Table:**
- `status`: 'pending', 'approved', 'rejected'
- `edited_data`: JSON of changed fields
- `original_data`: JSON of original values

## Key Features

### 1. Selective Approval
- Only specific fields trigger approval
- Other fields save immediately
- User sees mixed behavior when both types changed

### 2. Auto-Approve Bypass
- System setting controls behavior
- When ON: All fields bypass approval
- When OFF: Selective approval applies

### 3. Admin Privileges
- Admins bypass all approval
- All fields save immediately
- No restrictions

### 4. Enhanced User Communication
- Detailed popup shows what requires approval
- Lists specific changed fields
- Explains what happens next
- Clarifies which fields saved immediately

## System Settings

**Setting Name:** `auto_approve_edited_listings`

**Values:**
- `0`: OFF - Requires approval for owner edits to approval-required fields
- `1`: ON - Auto-approves all owner edits

**Location:** Admin Panel → Settings → System Settings

**Access:**
```php
HelperService::getSettingData('auto_approve_edited_listings')
```

## Testing Scenarios

### Test Case 1: Owner Edit - Approval Required
1. Set auto-approve OFF
2. Edit title and description
3. Submit
4. **Expected:** Edit request created, property pending

### Test Case 2: Owner Edit - Direct Save
1. Set auto-approve OFF
2. Edit only price and facilities
3. Submit
4. **Expected:** Property saved directly, status approved

### Test Case 3: Owner Edit - Auto-Approve ON
1. Set auto-approve ON
2. Edit title and description
3. Submit
4. **Expected:** Property saved directly, status approved

### Test Case 4: Mixed Fields
1. Set auto-approve OFF
2. Edit title (approval) + price (auto)
3. Submit
4. **Expected:** Edit request for title, price saved immediately

### Test Case 5: Admin Edit
1. Login as admin
2. Edit any fields
3. Submit
4. **Expected:** All fields saved directly

## Related Documentation

- **[PROPERTY_EDIT_FIELD_CLASSIFICATION.md](PROPERTY_EDIT_FIELD_CLASSIFICATION.md)** - Complete field classification
- **[PROPERTY_EDIT_FLOW_SCENARIOS.md](PROPERTY_EDIT_FLOW_SCENARIOS.md)** - Detailed flow scenarios
- **[PROPERTY_EDIT_FLOW_DIAGRAMS.md](PROPERTY_EDIT_FLOW_DIAGRAMS.md)** - Visual flow diagrams
- **[PROPERTY_EDIT_CODE_REFERENCES.md](PROPERTY_EDIT_CODE_REFERENCES.md)** - Code location references
- **[PROPERTY_EDIT_USER_EXPERIENCE_FLOW.md](PROPERTY_EDIT_USER_EXPERIENCE_FLOW.md)** - User experience details
- **[PROPERTY_EDIT_APPROVAL_VERIFICATION_REPORT.md](PROPERTY_EDIT_APPROVAL_VERIFICATION_REPORT.md)** - Verification report
- **[PROPERTY_EDIT_SUBMIT_USER_EXPERIENCE_REPORT.md](PROPERTY_EDIT_SUBMIT_USER_EXPERIENCE_REPORT.md)** - Submit UX analysis

## Summary

### How It Works
1. **Field Classification:** Fields are categorized as approval-required or auto-approval
2. **Selective Approval:** Only approval-required fields trigger the approval process
3. **Auto-Approve Setting:** Can bypass all approval when enabled
4. **Admin Privileges:** Admins have no restrictions
5. **User Communication:** Detailed popups explain what requires approval

### Key Behaviors
- ✅ **Selective:** Only specific fields require approval
- ✅ **Flexible:** Auto-approve setting controls behavior
- ✅ **Clear:** Users understand what requires approval
- ✅ **Efficient:** Non-approval fields save immediately

### Current Status
- ✅ Field classification implemented correctly
- ✅ Approval logic working as designed
- ✅ User communication enhanced with detailed popups
- ✅ All scenarios handled properly

## Conclusion

The property edit system implements selective approval, allowing some fields to update immediately while others require admin review. The system is flexible, with auto-approve settings and admin privileges providing different levels of control. Enhanced user communication ensures users understand what requires approval and what happens next.

