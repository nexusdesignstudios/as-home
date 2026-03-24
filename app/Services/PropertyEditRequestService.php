<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyEditRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyEditRequestService
{
    /**
     * Get list of fields that users are allowed to edit (require approval).
     *
     * @return array
     */
    public static function getAllowedEditableFields()
    {
        return [
            'title',
            'title_ar',
            'description',
            'description_ar',
            'area_description',
            'area_description_ar',
            'title_image',
            'three_d_image',
            'hotel_rooms', // Special handling for hotel room descriptions
            'gallery_images', // New gallery images
            'removed_gallery_images', // IDs of gallery images to remove
        ];
    }

    /**
     * Filter edited data to only include allowed editable fields.
     *
     * @param array $editedData
     * @param Property $property
     * @return array
     */
    public static function filterAllowedFields(array $editedData, Property $property)
    {
        $allowedFields = self::getAllowedEditableFields();
        $filteredData = [];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $editedData)) {
                if ($field === 'hotel_rooms' && isset($editedData['hotel_rooms'])) {
                    // Special handling for hotel rooms - only keep description field
                    $filteredRooms = [];
                    foreach ($editedData['hotel_rooms'] as $room) {
                        if (isset($room['id'])) {
                            $filteredRooms[] = [
                                'id' => $room['id'],
                                'description' => $room['description'] ?? null,
                            ];
                        }
                    }
                    if (!empty($filteredRooms)) {
                        $filteredData['hotel_rooms'] = $filteredRooms;
                    }
                } else {
                    $filteredData[$field] = $editedData[$field];
                }
            }
        }
        
        return $filteredData;
    }

    /**
     * Save property edits as a pending edit request instead of applying directly.
     *
     * @param Property $property
     * @param array $editedData
     * @param int $requestedBy
     * @return PropertyEditRequest
     */
    public function saveEditRequest(Property $property, array $editedData, int $requestedBy, array $originalData = null)
    {
        // Note: This method is called within an existing transaction in PropertController::update()
        // Do NOT start a new transaction here to avoid nested transaction issues
        try {
            // Get original property data as snapshot (use provided or get from property)
            if ($originalData === null) {
                $originalData = $property->getAttributes();
                // Remove timestamps from original data
                unset($originalData['created_at'], $originalData['updated_at']);
            }

            // Check if there's already a pending edit request for this property
            $existingRequest = PropertyEditRequest::where('property_id', $property->id)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                // Update existing pending request
                $existingRequest->edited_data = $editedData;
                $existingRequest->original_data = $originalData;
                $existingRequest->requested_by = $requestedBy;
                $existingRequest->save();
                
                Log::info('Property edit request updated', [
                    'edit_request_id' => $existingRequest->id,
                    'property_id' => $property->id,
                    'requested_by' => $requestedBy
                ]);
                
                return $existingRequest;
            } else {
                // Create new edit request
                $editRequest = PropertyEditRequest::create([
                    'property_id' => $property->id,
                    'requested_by' => $requestedBy,
                    'status' => 'pending',
                    'edited_data' => $editedData,
                    'original_data' => $originalData,
                ]);
                
                Log::info('Property edit request created', [
                    'edit_request_id' => $editRequest->id,
                    'property_id' => $property->id,
                    'requested_by' => $requestedBy
                ]);
                
                return $editRequest;
            }
        } catch (\Exception $e) {
            // Don't rollback here - let the parent transaction handle it
            Log::error('Failed to save property edit request: ' . $e->getMessage(), [
                'property_id' => $property->id,
                'requested_by' => $requestedBy,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Apply approved edits to the property.
     *
     * @param PropertyEditRequest $editRequest
     * @param int|null $reviewedBy
     * @return Property
     */
    public function applyEditRequest(PropertyEditRequest $editRequest, $reviewedBy = null)
    {
        try {
            DB::beginTransaction();

            $property = $editRequest->property;
            $editedData = $editRequest->edited_data;

            // Special handling for hotel rooms - only update description
            if (isset($editedData['hotel_rooms']) && $property->property_classification == 5) {
                foreach ($editedData['hotel_rooms'] as $roomData) {
                    if (isset($roomData['id']) && array_key_exists('description', $roomData)) {
                        $hotelRoom = \App\Models\HotelRoom::find($roomData['id']);
                        if ($hotelRoom && $hotelRoom->property_id == $property->id) {
                            $hotelRoom->description = $roomData['description'];
                            $hotelRoom->save();
                        }
                    }
                }
                // Remove hotel_rooms from editedData so it's not processed in the general loop
                unset($editedData['hotel_rooms']);
            }

            // Handle gallery image additions from edit request
            if (isset($editedData['gallery_images']) && is_array($editedData['gallery_images'])) {
                foreach ($editedData['gallery_images'] as $imageName) {
                    \App\Models\PropertyImages::create([
                        'image' => $imageName,
                        'propertys_id' => $property->id
                    ]);
                }
                unset($editedData['gallery_images']);
            }

            // Handle gallery image removals from edit request
            if (isset($editedData['removed_gallery_images']) && is_array($editedData['removed_gallery_images'])) {
                foreach ($editedData['removed_gallery_images'] as $imageId) {
                    $image = \App\Models\PropertyImages::find($imageId);
                    if ($image && $image->propertys_id == $property->id) {
                        // Unlink the file from storage (both local and S3 if configured)
                        if (function_exists('unlink_image')) {
                            $relativeImagePath = config('global.IMG_PATH') . config('global.PROPERTY_GALLERY_IMG_PATH') . $property->id . "/" . $image->image;
                            unlink_image($relativeImagePath);
                        } else {
                            $path = public_path('images') . config('global.PROPERTY_GALLERY_IMG_PATH') . "/" . $property->id . "/" . $image->image;
                            if (file_exists($path)) {
                                unlink($path);
                            }
                        }
                        $image->delete();
                    }
                }
                unset($editedData['removed_gallery_images']);
            }

            // Handle facilities from edit request
            if (isset($editedData['facilities']) && is_array($editedData['facilities'])) {
                \App\Models\AssignedOutdoorFacilities::where('property_id', $property->id)->delete();
                foreach ($editedData['facilities'] as $facility) {
                    if (isset($facility['facility_id'])) {
                        $assignedFacility = new \App\Models\AssignedOutdoorFacilities();
                        $assignedFacility->facility_id = $facility['facility_id'];
                        $assignedFacility->property_id = $property->id;
                        $assignedFacility->distance = $facility['distance'] ?? null;
                        $assignedFacility->save();
                    }
                }
                unset($editedData['facilities']);
            }

            // Apply all other edited fields to the property
            foreach ($editedData as $field => $value) {
                // Skip certain fields that shouldn't be updated directly
                if (in_array($field, ['id', 'created_at', 'updated_at', 'request_status', 'status'])) {
                    continue;
                }
                
                // Handle JSON fields
                if (in_array($field, ['available_dates', 'corresponding_day', 'agent_addons', 'gallery_images'])) {
                    $property->$field = is_array($value) ? $value : json_decode($value, true);
                } else {
                    $property->$field = $value;
                }
            }

            // Set request_status back to approved after applying edits
            $property->request_status = 'approved';
            $property->status = 1;
            
            $property->save();

            // Update edit request status
            $editRequest->status = 'approved';
            $editRequest->reviewed_by = $reviewedBy ?? (auth()->id() ?? auth('web')->id());
            $editRequest->reviewed_at = now();
            $editRequest->save();

            DB::commit();

            Log::info('Property edit request applied', [
                'edit_request_id' => $editRequest->id,
                'property_id' => $property->id,
                'reviewed_by' => $editRequest->reviewed_by
            ]);

            return $property;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply property edit request: ' . $e->getMessage(), [
                'edit_request_id' => $editRequest->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Reject an edit request.
     *
     * @param PropertyEditRequest $editRequest
     * @param string $rejectReason
     * @param int|null $reviewedBy
     * @return PropertyEditRequest
     */
    public function rejectEditRequest(PropertyEditRequest $editRequest, string $rejectReason, $reviewedBy = null)
    {
        try {
            DB::beginTransaction();

            $editRequest->status = 'rejected';
            $editRequest->reject_reason = $rejectReason;
            $editRequest->reviewed_by = $reviewedBy ?? (auth()->id() ?? auth('web')->id());
            $editRequest->reviewed_at = now();
            $editRequest->save();

            DB::commit();

            Log::info('Property edit request rejected', [
                'edit_request_id' => $editRequest->id,
                'property_id' => $editRequest->property_id,
                'reviewed_by' => $editRequest->reviewed_by,
                'reject_reason' => $rejectReason
            ]);

            return $editRequest;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject property edit request: ' . $e->getMessage(), [
                'edit_request_id' => $editRequest->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

