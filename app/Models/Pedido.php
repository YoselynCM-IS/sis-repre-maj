<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 
use Carbon\Carbon;
use App\Traits\FormatsAttributes;

class Pedido extends Model
{
    use HasFactory, FormatsAttributes; 

    protected $table = 'pedidos'; 

    /**
     * Atributos asignables en masa.
     * Se incluyen los campos de logística avanzada, receptor e identidad fiscal.
     */
    protected $fillable = [
        'numero_referencia',
        'user_id',
        'cliente_id',
        'tipo_pedido',
        'prioridad',
        'receptor_id',
        'receiver_type',
        // Datos del Receptor (Snapshot en el pedido)
        'receiver_nombre',
        'receiver_rfc',
        'receiver_regimen_fiscal',
        'receiver_telefono',
        'receiver_correo',
        // Dirección desglosada
        'delivery_cp',
        'delivery_municipio',
        'delivery_colonia',
        'delivery_calle_num',
        'delivery_address',
        // Logística
        'delivery_option',
        'paqueteria_nombre',
        'commentary_delivery_option',
        'comentarios_logistica',
        'comments',
        'status',
        'factura_path'
    ];
    
    /**
     * Atributos virtuales que se adjuntan automáticamente al JSON.
     */
    protected $appends = ['display_id', 'total_unidades', 'total_costo', 'factura_url']; 

    /**
     * Accesor para el ID visual del pedido.
     */
    protected function displayId(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['numero_referencia'] ?? 'PED-'.$attributes['id'],
        );
    }

    /**
     * Calcula el total de libros en el pedido.
     */
    protected function totalUnidades(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detalles()->sum('cantidad'),
        );
    }

    /**
     * Calcula la inversión total del pedido.
     */
    protected function totalCosto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detalles()->sum('costo_total'),
        );
    }

    /**
     * Genera la URL pública de la factura si existe.
     */
    protected function facturaUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->factura_path ? asset('storage/' . $this->factura_path) : null,
        );
    }


    /**
     * Relación con el desglose de materiales.
     */
    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }

    /**
     * Relación con el Plantel o Distribuidor principal.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con el receptor seleccionado de la agenda.
     */
    public function receptor()
    {
        return $this->belongsTo(PedidoReceptor::class, 'receptor_id');
    }

    /**
     * Relación con el representante (dueño del registro).
     */
    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el usuario (quien creo el pedido).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el Historial de Auditoría (Logs).
     * Esta relación es la que permite ver los motivos de cambio y comentarios de edición.
     */
    public function logs()
    {
        return $this->hasMany(PedidoLog::class, 'pedido_id');
    }

    public function guias()
    {
        return $this->hasMany(Guia::class, 'pedido_id');
    }

    public function historialStatus()
    {
        // Apunta a la nueva tabla 'status' ordenada desde el cambio más reciente
        return $this->hasMany(related: 'App\Models\Status', foreignKey: 'pedido_id')->orderBy('id', 'desc');
    }
}