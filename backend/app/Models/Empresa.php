<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Empresa extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nombre_empresa',
        'rut_empresa',
        'email',
        'logo_url',
        'rubro',
        'tipo_empresa',
        'presentacion',
        'beneficios',
        'contacto_nombre',
        'contacto_email',
        'contacto_telefono',
        'validado',
        'activo'

    ];

    protected $casts = [
        'beneficios' => 'array',
        'validado'   => 'boolean',
        'activo'     => 'boolean',
    ];
}
