<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsosCfdi extends Model
{
    use HasFactory;

    // Definimos explícitamente el nombre de la tabla por la convención de mayúsculas/minúsculas
    protected $table = 'usos_cfdi';

    /**
     * Atributos asignables de forma masiva (Mass Assignment).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'c_UsoCFDI',
        'descripcion',
        'persona_fisica',
        'persona_moral',
        'regimen_fiscal_receptor',
    ];

    /**
     * Casting de atributos.
     * * Esto transforma automáticamente las columnas al tipo de dato nativo de PHP 
     * al consultar o guardar en la base de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'persona_fisica' => 'boolean',
        'persona_moral'  => 'boolean',
        
        // Si decides guardar los regímenes como un array/JSON en lugar de texto plano con comas,
        // puedes descomentar la siguiente línea para que Laravel lo maneje automáticamente:
        // 'regimen_fiscal_receptor' => 'array', 
    ];
}