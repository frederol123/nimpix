<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return [
            'workflow_id' => ['required', 'integer', Rule::exists('workflows', 'id')->where('user_id', $this->user()->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'position' => ['nullable', 'integer', 'min:0'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
