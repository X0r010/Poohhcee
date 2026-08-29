<?php

namespace App\Http\Controllers;

use App\Models\ShirtType;
use Illuminate\Http\Request;

class ShirtTypeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:50|unique:shirt_types,name']);
        ShirtType::create($data);
        return back()->with('success', "Added shirt type \"{$data['name']}\"!");
    }

    public function destroy(ShirtType $shirtType)
    {
        $shirtType->delete();
        return back()->with('success', 'Shirt type removed.');
    }
}
