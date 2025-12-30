# Property Edit User Experience Flow

## Overview
This document describes what users see and experience at each step of the property editing process.

## User Journey: Property Owner Editing Property

### Step 1: Accessing Edit Form

**User Action:** Navigates to property edit page  
**What User Sees:**
- Property edit form with all fields
- Current property data pre-filled
- Form tabs (Basic Info, Parameters, Facilities, etc.)

**System Behavior:**
- Loads property data from database
- Checks for pending edit requests
- If pending request exists: Shows warning and disables form

**User Experience:**
```
✅ Form loads normally
⚠️ If pending request: Warning message appears
   "This property has a pending edit request. 
    Please wait for approval before making additional changes."
   Form fields are disabled (grayed out)
```

---

### Step 2: Making Changes

**User Action:** Edits various fields in the form

#### Scenario A: Editing Approval-Required Fields
**Fields Changed:** Title, Description, Images

**What User Sees:**
- Form fields are editable (no restrictions)
- No warnings or indicators during editing
- Can change multiple fields freely

**User Experience:**
```
User edits:
- Property Title: "Luxury Apartment" → "Premium Luxury Apartment"
- Description: Adds new paragraph
- Uploads new title image

No warnings shown during editing
Form behaves normally
```

#### Scenario B: Editing Auto-Approval Fields
**Fields Changed:** Price, Facilities, Parameters

**What User Sees:**
- Form fields are editable
- No warnings or indicators
- Can change freely

**User Experience:**
```
User edits:
- Price: 1000 → 1200
- Adds new facility: "Beach" at 500m
- Updates parameter: "Bedrooms" → 3

No warnings shown during editing
Form behaves normally
```

#### Scenario C: Editing Mixed Fields
**Fields Changed:** Title (approval) + Price (auto-approval) + Facilities (auto-approval)

**What User Sees:**
- All fields editable
- No distinction shown between field types
- Can edit all fields freely

**User Experience:**
```
User edits:
- Title: "Apartment" → "Luxury Apartment" (approval required)
- Price: 1000 → 1200 (auto-approval)
- Facilities: Adds "Park" (auto-approval)

No warnings shown during editing
All fields look the same
```

---

### Step 3: Submitting Changes

**User Action:** Clicks "Submit" or "Update Property" button

**What User Sees:**
- Loading indicator/spinner
- Form submission in progress

**System Behavior:**
- Frontend validates form
- Checks which fields changed
- Determines if approval required
- Sends API request to backend

**User Experience:**
```
✅ Click "Submit"
⏳ Loading spinner appears
   "Updating property..."
```

---

### Step 4: Response - Approval Required

**Scenario:** Owner + Auto-Approve OFF + Approval Fields Changed

**What User Sees:**
- Success popup with detailed information
- List of fields requiring approval
- "What happens next?" section
- "Saved immediately" section (if applicable)

**Popup Content:**
```
┌─────────────────────────────────────────────────────┐
│  ✅ Your changes have been submitted for approval   │
│                                                     │
│  📋 Fields requiring admin approval (2):            │
│     • Property Title (English)                      │
│     • Title Image                                   │
│                                                     │
│  ℹ️ What happens next?                              │
│     • An admin will review your changes             │
│     • You'll be notified once approved or rejected  │
│     • Changes will be visible on your property      │
│       listing after approval                        │
│                                                     │
│  ✅ Saved immediately:                               │
│     Price, Facilities, Parameters, Property Type,   │
│     and other operational settings were updated     │
│     right away.                                     │
│                                                     │
│              [ OK ]                                 │
└─────────────────────────────────────────────────────┘
```

**User Experience:**
- Clear understanding of what requires approval
- Knows which fields were saved immediately
- Understands next steps in the process
- Redirected to dashboard after clicking OK

---

### Step 4: Response - Direct Save (No Approval)

**Scenario:** Owner + Auto-Approve OFF + Only Auto-Approval Fields Changed

**What User Sees:**
- Simple success popup
- Success message

**Popup Content:**
```
┌─────────────────────────────────────┐
│  ✅ Property updated successfully   │
│                                     │
│  All changes have been applied.     │
│                                     │
│           [ OK ]                    │
└─────────────────────────────────────┘
```

**User Experience:**
- Simple confirmation
- Knows changes are live immediately
- Redirected to dashboard

---

### Step 4: Response - Auto-Approve ON

**Scenario:** Owner + Auto-Approve ON + Any Fields Changed

**What User Sees:**
- Simple success popup (same as direct save)
- Success message

**Popup Content:**
```
┌─────────────────────────────────────┐
│  ✅ Property updated successfully   │
│                                     │
│  All changes have been applied.     │
│                                     │
│           [ OK ]                    │
└─────────────────────────────────────┘
```

**User Experience:**
- All changes applied immediately
- No approval process mentioned
- Simple confirmation

---

### Step 5: After Submission - Pending Approval

**Scenario:** Edit request created, waiting for admin approval

**What User Sees:**
- Property still shows old values (approval fields)
- New values visible for auto-approval fields
- Can view property but approval fields unchanged

**User Experience:**
```
Property Listing Shows:
- Title: "Luxury Apartment" (old - pending approval)
- Price: 1200 (new - saved immediately)
- Facilities: Updated (new - saved immediately)
- Description: Old description (pending approval)
```

**User Actions:**
- Can view property
- Cannot make new edits (if pending request exists)
- Waits for admin approval notification

---

### Step 6: Admin Approval/Rejection

**User Action:** Receives notification (email/system notification)

#### If Approved:
**What User Sees:**
- Notification: "Your property edit request has been approved"
- Property listing updated with new values
- All changes now visible

**User Experience:**
```
✅ Notification received
✅ Property listing updated
✅ All changes now visible:
   - Title: "Premium Luxury Apartment" (approved)
   - Description: New description (approved)
   - Images: New images (approved)
```

#### If Rejected:
**What User Sees:**
- Notification: "Your property edit request has been rejected"
- Rejection reason (if provided)
- Property listing unchanged

**User Experience:**
```
❌ Notification received
❌ Rejection reason: "Title contains inappropriate content"
❌ Property listing unchanged
❌ Can submit new edit request
```

---

## Visual Flow: What User Sees

### Flow 1: Approval Required

```
1. Edit Form
   └─> User edits Title, Description, Images
   
2. Submit Button
   └─> Click "Submit"
   
3. Loading
   └─> Spinner: "Updating property..."
   
4. Success Popup
   └─> Detailed popup with:
       • Fields requiring approval (list)
       • What happens next (info box)
       • Saved immediately (info box)
   
5. Dashboard
   └─> Redirected to user dashboard
   
6. Property Listing
   └─> Shows:
       • Old values (approval fields - pending)
       • New values (auto fields - saved)
   
7. Notification (Later)
   └─> "Your property edit has been approved"
   
8. Property Listing (After Approval)
   └─> Shows all new values
```

### Flow 2: Direct Save (No Approval)

```
1. Edit Form
   └─> User edits Price, Facilities
   
2. Submit Button
   └─> Click "Submit"
   
3. Loading
   └─> Spinner: "Updating property..."
   
4. Success Popup
   └─> Simple popup: "Property updated successfully"
   
5. Dashboard
   └─> Redirected to user dashboard
   
6. Property Listing
   └─> Shows all new values immediately
```

### Flow 3: Mixed Fields

```
1. Edit Form
   └─> User edits Title (approval) + Price (auto)
   
2. Submit Button
   └─> Click "Submit"
   
3. Loading
   └─> Spinner: "Updating property..."
   
4. Success Popup
   └─> Detailed popup with:
       • "Property Title (English)" requires approval
       • Price saved immediately
       • What happens next
   
5. Dashboard
   └─> Redirected to user dashboard
   
6. Property Listing
   └─> Shows:
       • Title: Old value (pending)
       • Price: New value (saved)
   
7. Notification (Later)
   └─> "Your property edit has been approved"
   
8. Property Listing (After Approval)
   └─> Shows all new values
```

## User Interface Elements

### Form Fields

**Approval-Required Fields:**
- No visual distinction during editing
- No indicators or warnings
- Look identical to auto-approval fields

**Auto-Approval Fields:**
- No visual distinction during editing
- No indicators
- Look identical to approval-required fields

**Note:** Currently, there's no visual indication of which fields require approval during editing. This could be improved with:
- Info icons (ℹ️) next to approval-required fields
- Tooltips explaining approval requirements
- Color coding or badges

### Submit Button

**Appearance:**
- Standard submit button
- No indication of approval requirements
- Same for all scenarios

**Behavior:**
- Validates form before submission
- Shows loading state during submission
- Triggers appropriate popup based on fields changed

### Success Popup

**When Approval Required:**
- Width: 600px (wider for content)
- Title: "Your changes have been submitted for approval"
- Content: Detailed HTML with:
  - Field list (bulleted)
  - Info boxes (blue and green)
  - Clear formatting

**When No Approval:**
- Standard width
- Title: "Property updated successfully"
- Content: Simple message

### Pending Request Warning

**When Pending Request Exists:**
- Alert banner at top of form
- Message: "This property has a pending edit request..."
- Form fields disabled (grayed out)
- Submit button disabled

## User Confusion Points

### Current Issues:

1. **No Pre-Submission Warning**
   - User doesn't know which fields require approval until after submission
   - Could be surprised by approval requirement

2. **No Field-Level Indicators**
   - All fields look the same
   - No way to know which require approval before editing

3. **Mixed Behavior Not Clear**
   - When both types are changed, user might be confused
   - Some fields saved, some pending - not obvious

### Improvements Needed:

1. **Info Banner**
   - Show at top of form: "These fields require approval: ..."
   - Show: "These fields save immediately: ..."

2. **Field Indicators**
   - Add info icons to approval-required fields
   - Tooltips explaining approval process

3. **Real-Time Feedback**
   - Show warning when editing approval-required fields
   - Preview of what will require approval

4. **Better Popup**
   - Already improved with detailed information
   - Could add timeline estimate
   - Could add link to check approval status

## User Expectations vs. Reality

### User Expectation:
"I edited my property, so all changes should be visible immediately"

### Reality:
"Some changes require approval and won't be visible until admin approves"

### How System Handles This:
- Detailed popup explains what requires approval
- Shows which fields were saved immediately
- Explains what happens next
- Clear communication reduces confusion

## Summary

### What Works Well:
✅ Detailed success popup with field list  
✅ Clear "What happens next?" information  
✅ "Saved immediately" section clarifies behavior  
✅ Pending request check prevents duplicate edits  

### What Could Be Improved:
⚠️ No pre-submission indicators  
⚠️ No field-level visual distinction  
⚠️ No real-time approval requirement warnings  
⚠️ Could add approval status tracking page  

### Overall User Experience:
- **Clear:** Users understand what requires approval after submission
- **Informative:** Detailed popup explains everything
- **Functional:** System works as designed
- **Could be more proactive:** Better to show information before submission

