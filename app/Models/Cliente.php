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
        'tipo', 
        'nivel_educativo', 
        'name', 
        'contacto', 
        'email', 
        'telefono', 
        'tel_oficina', 
        'extension',
        'direccion',
        'beneficios_adicionales',
        'cp',              
        'municipio',      
        'colonia',        
        'calle_num', 
        'latitud',
        'longitud',
        'estado_id', 
        'moneda_id', 
        'condiciones_pago', 
        'rfc', 
        'regimen_fiscal', 
        'fiscal', 
        'user_id',
        'status',
        'foto_plantel',
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

    /**
     * Mutador nativo para asegurar que la ruta de la foto se guarde 
     * en minúsculas y se salte cualquier formato global del Trait.
     */
    protected function fotoPlantel(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: function ($value) {
                // Forzamos a minúsculas y guardamos directo en el array interno 
                // saltándonos cualquier evento o Trait intermedio.
                $this->attributes['foto_plantel'] = strtolower($value);
                return strtolower($value);
            }
        );
    }
}