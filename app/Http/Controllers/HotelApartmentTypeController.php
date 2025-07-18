<?php

namespace App\Http\Controllers;

use App\Models\HotelApartmentType;
use Illuminate\Http\Request;

class HotelApartmentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'hotel_apartment_types')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('hotel_apartment_types.index');
    }

    /**
     * Get list of hotel apartment types for DataTables
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getApartmentTypesList(Request $request)
    {
        if (!has_permissions('read', 'hotel_apartment_types')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ]);
        }

        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        $search = $request->search ?? '';

        $sql = HotelApartmentType::orderBy($sort, $order);

        if (!empty($search)) {
            $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('name', 'LIKE', "%$search%")
                ->orWhere('description', 'LIKE', "%$search%");
        }

        $total = $sql->count();
        $sql->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $bulkData['rows'] = $res;

        return response()->json($bulkData);
    }

    /**
     * Get hotel apartment types for API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHotelApartmentTypes()
    {
        $apartmentTypes = HotelApartmentType::all();
        return response()->json([
            'error' => false,
            'data' => $apartmentTypes,
            'message' => 'Hotel apartment types fetched successfully'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!has_permissions('create', 'hotel_apartment_types')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('hotel_apartment_types.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!has_permissions('create', 'hotel_apartment_types')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $apartmentType = HotelApartmentType::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'error' => false,
                'message' => 'Hotel apartment type created successfully',
                'data' => $apartmentType
            ]);
        }

        return redirect()->route('hotel-apartment-types.index')
            ->with('success', 'Hotel apartment type created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function show(HotelApartmentType $hotelApartmentType)
    {
        if (!has_permissions('read', 'hotel_apartment_types')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ]);
        }

        return response()->json([
            'error' => false,
            'data' => $hotelApartmentType,
            'message' => 'Hotel apartment type fetched successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function edit(HotelApartmentType $hotelApartmentType)
    {
        if (!has_permissions('update', 'hotel_apartment_types')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }
        return view('hotel_apartment_types.edit', compact('hotelApartmentType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, HotelApartmentType $hotelApartmentType)
    {
        if (!has_permissions('update', 'hotel_apartment_types')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $hotelApartmentType->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'error' => false,
                'message' => 'Hotel apartment type updated successfully',
                'data' => $hotelApartmentType
            ]);
        }

        return redirect()->route('hotel-apartment-types.index')
            ->with('success', 'Hotel apartment type updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HotelApartmentType  $hotelApartmentType
     * @return \Illuminate\Http\Response
     */
    public function destroy(HotelApartmentType $hotelApartmentType)
    {
        if (!has_permissions('delete', 'hotel_apartment_types')) {
            return response()->json([
                'error' => true,
                'message' => PERMISSION_ERROR_MSG
            ]);
        }

        // Check if there are any properties using this apartment type
        if ($hotelApartmentType->properties()->count() > 0) {
            return response()->json([
                'error' => true,
                'message' => 'Cannot delete this apartment type as it is being used by properties'
            ]);
        }

        $hotelApartmentType->delete();

        return response()->json([
            'error' => false,
            'message' => 'Hotel apartment type deleted successfully'
        ]);
    }
}
