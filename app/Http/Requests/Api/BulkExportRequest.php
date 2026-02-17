<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BulkExportRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:20'],
            'ids.*' => ['required', 'integer', 'exists:estimations,id'],
            'sheets' => ['required', 'array', 'min:1'],
            'sheets.*' => ['required', 'string', 'in:recap,detail,fcpbs,sal,boq,jaf,rawmat'],
        ];
    }
}
