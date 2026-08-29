<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    private const ALLOWED = [
        'shirt_status'    => ['Not Yet', 'Buying', 'Bought', 'Done'],
        'film_status'     => ['No Film', 'Have Film', 'Printed', 'Done'],
        'print_status'    => ['Pending', 'Printing', 'Printed', 'Done'],
        'delivery_status' => ['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled'],
        'payment_status'  => ['Paid', 'Not Yet', 'Partial'],
    ];

    public function rules(): array
    {
        return [
            'field' => ['required', Rule::in(array_keys(self::ALLOWED))],
            'value' => ['required', 'string', function ($attribute, $value, $fail) {
                $field = $this->input('field');
                if ($field && !in_array($value, self::ALLOWED[$field] ?? [])) {
                    $fail("'{$value}' is not a valid value for {$field}.");
                }
            }],
        ];
    }
}
