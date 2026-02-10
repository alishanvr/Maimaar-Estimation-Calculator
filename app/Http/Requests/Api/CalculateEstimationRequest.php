<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CalculateEstimationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'markups' => ['nullable', 'array'],
            'markups.steel' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'markups.panels' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'markups.ssl' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'markups.finance' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'markups.array' => 'The markups must be a valid object.',
            'markups.steel.numeric' => 'The steel markup must be a number.',
            'markups.steel.min' => 'The steel markup cannot be negative.',
            'markups.steel.max' => 'The steel markup cannot exceed 5.',
        ];
    }
}
