<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Empresa",
    properties: [
        new OA\Property(property: "id", type: "string", format: "uuid"),
        new OA\Property(property: "nombre_empresa", type: "string", example: "TechCorp SpA"),
        new OA\Property(property: "rut_empresa", type: "string", example: "76123456-7"),
        new OA\Property(property: "email", type: "string", format: "email"),
        new OA\Property(property: "tipo_empresa", type: "string", example: "contratacion-directa"),
        new OA\Property(property: "contacto_nombre", type: "string", example: "Ana López"),
        new OA\Property(property: "contacto_email", type: "string", format: "email"),
        new OA\Property(property: "validado", type: "boolean", example: false),
        new OA\Property(property: "activo", type: "boolean", example: true),
    ]
)]
class EmpresaController extends Controller



{

   #[OA\Get(
    path: "/empresas",
    operationId: "getEmpresas",
    summary: "Listar empresas activas",
    description: "Retorna empresas activas con filtros opcionales. Límite: 60 requests/minuto.",
    tags: ["Empresas"],
    parameters: [
        new OA\Parameter(name: "tipo_empresa", in: "query", required: false,
            schema: new OA\Schema(type: "string", enum: ["contratacion-directa","est","outsourcing"])
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Listado exitoso",
            content: new OA\JsonContent(ref: "#/components/schemas/Empresa")
        ),
        new OA\Response(
            response: 429,
            description: "Demasiadas solicitudes. Límite: 60 req/min. Espere el tiempo indicado en el header Retry-After."
        )
    ]
)]



    public function index(Request $request): JsonResponse
    {
        $query = Empresa::where('activo', true);
        if ($request->has('rubro')) {
            $query->where('rubro', $request->input('rubro'));
        }
        if ($request->has('tipo_empresa')) {
            $query->where('tipo_empresa', $request->input('tipo_empresa'));
        }
        return $this->successResponse($query->get());
    }

    #[OA\Post(
    path: "/empresas",
    operationId: "createEmpresa",
    summary: "Registrar nueva empresa",
    tags: ["Empresas"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["nombre_empresa", "rut_empresa", "email", "tipo_empresa", "contacto_nombre", "contacto_email"],
            properties: [
                new OA\Property(property: "nombre_empresa", type: "string", example: "TechCorp SpA"),
                new OA\Property(property: "rut_empresa", type: "string", example: "76123456-7"),
                new OA\Property(property: "email", type: "string", format: "email", example: "contacto@techcorp.cl"),
                new OA\Property(property: "tipo_empresa", type: "string", enum: ["contratacion-directa", "est", "outsourcing"]),
                new OA\Property(property: "logo_url", type: "string", format: "url", nullable: true),
                new OA\Property(property: "rubro", type: "string", nullable: true),
                new OA\Property(property: "presentacion", type: "string", nullable: true),
                new OA\Property(property: "beneficios", type: "array", items: new OA\Items(type: "string"), nullable: true),
                new OA\Property(property: "contacto_nombre", type: "string", example: "Ana López"),
                new OA\Property(property: "contacto_email", type: "string", format: "email"),
                new OA\Property(property: "contacto_telefono", type: "string", nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Empresa creada",
            content: new OA\JsonContent(ref: "#/components/schemas/Empresa")
        ),
        new OA\Response(response: 422, description: "Errores de validación")
    ]
)]


    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre_empresa'    => 'required|string|max:255',
            'rut_empresa'       => 'required|string|max:20|unique:empresas,rut_empresa,NULL,id,activo,1',
            'email'             => 'required|email|unique:empresas,email,NULL,id,activo,1',
            'tipo_empresa'      => 'required|in:contratacion-directa,est,outsourcing',
            'contacto_nombre'   => 'required|string|max:100',
            'contacto_email'    => 'required|email',
            'logo_url'          => 'nullable|url',
            'rubro'             => 'nullable|string|max:100',
            'presentacion'      => 'nullable|string',
            'beneficios'        => 'nullable|array',
            'contacto_telefono' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Los datos enviados no son válidos.', 422, $validator->errors()->toArray());
        }

        return $this->successResponse(Empresa::create($validator->validated()), 201);
    }

    #[OA\Get(
    path: "/empresas/{id}",
    operationId: "getEmpresa",
    summary: "Obtener empresa por ID",
    tags: ["Empresas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Empresa encontrada"),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function show(string $id): JsonResponse
    {
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->activo) {
            return $this->errorResponse('Empresa no encontrada.', 404);
        }

        return $this->successResponse($empresa);
    }

    #[OA\Put(
    path: "/empresas/{id}",
    operationId: "updateEmpresa",
    summary: "Actualizar empresa",
    tags: ["Empresas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Empresa actualizada"),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function update(Request $request, string $id): JsonResponse
    {
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->activo) {
            return $this->errorResponse('Empresa no encontrada.', 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_empresa'    => 'sometimes|string|max:255',
            'rut_empresa'       => 'sometimes|string|max:20|unique:empresas,rut_empresa,' . $id . ',id,activo,1',
            'email'             => 'sometimes|email|unique:empresas,email,' . $id . ',id,activo,1',
            'logo_url'          => 'nullable|url',
            'rubro'             => 'nullable|string|max:100',
            'tipo_empresa'      => 'sometimes|in:contratacion-directa,est,outsourcing',
            'presentacion'      => 'nullable|string',
            'beneficios'        => 'nullable|array',
            'contacto_nombre'   => 'sometimes|string|max:100',
            'contacto_email'    => 'sometimes|email',
            'contacto_telefono' => 'nullable|string|max:20',
        ]);


        if ($validator->fails()) {
            return $this->errorResponse('Los datos enviados no son válidos.', 422, $validator->errors()->toArray());
        }

        $empresa->update($validator->validated());

        return $this->successResponse($empresa);
    }

    #[OA\Delete(
    path: "/empresas/{id}",
    operationId: "deleteEmpresa",
    summary: "Desactivar empresa",
    tags: ["Empresas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Empresa desactivada"),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function destroy(string $id): JsonResponse
    {
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->activo) {
            return $this->errorResponse('Empresa no encontrada.', 404);
        }

        $empresa->update(['activo' => false]);

        return $this->successResponse(['message' => 'Empresa desactivada exitosamente.']);
    }

    #[OA\Patch(
    path: "/empresas/{id}/validar",
    operationId: "validarEmpresa",
    summary: "Validar empresa",
    tags: ["Empresas"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true,
            schema: new OA\Schema(type: "string", format: "uuid")
        )
    ],
    responses: [
        new OA\Response(response: 200, description: "Empresa validada"),
        new OA\Response(response: 404, description: "No encontrada")
    ]
)]


    public function validar(string $id): JsonResponse
    {
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->activo) {
            return $this->errorResponse('Empresa no encontrada.', 404);
        }

        $empresa->update(['validado' => true]);

        return $this->successResponse($empresa);
    }
}
