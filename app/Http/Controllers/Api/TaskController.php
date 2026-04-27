<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::query()
            ->with('workflow')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('workflow_id'), fn ($q) => $q->where('workflow_id', $request->integer('workflow_id')))
            ->when($request->filled('due_at_from'), fn ($q) => $q->whereDate('due_at', '>=', $request->due_at_from))
            ->when($request->filled('due_at_to'), fn ($q) => $q->whereDate('due_at', '<=', $request->due_at_to));

        $sort = in_array($request->sort, ['created_at', 'due_at', 'name', 'status', 'position'])
            ? $request->sort
            : 'created_at';

        $direction = $request->direction === 'asc' ? 'asc' : 'desc';

        $tasks->orderBy($sort, $direction);

        return TaskResource::collection($tasks->paginate());
    }

    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());

        return (new TaskResource($task->load('workflow')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load('workflow'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return new TaskResource($task->fresh()->load('workflow'));
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'tasks' => ['required', 'array'],
            'tasks.*.id' => ['required', 'integer', 'distinct'],
            'tasks.*.position' => ['required', 'integer', 'min:0'],
        ]);

        $taskIds = collect($request->tasks)->pluck('id');

        $tasks = Task::whereIn('id', $taskIds)->get();

        foreach ($tasks as $task) {
            $this->authorize('update', $task);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->tasks as $item) {
                Task::where('id', $item['id'])->update(['position' => $item['position']]);
            }
        });

        return response()->noContent();
    }
}
