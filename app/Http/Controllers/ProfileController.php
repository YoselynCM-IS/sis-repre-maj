<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\Delegate;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Carga el perfil con sus delegados asociados.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Cargamos los delegados y para cada uno incluimos sus datos de usuario de la tabla users
        $user->setRelation('delegates', Delegate::where('representative_id', $user->id)->with('user')->get());

        return response()->json($user);
    }

    /**
     * Actualiza la información personal.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $user->id,
            'rfc'            => 'nullable|string|max:13',
            'phone'          => 'nullable|string',
            'personal_phone' => 'nullable|string',
            'state_id'       => 'nullable|integer',
            'city'           => 'nullable|string',
            'address'        => 'nullable|string',
        ]);

        $user->update($validated);
        return response()->json(['message' => 'Perfil actualizado con éxito']);
    }

    /**
     * Actualiza el inventario de herramientas.
     */
    public function updateTools(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'car_plates'       => 'nullable|string',
            'tag_number'       => 'nullable|string',
            'insurance_policy' => 'nullable|string',
            'phone_model'      => 'nullable|string',
            'tablet_model'     => 'nullable|string',
            'computer_model'   => 'nullable|string',
            'business_card'    => 'nullable|string',
        ]);

        $user->update($validated);
        return response()->json(['message' => 'Inventario actualizado']);
    }

    /**
     * Gestión de contraseña.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Contraseña actualizada']);
    }

    /**
     * Registro de Delegado con regla de restricción para delegados existentes.
     */
    public function addDelegate(Request $request)
    {
        // --- REGLA DE SEGURIDAD: Solo Representantes pueden crear delegados ---
        if ($request->user()->position === 'Delegado Autorizado') {
            return response()->json([
                'message' => 'Acceso denegado. Un delegado no tiene permisos para autorizar a otros usuarios.'
            ], 403);
        }

        $validated = $request->validate([
            'username' => 'required|string|unique:users,name|max:255',
            'full_name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                // 1. Crear el usuario para el acceso (Login)
                $newUser = User::create([
                    'name'      => $validated['username'],
                    'full_name' => $validated['full_name'], // <-- SE CAMBIA: Ahora guarda el valor dinámico ingresado
                    'email'     => $validated['username'] . '@delegate.majestic',
                    'password'  => $validated['password'],
                    'position'  => 'Delegado Autorizado',
                ]);

                // 2. Vincular a la tabla de delegados bajo el ID del representante actual
                $delegate = Delegate::create([
                    'user_id'           => $newUser->id,             // Se corrige: ID del Promotor (nuevo usuario)
                    'representative_id' => $request->user()->id,     // Se agrega: ID del Representante que lo da de alta
                    'name'              => $newUser->name,
                    'email'             => $newUser->email,
                ]);

                return response()->json([
                    'message'  => 'Cuenta de delegado creada y vinculada',
                    'delegate' => $delegate
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al procesar el registro: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Revocación de acceso.
     */
    public function removeDelegate(Request $request, $id)
    {
        // Opcional: También podrías validar aquí que un delegado no pueda borrar a otros
        if ($request->user()->position === 'Delegado Autorizado') {
            return response()->json(['message' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        $delegate = $request->user()->delegates()->findOrFail($id);
        $delegate->delete();

        return response()->json(['message' => 'Acceso revocado']);
    }
}