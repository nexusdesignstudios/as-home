<?php

namespace App\Http\Controllers;

use App\Models\HotelRoomType;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HotelRoomTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->is('api/*')) {
            // API request
            $roomTypes = HotelRoomType::all();
            return response()->json([
                'error' => false,
                'message' => 'Room types fetched successfully',
                'data' => $roomTypes
            ]);
        } else {
            // Web request
            if (!has_permissions('read', 'hotel_room_types')) {
                return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
            } else {
                return view('hotel_room_types.index');
            }
        }
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
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        try {
            $roomType = HotelRoomType::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 1
            ]);

            if ($request->is('api/*')) {
                return response()->json([
                    'error' => false,
                    'message' => 'Room type created successfully',
                    'data' => $roomType
                ], 201);
            } else {
                if (!has_permissions('create', 'hotel_room_types')) {
                    return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
                }
                return redirect()->route('hotel_room_types.index')->with('success', 'Room type created successfully');
            }
        } catch (\Exception $e) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $roomType = HotelRoomType::findOrFail($id);

            if ($request->is('api/*')) {
                return response()->json([
                    'error' => false,
                    'message' => 'Room type fetched successfully',
                    'data' => $roomType
                ]);
            } else {
                if (!has_permissions('read', 'hotel_room_types')) {
                    return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
                }
                return view('hotel_room_types.show', compact('roomType'));
            }
        } catch (\Exception $e) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Room type not found'
                ], 404);
            } else {
                return redirect()->back()->with('error', 'Room type not found');
            }
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        try {
            $roomType = HotelRoomType::findOrFail($id);
            $roomType->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? $roomType->status
            ]);

            if ($request->is('api/*')) {
                return response()->json([
                    'error' => false,
                    'message' => 'Room type updated successfully',
                    'data' => $roomType
                ]);
            } else {
                if (!has_permissions('update', 'hotel_room_types')) {
                    return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
                }
                return redirect()->route('hotel_room_types.index')->with('success', 'Room type updated successfully');
            }
        } catch (\Exception $e) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            $roomType = HotelRoomType::findOrFail($id);

            // Check if room type is in use
            if ($roomType->rooms()->count() > 0) {
                if ($request->is('api/*')) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Cannot delete room type that is in use'
                    ], 422);
                } else {
                    return redirect()->back()->with('error', 'Cannot delete room type that is in use');
                }
            }

            $roomType->delete();

            if ($request->is('api/*')) {
                return response()->json([
                    'error' => false,
                    'message' => 'Room type deleted successfully'
                ]);
            } else {
                if (env('DEMO_MODE') && Auth::user()->email != "superadmin@gmail.com") {
                    return redirect()->back()->with('error', 'This action is not allowed in demo mode');
                }

                if (!has_permissions('delete', 'hotel_room_types')) {
                    return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
                }

                return redirect()->route('hotel_room_types.index')->with('success', 'Room type deleted successfully');
            }
        } catch (\Exception $e) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get room types for bootstrap table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRoomTypeList(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'ASC';
        $search = $request->search ?? '';

        $sql = HotelRoomType::orderBy($sort, $order);

        if (!empty($search)) {
            $sql = $sql->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%");
            });
        }

        $total = $sql->count();
        $sql = $sql->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];

        foreach ($res as $row) {
            $tempRow = $row->toArray();

            $operate = '';
            if (has_permissions('update', 'hotel_room_types')) {
                $operate .= BootstrapTableService::editButton('javascript:void(0)', true, $row->id);
            }

            if (has_permissions('delete', 'hotel_room_types')) {
                $operate .= BootstrapTableService::deleteButton(route('hotel_room_types.destroy', $row->id));
            }

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Get all active room types for API.
     *
     * @return \Illuminate\Http\Response
     */
    public function getActiveRoomTypes()
    {
        $roomTypes = HotelRoomType::where('status', 1)->get();

        return response()->json([
            'error' => false,
            'message' => 'Room types fetched successfully',
            'data' => $roomTypes
        ]);
    }
}
