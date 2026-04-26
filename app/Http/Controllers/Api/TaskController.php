<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::query()
            ->with('workflow')
            ->latest()
            ->get();

        return response()->json($tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());

        return response()->json($task->load('workflow'), 201);
    }

    public function show(Task $task)
    {
        return response()->json($task->load('workflow'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return response()->json($task->fresh()->load('workflow'));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }
}
