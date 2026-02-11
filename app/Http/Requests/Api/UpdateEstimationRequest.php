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
            'input_data.cf_finish' => ['nullable'],
            'input_data.panel_profile' => ['nullable', 'string'],
            'input_data.outer_skin_material' => ['nullable', 'string'],
            'input_data.core_thickness' => ['nullable', 'numeric', 'min:0'],
            'input_data.monitor_type' => ['nullable', 'string'],
            'input_data.monitor_width' => ['nullable', 'numeric', 'min:0'],
            'input_data.monitor_height' => ['nullable', 'numeric', 'min:0'],
            'input_data.monitor_length' => ['nullable', 'numeric', 'min:0'],
            'input_data.markup_steel' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'input_data.markup_panels' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'input_data.markup_ssl' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'input_data.markup_finance' => ['nullable', 'numeric', 'min:0', 'max:5'],

            // Frame Configuration
            'input_data.min_thickness' => ['nullable', 'numeric', 'min:0'],
            'input_data.double_weld' => ['nullable', 'string', 'in:Yes,No'],

            // Endwall Configuration
            'input_data.left_endwall_columns' => ['nullable', 'string'],
            'input_data.left_endwall_type' => ['nullable', 'string', 'in:Bearing Frame,Main Frame,MF 1/2 Loaded,False Rafter'],
            'input_data.left_endwall_portal' => ['nullable', 'string', 'in:None,Portal'],
            'input_data.right_endwall_columns' => ['nullable', 'string'],
            'input_data.right_endwall_type' => ['nullable', 'string', 'in:Bearing Frame,Main Frame,MF 1/2 Loaded,False Rafter'],
            'input_data.right_endwall_portal' => ['nullable', 'string', 'in:None,Portal'],

            // Secondary Members
            'input_data.purlin_depth' => ['nullable', 'string', 'in:200,250,360'],
            'input_data.roof_sag_rods' => ['nullable', 'string'],
            'input_data.wall_sag_rods' => ['nullable', 'string'],
            'input_data.roof_sag_rod_dia' => ['nullable', 'string', 'in:12,16,20,22'],
            'input_data.wall_sag_rod_dia' => ['nullable', 'string', 'in:12,16,20,22'],
            'input_data.bracing_type' => ['nullable', 'string', 'in:Cables,Rods,Angles'],

            // Extended Loads
            'input_data.live_load_permanent' => ['nullable', 'numeric', 'min:0'],
            'input_data.live_load_floor' => ['nullable', 'numeric', 'min:0'],
            'input_data.additional_load' => ['nullable', 'numeric', 'min:0'],

            // Roof Sheeting
            'input_data.roof_top_skin' => ['nullable', 'string'],
            'input_data.roof_core' => ['nullable', 'string'],
            'input_data.roof_bottom_skin' => ['nullable', 'string'],
            'input_data.roof_insulation' => ['nullable', 'string'],

            // Wall Sheeting
            'input_data.wall_top_skin' => ['nullable', 'string'],
            'input_data.wall_core' => ['nullable', 'string'],
            'input_data.wall_bottom_skin' => ['nullable', 'string'],
            'input_data.wall_insulation' => ['nullable', 'string'],

            // Trims & Flashings
            'input_data.trim_size' => ['nullable', 'string'],
            'input_data.back_eave_condition' => ['nullable', 'string'],
            'input_data.front_eave_condition' => ['nullable', 'string'],

            // Insulation
            'input_data.wwm_option' => ['nullable', 'string', 'in:None,Roof Only,Wall Only,Roof+Wall'],

            // Finishes
            'input_data.bu_finish' => ['nullable', 'string'],

            // Freight
            'input_data.freight_type' => ['nullable', 'string', 'in:By Mammut,By Customer,FOB'],
            'input_data.freight_rate' => ['nullable', 'numeric', 'min:0'],
            'input_data.container_count' => ['nullable', 'numeric', 'min:0'],
            'input_data.container_rate' => ['nullable', 'numeric', 'min:0'],

            // Sales Codes
            'input_data.area_sales_code' => ['nullable', 'numeric'],
            'input_data.area_description' => ['nullable', 'string'],
            'input_data.acc_sales_code' => ['nullable', 'numeric'],
            'input_data.acc_description' => ['nullable', 'string'],

            // Project / Pricing
            'input_data.sales_office' => ['nullable', 'string'],
            'input_data.num_buildings' => ['nullable', 'numeric', 'min:1'],
            'input_data.erection_price' => ['nullable', 'numeric', 'min:0'],

            // Openings array
            'input_data.openings' => ['nullable', 'array'],
            'input_data.openings.*.location' => ['nullable', 'string'],
            'input_data.openings.*.size' => ['nullable', 'string'],
            'input_data.openings.*.qty' => ['nullable', 'numeric', 'min:0'],
            'input_data.openings.*.purlin_support' => ['nullable', 'numeric'],
            'input_data.openings.*.bracing' => ['nullable', 'numeric'],

            // Accessories array
            'input_data.accessories' => ['nullable', 'array'],
            'input_data.accessories.*.description' => ['nullable', 'string'],
            'input_data.accessories.*.code' => ['nullable', 'string'],
            'input_data.accessories.*.qty' => ['nullable', 'numeric', 'min:0'],
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
