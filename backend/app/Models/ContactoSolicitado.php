<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactoSolicitado extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'contactos_solicitados';

    protected $fillable = [
        'empresa_id',
        'persona_id',
        'estado',
        'notas_admin',
        'fecha_contacto',
        'fecha_entrevista',
        'fecha_resultado'
    ];

    protected $casts = [
        'fecha_contacto'   => 'datetime',
        'fecha_entrevista' => 'datetime',
        'fecha_resultado'  => 'datetime',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }
}
