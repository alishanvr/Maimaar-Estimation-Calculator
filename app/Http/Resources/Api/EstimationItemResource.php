<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstimationItemResource extends JsonResource
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
            'item_code' => $this->item_code,
            'description' => $this->description,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'weight_kg' => $this->weight_kg,
            'rate' => $this->rate,
            'amount' => $this->amount,
            'category' => $this->category,
            'sort_order' => $this->sort_order,
        ];
    }
}
