<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstimationRequest extends FormRequest
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
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
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

            // Cranes array
            'input_data.cranes' => ['nullable', 'array'],
            'input_data.cranes.*.description' => ['nullable', 'string', 'max:255'],
            'input_data.cranes.*.sales_code' => ['nullable', 'numeric'],
            'input_data.cranes.*.capacity' => ['nullable', 'numeric', 'min:0'],
            'input_data.cranes.*.duty' => ['nullable', 'string', 'in:L,M,H'],
            'input_data.cranes.*.rail_centers' => ['nullable', 'numeric', 'min:0'],
            'input_data.cranes.*.crane_run' => ['nullable', 'string'],

            // Mezzanines array
            'input_data.mezzanines' => ['nullable', 'array'],
            'input_data.mezzanines.*.description' => ['nullable', 'string', 'max:255'],
            'input_data.mezzanines.*.sales_code' => ['nullable', 'numeric'],
            'input_data.mezzanines.*.col_spacing' => ['nullable', 'string'],
            'input_data.mezzanines.*.beam_spacing' => ['nullable', 'string'],
            'input_data.mezzanines.*.joist_spacing' => ['nullable', 'string'],
            'input_data.mezzanines.*.clear_height' => ['nullable', 'numeric', 'min:0'],
            'input_data.mezzanines.*.double_welded' => ['nullable', 'string', 'in:Yes,No'],
            'input_data.mezzanines.*.deck_type' => ['nullable', 'string'],
            'input_data.mezzanines.*.n_stairs' => ['nullable', 'numeric', 'min:0'],
            'input_data.mezzanines.*.dead_load' => ['nullable', 'numeric', 'min:0'],
            'input_data.mezzanines.*.live_load' => ['nullable', 'numeric', 'min:0'],
            'input_data.mezzanines.*.additional_load' => ['nullable', 'numeric', 'min:0'],
            'input_data.mezzanines.*.bu_finish' => ['nullable', 'string'],
            'input_data.mezzanines.*.cf_finish' => ['nullable', 'string'],
            'input_data.mezzanines.*.min_thickness' => ['nullable', 'numeric', 'min:0'],

            // Partitions array
            'input_data.partitions' => ['nullable', 'array'],
            'input_data.partitions.*.description' => ['nullable', 'string', 'max:255'],
            'input_data.partitions.*.sales_code' => ['nullable', 'numeric'],
            'input_data.partitions.*.direction' => ['nullable', 'string', 'in:Longitudinal,Transverse'],
            'input_data.partitions.*.bu_finish' => ['nullable', 'string'],
            'input_data.partitions.*.cf_finish' => ['nullable', 'string'],
            'input_data.partitions.*.wind_speed' => ['nullable', 'numeric', 'min:0'],
            'input_data.partitions.*.col_spacing' => ['nullable', 'string'],
            'input_data.partitions.*.height' => ['nullable', 'numeric', 'min:0'],
            'input_data.partitions.*.opening_height' => ['nullable', 'numeric', 'min:0'],
            'input_data.partitions.*.front_sheeting' => ['nullable', 'string'],
            'input_data.partitions.*.back_sheeting' => ['nullable', 'string'],
            'input_data.partitions.*.insulation' => ['nullable', 'string'],

            // Canopies array
            'input_data.canopies' => ['nullable', 'array'],
            'input_data.canopies.*.description' => ['nullable', 'string', 'max:255'],
            'input_data.canopies.*.sales_code' => ['nullable', 'numeric'],
            'input_data.canopies.*.frame_type' => ['nullable', 'string', 'in:Roof Extension,Lean-To,Fascia'],
            'input_data.canopies.*.location' => ['nullable', 'string', 'in:Front,Back,Left,Right,All Around'],
            'input_data.canopies.*.height' => ['nullable', 'numeric', 'min:0'],
            'input_data.canopies.*.width' => ['nullable', 'numeric', 'min:0'],
            'input_data.canopies.*.col_spacing' => ['nullable', 'string'],
            'input_data.canopies.*.roof_sheeting' => ['nullable', 'string'],
            'input_data.canopies.*.drainage' => ['nullable', 'string'],
            'input_data.canopies.*.soffit' => ['nullable', 'string'],
            'input_data.canopies.*.wall_sheeting' => ['nullable', 'string'],
            'input_data.canopies.*.internal_sheeting' => ['nullable', 'string'],
            'input_data.canopies.*.bu_finish' => ['nullable', 'string'],
            'input_data.canopies.*.cf_finish' => ['nullable', 'string'],
            'input_data.canopies.*.live_load' => ['nullable', 'numeric', 'min:0'],
            'input_data.canopies.*.wind_speed' => ['nullable', 'numeric', 'min:0'],

            // Liners array
            'input_data.liners' => ['nullable', 'array'],
            'input_data.liners.*.description' => ['nullable', 'string', 'max:255'],
            'input_data.liners.*.sales_code' => ['nullable', 'numeric'],
            'input_data.liners.*.type' => ['nullable', 'string', 'in:Roof Liner,Wall Liner,Both'],
            'input_data.liners.*.roof_liner_code' => ['nullable', 'string', 'max:50'],
            'input_data.liners.*.wall_liner_code' => ['nullable', 'string', 'max:50'],
            'input_data.liners.*.roof_area' => ['nullable', 'numeric', 'min:0'],
            'input_data.liners.*.wall_area' => ['nullable', 'numeric', 'min:0'],
            'input_data.liners.*.roof_openings_area' => ['nullable', 'numeric', 'min:0'],
            'input_data.liners.*.wall_openings_area' => ['nullable', 'numeric', 'min:0'],

            // Imported items (from CSV import)
            'input_data.imported_items' => ['nullable', 'array'],
            'input_data.imported_items.*.description' => ['nullable', 'string'],
            'input_data.imported_items.*.code' => ['nullable', 'string'],
            'input_data.imported_items.*.sales_code' => ['nullable', 'numeric'],
            'input_data.imported_items.*.cost_code' => ['nullable', 'string'],
            'input_data.imported_items.*.size' => ['nullable', 'numeric', 'min:0'],
            'input_data.imported_items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'input_data.imported_items.*.unit' => ['nullable', 'string'],
            'input_data.imported_items.*.weight_per_unit' => ['nullable', 'numeric', 'min:0'],
            'input_data.imported_items.*.rate' => ['nullable', 'numeric', 'min:0'],
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
            'input_data.back_eave_height.numeric' => 'The back eave height must be a number.',
            'input_data.front_eave_height.numeric' => 'The front eave height must be a number.',
            'input_data.dead_load.min' => 'The dead load cannot be negative.',
            'input_data.live_load.min' => 'The live load cannot be negative.',
            'input_data.wind_speed.min' => 'The wind speed cannot be negative.',
        ];
    }
}
