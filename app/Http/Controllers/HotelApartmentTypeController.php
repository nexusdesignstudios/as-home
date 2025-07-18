<?php

namespace App\Http\Controllers;

use App\Models\HotelApartmentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HotelApartmentTypeController extends Controller
{
    /**
     * Display a listing of the hotel apartment types.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $apartmentTypes = HotelApartmentType::all();
        return view('hotel_apartment_types.index', compact('apartmentTypes'));
    }

    /**
     * Show the form for creating a new hotel apartment type.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('hotel_apartment_types.create');
    }

    /**
     * Store a newly created hotel apartment type in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:hotel_apartment_types',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        HotelApartmentType::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('hotel_apartment_types.index')
            ->with('success', 'Hotel apartment type created successfully.');
    }

    /**
     * Display the specified hotel apartment type.
     *
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function show(HotelApartmentType $hotelApartmentType)
    {
        return view('hotel_apartment_types.show', compact('hotelApartmentType'));
    }

    /**
     * Show the form for editing the specified hotel apartment type.
     *
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function edit(HotelApartmentType $hotelApartmentType)
    {
        return view('hotel_apartment_types.edit', compact('hotelApartmentType'));
    }

    /**
     * Update the specified hotel apartment type in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HotelApartmentType $hotelApartmentType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:hotel_apartment_types,name,' . $hotelApartmentType->id,
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $hotelApartmentType->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('hotel_apartment_types.index')
            ->with('success', 'Hotel apartment type updated successfully.');
    }

    /**
     * Remove the specified hotel apartment type from storage.
     *
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function destroy(HotelApartmentType $hotelApartmentType)
    {
        $hotelApartmentType->delete();

        return redirect()->route('hotel_apartment_types.index')
            ->with('success', 'Hotel apartment type deleted successfully.');
    }

    /**
     * API endpoint to get all hotel apartment types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHotelApartmentTypes()
    {
        $apartmentTypes = HotelApartmentType::select('id', 'name', 'description')->get();

        return response()->json([
            'error' => false,
            'message' => 'Hotel apartment types retrieved successfully',
            'data' => $apartmentTypes
        ]);
    }

    /**
     * Get list of hotel apartment types for datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApartmentTypesList()
    {
        $apartmentTypes = HotelApartmentType::all();

        $data = [];
        foreach ($apartmentTypes as $key => $type) {
            $data[] = [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'created_at' => $type->created_at->format('Y-m-d H:i:s'),
                'actions' => '<div class="btn-group" role="group">
                                <a href="' . route('hotel-apartment-types.edit', $type->id) . '" class="btn btn-sm btn-primary">Edit</a>
                                <form action="' . route('hotel-apartment-types.destroy', $type->id) . '" method="POST" class="d-inline delete-form">
                                    ' . csrf_field() . '
                                    ' . method_field('DELETE') . '
                                    <button type="submit" class="btn btn-sm btn-danger delete-btn">Delete</button>
                                </form>
                            </div>'
            ];
        }

        return response()->json(['data' => $data]);
    }
}
