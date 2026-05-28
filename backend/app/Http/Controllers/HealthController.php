<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class HealthController extends Controller
{
    #[OA\Get(
        path: "/health",
        operationId: "healthCheck",
        summary: "Verificar estado del servicio",
        description: "Verifica que la API está operativa.",
        tags: ["Health"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Servicio operativo",
                content: new OA\JsonContent(
                    example: ["status" => "online", "service" => "ProviEmplea API", "version" => "1.0.0"]
                )
            )
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status'    => 'online',
            'service'   => 'ProviEmplea API',
            'version'   => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
