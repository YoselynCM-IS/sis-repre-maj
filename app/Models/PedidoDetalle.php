<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class PedidoDetalle extends Model
{
    use HasFactory, FormatsAttributes;
    use SoftDeletes; //Implementamos 

    protected $table = 'pedido_detalles';
    protected $dates = ['deleted_at']; //Registramos la nueva columna
    
    protected $fillable = [
        'pedido_id',
        'libro_id',
        'tipo', // <--- CRÍTICO: Añadir este campo
        'tipo_licencia',
        'cantidad',
        'precio_unitario', 
        'costo_total',     
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class); 
    }
}