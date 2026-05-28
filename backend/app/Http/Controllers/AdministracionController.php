<?php

namespace App\Http\Controllers;

use App\Models\ContactoSolicitado;
use App\Models\Empresa;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ContactoSolicitado",
    properties: [
        new OA\Property(property: "id", type: "string", format: "uuid"),
        new OA\Property(property: "empresa_id", type: "string", format: "uuid"),
        new OA\Property(property: "persona_id", type: "string", format: "uuid"),
        new OA\Property(property: "estado", type: "string", example: "pendiente"),
        new OA\Property(property: "notas_admin", type: "string", nullable: true),
        new OA\Property(property: "fecha_contacto", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "fecha_entrevista", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "fecha_resultado", type: "string", format: "date-time", nullable: true),
    ]
)]
class AdministracionController extends Controller

{

    #[OA\Get(
        path: "/admin/contactos",
        operationId: "getContactos",
        summary: "Listar solicitudes de contacto",
        tags: ["Administración"],
        parameters: [
            new OA\Parameter(
                name: "estado",
                in: "query",
                required: false,
                schema: new OA\Schema(
                    type: "string",
                    enum: ["pendiente", "contactado", "entrevista", "seleccionado", "no-seleccionado", "proceso-cerrado"]
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado exitoso",
                content: new OA\JsonContent(ref: "#/components/schemas/ContactoSolicitado")
            )
        ]
    )]


    public function listarContactos(Request $request): JsonResponse
    {
        $query = ContactoSolicitado::with(['empresa', 'persona']);

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        return $this->successResponse($query->orderBy('created_at', 'desc')->get());
    }

    #[OA\Post(
        path: "/admin/contactos",
        operationId: "crearContacto",
        summary: "Registrar solicitud de contacto",
        tags: ["Administración"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["empresa_id", "persona_id"],
                properties: [
                    new OA\Property(property: "empresa_id", type: "string", format: "uuid"),
                    new OA\Property(property: "persona_id", type: "string", format: "uuid"),
                    new OA\Property(property: "notas_admin", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Contacto creado"),
            new OA\Response(response: 409, description: "Ya existe solicitud activa"),
            new OA\Response(response: 422, description: "Errores de validación")
        ]
    )]


    public function crearContacto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'empresa_id'  => 'required|exists:empresas,id',
            'persona_id'  => 'required|exists:personas,id',
            'notas_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Los datos enviados no son válidos.', 422, $validator->errors()->toArray());
        }

        $existente = ContactoSolicitado::where('empresa_id', $request->empresa_id)
            ->where('persona_id', $request->persona_id)
            ->whereNotIn('estado', ['no-seleccionado', 'proceso-cerrado'])
            ->first();

        if ($existente) {
            return $this->errorResponse('Ya existe una solicitud activa entre esta empresa y talento.', 409);
        }

        $contacto = ContactoSolicitado::create($validator->validated());

        return $this->successResponse($contacto->load(['empresa', 'persona']), 201);
    }

    #[OA\Patch(
        path: "/admin/contactos/{id}/estado",
        operationId: "actualizarEstado",
        summary: "Actualizar estado del proceso",
        tags: ["Administración"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["estado"],
                properties: [
                    new OA\Property(
                        property: "estado",
                        type: "string",
                        enum: ["pendiente", "contactado", "entrevista", "seleccionado", "no-seleccionado", "proceso-cerrado"]
                    ),
                    new OA\Property(property: "notas_admin", type: "string", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Estado actualizado"),
            new OA\Response(response: 404, description: "Contacto no encontrado")
        ]
    )]


    public function actualizarEstado(Request $request, string $id): JsonResponse
    {
        $contacto = ContactoSolicitado::find($id);

        if (!$contacto) {
            return $this->errorResponse('Contacto no encontrado.', 404);
        }

        $validator = Validator::make($request->all(), [
            'estado'      => 'required|in:pendiente,contactado,entrevista,seleccionado,no-seleccionado,proceso-cerrado',
            'notas_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Los datos enviados no son válidos.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();

        if ($data['estado'] === 'contactado' && !$contacto->fecha_contacto) {
            $data['fecha_contacto'] = now();
        } elseif ($data['estado'] === 'entrevista' && !$contacto->fecha_entrevista) {
            $data['fecha_entrevista'] = now();
        } elseif (in_array($data['estado'], ['seleccionado', 'no-seleccionado']) && !$contacto->fecha_resultado) {
            $data['fecha_resultado'] = now();
        }

        $contacto->update($data);

        return $this->successResponse($contacto->load(['empresa', 'persona']));
    }

    #[OA\Get(
    path: "/admin/estadisticas",
    operationId: "getEstadisticas",
    summary: "Estadísticas generales de la plataforma",
    tags: ["Administración"],
    responses: [
        new OA\Response(response: 200, description: "Estadísticas generadas",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "total_personas", type: "integer", example: 45),
                    new OA\Property(property: "personas_validadas", type: "integer", example: 38),
                    new OA\Property(property: "total_empresas", type: "integer", example: 12),
                    new OA\Property(property: "contactos_exitosos", type: "integer", example: 15),
                ]
            )
        )
    ]
)]


    public function estadisticas(): JsonResponse
    {
        return $this->successResponse([
            'total_personas'       => Persona::count(),
            'personas_validadas'   => Persona::where('validado', true)->count(),
            'total_empresas'       => Empresa::count(),
            'empresas_validadas'   => Empresa::where('validado', true)->count(),
            'contactos_pendientes' => ContactoSolicitado::where('estado', 'pendiente')->count(),
            'contactos_en_proceso' => ContactoSolicitado::whereIn('estado', ['contactado', 'entrevista'])->count(),
            'contactos_exitosos'   => ContactoSolicitado::where('estado', 'seleccionado')->count(),
        ]);
    }
}
