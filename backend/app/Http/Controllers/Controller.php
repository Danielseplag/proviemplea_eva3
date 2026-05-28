<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "ProviEmplea API",
    description: "API REST para la plataforma de empleo ProviEmplea. Permite gestionar talentos, empresas y procesos de selección con curriculum ciego."
)]
#[OA\Server(
    url: "http://localhost:8080/api",
    description: "Servidor de desarrollo local"
)]
#[OA\Tag(name: "Health",          description: "Estado del sistema")]
#[OA\Tag(name: "Personas",        description: "Gestión de perfiles de talentos")]
#[OA\Tag(name: "Empresas",        description: "Gestión de empresas empleadoras")]
#[OA\Tag(name: "Administración",  description: "Gestión administrativa")]

#[OA\SecurityScheme(
    securityScheme: "rateLimitHeaders",
    type: "apiKey",
    in: "header",
    name: "X-RateLimit-Limit"
)]

abstract class Controller
{
    use ApiResponse;
}
