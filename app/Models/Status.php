<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    // 1. Especificar el nombre exacto de la tabla en la base de datos
    protected $table = 'status';

    // 2. Definir los atributos que se pueden asignar de forma masiva
    protected $fillable = [
        'user_id',
        'pedido_id',
        'status',
        'comentarios',
    ];

    /**
     * Obtiene el usuario (representante) que realizó esta actualización de estado.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtiene el pedido al cual pertenece este registro de estado.
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}