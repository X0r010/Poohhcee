<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'order_date'        => 'required|date',
            'customer_handle'   => 'required|string|max:100',
            'customer_phone'    => 'nullable|string|max:20',
            'customer_location' => 'nullable|string|max:255',
            'source'            => 'required|in:TikTok,Instagram,Website,Walk-in,Other',
            'design_id'         => 'required|exists:designs,id',
            'size'              => 'required|string|max:10',
            'color'             => 'required|string|max:30',
            'shirt_type_id'     => 'nullable|exists:shirt_types,id',
            'base_price'        => 'required|numeric|min:0',
            'delivery_fee'      => 'nullable|numeric|min:0',
            'payment_status'    => 'required|in:Paid,Not Yet,Partial',
            'payment_method'    => 'nullable|string|max:50',
            'partial_amount'    => 'nullable|numeric|min:0',
            'shirt_status'      => 'required|in:Not Yet,Buying,Bought,Done',
            'film_status'       => 'required|in:No Film,Ordering,Have Film,Printed,Done',
            'notes'             => 'nullable|string',
            'printed_shirt_id'  => 'nullable|exists:printed_shirts,id',
        ];
    }
}