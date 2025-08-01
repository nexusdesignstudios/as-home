<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PropertyTaxController extends Controller
{
    /**
     * Display a listing of property taxes.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'property_taxes')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        // Get taxes and index them by property_classification for easier access in the view
        $taxesCollection = \App\Models\PropertyTax::all();
        $taxes = [];

        foreach ($taxesCollection as $tax) {
            $taxes[$tax->property_classification] = $tax;
        }

        return view('property_tax.index', compact('taxes'));
    }

    /**
     * Store or update property tax.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!has_permissions('update', 'property_taxes')) {
            return response()->json([
                'success' => false,
                'message' => PERMISSION_ERROR_MSG
            ]);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'property_classification' => 'required|integer|in:4,5',
            'service_charge' => 'nullable|numeric|min:0|max:100',
            'sales_tax' => 'nullable|numeric|min:0|max:100',
            'city_tax' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            \App\Models\PropertyTax::updateOrCreate(
                ['property_classification' => $request->property_classification],
                [
                    'service_charge' => $request->service_charge,
                    'sales_tax' => $request->sales_tax,
                    'city_tax' => $request->city_tax,
                ]
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Property tax updated successfully'
                ]);
            }

            return redirect()->route('property-taxes.index')
                ->with('success', 'Property tax updated successfully');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

            return redirect()->back()
                ->with('error', 'Error updating property tax: ' . $e->getMessage());
        }
    }
}
