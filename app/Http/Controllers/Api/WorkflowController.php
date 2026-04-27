<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkflowRequest;
use App\Http\Requests\UpdateWorkflowRequest;
use App\Http\Resources\WorkflowResource;
use App\Models\Workflow;

class WorkflowController extends Controller
{
    public function index()
    {
        $workflows = Workflow::query()
            ->with(['user', 'tasks'])
            ->latest()
            ->paginate();

        return WorkflowResource::collection($workflows);
    }

    public function store(StoreWorkflowRequest $request)
    {
        $workflow = $request->user()->workflows()->create($request->validated());

        return (new WorkflowResource($workflow->load(['user', 'tasks'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Workflow $workflow)
    {
        $this->authorize('view', $workflow);

        return new WorkflowResource($workflow->load(['user', 'tasks']));
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow)
    {
        $workflow->update($request->validated());

        return new WorkflowResource($workflow->fresh()->load(['user', 'tasks']));
    }

    public function destroy(Workflow $workflow)
    {
        $this->authorize('delete', $workflow);

        $workflow->delete();

        return response()->noContent();
    }
}
