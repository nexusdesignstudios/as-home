<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Property;
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
            $query->with(['property_question_field', 'customer:id,name,email', 'reservation:id']);
            $query->orderBy('created_at', 'desc'); // Order by newest first
        }])->findOrFail($propertyId);

        // Get all question fields for this property's classification
        $allFields = PropertyQuestionField::where('property_classification', $property->getRawOriginal('property_classification'))
            ->where('status', 'active')
            ->with('field_values')
            ->get();

        // Calculate reviews analytics
        $reviewsAnalytics = $this->calculateReviewsAnalytics($property);

        return view('property-question-form.answers', compact('property', 'allFields', 'reviewsAnalytics'));
    }

    /**
     * Show public feedback form for customers (no authentication required)
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showFeedbackForm(Request $request, $token)
    {
        try {
            // Find reservation by token
            $reservation = \App\Models\Reservation::where('feedback_token', $token)
                ->whereNotNull('feedback_token')
                ->with(['customer', 'reservable'])
                ->first();

            if (!$reservation) {
                return redirect()->route('home')->with('error', 'Invalid or expired feedback link.');
            }

            // Determine property and classification
            // First try to get property_id from query parameter (if provided in URL)
            $propertyIdFromQuery = $request->query('property_id');
            $property = null;
            $propertyClassification = null;
            $formType = null;
            
            // If property_id provided in URL, use it directly (for frontend convenience)
            if ($propertyIdFromQuery) {
                $property = Property::find($propertyIdFromQuery);
                if ($property) {
                    $propertyClassification = $property->getRawOriginal('property_classification');
                    // Set formType based on classification
                    if ($propertyClassification == 4) {
                        $formType = 'vacation_homes';
                    } elseif ($propertyClassification == 5) {
                        $formType = 'hotel_booking';
                    }
                }
            }
            
            // Fallback to original method if property_id not provided or not found, or if formType not set
            if (!$property || !$formType || !$propertyClassification) {
                if ($reservation->reservable_type === 'App\\Models\\Property') {
                    $reservationProperty = $reservation->reservable;
                    if ($reservationProperty) {
                        // Use property from reservation if query param property doesn't have valid classification
                        if (!$property || !in_array($property->getRawOriginal('property_classification'), [4, 5])) {
                            $property = $reservationProperty;
                        }
                        $propertyClassification = $property->getRawOriginal('property_classification');
                        if ($propertyClassification == 4) {
                            $formType = 'vacation_homes';
                        } elseif ($propertyClassification == 5) {
                            $formType = 'hotel_booking';
                        }
                    }
                } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                    $hotelRoom = $reservation->reservable;
                    if ($hotelRoom && $hotelRoom->property) {
                        // Use property from hotel room if query param property doesn't have valid classification
                        if (!$property || !in_array($property->getRawOriginal('property_classification'), [4, 5])) {
                            $property = $hotelRoom->property;
                        }
                        $propertyClassification = $property->getRawOriginal('property_classification');
                        if ($propertyClassification == 5) {
                            $formType = 'hotel_booking';
                        }
                    }
                }
            }
            
            // Ensure propertyClassification is set from the final property
            if ($property && !$propertyClassification) {
                $propertyClassification = $property->getRawOriginal('property_classification');
                // Set formType if classification is valid
                if ($propertyClassification == 4) {
                    $formType = 'vacation_homes';
                } elseif ($propertyClassification == 5) {
                    $formType = 'hotel_booking';
                }
            }

            if (!$property || !$formType || !$propertyClassification) {
                \Illuminate\Support\Facades\Log::error('Feedback form error - missing data', [
                    'property_id' => $property->id ?? null,
                    'property_classification' => $propertyClassification ?? null,
                    'form_type' => $formType ?? null,
                    'reservation_id' => $reservation->id,
                    'reservable_type' => $reservation->reservable_type,
                    'property_id_from_query' => $propertyIdFromQuery
                ]);
                return redirect()->route('home')->with('error', 'Invalid property type for feedback.');
            }

            // Log for debugging
            \Illuminate\Support\Facades\Log::info('Feedback form loaded', [
                'reservation_id' => $reservation->id,
                'property_id' => $property->id,
                'property_classification' => $propertyClassification,
                'form_type' => $formType,
                'property_id_from_query' => $propertyIdFromQuery
            ]);

            // Get all active question fields for this property classification
            // IMPORTANT: Use the propertyClassification variable that was determined above
            $allFields = PropertyQuestionField::where('property_classification', $propertyClassification)
                ->where('status', 'active')
                ->with('field_values')
                ->orderBy('created_at', 'asc')
                ->get();
            
            \Illuminate\Support\Facades\Log::info('Feedback form questions loaded', [
                'reservation_id' => $reservation->id,
                'property_classification' => $propertyClassification,
                'questions_count' => $allFields->count(),
                'first_question' => $allFields->first() ? $allFields->first()->name : null
            ]);

            // Check if customer already submitted feedback for this reservation
            $existingAnswers = \App\Models\PropertyQuestionAnswer::where('reservation_id', $reservation->id)
                ->where('customer_id', $reservation->customer_id)
                ->where('property_id', $property->id)
                ->first();

            return view('property-question-form.public-feedback', [
                'reservation' => $reservation,
                'property' => $property,
                'allFields' => $allFields,
                'formType' => $formType,
                'token' => $token,
                'hasExistingFeedback' => $existingAnswers !== null
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error showing feedback form', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('home')->with('error', 'An error occurred. Please try again later.');
        }
    }

    /**
     * Calculate reviews analytics for a property
     *
     * @param \App\Models\Property $property
     * @return array
     */
    private function calculateReviewsAnalytics($property)
    {
        $analytics = [
            'total_reviews' => 0,
            'average_rating' => 0,
            'rating_distribution' => [],
            'numeric_answers' => [],
            'recent_reviews' => [],
            'by_question' => []
        ];

        // Get all answers for this property
        $answers = $property->propertyQuestionAnswers()
            ->with(['property_question_field', 'customer'])
            ->orderBy('created_at', 'DESC')
            ->get();

        // Group answers by customer (one review per customer)
        $reviewsByCustomer = $answers->groupBy('customer_id');
        $analytics['total_reviews'] = $reviewsByCustomer->count();

        // Find dropdown fields that have values from 1 to 5 (rating fields)
        $dropdownFields = PropertyQuestionField::where('property_classification', $property->getRawOriginal('property_classification'))
            ->where('field_type', 'dropdown')
            ->where('status', 'active')
            ->with('field_values')
            ->get();

        // Filter dropdown fields that have values 1-5
        $ratingFields = $dropdownFields->filter(function($field) {
            $values = $field->field_values->pluck('value')->toArray();
            // Check if field has values 1, 2, 3, 4, 5 (can be string or numeric)
            $hasRatingValues = false;
            foreach ($values as $value) {
                $numValue = (int)$value;
                if ($numValue >= 1 && $numValue <= 5) {
                    $hasRatingValues = true;
                    break;
                }
            }
            return $hasRatingValues;
        });

        // Calculate average ratings for dropdown rating fields
        foreach ($ratingFields as $field) {
            $fieldAnswers = $answers->where('property_question_field_id', $field->id)
                ->where('value', '!=', '')
                ->filter(function($answer) {
                    // Check if answer value is numeric and between 1-5
                    $value = trim($answer->value);
                    if (is_numeric($value)) {
                        $numValue = (int)$value;
                        return $numValue >= 1 && $numValue <= 5;
                    }
                    return false;
                })
                ->map(function($answer) {
                    return (float)$answer->value;
                });

            if ($fieldAnswers->count() > 0) {
                $average = $fieldAnswers->average();
                $analytics['by_question'][$field->id] = [
                    'question_name' => $field->name,
                    'average_rating' => round($average, 2),
                    'total_ratings' => $fieldAnswers->count(),
                    'min_rating' => $fieldAnswers->min(),
                    'max_rating' => $fieldAnswers->max(),
                    'rating_distribution' => $this->calculateRatingDistribution($fieldAnswers->toArray())
                ];
            }
        }

        // Calculate overall average from dropdown rating fields
        $allRatings = $answers->whereIn('property_question_field_id', $ratingFields->pluck('id'))
            ->where('value', '!=', '')
            ->filter(function($answer) {
                // Check if answer value is numeric and between 1-5
                $value = trim($answer->value);
                if (is_numeric($value)) {
                    $numValue = (int)$value;
                    return $numValue >= 1 && $numValue <= 5;
                }
                return false;
            })
            ->map(function($answer) {
                return (float)$answer->value;
            });

        if ($allRatings->count() > 0) {
            $analytics['average_rating'] = round($allRatings->average(), 2);
            $analytics['rating_distribution'] = $this->calculateRatingDistribution($allRatings->toArray());
        }

        // Get recent reviews (grouped by customer)
        $analytics['recent_reviews'] = $reviewsByCustomer->take(10)->map(function($customerAnswers, $customerId) {
            $firstAnswer = $customerAnswers->first();
            return [
                'customer_name' => $firstAnswer->customer->name ?? 'Anonymous',
                'customer_email' => $firstAnswer->customer->email ?? '',
                'review_date' => $firstAnswer->created_at->format('Y-m-d H:i:s'),
                'answers' => $customerAnswers->map(function($answer) {
                    return [
                        'question' => $answer->property_question_field->name ?? 'N/A',
                        'value' => $answer->value,
                        'field_type' => $answer->property_question_field->field_type ?? 'text'
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        return $analytics;
    }

    /**
     * Calculate rating distribution (for 1-5 star ratings)
     *
     * @param array $ratings
     * @return array
     */
    private function calculateRatingDistribution($ratings)
    {
        $distribution = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        ];

        foreach ($ratings as $rating) {
            $star = (int)round($rating);
            if ($star >= 1 && $star <= 5) {
                $distribution[$star]++;
            }
        }

        $total = array_sum($distribution);
        foreach ($distribution as $star => $count) {
            $distribution[$star] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }

        return $distribution;
    }
}
