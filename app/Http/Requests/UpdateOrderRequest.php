<?php

namespace App\Http\Requests;

class UpdateOrderRequest extends StoreOrderRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'print_status'     => 'required|in:Pending,Printing,Printed,Done',
            'delivery_status'  => 'required|in:Pending,Packaging,Delivering,Delivered,Cancelled',
        ]);
    }
}
