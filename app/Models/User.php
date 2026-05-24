<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\FormatsAttributes;
use App\Models\Delegate;
use App\Models\Estado;
use App\Models\Cliente;
use App\Models\Visita;
use App\Models\Gasto;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, FormatsAttributes;

  
    public function findForArrays(array $credentials)
    {
        if (isset($credentials['username'])) {
            return $this->where('name', $credentials['username'])->first();
        }
        return parent::findForArrays($credentials);
    }
    
 
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        // --- Perfil e Identidad ---
        'rfc',
        'phone',
        'personal_phone',
        'position',
        'employee_id',
        // --- Ubicación Geográfica ---
        'state_id',
        'city',
        'address',
        // --- Herramientas de Trabajo (Activos) ---
        'car_plates',
        'tag_number',
        'insurance_policy',
        'phone_model',
        'tablet_model',
        'computer_model',
        'business_card',
    ];

  
    protected $hidden = [
        'password',
        'remember_token',
    ];

  
    protected $casts = [
        'email_verified_at' => 'datetime',
        'state_id' => 'integer',
    ];
    

    public function getEffectiveId()
    {
        if ($this->position === 'Delegado Autorizado') {
            $delegation = Delegate::where('email', $this->email)->first();
            
            return $delegation ? $delegation->user_id : $this->id;
        }
        
        return $this->id;
    }

    /**
     * RELACIÓN: Un representante puede autorizar a muchos delegados.
     */
    public function delegates()
    {
        return $this->hasMany(Delegate::class, 'representative_id');
    }

    /**
     * RELACIÓN: Un usuario pertenece a un Estado geográfico.
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'state_id');
    }

    /**
     * RELACIÓN: Un representante gestiona múltiples clientes (planteles oficiales).
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'user_id');
    }

    /**
     * RELACIÓN: Un representante registra sus actividades en la bitácora de visitas.
     */
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'user_id');
    }

    /**
     * RELACIÓN: Un representante registra sus gastos operativos.
     */
    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'user_id');
    }
}