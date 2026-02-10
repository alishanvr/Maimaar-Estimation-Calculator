<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignConfigurationResource extends JsonResource
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
            'category' => $this->category,
            'key' => $this->key,
            'value' => $this->value,
            'label' => $this->label,
            'sort_order' => $this->sort_order,
            'metadata' => $this->metadata,
        ];
    }
}
