<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Projects', description: 'Управление проектами')]
class ProjectController extends Controller
{
    #[OA\Get(
        path: '/api/projects',
        summary: 'Получить список проектов пользователя',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Список проектов',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Мой проект'),
                                    new OA\Property(property: 'description', type: 'string', example: 'Описание проекта'),
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
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $projects = auth()->user()->projects()->latest()->get();

        return ProjectResource::collection($projects);
    }

    #[OA\Post(
        path: '/api/projects',
        summary: 'Создать новый проект',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Новый проект'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Описание проекта'),
                ]
            )
        ),
        tags: ['Projects'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Проект успешно создан',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Новый проект'),
                                new OA\Property(property: 'description', type: 'string', example: 'Описание проекта'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ]
    )]
    public function store(ProjectRequest $request): ProjectResource
    {
        $project = auth()->user()->projects()->create($request->validated());

        return new ProjectResource($project);
    }

    #[OA\Get(
        path: '/api/projects/{id}',
        summary: 'Получить проект по ID',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID проекта',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Данные проекта',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Мой проект'),
                                new OA\Property(property: 'description', type: 'string', example: 'Описание проекта'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Проект не найден'),
        ]
    )]
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project);
    }

    #[OA\Put(
        path: '/api/projects/{id}',
        summary: 'Обновить проект',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Обновленный проект'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Новое описание'),
                ]
            )
        ),
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID проекта',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Проект успешно обновлен',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Обновленный проект'),
                                new OA\Property(property: 'description', type: 'string', example: 'Новое описание'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Проект не найден'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ]
    )]
    public function update(ProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return new ProjectResource($project);
    }

    #[OA\Delete(
        path: '/api/projects/{id}',
        summary: 'Удалить проект',
        security: [['bearerAuth' => []]],
        tags: ['Projects'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID проекта',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Проект успешно удален'
            ),
            new OA\Response(response: 401, description: 'Не авторизован'),
            new OA\Response(response: 403, description: 'Доступ запрещен'),
            new OA\Response(response: 404, description: 'Проект не найден'),
        ]
    )]
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully'], 204);
    }
}
