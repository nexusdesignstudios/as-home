<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.customer.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('customer')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('explorer.home'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.customer.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name'            => 'required|string|max:100',
            'last_name'             => 'required|string|max:100',
            'email'                 => 'required|email|max:200|unique:customers,email',
            'mobile'                => 'required|string|max:30|unique:customers,mobile',
            'nationality'           => 'nullable|string|max:100',
            'password'              => 'required|string|min:8|confirmed',
            'agree'                 => 'accepted',
        ]);

        // Build the Customer record.
        // firebase_id and address are NOT NULL in the schema but not in $fillable,
        // so we set them directly on the model instance to bypass mass-assignment guard.
        $customer = new Customer([
            'name'      => trim($data['first_name'] . ' ' . $data['last_name']),
            'email'     => $data['email'],
            'mobile'    => $data['mobile'],
            'password'  => Hash::make($data['password']),
            'country'   => $data['nationality'] ?? null,
            'logintype' => 'email',
            'isActive'  => 1,
        ]);
        $customer->firebase_id = '';
        $customer->address     = '';
        $customer->save();

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('explorer.home');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('explorer.home');
    }
}
