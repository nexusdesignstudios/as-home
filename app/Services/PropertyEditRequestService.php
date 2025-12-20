<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyEditRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyEditRequestService
{
    /**
     * Save property edits as a pending edit request instead of applying directly.
     *
     * @param Property $property
     * @param array $editedData
     * @param int $requestedBy
     * @return PropertyEditRequest
     */
    public function saveEditRequest(Property $property, array $editedData, int $requestedBy)
    {
        try {
            DB::beginTransaction();

            // Get original property data as snapshot
            $originalData = $property->getAttributes();
            
            // Remove timestamps from original data
            unset($originalData['created_at'], $originalData['updated_at']);

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
                
                DB::commit();
                
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
                
                DB::commit();
                
                Log::info('Property edit request created', [
                    'edit_request_id' => $editRequest->id,
                    'property_id' => $property->id,
                    'requested_by' => $requestedBy
                ]);
                
                return $editRequest;
            }
        } catch (\Exception $e) {
            DB::rollBack();
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

            // Apply all edited fields to the property
            foreach ($editedData as $field => $value) {
                // Skip certain fields that shouldn't be updated directly
                if (in_array($field, ['id', 'created_at', 'updated_at', 'request_status', 'status'])) {
                    continue;
                }
                
                // Handle JSON fields
                if (in_array($field, ['available_dates', 'corresponding_day', 'agent_addons'])) {
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

