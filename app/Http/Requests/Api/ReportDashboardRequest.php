<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReportDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['string', 'in:draft,calculated,finalized'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'salesperson_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
