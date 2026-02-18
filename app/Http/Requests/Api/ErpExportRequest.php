<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ErpExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'job_number' => ['required', 'string', 'max:9'],
            'building_number' => ['required', 'string', 'max:10'],
            'contract_date' => ['required', 'date'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ];
    }
}
