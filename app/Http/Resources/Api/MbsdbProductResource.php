<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MbsdbProductResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'unit' => $this->unit,
            'category' => $this->category,
            'rate' => $this->rate,
            'rate_type' => $this->rate_type,
            'metadata' => $this->metadata,
        ];
    }
}
