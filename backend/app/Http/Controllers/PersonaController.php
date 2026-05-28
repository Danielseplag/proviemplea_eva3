<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Persona",
    properties: [
        new OA\Property(property: "id", type: "string", format: "uuid"),
        new OA\Property(property: "codigo_talento", type: "string", example: "PROV-2026-A1B2"),
        new OA\Property(property: "email", type: "string", format: "email"),
        new OA\Property(property: "nivel_educacional", type: "string", nullable: true),
        new OA\Property(property: "anios_experiencia", type: "integer", example: 3),
        new OA\Property(property: "tipo_jornada", type: "string", nullable: true),
        new OA\Property(property: "modalidad", type: "string", nullable: true),
        new OA\Property(property: "validado", type: "boolean", example: false),
        new OA\Property(property: "activo", type: "boolean", example: true),
        new OA\Property(property: "porcentaje_completitud", type: "integer", example: 73),
    ]
)]
class PersonaController extends Controller

{
    #[OA\Get(
    path: "/personas",
    operationId: "getPersonas",
    summary: "Listar talentos en formato CV ciego",
    description: "Retorna perfiles sin datos personales identificables. Resultados recomendados para caché del lado cliente por 5 minutos. Límite: 60 requests/minuto.",
    tags: ["Personas"],
    parameters: [
        new OA\Parameter(
            name: "validado",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "boolean")
        ),
        new OA\Parameter(
            name: "nivel_educacional",
            in: "query",
            required: false,
            schema: new OA\Schema(type: "string", enum: ["basica", "media", "tecnica", "universitaria", "postgrado"])
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Listado exitoso",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "success", type: "boolean", example: true),
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/Persona")
                    )
                ]
            )
        ),
        new OA\Response(
            response: 429,
            description: "Demasiadas solicitudes. Límite: 60 req/min. Espere el tiempo indicado en el header Retry-After."
        )
    ]
)]

    public function index(Request $request): JsonResponse
    {
        $query = Persona::where('activo', true);

        if ($request->has('validado')) {
            $query->where('validado', $request->boolean('validado'));
        }

        if ($request->has('nivel_educacional')) {
            $query->where('nivel_educacional', $request->input('nivel_educacional'));
        }

        return $this->successResponse(
            $query->get()->map(fn($p) => $p->getCvCiego())
        );
    }

    #[OA\Post(
    path: "/personas",
    operationId: "createPersona",
    summary: "Registrar nuevo talento",
    tags: ["Personas"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "talento@ejemplo.cl"),
                new OA\Property(property: "nivel_educacional", type: "string", enum: ["basica","media","tecnica","universitaria","postgrado"]),
                new OA\Property(property: "anios_experiencia", type: "integer", example: 3),
                new OA\Property(property: "tipo_jornada", type: "string", enum: ["completa","part-time","por-horas"]),
                new OA\Property(property: "modalidad", type: "string", enum: ["presencial","remoto","hibrido"])
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Persona creada",
            content: new OA\JsonContent(ref: "#/components/schemas/Persona")
        ),
        new OA\Response(response: 422, description: "Errores de validación")
    ]
)]


    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'                => 'required|email|unique:personas,email',
            'telefono'             => 'nullable|string|max:15',
            'resumen'              => 'nullable|string',
            'nivel_educacional'    => 'nullable|in:basica,media,tecnica,universitaria,postgrado',
            'titulo_carrera'       => 'nullable|string',
            'anio_egreso'          => 'nullable|integer|min:1950|max:' . date('Y'),
            'anios_experiencia'    => 'nullable|integer|min:0',
            'areas_experiencia'    => 'nullable|array',
            'competencias'         => 'nullable|array',
            'rango_renta'          => 'nullable|string',
            'tipo_jornada'         => 'nullable|in:completa,part-time,por-horas',
            'modalidad'            => 'nullable|in:presencial,remoto,hibrido',
            'cursos'               => 'nullable|array',
            'idiomas'              => 'nullable|array',
            'portafolio_url'       => 'nullable|url',
            'persona_discapacidad' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Los datos enviados no son válidos.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $data['codigo_talento'] = $this->generarCodigoTalento();
        $data['porcentaje_completitud'] = $this->calcularCompletitud($data);

        return $this->successResponse(Persona::create($data), 201);
    }

    #[OA\Get(
    path: "/personas/{id}",
    operationId: "getPersona",
    summary: "Obtener persona por ID",
    tags: ["Personas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Persona encontrada",
            content: new OA\JsonContent(ref: "#/components/schemas/Persona")
        ),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function show(string $id): JsonResponse
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return $this->errorResponse('Persona no encontrada.', 404);
        }

        return $this->successResponse($persona);
    }

    #[OA\Put(
    path: "/personas/{id}",
    operationId: "updatePersona",
    summary: "Actualizar persona",
    tags: ["Personas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Persona actualizada"),
        new OA\Response(response: 404, description: "No encontrada"),
        new OA\Response(response: 422, description: "Errores de validación")
    ]
)]


    public function update(Request $request, string $id): JsonResponse
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return $this->errorResponse('Persona no encontrada.', 404);
        }

        $validator = Validator::make($request->all(), [
            'email'                => 'sometimes|email|unique:personas,email,' . $persona->id,
            'telefono'             => 'nullable|string|max:15',
            'resumen'              => 'nullable|string',
            'nivel_educacional'    => 'nullable|in:basica,media,tecnica,universitaria,postgrado',
            'titulo_carrera'       => 'nullable|string',
            'anio_egreso'          => 'nullable|integer|min:1950|max:' . date('Y'),
            'anios_experiencia'    => 'nullable|integer|min:0',
            'areas_experiencia'    => 'nullable|array',
            'competencias'         => 'nullable|array',
            'rango_renta'          => 'nullable|string',
            'tipo_jornada'         => 'nullable|in:completa,part-time,por-horas',
            'modalidad'            => 'nullable|in:presencial,remoto,hibrido',
            'cursos'               => 'nullable|array',
            'idiomas'              => 'nullable|array',
            'portafolio_url'       => 'nullable|url',
            'persona_discapacidad' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Los datos enviados no son válidos.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $data['porcentaje_completitud'] = $this->calcularCompletitud(
            array_merge($persona->toArray(), $data)
        );

        $persona->update($data);

        return $this->successResponse($persona->fresh());
    }

    #[OA\Delete(
    path: "/personas/{id}",
    operationId: "deletePersona",
    summary: "Desactivar persona",
    tags: ["Personas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Persona desactivada"),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function destroy(string $id): JsonResponse
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return $this->errorResponse('Persona no encontrada.', 404);
        }

        $persona->update(['activo' => false]);

        return $this->successResponse(['message' => 'Persona desactivada exitosamente.']);
    }

    #[OA\Patch(
    path: "/personas/{id}/validar",
    operationId: "validarPersona",
    summary: "Validar perfil de persona",
    tags: ["Personas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Persona validada"),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function validar(string $id): JsonResponse
    {
        $persona = Persona::find($id);

        if (!$persona) {
            return $this->errorResponse('Persona no encontrada.', 404);
        }

        $persona->update(['validado' => true]);

        return $this->successResponse($persona->fresh());
    }


    private function generarCodigoTalento(): string
    {
        do {
            $codigo = 'PROV-' . date('Y') . '-' . strtoupper(Str::random(4));
        } while (Persona::where('codigo_talento', $codigo)->exists());

        return $codigo;
    }

    private function calcularCompletitud(array $data): int
    {
        $campos = [
            'email',
            'telefono',
            'resumen',
            'nivel_educacional',
            'titulo_carrera',
            'anio_egreso',
            'anios_experiencia',
            'competencias',
            'rango_renta',
            'tipo_jornada',
            'modalidad'
        ];

        $completados = count(array_filter($campos, fn($c) => !empty($data[$c])));

        return (int) round(($completados / count($campos)) * 100);
    }
}
