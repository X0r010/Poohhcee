<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShirtInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'          => 'required|string|max:50',
            'size'          => 'required|string|max:10',
            'color'         => 'required|string|max:30',
            'quantity'      => 'required|integer|min:1',
            'cost_per_unit' => 'required|numeric|min:0',
            'vendor'        => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ];
    }
}
