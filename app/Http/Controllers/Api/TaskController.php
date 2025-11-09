<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $query = $project->tasks()->with('assignee');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }

        $tasks = $query->latest()->get();

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, Project $project): TaskResource
    {
        $this->authorize('view', $project);

        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $task = $project->tasks()->create($data);

        return new TaskResource($task->load('assignee'));
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task->load('assignee'));
    }

    public function update(TaskRequest $request, Task $task): TaskResource
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            if ($task->attachment) {
                Storage::disk('public')->delete($task->attachment);
            }
            $data['attachment'] = $request->file('attachment')->store('attachments', 'public');
        }

        $task->update($data);

        return new TaskResource($task->load('assignee'));
    }

    public function destroy(Task $task): JsonResponse
    {
        if ($task->attachment) {
            Storage::disk('public')->delete($task->attachment);
        }

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 204);
    }
}
