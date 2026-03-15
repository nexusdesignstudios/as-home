<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Article;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\PropertysInquiry;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $currency_symbol = Setting::where('type', 'currency_symbol')->pluck('data')->first();

        if (!has_permissions('read', 'dashboard')) {
            return redirect('dashboard')->with('error', PERMISSION_ERROR_MSG);
        } else {
            
            $year = $request->input('year');
            $years = Property::select(DB::raw('YEAR(created_at) as year'))->distinct()->orderBy('year', 'desc')->pluck('year');
            
            $propertyQuery = Property::query();
            if ($year) {
                $propertyQuery->whereYear('created_at', $year);
            }

            // 0:Sell 1:Rent 2:Sold 3:Rented
            $list['total_sell_property'] = (clone $propertyQuery)->where('propery_type', '0')->count();
            $list['total_rant_property'] = (clone $propertyQuery)->where('propery_type', '1')->count();

            $list['total_properties'] = (clone $propertyQuery)->count();
            $list['total_articles'] = Article::count();
            $list['total_categories'] = Category::count();
            $list['total_customer'] = Customer::count();
            
            // New Totals
            $list['total_vacation_homes'] = (clone $propertyQuery)->where('property_classification', 4)->count();
            $list['total_hotels'] = (clone $propertyQuery)->where('property_classification', 5)->count();
            $list['total_commercials'] = (clone $propertyQuery)->where('property_classification', 2)->count();

            // $list['recent_properties'] = Property::orderBy('id', 'DESC')->limit(10)->where('status', 1)->get();
            $today = now();

            /************************************************************************************ */
            // Get Month wise data
            $monthDates = array();
            for ($month = 1; $month <= 12; $month++) {
                $monthName = Carbon::create(null, $month, 1)->format('M');
                array_push($monthDates, "'" . $monthName . "'");
            }
            $propertiesQuery = Property::query();
            if ($year) {
                $propertiesQuery->whereYear('created_at', $year);
            }

            // Create month series for sell and rent properties
            $sellMonthSeries = array_fill(0, 12, 0);
            $rentMonthSeries = array_fill(0, 12, 0);

            // Optimized Aggregation: Sell Properties
            $sellCounts = $propertiesQuery->clone()
                ->where('propery_type', 0)
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->pluck('count', 'month');

            foreach ($sellCounts as $month => $count) {
                $sellMonthSeries[$month - 1] = $count;
            }

            // Optimized Aggregation: Rent Properties
            $rentCounts = $propertiesQuery->clone()
                ->where('propery_type', 1)
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy(DB::raw('MONTH(created_at)'))
                ->pluck('count', 'month');

            foreach ($rentCounts as $month => $count) {
                $rentMonthSeries[$month - 1] = $count;
            }
            /************************************************************************************ */


            /************************************************************************************ */
            // Get Week wise data
            // Create an array to store the counts for each day of the week
            $sellWeekSeries = array_fill(1, 7, 0);
            $rentWeekSeries = array_fill(1, 7, 0);

            // Optimized Aggregation: Week wise (Sell & Rent)
            // Note: We use $propertiesQuery to respect the year filter if provided
            $weekCounts = $propertiesQuery->clone()
                ->selectRaw('DAYOFWEEK(created_at) as day_of_week, propery_type, COUNT(*) as count')
                ->groupBy(DB::raw('DAYOFWEEK(created_at)'), 'propery_type')
                ->get();

            foreach ($weekCounts as $item) {
                if ($item->propery_type == 0) {
                    $sellWeekSeries[$item->day_of_week] = $item->count;
                } elseif ($item->propery_type == 1) {
                    $rentWeekSeries[$item->day_of_week] = $item->count;
                }
            }

            /************************************************************************************ */
            // Get day wise data
            $sellCountForDay = array_fill(1, 31, 0); // Initialize array for days 1 to 31
            $rentCountForDay = array_fill(1, 31, 0); // Initialize array for days 1 to 31

            // Optimized Aggregation: Day wise
            $dayCounts = $propertiesQuery->clone()
                ->selectRaw('DAY(created_at) as day, propery_type, COUNT(*) as count')
                ->groupBy(DB::raw('DAY(created_at)'), 'propery_type')
                ->get();

            foreach ($dayCounts as $item) {
                $day = $item->day;
                if ($item->propery_type == 0) {
                    $sellCountForDay[$day] = $item->count;
                } elseif ($item->propery_type == 1) {
                    $rentCountForDay[$day] = $item->count;
                }
            }

            $currentDates = range(1, 31); // Days of the month
            $sellCountForCurrentDay = array_values($sellCountForDay);
            $rentCountForCurrentDay = array_values($rentCountForDay);


            /************************************************************************************ */



            // Properties Data Query
            $properties = Property::select('id', 'category_id', 'title', 'price', 'title_image', 'latitude', 'longitude', 'city', 'total_click','propery_type')->with('category')->where('total_click', '>', 0)->orderBy('total_click', 'DESC')->limit(10)->get()->map(function($property){
                $property->property_type = ucfirst($property->propery_type);
                $property->promoted = $property->is_promoted;
                return $property;
            });

            // Get Category Data
            $getCategory = Category::withCount('properties')->get();
            $category_name = array();
            $category_count = array();
            foreach ($getCategory as $key => $value) {
                array_push($category_name, "`" . $value->category . "`");
                array_push($category_count, $value->properties_count);
            }

            // Prepare the chart data
            $chartData = [
                'sellmonthSeries' => $sellMonthSeries,
                'sellcountForCurrentDay' => $sellCountForCurrentDay,
                'rentcountForCurrentDay' => $rentCountForCurrentDay,
                'sellweekSeries' => $sellWeekSeries,
                'rentweekSeries' => $rentWeekSeries,
                'rentmonthSeries' => $rentMonthSeries,
                'weekDates' =>  [0 => "'Day1'", 1 => "'Day2'", 2 => "'Day3'", 3 => "'Day4'", 4 => "'Day5'", 5 => "'Day6'", 6 => "'Day7'"],
                'monthDates' =>  $monthDates,
                'currentDates' => $currentDates,
                'currentDate' => "[" . Carbon::now()->format('Y-m-d') . "]"

            ];

            $rows = array();
            $firebase_settings = array();



            $operate = '';

            $settings['company_name'] = system_setting('company_name');
            $settings['currency_symbol'] = system_setting('currency_symbol');



            // $userData = Customer::select(DB::raw("COUNT(*) as count"))
            //     ->whereYear('created_at', date('Y'))
            //     ->groupBy(DB::raw("Month(created_at)"))
            //     ->pluck('count');
            $userData = [];

            return view('home', compact('list', 'settings', 'properties', 'userData', 'chartData', 'currency_symbol', 'category_name', 'category_count', 'years', 'year'));
        }
    }
    public function export_dashboard_properties(Request $request)
    {
        $year = $request->input('year');
        $propertyQuery = Property::query();
        if ($year) {
            $propertyQuery->whereYear('created_at', $year);
        }

        $properties = $propertyQuery->get();

        $filename = "properties_export_" . date('Ymd') . ".csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['ID', 'Title', 'Type', 'Classification', 'Price', 'Created At']);

        foreach ($properties as $property) {
            $type = $property->propery_type == 0 ? 'Sell' : 'Rent';
            $classification = '';
            switch ($property->property_classification) {
                case 1: $classification = 'Residential'; break;
                case 2: $classification = 'Commercial'; break;
                case 3: $classification = 'New Project'; break;
                case 4: $classification = 'Vacation Home'; break;
                case 5: $classification = 'Hotel'; break;
            }
            
            fputcsv($handle, [
                $property->id,
                $property->title,
                $type,
                $classification,
                $property->price,
                $property->created_at
            ]);
        }

        fclose($handle);
        exit;
    }
    public function blank_dashboard()
    {


        return view('blank_home');
    }


    public function change_password()
    {

        return view('change_password.index');
    }
    public function changeprofile()
    {
        return view('change_profile.index');
    }

    public function check_password(Request $request)
    {
        $id = Auth::id();
        $oldpassword = $request->old_password;
        $user = DB::table('users')->where('id', $id)->first();


        $response['error'] = password_verify($oldpassword, $user->password) ? true : false;
        return response()->json($response);
    }



    public function store_password(Request $request)
    {

        $confPassword = $request->confPassword;
        $id = Auth::id();
        $role = Auth::user()->type;

        $users = User::find($id);

        if (isset($confPassword) && $confPassword != '') {
            $users->password = Hash::make($confPassword);
        }

        $users->update();
        return back()->with('success', 'Password Change Successfully');
    }

    public function update_profile(Request $request)
    {
        $request->validate([
        ]);
        try {
            $id = Auth::id();
            $role = Auth::user()->type;

            $users = User::find($id);
            if ($role == 0) {
                $users->name  = $request->name;
                $users->email  = $request->email;
            }

            if ($request->hasFile('profile_image')) {
                if(!empty($users->getRawOriginal('profile'))){
                    unlink_image($users->profile);
                }
                $users->profile = store_image($request->file('profile_image'), 'ADMIN_PROFILE_IMG_PATH');
            }
            $users->update();
            return back()->with('success', trans("Data Updated Successfully"));
        } catch (Exception $e) {
            return back()->with('error', trans("Something Went Wrong"));
        }
    }

    public function privacy_policy()
    {
        echo system_setting('privacy_policy');
    }


    public function firebase_messaging_settings(Request $request)
    {
        $file_path = public_path('firebase-messaging-sw.js');

        // Check if file exists
        if (File::exists($file_path)) {

            File::delete($file_path);
        }

        // Move new file
        $request->file->move(public_path(), 'firebase-messaging-sw.js');
    }
    public function getMapsData()
    {
        $apiKey = env('PLACE_API_KEY');

        $url = "https://maps.googleapis.com/maps/api/js?" . http_build_query([
            'libraries' => 'places',
            'key' => $apiKey, // Use the API key from the .env file
            // Add any other parameters you need here
        ]);

        return file_get_contents($url);
    }
}
