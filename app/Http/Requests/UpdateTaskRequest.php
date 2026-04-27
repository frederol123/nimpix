<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'workflow_id' => ['sometimes', 'required', 'integer', Rule::exists('workflows', 'id')->where('user_id', $this->user()->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'position' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'due_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
