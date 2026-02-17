<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'project_number' => $this->project_number,
            'project_name' => $this->project_name,
            'customer_name' => $this->customer_name,
            'location' => $this->location,
            'description' => $this->description,
            'status' => $this->status,
            'summary' => $this->getSummary(),
            'estimations' => EstimationResource::collection($this->whenLoaded('estimations')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
