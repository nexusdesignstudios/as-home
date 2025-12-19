# Category Facilities Ordering - Implementation Guide

## Overview
Both **Create** and **Edit** category forms now support:
1. Adding facilities in order
2. Reordering facilities via drag-and-drop
3. Previewing the order before saving
4. Saving the order correctly to the database

---

## Create Category Form

### Features:
- **Facilities Selection**: Multi-select dropdown to choose facilities
- **Order Preview**: Selected facilities appear below in a draggable list
- **Drag & Drop**: Reorder facilities by dragging
- **Order Preview Text**: Shows the final order as "Facility 1 → Facility 2 → Facility 3"
- **Auto-save Order**: Order is saved to `parameter_types` field

### How It Works:
1. User selects facilities from dropdown
2. Facilities appear in preview area in selection order
3. User can drag-and-drop to reorder
4. Preview text updates automatically
5. On form submit, `create_seq` field contains ordered facility IDs
6. Controller saves order to `parameter_types` field

### Code Location:
- **View**: `resources/views/categories/index.blade.php` (lines 69-95)
- **JavaScript**: `resources/views/categories/index.blade.php` (lines 437-475)
- **Controller**: `app/Http/Controllers/CategoryController.php` (line 72-74)

---

## Edit Category Form

### Features:
- **Loads Saved Order**: When modal opens, facilities appear in saved order
- **Preserves Order on Add**: When adding new facilities, existing order is preserved
- **Adds New at End**: Newly added facilities appear at the end
- **Removes Deselected**: Facilities that are deselected are removed
- **Drag & Drop**: Reorder facilities by dragging
- **Saves Order**: Order is saved to `parameter_types` field

### How It Works:
1. Modal opens with facilities in saved order (`parameter_types`)
2. User can add/remove facilities from dropdown
3. **When adding**: New facilities are appended at the end, existing order preserved
4. **When removing**: Facility is removed, remaining order preserved
5. User can drag-and-drop to reorder
6. On form submit, `update_seq` field contains ordered facility IDs
7. Controller saves order to `parameter_types` field

### Code Location:
- **View**: `resources/views/categories/index.blade.php` (lines 254-280)
- **JavaScript**: 
  - Change handler: `resources/views/categories/index.blade.php` (lines 532-610)
  - Modal open: `resources/views/categories/index.blade.php` (lines 668-731)
- **Controller**: `app/Http/Controllers/CategoryController.php` (line 155)

---

## Order Preservation Logic

### Edit Form - Adding Facilities:
```javascript
// Preserves existing order, adds new at end
var preservedOrder = existingOrder.filter(function(id) {
    return $.inArray(id, selectedIds) !== -1;
});

var newFacilities = selectedIds.filter(function(id) {
    return $.inArray(id, currentDisplayedIds) === -1;
});

var finalOrder = preservedOrder.slice(); // Preserved order first
newFacilities.forEach(function(id) {
    if ($.inArray(id, finalOrder) === -1) {
        finalOrder.push(id); // New facilities at end
    }
});
```

### Edit Form - Removing Facilities:
```javascript
// Removes deselected facilities, preserves order of remaining
$('#par .seq').each(function() {
    var id = $(this).attr('id');
    if ($.inArray(id, selectedIds) === -1) {
        $(this).remove(); // Remove if not selected
    }
});
```

---

## Database Storage

### Field:
- **`categories.parameter_types`**: Stores comma-separated facility IDs in order
- Example: `"5,12,8,3,15"` means:
  - Facility 5 is first
  - Facility 12 is second
  - Facility 8 is third
  - etc.

### Saving:
- **Create**: `$categoryData['parameter_types'] = $request->create_seq;`
- **Edit**: `$Category->parameter_types = $request->update_seq;`

---

## Frontend Display

The order saved in `parameter_types` is used by:
1. **Property Model Accessor** (`Property::getParametersAttribute()`)
   - Sorts facilities by category's `parameter_types` order
2. **API Endpoints**:
   - `GET /api/get-property-list` - Returns properties with ordered facilities
   - `GET /api/get-facilities-for-filter` - Returns facilities in category order
3. **Frontend Website**:
   - Displays facilities in the exact order received from API

---

## Verification Steps

### 1. Create Category:
1. Go to Categories page
2. Click "Add Category"
3. Select facilities from dropdown
4. Verify facilities appear in preview area
5. Drag and drop to reorder
6. Verify preview text updates
7. Save category
8. Verify order is saved correctly

### 2. Edit Category:
1. Click edit on a category
2. Verify facilities appear in saved order
3. Add a new facility
4. Verify new facility appears at the end
5. Remove a facility
6. Verify remaining facilities keep their order
7. Drag and drop to reorder
8. Save category
9. Verify order is saved correctly

### 3. Frontend Verification:
1. View a property in the category
2. Verify facilities appear in the order set in category
3. Check API response to verify order

---

## Important Notes

1. **Order is Preserved**: When adding/removing facilities, existing order is maintained
2. **New Facilities at End**: Newly added facilities appear at the end (can be reordered)
3. **Drag-and-Drop**: Always available to reorder facilities
4. **Frontend Respects Order**: Frontend displays facilities in the exact order saved
5. **No Re-sorting**: Frontend does NOT re-sort; it uses backend order

---

## Troubleshooting

### Issue: Order not saving
**Check**:
- `create_seq` or `update_seq` field is being sent in form
- Controller is receiving and saving the value
- Database field `parameter_types` is being updated

### Issue: Order not showing in frontend
**Check**:
- Category's `parameter_types` field has correct order
- Property model accessor is using category order
- API endpoints are eager-loading `parameter_types`

### Issue: Order changes when adding facilities
**Solution**: The code now preserves existing order and adds new facilities at the end. If you want different behavior, modify the `finalOrder` logic.

