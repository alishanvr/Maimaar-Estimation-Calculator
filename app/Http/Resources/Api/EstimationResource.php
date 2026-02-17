<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstimationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'project_id' => $this->project_id,
            'quote_number' => $this->quote_number,
            'revision_no' => $this->revision_no,
            'building_name' => $this->building_name,
            'building_no' => $this->building_no,
            'project_name' => $this->project_name,
            'customer_name' => $this->customer_name,
            'salesperson_code' => $this->salesperson_code,
            'estimation_date' => $this->estimation_date?->format('Y-m-d'),
            'status' => $this->status,
            'input_data' => $this->input_data,
            'total_weight_mt' => $this->total_weight_mt,
            'total_price_aed' => $this->total_price_aed,
            'summary' => $this->when(
                $this->isCalculated() || $this->status === 'finalized',
                fn () => $this->results_data['summary'] ?? null
            ),
            'items' => EstimationItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
