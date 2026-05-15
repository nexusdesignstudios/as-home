<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Property;
use Illuminate\Http\Request;

class ExplorerHomeController extends Controller
{
    /**
     * Guest-facing Discover / Home page.
     *
     * Property type mapping (property_classification column):
     *   5 = Hotel
     *   4 = Vacation Home / Apartment
     *   other = Real Estate (sell / rent)
     */
    public function index(Request $request)
    {
        $vertical = $request->query('v', 'hotel');

        $properties = match ($vertical) {
            'vacation' => Property::where('property_classification', 4)
                ->where('status', 1)
                ->latest()
                ->limit(4)
                ->get(),
            're' => Property::whereNotIn('property_classification', [4, 5])
                ->where('status', 1)
                ->latest()
                ->limit(4)
                ->get(),
            default => Property::where('property_classification', 5)
                ->where('status', 1)
                ->latest()
                ->limit(4)
                ->get(),
        };

        $articles = Article::latest()->limit(3)->get();

        return view('explorer.home', compact('vertical', 'properties', 'articles'));
    }
}
