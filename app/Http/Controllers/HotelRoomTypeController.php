<?php

namespace App\Http\Controllers;

use App\Models\HotelRoomType;
use Illuminate\Http\Request;
use App\Services\ResponseService;
use App\Services\BootstrapTableService;
use Illuminate\Support\Facades\Validator;

class HotelRoomTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'hotel_room_types')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('hotel_room_types.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!has_permissions('create', 'hotel_room_types')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            HotelRoomType::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status ?? 1
            ]);

            ResponseService::successResponse('Room Type Created Successfully');
        } catch (\Exception $e) {
            ResponseService::logErrorResponse($e, 'Room Type Creation Error');
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (!has_permissions('read', 'hotel_room_types')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = HotelRoomType::when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%");
            });
        });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();

        foreach ($res as $row) {
            $row = (object)$row;

            $operate = BootstrapTableService::editButton('editRoomType', true, null, null, $row->id, null);
            $operate .= BootstrapTableService::deleteAjaxButton(route('hotel_room_types.destroy', $row->id));

            $tempRow = $row->toArray();
            $tempRow['status_text'] = $row->status ? 'Active' : 'Inactive';
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!has_permissions('update', 'hotel_room_types')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:hotel_room_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $roomType = HotelRoomType::findOrFail($request->id);
            $roomType->name = $request->name;
            $roomType->description = $request->description;
            $roomType->save();

            ResponseService::successResponse('Room Type Updated Successfully');
        } catch (\Exception $e) {
            ResponseService::logErrorResponse($e, 'Room Type Update Error');
        }
    }

    /**
     * Update the status of the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        if (!has_permissions('update', 'hotel_room_types')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        try {
            $roomType = HotelRoomType::findOrFail($request->id);
            $roomType->status = $request->status;
            $roomType->save();

            ResponseService::successResponse($request->status ? "Room Type Activated Successfully" : "Room Type Deactivated Successfully");
        } catch (\Exception $e) {
            ResponseService::logErrorResponse($e, 'Room Type Status Update Error');
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
        if (!has_permissions('delete', 'hotel_room_types')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        try {
            $roomType = HotelRoomType::findOrFail($id);

            // Check if this room type is being used by any rooms
            if ($roomType->rooms()->count() > 0) {
                ResponseService::errorResponse('This room type cannot be deleted as it is being used by one or more rooms');
                return;
            }

            $roomType->delete();
            ResponseService::successResponse('Room Type Deleted Successfully');
        } catch (\Exception $e) {
            ResponseService::logErrorResponse($e, 'Room Type Deletion Error');
        }
    }
}
