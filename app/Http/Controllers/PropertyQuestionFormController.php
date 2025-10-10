<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\PropertyQuestionField;
use App\Models\PropertyQuestionFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BootstrapTableService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Validator;

class PropertyQuestionFormController extends Controller
{
    /**
     * Display the form for the specified property classification
     */
    public function index($classification = null)
    {
        if (!has_permissions('read', 'property_question_form')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        // Map classification parameter to the numeric value(s)
        $classificationId = null;
        $classificationIds = [];

        switch ($classification) {
            case 'sell_rent':
                // For sell_rent, we'll handle both sell_rent (1) and commercial (2)
                $classificationId = 1; // Use sell_rent as the primary for the form
                $classificationIds = [1, 2]; // Include both for data fetching
                break;
            case 'vacation_homes':
                $classificationId = 4;
                $classificationIds = [4];
                break;
            case 'hotel_booking':
                $classificationId = 5;
                $classificationIds = [5];
                break;
            default:
                $classificationId = 1;
                $classificationIds = [1, 2];
        }

        return view('property-question-form.index', compact('classificationId', 'classification', 'classificationIds'));
    }

    /**
     * Store a newly created question field in storage.
     */
    public function store(Request $request)
    {
        if (!has_permissions('create', 'property_question_form')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'field_type' => 'required|in:text,number,radio,checkbox,textarea,file,dropdown',
            'property_classification' => 'required|integer|between:1,5',
            'option_data.*' => 'required_if:field_type,radio|required_if:field_type,checkbox|required_if:field_type,dropdown',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            // Get the data from Request
            $name = $request->name;
            $fieldType = $request->field_type;
            $propertyClassification = $request->property_classification;

            // Store question field data
            $propertyQuestionField = PropertyQuestionField::create([
                'name' => $name,
                'field_type' => $fieldType,
                'property_classification' => $propertyClassification
            ]);

            // Check if option data is available or not
            if ($request->has('option_data') && !empty($request->option_data)) {
                $fieldValueData = array();

                // Loop through options
                foreach ($request->option_data as $option) {
                    if (!empty($option['option'])) {
                        $fieldValueData[] = array(
                            'property_question_field_id' => $propertyQuestionField->id,
                            'value' => $option['option'],
                            'created_at' => now(),
                            'updated_at' => now()
                        );
                    }
                }

                if (!empty($fieldValueData)) {
                    PropertyQuestionFieldValue::insert($fieldValueData);
                }
            }

            DB::commit();
            ResponseService::successResponse(trans('Data Created Successfully'));
        } catch (Exception $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, trans('Something Went Wrong'));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        if (!has_permissions('read', 'property_question_form')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $propertyClassification = request('property_classification');
        $propertyClassificationIds = request('property_classification_ids');

        $sql = PropertyQuestionField::with('field_values')
            ->when($propertyClassification && !$propertyClassificationIds, function ($query) use ($propertyClassification) {
                $query->where('property_classification', $propertyClassification);
            })
            ->when($propertyClassificationIds, function ($query) use ($propertyClassificationIds) {
                $ids = is_array($propertyClassificationIds) ? $propertyClassificationIds : [$propertyClassificationIds];
                $query->whereIn('property_classification', $ids);
            })
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

        foreach ($res as $row) {
            $row = (object)$row;

            $operate = BootstrapTableService::editButton('', true, null, null, $row->id, null);
            $operate .= BootstrapTableService::deleteAjaxButton(route('property-question-form.delete', $row->id));

            $tempRow = $row->toArray();
            $tempRow['edit_status_url'] = route('property-question-form.status');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Update the specified question field status.
     */
    public function status(Request $request)
    {
        if (!has_permissions('update', 'property_question_form')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        } else {
            if ($request->status == '1') {
                $status = 'active';
            } else {
                $status = 'inactive';
            }

            PropertyQuestionField::where('id', $request->id)->update(['status' => $status]);
            ResponseService::successResponse($request->status ? "Field Activated Successfully" : "Field Deactivated Successfully");
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        if (!has_permissions('update', 'property_question_form')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            PropertyQuestionField::where('id', $request->id)->update(['name' => $request->name]);
            ResponseService::successResponse(trans('Data Updated Successfully'));
        } catch (Exception $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, trans('Something Went Wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!has_permissions('delete', 'property_question_form')) {
            ResponseService::errorResponse(PERMISSION_ERROR_MSG);
        }

        try {
            PropertyQuestionField::where('id', $id)->delete();
            ResponseService::successResponse(trans('Data Deleted Successfully'));
        } catch (Exception $e) {
            ResponseService::logErrorResponse($e, trans('Something Went Wrong'));
        }
    }

    /**
     * Display property question answers.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function answers(Request $request)
    {
        if (!has_permissions('read', 'property_question_form')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        $propertyId = $request->property_id;
        $classification = $request->classification;

        // If no property ID is provided, show a form to select a property
        if (!$propertyId) {
            $properties = \App\Models\Property::select('id', 'title', 'property_classification')
                ->when($classification, function($query) use ($classification) {
                    $query->where('property_classification', $classification);
                })
                ->orderBy('title')
                ->get();

            return view('property-question-form.select-property', compact('properties', 'classification'));
        }

        // Get the property details
        $property = \App\Models\Property::with(['propertyQuestionAnswers' => function($query) {
            $query->with('property_question_field');
        }])->findOrFail($propertyId);

        // Get all question fields for this property's classification
        $allFields = PropertyQuestionField::where('property_classification', $property->getRawOriginal('property_classification'))
            ->where('status', 'active')
            ->with('field_values')
            ->get();

        return view('property-question-form.answers', compact('property', 'allFields'));
    }
}
