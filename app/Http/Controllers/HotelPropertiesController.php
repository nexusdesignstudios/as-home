<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use App\Services\BootstrapTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelPropertiesController extends Controller
{
    /**
     * Display a listing of hotel properties.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'hotel_properties')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        return view('hotel_properties.index');
    }

    /**
     * Get hotel properties for bootstrap table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getHotelPropertiesList(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        $search = $request->search ?? '';

        // Query only hotel properties (classification = 5)
        $sql = Property::classification(5)->orderBy($sort, $order);

        if (!empty($search)) {
            $sql = $sql->where(function ($query) use ($search) {
                $query->where('title', 'LIKE', "%$search%")
                    ->orWhere('address', 'LIKE', "%$search%")
                    ->orWhere('client_address', 'LIKE', "%$search%");
            });
        }

        $total = $sql->count();
        $sql = $sql->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];

        foreach ($res as $row) {
            $tempRow = [];
            $tempRow['id'] = $row->id;
            $tempRow['title'] = $row->title;
            $tempRow['address'] = $row->address;
            $tempRow['refund_policy'] = $row->refund_policy ?? 'N/A';
            $tempRow['cancellation_period'] = $row->cancellation_period ?? 'N/A';
            $tempRow['room_count'] = $row->hotelRooms()->count();
            $tempRow['status'] = $row->status;
            $tempRow['instant_booking'] = $row->instant_booking;
            $tempRow['created_at'] = $row->created_at;

            $operate = '';

            // Update Instant Booking toggle
            if (has_permissions('update', 'property')) {
                $checked = $row->instant_booking ? 'checked' : '';
                $operate .= '<div class="form-check form-switch d-inline-block align-middle me-2">
                                <input class="form-check-input update-instant-booking" type="checkbox" role="switch" data-id="' . $row->id . '" ' . $checked . ' title="Toggle Instant Booking">
                             </div>';
            }

            // Update Cancellation Period button
            if (has_permissions('update', 'property')) {
                $operate .= '<a href="javascript:void(0)" class="btn btn-sm btn-info update-cancellation-period" data-id="' . $row->id . '" data-cancellation-period="' . $row->cancellation_period . '" title="Update Cancellation Period"><i class="bi bi-clock"></i></a>&nbsp;&nbsp;';
            }

            // View property details
            if (has_permissions('read', 'property')) {
                $operate .= '<a href="' . route('property.show', $row->id) . '" class="btn btn-sm btn-primary" title="View"><i class="bi bi-eye"></i></a>&nbsp;&nbsp;';
            }

            // Edit property
            if (has_permissions('update', 'property')) {
                $operate .= '<a href="' . route('property.edit', $row->id) . '" class="btn btn-sm btn-success" title="Edit"><i class="bi bi-pencil-square"></i></a>&nbsp;&nbsp;';
            }

            // Delete property
            if (has_permissions('delete', 'property')) {
                $operate .= '<a href="javascript:void(0)" class="btn btn-sm btn-danger delete-data" data-id="' . $row->id . '" data-url="' . route('property.destroy', $row->id) . '" title="Delete"><i class="bi bi-trash"></i></a>';
            }

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Update cancellation policy for a hotel property.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateCancellationPeriod(Request $request)
    {
        if (!has_permissions('update', 'property')) {
            return response()->json(['error' => true, 'message' => PERMISSION_ERROR_MSG]);
        }

        try {
            $validated = $request->validate([
                'property_id' => 'required|exists:propertys,id',
                'cancellation_policy' => 'nullable|string|in:none,3_days,5_days,7_days,same_day_6pm,custom',
                'cancellation_custom_days' => 'nullable|integer|min:1|max:365',
            ]);

            $property = Property::findOrFail($request->property_id);
            
            // Store the cancellation policy type
            $property->cancellation_policy = $request->cancellation_policy ?: 'none';
            
            // Store custom days if provided
            if ($request->cancellation_policy === 'custom' && $request->has('cancellation_custom_days')) {
                $property->cancellation_custom_days = $request->cancellation_custom_days;
                $property->cancellation_period = $request->cancellation_custom_days . '_days';
            } else {
                $property->cancellation_custom_days = null;
                // Map policy to cancellation_period format
                $policyMap = [
                    'none' => null,
                    '3_days' => '3_days',
                    '5_days' => '5_days',
                    '7_days' => '7_days',
                    'same_day_6pm' => 'same_day_6pm',
                    'custom' => null // handled above
                ];
                $property->cancellation_period = $policyMap[$request->cancellation_policy] ?? null;
            }
            
            $property->save();

            return response()->json([
                'error' => false, 
                'message' => 'Cancellation policy updated successfully',
                'data' => [
                    'cancellation_policy' => $property->cancellation_policy,
                    'cancellation_period' => $property->cancellation_period,
                    'cancellation_custom_days' => $property->cancellation_custom_days
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true, 
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Cancellation policy update error: ' . $e->getMessage());
            return response()->json([
                'error' => true, 
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update instant booking status for a hotel property.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateInstantBooking(Request $request)
    {
        if (!has_permissions('update', 'property')) {
            return response()->json(['error' => true, 'message' => PERMISSION_ERROR_MSG]);
        }

        $request->validate([
            'property_id' => 'required|exists:propertys,id',
            'instant_booking' => 'required|boolean',
        ]);

        try {
            $property = Property::findOrFail($request->property_id);
            $property->instant_booking = $request->instant_booking;
            $property->save();

            return response()->json(['error' => false, 'message' => 'Instant booking status updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
