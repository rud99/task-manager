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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tasks', description: 'Управление задачами')]
class TaskController extends Controller
{
    #[OA\Get(
        path: '/api/projects/{project_id}/tasks',
        summary: 'Получить список задач проекта',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'project_id',
                in: 'path',
                required: true,
                description: 'ID проекта',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Фильтр по статусу (planned, in_progress, done)',
                schema: new OA\Schema(type: 'string', enum: ['planned', 'in_progress', 'done'])
            ),
            new OA\Parameter(
                name: 'assignee_id',
                in: 'query',
                required: false,
                description: 'Фильтр по ID исполнителя',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'due_date',
                in: 'query',
                required: false,
                description: 'Фильтр по точной дате завершения (формат: Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-12-31')
            ),
            new OA\Parameter(
                name: 'due_date_from',
                in: 'query',
                required: false,
                description: 'Фильтр по дате завершения от (формат: Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-11-01')
            ),
            new OA\Parameter(
                name: 'due_date_to',
                in: 'query',
                required: false,
                description: 'Фильтр по дате завершения до (формат: Y-m-d)',
                schema: new OA\Schema(type: 'string', format: 'date', example: '2025-12-31')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список задач',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'project_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'title', type: 'string', example: 'Моя задача'),
                                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Описание задачи'),
                                    new OA\Property(property: 'status', type: 'string', example: 'planned'),
                                    new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2025-12-31'),
                                    new OA\Property(
                                        property: 'assignee',
                                        type: 'object',
                                        nullable: true,
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'Иван Иванов'),
                                            new OA\Property(property: 'email', type: 'string', example: 'ivan@example.com'),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'attachments',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                                new OA\Property(property: 'name', type: 'string', example: 'document'),
                                                new OA\Property(property: 'file_name', type: 'string', example: 'document.pdf'),
                                                new OA\Property(property: 'mime_type', type: 'string', example: 'application/pdf'),
                                                new OA\Property(property: 'size', type: 'integer', example: 102400),
                                                new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/1/document.pdf'),
                                            ],
                                            type: 'object'
                                        )
                                    ),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Проект не найден'),
        ]
    )]
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
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

    #[OA\Post(
        path: '/api/projects/{project_id}/tasks',
        summary: 'Создать новую задачу',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['title'],
                        properties: [
                            new OA\Property(property: 'title', type: 'string', example: 'Новая задача'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Описание задачи'),
                            new OA\Property(property: 'status', type: 'string', enum: ['planned', 'in_progress', 'done'], example: 'planned'),
                            new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2025-12-31'),
                            new OA\Property(property: 'assignee_id', type: 'integer', nullable: true, example: 1),
                            new OA\Property(
                                property: 'attachments',
                                type: 'array',
                                items: new OA\Items(type: 'string', format: 'binary'),
                                description: 'Массив файлов (PDF, DOC, DOCX, JPG, JPEG, PNG до 10 МБ)'
                            ),
                        ]
                    )
                ),
            ]
        ),
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'project_id',
                in: 'path',
                required: true,
                description: 'ID проекта',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Задача успешно создана',
                content: new OA\JsonContent(ref: '#/components/schemas/TaskResource')
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ]
    )]
    public function store(TaskRequest $request, Project $project): TaskResource
    {
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

    #[OA\Get(
        path: '/api/tasks/{id}',
        summary: 'Получить задачу по ID',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID задачи',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Данные задачи',
                content: new OA\JsonContent(ref: '#/components/schemas/TaskResource')
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
        ]
    )]
    public function show(Task $task): TaskResource
    {
        return new TaskResource($task->load('assignee'));
    }

    #[OA\Put(
        path: '/api/tasks/{id}',
        summary: 'Обновить задачу',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'title', type: 'string', example: 'Обновленная задача'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Новое описание'),
                            new OA\Property(property: 'status', type: 'string', enum: ['planned', 'in_progress', 'done'], example: 'in_progress'),
                            new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2025-12-31'),
                            new OA\Property(property: 'assignee_id', type: 'integer', nullable: true, example: 1),
                            new OA\Property(
                                property: 'attachments',
                                type: 'array',
                                items: new OA\Items(type: 'string', format: 'binary'),
                                description: 'Массив файлов (заменит существующие)'
                            ),
                        ]
                    )
                ),
            ]
        ),
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID задачи',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Задача успешно обновлена',
                content: new OA\JsonContent(ref: '#/components/schemas/TaskResource')
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ]
    )]
    public function update(TaskRequest $request, Task $task): TaskResource
    {
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

    #[OA\Delete(
        path: '/api/tasks/{id}',
        summary: 'Удалить задачу',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID задачи',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Задача успешно удалена'
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Задача не найдена'),
        ]
    )]
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully'], 204);
    }
}

#[OA\Schema(
    schema: 'TaskResource',
    properties: [
        new OA\Property(
            property: 'data',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'project_id', type: 'integer', example: 1),
                new OA\Property(property: 'title', type: 'string', example: 'Моя задача'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Описание задачи'),
                new OA\Property(property: 'status', type: 'string', example: 'planned'),
                new OA\Property(property: 'due_date', type: 'string', format: 'date', nullable: true, example: '2025-12-31'),
                new OA\Property(
                    property: 'assignee',
                    type: 'object',
                    nullable: true,
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Иван Иванов'),
                        new OA\Property(property: 'email', type: 'string', example: 'ivan@example.com'),
                    ]
                ),
                new OA\Property(
                    property: 'attachments',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'document'),
                            new OA\Property(property: 'file_name', type: 'string', example: 'document.pdf'),
                            new OA\Property(property: 'mime_type', type: 'string', example: 'application/pdf'),
                            new OA\Property(property: 'size', type: 'integer', example: 102400),
                            new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/1/document.pdf'),
                        ],
                        type: 'object'
                    )
                ),
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
            ],
            type: 'object'
        ),
    ]
)]
class TaskResourceSchema {}
