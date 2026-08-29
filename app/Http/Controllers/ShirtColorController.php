<?php

namespace App\Http\Controllers;

use App\Models\ShirtColor;
use Illuminate\Http\Request;

class ShirtColorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:50|unique:shirt_colors,name']);
        ShirtColor::create($data);
        return back()->with('success', "Added color \"{$data['name']}\"!");
    }

    public function destroy(ShirtColor $shirtColor)
    {
        $shirtColor->delete();
        return back()->with('success', 'Color removed.');
    }
}