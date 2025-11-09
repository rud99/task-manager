<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\TaskCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
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

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->input('due_date'));
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->input('due_date_from'));
        }

        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->input('due_date_to'));
        }

        $tasks = $query->latest()->get();

        return TaskResource::collection($tasks);
    }

    public function store(TaskRequest $request, Project $project): TaskResource
    {
        $this->authorize('view', $project);

        $data = $request->except('attachments');

        $task = $project->tasks()->create($data);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $task->addMedia($file)->toMediaCollection('attachments');
            }
        }

        $task->load('assignee', 'project');

        if ($task->assignee_id) {
            $task->assignee->notify(new TaskCreatedNotification($task));
        }

        return new TaskResource($task);
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load('assignee'));
    }

    public function update(TaskRequest $request, Task $task): TaskResource
    {
        //        dd(json_decode($request->getContent(), true));
        //        dd($request->all());
        $this->authorize('update', $task);

        $data = $request->except('attachments');

        $task->update($data);

        if ($request->hasFile('attachments')) {
            $task->clearMediaCollection('attachments');
            foreach ($request->file('attachments') as $file) {
                $task->addMedia($file)->toMediaCollection('attachments');
            }
        }

        return new TaskResource($task->load('assignee'));
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 204);
    }
}
