<?php

namespace App\Http\Controllers;

use App\Models\PropertyTerms;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class PropertyTermsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Check if the request is from API
        if (request()->expectsJson()) {
            // Use Sanctum authentication for API requests
            $this->middleware('auth:sanctum');
        } else {
            // Use web authentication for web requests
            $this->middleware('auth');
            $this->middleware(function ($request, $next) {
                if (!has_permissions('read', 'property')) {
                    return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
                }
                return $next($request);
            });
        }
    }

    /**
     * Display a listing of property terms.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Check if the table exists first
            if (!Schema::hasTable('property_terms')) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The property_terms table doesn\'t exist. Please run the migration first.'
                    ], 500);
                }
                return view('property_terms.index', ['propertyTerms' => [], 'tableNotExists' => true]);
            }

            $propertyTerms = PropertyTerms::all();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $propertyTerms
                ]);
            }

            return view('property_terms.index', compact('propertyTerms'));
        } catch (QueryException $e) {
            // Handle database errors
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return view('property_terms.index', ['propertyTerms' => [], 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for creating a new property term.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            // Check if the table exists first
            if (!Schema::hasTable('property_terms')) {
                return redirect()->route('property-terms.index')
                    ->with('error', 'The property_terms table doesn\'t exist. Please run the migration first.');
            }

            // Define available classifications
            $classifications = [
                1 => 'Sell/Rent',
                2 => 'Commercial',
                3 => 'New Project',
                4 => 'Vacation Homes',
                5 => 'Hotel Booking'
            ];

            // Get classifications that already have terms
            $existingClassifications = PropertyTerms::pluck('classification_id')->toArray();

            // Filter out classifications that already have terms
            $availableClassifications = array_filter($classifications, function ($key) use ($existingClassifications) {
                return !in_array($key, $existingClassifications);
            }, ARRAY_FILTER_USE_KEY);

            return view('property_terms.create', compact('availableClassifications'));
        } catch (QueryException $e) {
            return redirect()->route('property-terms.index')
                ->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created property term in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Check if the table exists first
            if (!Schema::hasTable('property_terms')) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The property_terms table doesn\'t exist. Please run the migration first.'
                    ], 500);
                }
                return redirect()->route('property-terms.index')
                    ->with('error', 'The property_terms table doesn\'t exist. Please run the migration first.');
            }

            $request->validate([
                'classification_id' => 'required|integer|unique:property_terms',
                'terms_conditions' => 'required'
            ]);

            $propertyTerm = PropertyTerms::create($request->all());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Terms and conditions created successfully.',
                    'data' => $propertyTerm
                ], 201);
            }

            return redirect()->route('property-terms.index')
                ->with('success', 'Terms and conditions created successfully.');
        } catch (QueryException $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Database error: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('property-terms.index')
                ->with('error', 'Database error: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified property term.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $propertyTerm = PropertyTerms::findOrFail($id);

            $classifications = [
                1 => 'Sell/Rent',
                2 => 'Commercial',
                3 => 'New Project',
                4 => 'Vacation Homes',
                5 => 'Hotel Booking'
            ];

            return view('property_terms.edit', compact('propertyTerm', 'classifications'));
        } catch (\Exception $e) {
            return redirect()->route('property-terms.index')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified property term in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'terms_conditions' => 'required'
            ]);

            $propertyTerm = PropertyTerms::findOrFail($id);
            $propertyTerm->update($request->all());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Terms and conditions updated successfully.',
                    'data' => $propertyTerm
                ]);
            }

            return redirect()->route('property-terms.index')
                ->with('success', 'Terms and conditions updated successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('property-terms.index')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified property term from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $propertyTerm = PropertyTerms::findOrFail($id);
            $propertyTerm->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Terms and conditions deleted successfully.'
                ]);
            }

            return redirect()->route('property-terms.index')
                ->with('success', 'Terms and conditions deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('property-terms.index')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Get terms and conditions for a specific property classification.
     *
     * @param  int  $classificationId
     * @return \Illuminate\Http\Response
     */
    public function getTermsByClassification($classificationId)
    {
        try {
            $terms = PropertyTerms::where('classification_id', $classificationId)->first();

            if ($terms) {
                return response()->json([
                    'success' => true,
                    'data' => $terms
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terms not found for this classification'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
            $propertyTerm = PropertyTerms::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $propertyTerm
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
