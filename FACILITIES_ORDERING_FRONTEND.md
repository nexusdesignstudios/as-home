# How Facilities Are Ordered in Frontend Website

## Overview
Facilities (parameters) displayed on the frontend website are ordered based on the **category's `parameter_types` field**, which stores a comma-separated list of parameter IDs in the desired order.

---

## Ordering Flow

### 1. **Category Configuration** (Admin Panel)
- Admin sets the order of facilities for each category
- Order is saved in `categories.parameter_types` field (e.g., "5,12,8,3,15")
- This order defines how facilities appear for all properties in that category

### 2. **Property Model Accessor** (`Property::getParametersAttribute()`)
**Location**: `app/Models/Property.php` (lines 492-672)

**Process**:
1. Retrieves all parameters assigned to the property
2. Loads the property's category with `parameter_types` field
3. Extracts parameter IDs from `category.parameter_types` (comma-separated string)
4. Sorts parameters using `usort()` based on their position in the category order
5. Returns ordered array of parameters

**Key Code**:
```php
// Get category parameter order
$categoryParameterOrder = [];
if ($this->category_id) {
    $category = $this->category;
    if (!$category || !isset($category->parameter_types)) {
        $category = Category::select('id', 'parameter_types')->find($this->category_id);
    }
    if ($category && !empty($category->parameter_types)) {
        $categoryParameterOrder = explode(',', $category->parameter_types);
        $categoryParameterOrder = array_filter(array_map('intval', $categoryParameterOrder));
    }
}

// Sort parameters by category order
if (!empty($categoryParameterOrder) && !empty($parameters)) {
    usort($parameters, function ($a, $b) use ($categoryParameterOrder) {
        $indexA = array_search($a['id'], $categoryParameterOrder);
        $indexB = array_search($b['id'], $categoryParameterOrder);
        
        if ($indexA !== false && $indexB !== false) {
            return $indexA <=> $indexB; // Sort by position in category order
        }
        // Fallback logic for parameters not in category order
        if ($indexA !== false) return -1;
        if ($indexB !== false) return 1;
        return $a['id'] <=> $b['id'];
    });
}
```

### 3. **API Endpoints**

#### A. Property List Endpoint
**Route**: `GET /api/get-property-list`  
**Method**: `ApiController::getPropertyList()`

**Process**:
1. Eager loads category with `parameter_types`:
   ```php
   ->with('category:id,category,image,slug_id,parameter_types', ...)
   ```

2. Forces accessor usage for correct ordering:
   ```php
   $property->unsetRelation('parameters');
   $property->parameters = $property->parameters; // Calls getParametersAttribute()
   ```

3. Returns properties with ordered `parameters` array

#### B. Property Details Endpoint
**Route**: `GET /api/get-property`  
**Method**: `ApiController::get_property()`

**Process**:
1. Uses `get_property_details()` helper function
2. Helper function also forces accessor usage:
   ```php
   $row->unsetRelation('parameters');
   $tempRow['parameters'] = $row->parameters; // Calls accessor with ordering
   ```

#### C. Facilities Filter Endpoint
**Route**: `GET /api/get-facilities-for-filter`  
**Method**: `ApiController::getFacilitiesForFilter()`

**Process**:
1. If `category_id` is provided, orders facilities by category's `parameter_types`
2. Returns ordered list for filter form

**Code**:
```php
if ($categoryId) {
    $category = Category::find($categoryId);
    if ($category && !empty($category->parameter_types)) {
        $parameterIds = explode(',', $category->parameter_types);
        $parameterIds = array_filter(array_map('intval', $parameterIds));
        
        $parameters = parameter::whereIn('id', $parameterIds)->get();
        // Sort by category order
        $parameters = $parameters->sortBy(function ($item) use ($parameterIds) {
            return array_search($item->id, $parameterIds);
        })->values();
    }
}
```

### 4. **Frontend Display**

#### Property Cards/Listings
- **Source**: `GET /api/get-property-list`
- **Field**: `parameters` (ordered array)
- **Behavior**: Frontend displays facilities in the exact order received from API
- **Code**: Uses `forEach` or `map` which preserves array order

#### Filter Form
- **Source**: `GET /api/get-facilities-for-filter?category_id={id}`
- **Behavior**: Displays facilities in category-defined order
- **Code**: `facilities?.map((facility) => ...)` - preserves order

#### Property Details Page
- **Source**: `GET /api/get-property?id={id}`
- **Field**: `parameters` (ordered array)
- **Behavior**: Displays facilities in category-defined order

---

## Two Types of Facilities

### 1. **Parameters** (`parameters` field)
- **Ordering**: ✅ Ordered by category's `parameter_types`
- **Source**: `assign_parameters` table (many-to-many relationship)
- **Used for**: Property-specific facilities (bedrooms, bathrooms, etc.)

### 2. **Assign Facilities** (`assign_facilities` field)
- **Ordering**: ⚠️ Preserves database insertion order (no explicit ordering)
- **Source**: `assigned_outdoor_facilities` table
- **Used for**: Outdoor facilities (parking, pool, etc.)

---

## Example Flow

### Scenario: Hotel Category with Custom Facility Order

1. **Admin sets order** in category edit:
   - Facilities: "Swimming Pool" (ID: 5), "WiFi" (ID: 12), "Parking" (ID: 8)
   - Order saved: `parameter_types = "5,12,8"`

2. **Property created**:
   - Property assigned to Hotel category
   - Facilities assigned: WiFi, Parking, Swimming Pool

3. **API request**:
   - Frontend calls: `GET /api/get-property-list`
   - Backend loads property with category
   - `getParametersAttribute()` sorts facilities by category order

4. **Response**:
   ```json
   {
     "parameters": [
       {"id": 5, "name": "Swimming Pool", ...},  // First (position 0 in order)
       {"id": 12, "name": "WiFi", ...},         // Second (position 1)
       {"id": 8, "name": "Parking", ...}        // Third (position 2)
     ]
   }
   ```

5. **Frontend display**:
   - Facilities shown in exact order: Swimming Pool → WiFi → Parking

---

## Verification Checklist

### ✅ Working Correctly
- [x] Category `parameter_types` field stores facility order
- [x] `Property::getParametersAttribute()` sorts by category order
- [x] API endpoints eager-load `parameter_types` with category
- [x] API endpoints force accessor usage (unsetRelation)
- [x] Frontend preserves order as received from API

### 🔍 How to Verify

1. **Check Category Order**:
   ```sql
   SELECT id, category, parameter_types FROM categories WHERE id = {category_id};
   ```

2. **Check Property Response**:
   - Call API: `GET /api/get-property?id={property_id}`
   - Verify `parameters` array order matches `category.parameter_types`

3. **Check Frontend**:
   - View property listing/details page
   - Verify facilities appear in category-defined order

---

## Important Notes

1. **Category Order is Required**: If category has no `parameter_types`, facilities fall back to ID order
2. **Accessor is Key**: The `getParametersAttribute()` accessor handles all ordering logic
3. **Eager Loading**: Category must be eager-loaded with `parameter_types` for ordering to work
4. **Relationship Cache**: Must unset relationship cache to force accessor usage
5. **Frontend Preserves Order**: Frontend does NOT re-sort; it displays in API order

---

## Troubleshooting

### Issue: Facilities not in correct order
**Check**:
1. Category has `parameter_types` set
2. Category is eager-loaded with `parameter_types` in API
3. Accessor is being called (relationship cache unset)
4. Frontend is not re-sorting the array

### Issue: Order changes not reflected
**Solution**:
1. Update category's `parameter_types` field
2. Clear any API caches
3. Verify property's category_id is correct

---

## Code Locations

- **Model Accessor**: `app/Models/Property.php::getParametersAttribute()` (line 492)
- **API Endpoint**: `app/Http/Controllers/ApiController.php::getPropertyList()` (line 6589)
- **Helper Function**: `app/Helpers/custom_helper.php::get_property_details()` (line 627)
- **Filter Endpoint**: `app/Http/Controllers/ApiController.php::getFacilitiesForFilter()` (line 7681)

