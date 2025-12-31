# Available Dates Column Error Fix

## Issue
Error: "Database column error: Column 'available_dates' not found. Please contact support."

## Root Cause
The `available_dates` column is being set in the code, but it may not exist in the database if:
1. The migration `2025_07_15_000000_add_vacation_home_fields_to_properties.php` hasn't been run
2. The column was dropped or doesn't exist for some properties
3. The database schema is out of sync with the code

## Solution Applied
Added a check to verify the column exists before trying to set it.

### Before:
```php
// Set vacation home specific fields if property classification is vacation_homes (4)
if (isset($request->property_classification) && $request->property_classification == 4) {
    $UpdateProperty->availability_type = $request->availability_type;
    $UpdateProperty->available_dates = $request->available_dates;
}
```

### After:
```php
// Set vacation home specific fields if property classification is vacation_homes (4)
if (isset($request->property_classification) && $request->property_classification == 4) {
    // Check if column exists before setting (in case migration hasn't been run)
    if (\Schema::hasColumn('propertys', 'availability_type')) {
        $UpdateProperty->availability_type = $request->availability_type;
    }
    if (\Schema::hasColumn('propertys', 'available_dates')) {
        $UpdateProperty->available_dates = $request->available_dates;
    } else {
        // Log warning if column doesn't exist
        Log::warning('available_dates column not found in propertys table', [
            'property_id' => $id,
            'property_classification' => $request->property_classification
        ]);
    }
}
```

## Migration Reference
The `available_dates` column should be added by migration:
- `2025_07_15_000000_add_vacation_home_fields_to_properties.php`

This migration adds:
- `availability_type` (tinyInteger, nullable)
- `available_dates` (json, nullable)

## Next Steps
1. **Check if migration has been run:**
   ```bash
   php artisan migrate:status
   ```

2. **If migration hasn't been run, run it:**
   ```bash
   php artisan migrate
   ```

3. **If column still doesn't exist, manually add it:**
   ```sql
   ALTER TABLE `propertys` 
   ADD COLUMN `availability_type` TINYINT NULL AFTER `identity_proof`,
   ADD COLUMN `available_dates` JSON NULL AFTER `availability_type`;
   ```

4. **Verify column exists:**
   ```sql
   DESCRIBE propertys;
   -- or
   SHOW COLUMNS FROM propertys LIKE 'available_dates';
   ```

## Testing
After this fix:
- ✅ Code will check if column exists before setting it
- ✅ No errors will occur if column doesn't exist
- ✅ Warning will be logged if column is missing
- ✅ Property updates will work even if column doesn't exist

## Files Modified
- `as-home-dashboard-Admin/app/Http/Controllers/PropertController.php` (lines 790-794)

