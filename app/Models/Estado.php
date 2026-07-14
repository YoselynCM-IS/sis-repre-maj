<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class Estado extends Model
{
    protected $table = 'estados';
    protected $fillable = ['estado'];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }
}