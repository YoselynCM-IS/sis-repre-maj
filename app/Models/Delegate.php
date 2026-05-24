<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class Delegate extends Model
{
    use HasFactory, FormatsAttributes;

    protected $table = 'delegates';

    protected $fillable = [
        'user_id',
        'representative_id',
        'name',
        'email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}