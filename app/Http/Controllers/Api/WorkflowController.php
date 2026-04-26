<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Models\Workflow;

class WorkflowController extends Controller
{
    public function index()
    {
        $workflows = Workflow::query()
            ->with('tasks')
            ->latest()
            ->get();

        return response()->json($workflows);
    }

    public function store(StoreWorkflowRequest $request)
    {
        $workflow = Workflow::create($request->validated());

        return response()->json($workflow->load('tasks'), 201);
    }

    public function show(Workflow $workflow)
    {
        return response()->json($workflow->load('tasks'));
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow)
    {
        $workflow->update($request->validated());

        return response()->json($workflow->fresh()->load('tasks'));
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();

        return response()->noContent();
    }
}
