<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'position' => $this->position,
            'due_at' => $this->due_at,
            'workflow' => new WorkflowResource($this->whenLoaded('workflow')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
