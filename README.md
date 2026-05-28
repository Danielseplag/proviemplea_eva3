# ProviEmplea — API REST · Evaluación 3 Unidad 3

Proyecto de backend desarrollado para la evaluación sumativa de la asignatura de Backend, Instituto Profesional San Sebastián. Consiste en una API REST para la Municipalidad de Providencia que implementa una **plataforma de empleo con búsqueda inversa**: las empresas encuentran talentos, no al revés.

---

## ¿Qué construimos?

Una API REST completa en **Laravel 11** con base de datos **MySQL**, corriendo en contenedores **Docker**, documentada con **Swagger / OpenAPI 3.0**.

La plataforma se llama **ProviEmplea** y maneja tres recursos principales:

- **Personas (talentos):** postulantes que crean un perfil profesional. La API los expone como *CV Ciego* (sin datos personales identificables como email o teléfono) para que las empresas los evalúen sin sesgos.
- **Empresas:** organizaciones que buscan talentos y pueden solicitar contacto.
- **Administración:** los funcionarios municipales gestionan los procesos de selección, actualizando estados (pendiente → contactado → entrevista → seleccionado).

---

## Stack tecnológico

| Tecnología | Versión | Para qué |
|---|---|---|
| PHP | 8.4 | Lenguaje base |
| Laravel | 11 | Framework backend |
| MySQL | 8.0 | Base de datos |
| Nginx | 1.27 | Servidor web |
| Docker | - | Contenedores |
| L5-Swagger | 11.0 | Generador de documentación |
| swagger-php | 6.1 | Anotaciones OpenAPI en PHP |

---

## Cómo levantar el proyecto

### Requisitos previos

- Docker Desktop instalado y corriendo
- Git

### Pasos

**1. Clonar el repositorio**

```bash
git clone https://github.com/Danielseplag/proviemplea_eva3.git
cd proviemplea_eva3
```

**2. Configurar el entorno**

Dentro de la carpeta `backend/`, crear el archivo `.env` copiando el ejemplo:

```bash
cp backend/.env.example backend/.env
```

Asegurarse de que el `.env` tenga estas variables de base de datos:

```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=proviemplea
DB_USERNAME=proviemplea_user
DB_PASSWORD=proviemplea_pass
```

**3. Levantar los contenedores**

```bash
cd backend
docker compose up -d
```

Esto levanta tres servicios:
- `proviemplea_app` — PHP 8.4-FPM con Laravel
- `proviemplea_web` — Nginx en el puerto 8080
- `proviemplea_db` — MySQL en el puerto 3307

**4. Instalar dependencias y generar clave**

```bash
docker exec proviemplea_app composer install
docker exec proviemplea_app php artisan key:generate
```

**5. Ejecutar las migraciones**

```bash
docker exec proviemplea_app php artisan migrate
```

Esto crea las tres tablas: `personas`, `empresas`, `contactos_solicitados`.

**6. Generar la documentación Swagger**

```bash
docker exec proviemplea_app php artisan l5-swagger:generate
```

**7. Verificar que todo funciona**

Abrir en el navegador:

```
http://localhost:8080/api/health
```

Debería responder:

```json
{
  "status": "online",
  "service": "ProviEmplea API",
  "version": "1.0.0"
}
```

---

## Documentación Swagger

Una vez levantado el proyecto, la documentación interactiva está disponible en:

```
http://localhost:8080/api/documentation
```

Desde ahí se puede explorar y probar cada endpoint directamente en el navegador, sin necesidad de Postman ni herramientas externas.

---

## Endpoints disponibles

### Health
| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/health` | Estado del servidor |

### Personas
| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/personas` | Listar talentos (CV Ciego, con filtros) |
| POST | `/api/personas` | Registrar nuevo talento |
| GET | `/api/personas/{id}` | Ver perfil completo de un talento |
| PUT | `/api/personas/{id}` | Actualizar datos del talento |
| DELETE | `/api/personas/{id}` | Desactivar talento (borrado lógico) |
| PATCH | `/api/personas/{id}/validar` | Validar perfil por administración |

### Empresas
| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/empresas` | Listar empresas activas |
| POST | `/api/empresas` | Registrar nueva empresa |
| GET | `/api/empresas/{id}` | Ver detalle de empresa |
| PUT | `/api/empresas/{id}` | Actualizar empresa |
| DELETE | `/api/empresas/{id}` | Desactivar empresa (borrado lógico) |
| PATCH | `/api/empresas/{id}/validar` | Validar empresa por administración |

### Administración
| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/admin/contactos` | Listar solicitudes de contacto |
| POST | `/api/admin/contactos` | Registrar nueva solicitud |
| PATCH | `/api/admin/contactos/{id}/estado` | Avanzar estado del proceso |
| GET | `/api/admin/estadisticas` | Estadísticas generales de la plataforma |

---

## Decisiones de diseño importantes

### CV Ciego
El listado de personas (`GET /api/personas`) nunca expone el email ni el teléfono. Esto se implementó con el método `getCvCiego()` en el modelo `Persona`, que retorna solo los campos públicos. El objetivo es que las empresas evalúen perfiles sin datos que puedan generar sesgos.

### UUID como clave primaria
Todos los modelos usan UUID en lugar de ID numérico autoincremental. Esto evita que se puedan enumerar registros (intentar `/personas/1`, `/personas/2`, etc.) y mejora la seguridad de la API.

### Borrado lógico
No se elimina nada de la base de datos. Las personas y empresas tienen un campo `activo`. Al hacer DELETE, se setea `activo = false`. Así se mantiene el historial completo.

### Estado del proceso de selección
Los contactos entre empresas y talentos siguen una máquina de estados:

```
pendiente → contactado → entrevista → seleccionado
                                    → no-seleccionado → proceso-cerrado
```

Cada transición registra automáticamente la fecha (`fecha_contacto`, `fecha_entrevista`, `fecha_resultado`).

### Rate Limiting
La API tiene un límite de **60 requests por minuto** por cliente, configurado en `bootstrap/app.php` con `throttleApi('60,1')`. Al superarlo, responde con HTTP `429 Too Many Requests` e incluye el header `Retry-After` con los segundos de espera. Esto está documentado en Swagger en cada endpoint de listado.

---

## Estructura del proyecto

```
proviemplea_eva3/
└── backend/
    ├── app/
    │   ├── Http/Controllers/
    │   │   ├── Controller.php          ← Clase base con info Swagger y ApiResponse
    │   │   ├── HealthController.php
    │   │   ├── PersonaController.php
    │   │   ├── EmpresaController.php
    │   │   └── AdministracionController.php
    │   ├── Models/
    │   │   ├── Persona.php
    │   │   ├── Empresa.php
    │   │   └── ContactoSolicitado.php
    │   └── Traits/
    │       └── ApiResponse.php         ← Respuestas JSON estandarizadas
    ├── bootstrap/
    │   └── app.php                     ← Rate limiting configurado aquí
    ├── database/migrations/            ← Estructura de las 3 tablas
    ├── docker/
    │   ├── php/Dockerfile
    │   └── nginx/default.conf
    ├── docker-compose.yaml
    ├── routes/
    │   └── api.php                     ← Todas las rutas de la API
    └── storage/api-docs/
        └── api-docs.json               ← Documentación Swagger generada
```

---

## Puntos de evaluación cubiertos

| Punto | Descripción | Estado |
|---|---|---|
| 1 | Especificación Swagger documentando todos los CRUD | Completo |
| 2 | Probar la documentación desde Swagger UI | Completo |
| 3 | Ejemplos de distintos tipos de datos en la API | Completo |
| 4 | Usar Swagger para identificar errores (404, 409, 422) | Completo |
| 5 | Documentar rate limiting y caché (429 en Swagger) | Completo |

---

## Autores

Desarrollado por **Daniel Sepúlveda** — Ingeniería en Informática, Instituto Profesional San Sebastián, 2026.