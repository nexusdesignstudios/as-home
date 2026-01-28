<?php

namespace App\Http\Controllers;

use App\Models\AddonsPackage;
use App\Models\HotelAddonField;
use App\Models\PropertyHotelAddonValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddonsPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $propertyId = $request->property_id;

        // Validate property_id if provided
        if ($propertyId) {
            $validator = Validator::make(['property_id' => $propertyId], [
                'property_id' => 'exists:propertys,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid property ID'
                ], 422);
            }
        }

        // Build query
        $query = AddonsPackage::with('addon_values.hotel_addon_field');

        // Filter by property if provided
        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        // Get packages
        $packages = $query->get();

        return response()->json([
            'error' => false,
            'message' => 'Addons packages fetched successfully',
            'data' => $packages
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'property_id' => 'required|exists:propertys,id',
            'status' => 'nullable|in:active,inactive',
            'addon_values' => 'required|array',
            'addon_values.*.hotel_addon_field_id' => 'required|exists:hotel_addon_fields,id',
            'addon_values.*.value' => 'required',
            'addon_values.*.static_price' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create package
            $package = AddonsPackage::create([
                'name' => $request->name,
                'description' => $request->description,
                'property_id' => $request->property_id,
                'status' => $request->status ?? 'active'
            ]);

            // Create addon values
            foreach ($request->addon_values as $addonValue) {
                // Get the addon field to check its type
                $addonField = HotelAddonField::where('id', $addonValue['hotel_addon_field_id'])
                    ->where('status', 'active')
                    ->first();

                if (!$addonField) {
                    continue; // Skip inactive or non-existent fields
                }

                $value = $addonValue['value'];

                // Handle checkbox values (convert array to JSON)
                if ($addonField->field_type == 'checkbox' && is_array($value)) {
                    $value = json_encode($value);
                }

                // Handle radio and dropdown values (validate against available options)
                if (in_array($addonField->field_type, ['radio', 'dropdown'])) {
                    $validValue = $addonField->field_values()
                        ->where('value', $value)
                        ->exists();

                    if (!$validValue) {
                        continue; // Skip invalid values
                    }
                }

                // Create addon value
                PropertyHotelAddonValue::create([
                    'property_id' => $request->property_id,
                    'hotel_addon_field_id' => $addonValue['hotel_addon_field_id'],
                    'value' => $value,
                    'static_price' => (isset($addonValue['static_price']) && is_numeric($addonValue['static_price'])) ? $addonValue['static_price'] : null,
                    'multiply_price' => (isset($addonValue['multiply_price']) && is_numeric($addonValue['multiply_price'])) ? $addonValue['multiply_price'] : 1,
                    'package_id' => $package->id
                ]);
            }

            DB::commit();

            // Reload package with addon values
            $package = AddonsPackage::with('addon_values.hotel_addon_field')->find($package->id);

            return response()->json([
                'error' => false,
                'message' => 'Addons package created successfully',
                'data' => $package
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => 'Failed to create addons package: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $package = AddonsPackage::with('addon_values.hotel_addon_field')->findOrFail($id);

            return response()->json([
                'error' => false,
                'message' => 'Addons package fetched successfully',
                'data' => $package
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Addons package not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'addon_values' => 'nullable|array',
            'addon_values.*.id' => 'nullable|exists:property_hotel_addon_values,id',
            'addon_values.*.hotel_addon_field_id' => 'required_with:addon_values|exists:hotel_addon_fields,id',
            'addon_values.*.value' => 'required_with:addon_values',
            'addon_values.*.static_price' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find package
            $package = AddonsPackage::findOrFail($id);

            // Update package fields
            if ($request->has('name')) {
                $package->name = $request->name;
            }

            if ($request->has('description')) {
                $package->description = $request->description;
            }

            if ($request->has('status')) {
                $package->status = $request->status;
            }

            $package->save();

            // Update addon values if provided
            if ($request->has('addon_values')) {
                // Keep track of processed addon values
                $processedAddonValueIds = [];

                foreach ($request->addon_values as $addonValue) {
                    // Get the addon field to check its type
                    $addonField = HotelAddonField::where('id', $addonValue['hotel_addon_field_id'])
                        ->where('status', 'active')
                        ->first();

                    if (!$addonField) {
                        continue; // Skip inactive or non-existent fields
                    }

                    $value = $addonValue['value'];

                    // Handle checkbox values (convert array to JSON)
                    if ($addonField->field_type == 'checkbox' && is_array($value)) {
                        $value = json_encode($value);
                    }

                    // Handle radio and dropdown values (validate against available options)
                    if (in_array($addonField->field_type, ['radio', 'dropdown'])) {
                        $validValue = $addonField->field_values()
                            ->where('value', $value)
                            ->exists();

                        if (!$validValue) {
                            continue; // Skip invalid values
                        }
                    }

                    if (isset($addonValue['id'])) {
                        // Update existing addon value
                        $addonValueModel = PropertyHotelAddonValue::where('id', $addonValue['id'])
                            ->where('package_id', $package->id)
                            ->first();

                        if ($addonValueModel) {
                            $addonValueModel->update([
                                'hotel_addon_field_id' => $addonValue['hotel_addon_field_id'],
                                'value' => $value,
                                'static_price' => (isset($addonValue['static_price']) && is_numeric($addonValue['static_price'])) ? $addonValue['static_price'] : $addonValueModel->static_price,
                                'multiply_price' => (isset($addonValue['multiply_price']) && is_numeric($addonValue['multiply_price'])) ? $addonValue['multiply_price'] : $addonValueModel->multiply_price
                            ]);

                            $processedAddonValueIds[] = $addonValueModel->id;
                        }
                    } else {
                        // Create new addon value
                        $addonValueModel = PropertyHotelAddonValue::create([
                            'property_id' => $package->property_id,
                            'hotel_addon_field_id' => $addonValue['hotel_addon_field_id'],
                            'value' => $value,
                            'static_price' => (isset($addonValue['static_price']) && is_numeric($addonValue['static_price'])) ? $addonValue['static_price'] : null,
                            'multiply_price' => (isset($addonValue['multiply_price']) && is_numeric($addonValue['multiply_price'])) ? $addonValue['multiply_price'] : 1,
                            'package_id' => $package->id
                        ]);

                        $processedAddonValueIds[] = $addonValueModel->id;
                    }
                }

                // Delete addon values that were not processed (removed from the package)
                if ($request->has('delete_missing_values') && $request->delete_missing_values) {
                    PropertyHotelAddonValue::where('package_id', $package->id)
                        ->whereNotIn('id', $processedAddonValueIds)
                        ->delete();
                }
            }

            DB::commit();

            // Reload package with addon values
            $package = AddonsPackage::with('addon_values.hotel_addon_field')->find($package->id);

            return response()->json([
                'error' => false,
                'message' => 'Addons package updated successfully',
                'data' => $package
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'error' => true,
                    'message' => 'Addons package not found'
                ], 404);
            }

            return response()->json([
                'error' => true,
                'message' => 'Failed to update addons package: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $package = AddonsPackage::findOrFail($id);

            // Delete associated addon values
            PropertyHotelAddonValue::where('package_id', $package->id)->delete();

            // Delete package
            $package->delete();

            return response()->json([
                'error' => false,
                'message' => 'Addons package deleted successfully'
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'error' => true,
                    'message' => 'Addons package not found'
                ], 404);
            }

            return response()->json([
                'error' => true,
                'message' => 'Failed to delete addons package: ' . $e->getMessage()
            ], 500);
        }
    }
}
