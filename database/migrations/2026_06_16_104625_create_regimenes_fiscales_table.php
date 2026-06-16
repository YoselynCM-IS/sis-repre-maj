<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegimenFiscal extends Model
{
    use HasFactory;

    // Especificamos explícitamente el nombre de la tabla en español
    protected $table = 'regimenes_fiscales';

    // Atributos asignables de forma masiva
    protected $fillable = [
        'codigo',
        'descripcion',
    ];
}