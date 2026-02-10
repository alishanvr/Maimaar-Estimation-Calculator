<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstimationRequest extends FormRequest
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
            'quote_number' => ['nullable', 'string', 'max:50'],
            'revision_no' => ['nullable', 'string', 'max:20'],
            'building_name' => ['nullable', 'string', 'max:255'],
            'building_no' => ['nullable', 'string', 'max:50'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'salesperson_code' => ['nullable', 'string', 'max:10'],
            'estimation_date' => ['nullable', 'date'],
            'input_data' => ['nullable', 'array'],
            'input_data.bay_spacing' => ['nullable', 'string'],
            'input_data.span_widths' => ['nullable', 'string'],
            'input_data.back_eave_height' => ['nullable', 'numeric', 'min:0'],
            'input_data.front_eave_height' => ['nullable', 'numeric', 'min:0'],
            'input_data.left_roof_slope' => ['nullable', 'numeric'],
            'input_data.right_roof_slope' => ['nullable', 'numeric'],
            'input_data.dead_load' => ['nullable', 'numeric', 'min:0'],
            'input_data.live_load' => ['nullable', 'numeric', 'min:0'],
            'input_data.wind_speed' => ['nullable', 'numeric', 'min:0'],
            'input_data.frame_type' => ['nullable', 'string'],
            'input_data.base_type' => ['nullable', 'string'],
            'input_data.roof_panel_code' => ['nullable', 'string'],
            'input_data.wall_panel_code' => ['nullable', 'string'],
            'input_data.paint_system' => ['nullable', 'string'],
            'input_data.collateral_load' => ['nullable', 'numeric', 'min:0'],
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
            'estimation_date.date' => 'The estimation date must be a valid date.',
            'input_data.array' => 'The input data must be a valid object.',
        ];
    }
}
