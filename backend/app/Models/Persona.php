<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Persona extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'telefono',
        'codigo_talento',
        'resumen',
        'nivel_educacional',
        'titulo_carrera',
        'anio_egreso',
        'anios_experiencia',
        'areas_experiencia',
        'competencias',
        'rango_renta',
        'tipo_jornada',
        'modalidad',
        'cursos',
        'idiomas',
        'portafolio_url',
        'persona_discapacidad',
        'validado',
        'activo',
        'porcentaje_completitud',
    ];

    protected $casts = [
        'areas_experiencia'    => 'array',
        'competencias'         => 'array',
        'cursos'               => 'array',
        'idiomas'              => 'array',
        'persona_discapacidad' => 'boolean',
        'validado'             => 'boolean',
        'activo'               => 'boolean',
    ];

    public function getCvCiego(): array
    {
        return [
            'id'                   => $this->id,
            'codigo_talento'       => $this->codigo_talento,
            'resumen'              => $this->resumen,
            'nivel_educacional'    => $this->nivel_educacional,
            'titulo_carrera'       => $this->titulo_carrera,
            'anio_egreso'          => $this->anio_egreso,
            'anios_experiencia'    => $this->anios_experiencia,
            'areas_experiencia'    => $this->areas_experiencia,
            'competencias'         => $this->competencias,
            'rango_renta'          => $this->rango_renta,
            'tipo_jornada'         => $this->tipo_jornada,
            'modalidad'            => $this->modalidad,
            'cursos'               => $this->cursos,
            'idiomas'              => $this->idiomas,
            'portafolio_url'       => $this->portafolio_url,
            'persona_discapacidad' => $this->persona_discapacidad,
        ];
    }
}
