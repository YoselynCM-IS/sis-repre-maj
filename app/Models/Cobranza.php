<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobranza extends Model
{
    protected $table = 'cobranzas';
    
    protected $fillable = [
        'cliente_id',
        'metodo_pago',
        'tipo_pago',
        'responsable',
        'telefono',
        'correo',
        'rfc',
        'direccion',
        'regimen_fiscal_id', 
        'uso_cfdi_id'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Obtiene el régimen fiscal asociado a esta información de cobranza.
     */
    public function regimenFiscal()
    {
        return $this->belongsTo(RegimenFiscal::class, 'regimen_fiscal_id');
    }
}