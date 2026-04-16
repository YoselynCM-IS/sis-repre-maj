<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;

class Cliente extends Model
{
    use HasFactory, FormatsAttributes;

    protected $table = 'clientes'; 

    protected $fillable = [
        'referencia_id',
        'user_id',
        'tipo', 
        'nivel_educativo', 
        'name', 
        'contacto', 
        'email', 
        'telefono', 
        'tel_oficina', 
        'direccion', 
        'latitud',
        'longitud',
        'estado_id', 
        'moneda_id', 
        'condiciones_pago', 
        'rfc', 
        'regimen_fiscal', 
        'cp',             
        'municipio',      
        'colonia',        
        'calle_num',      
        'fiscal', 
        'status'
    ];

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Un cliente tiene muchas visitas.
     * Esto permite hacer $cliente->visitas para ver todo el historial de seguimiento.
     */
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'cliente_id');
    }

    /**
     * Relación con el Estado (Geográfico).
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    /**
     * Scope para filtrar por tipo de cliente.
     */
    public function scopeProspectos($query)
    {
        return $query->where('tipo', 'PROSPECTO');
    }

    public function scopeClientesActivos($query)
    {
        return $query->where('tipo', 'CLIENTE')->where('status', 'activo');
    }
}