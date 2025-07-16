<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\HotelAddonField;
use App\Models\HotelAddonFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;

class HotelAddonFieldController extends Controller
{
    public function index()
    {
        if (!has_permissions('read', 'hotel_addon_field')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('hotel-addon-field.index');
    }

    public function store(Request $request)
    {
        if (!has_permissions('create', 'hotel_addon_field')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'field_type' => 'required|in:text,number,radio,checkbox,textarea,file,dropdown',
            'option_data.*' => 'required_if:field_type,radio|required_if:field_type,checkbox|required_if:field_type,dropdown',
            'option_data.*.static_price' => 'nullable|numeric',
            'option_data.*.multiply_price' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            // Get the data from Request
            $name = $request->name;
            $fieldType = $request->field_type;

            // Store name and field type in hotel addon form
            $hotelAddonField = HotelAddonField::create(['name' => $name, 'field_type' => $fieldType]);

            // Check if option data is available or not
            if ($request->has('option_data') && !empty($request->option_data)) {
                $hotelAddonFieldValueData = array();
                // Loop through
                foreach ($request->option_data as $option) {
                    if (!empty($option['option'])) {
                        $hotelAddonFieldValueData[] = array(
                            'hotel_addon_field_id'   => $hotelAddonField->id,
                            'value'                  => $option['option'],
                            'static_price'           => isset($option['static_price']) ? $option['static_price'] : null,
                            'multiply_price'         => isset($option['multiply_price']) ? $option['multiply_price'] : null,
                            'created_at'             => now(),
                            'updated_at'             => now()
                        );
                    }
                }
                if (!empty($hotelAddonFieldValueData)) {
                    HotelAddonFieldValue::insert($hotelAddonFieldValueData);
                }
            }
            DB::commit();
            ResponseService::successResponse(trans('Data Created Successfully'));
        } catch (Exception $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, trans('Something Went Wrong'));
        }
    }

    public function show()
    {
        if (!has_permissions('read', 'hotel_addon_field')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = HotelAddonField::with('field_values')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orWhere('name', 'LIKE', "%$search%")
                        ->orWhere('field_type', 'LIKE', "%$search%")
                        ->orWhereHas('field_values', function ($query) use ($search) {
                            $query->where('value', 'LIKE', "%$search%");
                        });
                });
            });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $row = (object)$row;

            $operate = BootstrapTableService::editButton('', true, null, null, $row->id, null);
            $operate .= BootstrapTableService::deleteAjaxButton(route('hotel-addon-field.delete', $row->id));

            $tempRow = $row->toArray();
            $tempRow['edit_status_url'] = route('hotel-addon-field.status');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function status(Request $request)
    {
        if (!has_permissions('update', 'hotel_addon_field')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            if ($request->status == '1') {
                $status = 'active';
            } else {
                $status = 'inactive';
            }
            HotelAddonField::where('id', $request->id)->update(['status' => $status]);
            ResponseService::successResponse($request->status ? "Field Activated Successfully" : "Field Deactivated Successfully");
        }
    }

    public function update(Request $request)
    {
        if (!has_permissions('update', 'hotel_addon_field')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            HotelAddonField::where('id', $request->id)->update(['name' => $request->name]);
            ResponseService::successResponse(trans('Data Updated Successfully'));
        } catch (Exception $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, trans('Something Went Wrong'));
        }
    }

    public function destroy($id)
    {
        if (!has_permissions('delete', 'hotel_addon_field')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }
        try {
            HotelAddonField::where('id', $id)->delete();
            ResponseService::successResponse(trans('Data Deleted Successfully'));
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, trans('Something Went Wrong'));
        }
    }
}
