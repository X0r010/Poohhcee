<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Expense;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expense_date' => 'required|date',
            'category'     => ['required', Rule::in(Expense::categories())],
            'title'        => 'required|string|max:150',
            'description'  => 'nullable|string',
            'amount'       => 'required|numeric|min:0',
            'reference'    => 'nullable|string|max:100',
        ];
    }
}
