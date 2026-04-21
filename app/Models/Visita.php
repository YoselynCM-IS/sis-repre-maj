<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\FormatsAttributes;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Estado;

class Visita extends Model
{
    use HasFactory, FormatsAttributes;

    protected $table = 'visitas';

  

    protected $fillable = [
        'user_id',
        'cliente_id', 
        'nombre_plantel',
        'rfc_plantel',
        'nivel_educativo_plantel',
        'direccion_plantel',
        'estado_id',
        'latitud',
        'longitud',
        'telefono_plantel',
        'email_plantel',
        'director_plantel',
        'fecha',
        'persona_entrevistada',
        'cargo',
        'libros_interes',
        'material_entregado',
        'material_cantidad',
        'comentarios',
        'resultado_visita',
        'proxima_accion',
        'proxima_visita_estimada',
        'es_primera_visita',
    ];

  
    protected $casts = [
        'fecha' => 'date',
        'proxima_visita_estimada' => 'date',
        'material_entregado' => 'boolean',
        'es_primera_visita' => 'boolean',
        'libros_interes' => 'array',
    ];

 
    /**
     * Buscador avanzado por nombre de plantel o persona entrevistada.
     */
    public function scopeSearch(Builder $query, $term)
    {
        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('nombre_plantel', 'like', "%{$term}%")
                  ->orWhere('persona_entrevistada', 'like', "%{$term}%")
                  ->orWhereHas('cliente', function($sub) use ($term) {
                      $sub->where('name', 'like', "%{$term}%");
                  });
            });
        }
    }

    /**
     * Filtrado por periodo de tiempo.
     */
    public function scopeByDateRange(Builder $query, $from, $to)
    {
        if ($from) {
            $query->whereDate('fecha', '>=', $from);
        }
        if ($to) {
            $query->whereDate('fecha', '<=', $to);
        }
    }

    /**
     * Filtrado por resultado de la visita (seguimiento, compra, rechazo).
     */
    public function scopeByResultado(Builder $query, $resultado)
    {
        if ($resultado) {
            $query->where('resultado_visita', $resultado);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Vínculo con el Cliente (Plantel).
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Vínculo con el Representante / Dueño (User).
     */
    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Vínculo con el Estado geográfico para geolocalización.
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    /**
     * Relación con el usuario (quien creo el pedido).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
{
    return $this->hasMany(VisitaLog::class, 'visita_id');
}
}