<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\ShirtType;
use App\Models\ShirtColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'business_name'          => Setting::get('business_name', 'Poohhcee'),
            'business_phone'         => Setting::get('business_phone', ''),
            'business_email'         => Setting::get('business_email', ''),
            'business_address'       => Setting::get('business_address', ''),
            'default_source'         => Setting::get('default_source', 'TikTok'),
            'default_payment_method' => Setting::get('default_payment_method', ''),
            'low_stock_threshold'    => Setting::get('low_stock_threshold', 2),
        ];

        $shirtTypes = ShirtType::orderBy('name')->get();
        $shirtColors = ShirtColor::orderBy('name')->get();

        return view('settings.index', compact('settings', 'shirtTypes', 'shirtColors'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'business_name'          => 'required|string|max:100',
            'business_phone'         => 'nullable|string|max:30',
            'business_email'         => 'nullable|email|max:100',
            'business_address'       => 'nullable|string|max:255',
            'default_source'         => 'required|in:TikTok,Instagram,Website,Walk-in,Other',
            'default_payment_method' => 'nullable|string|max:50',
            'low_stock_threshold'    => 'required|integer|min:0',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Settings saved!');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($data['current_password'], Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        Auth::user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Password updated!');
    }
}