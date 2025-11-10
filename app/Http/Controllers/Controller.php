<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Task Manager API',
    description: 'REST API для управления проектами и задачами с системой авторизации, назначением исполнителей и поддержкой файловых вложений.',
    contact: new OA\Contact(
        email: 'support@task-manager.ru'
    )
)]
#[OA\Server(
    url: 'https://task-manager.local',
    description: 'Локальный сервер разработки'
)]
#[OA\Server(
    url: 'https://dev.xxxtask-manager.ru',
    description: 'DEV сервер'
)]
#[OA\Server(
    url: 'https://prod.xxxtask-manager.ru',
    description: 'PROD сервер'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Введите токен полученный после регистрации или входа'
)]
abstract class Controller
{
    use AuthorizesRequests;
}
