<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobranza extends Model
{
    protected $table = 'cobranzas';
    
    protected $fillable = [
        'cliente_id',
        'metodo_pago',
        'responsable',
        'telefono',
        'correo'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}