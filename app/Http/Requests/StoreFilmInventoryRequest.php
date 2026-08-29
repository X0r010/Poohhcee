<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFilmInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'design_id'        => 'required|exists:designs,id',
            'side'             => 'required|in:front,back',
            'shirt_color'      => 'nullable|string|max:30',
            'prints_available' => 'required|integer|min:0',
            'cost_per_print'   => 'required|numeric|min:0',
            'notes'            => 'nullable|string',
        ];
    }
}
