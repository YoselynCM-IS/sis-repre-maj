<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Visita;
use App\Models\Cliente;
use App\Models\VisitaLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VisitaController extends Controller
{
    /**
     * Listado de visitas con aislamiento de seguridad por representante.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }

            // 1. Definimos el Query base
            $query = Visita::query();

            // 2. Lógica de visibilidad por Rol
            // Si NO es representante (asumiendo que los demás ven todo), no filtramos por user_id.
            // Si ES representante, aplicamos el filtro de propiedad.
            if ($user->role === 'representante') {
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
                $query->where('user_id', $ownerId);
            }

            // Definimos las relaciones base
            $relations = ['estado', 'cliente', 'user'];

            // Si el frontend solicita logs (para la tabla de auditoría en el detalle)
            if ($request->has('include_logs')) {
                $relations[] = 'logs.user';
            }

            // Filtrado técnico por ID de Cliente (Prioridad para la cadena de historial)
            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            } else {
                // Si es la lista general, mostrar solo cabeceras (primeras visitas)
                if (!$request->has('full_history')) {
                    $query->where('es_primera_visita', 1);
                }
            }

            // Filtros de búsqueda general
            if ($request->filled('search') && !$request->filled('cliente_id')) {
                $term = $request->search;
                $query->where(function($q) use ($term) {
                    $q->where('nombre_plantel', 'like', "%{$term}%")
                      ->orWhere('persona_entrevistada', 'like', "%{$term}%")
                      ->orWhereHas('cliente', function($c) use ($term) {
                          $c->where('name', 'like', "%{$term}%");
                      });
                });
            }

            // Filtros adicionales
            if ($request->filled('desde')) $query->whereDate('fecha', '>=', $request->desde);
            if ($request->filled('hasta')) $query->whereDate('fecha', '<=', $request->hasta);
            if ($request->filled('resultado')) $query->where('resultado_visita', $request->resultado);
            if ($request->filled('estado_id')) $query->where('estado_id', $request->estado_id);

            $visitas = $query->with($relations)
                ->orderBy('id', 'desc') 
                ->paginate(15);

            return response()->json($visitas);

        } catch (\Exception $e) {
            Log::error("Error en VisitaController@index: " . $e->getMessage());
            return response()->json(['message' => 'Error al cargar el historial de bitácoras.'], 500);
        }
    }

    /**
     * Registro de Primera Visita (Prospectación Inicial).
     */
      public function storePrimeraVisita(Request $request)
    {
        $request->validate([
            'plantel.name'      => 'required|string|max:100',
            'plantel.rfc'       => 'required|string|max:50',
            'plantel.niveles'   => 'required|array|min:1',
            'plantel.direccion' => 'required|string',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.telefono'  => 'required|string',
            'plantel.email'     => 'required|email',
            'plantel.director'  => 'required|string',
            'visita.fecha'                => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.cargo'                => 'required|string',
            'visita.libros_interes'       => 'required|array',
            'visita.resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
        ]);

        try {
            $rfc   = strtoupper($request->input('plantel.rfc'));
            $name  = strtoupper($request->input('plantel.name'));
            $email = strtolower($request->input('plantel.email'));
            $phone = $request->input('plantel.telefono');

            // REGLA DE ORO: Validación de Integridad Total
            $duplicado = Cliente::where('rfc', $rfc)
                ->orWhere('name', $name)
                ->orWhere('email', $email)
                ->orWhere('telefono', $phone)
                ->first();

            if ($duplicado) {
                $campo = 'dato';
                if ($duplicado->rfc === $rfc) $campo = 'RFC';
                elseif ($duplicado->name === $name) $campo = 'NOMBRE';
                elseif ($duplicado->email === $email) $campo = 'CORREO';
                elseif ($duplicado->telefono === $phone) $campo = 'TELÉFONO';

                return response()->json([
                    'message' => "ACCIÓN BLOQUEADA: El {$campo} ya existe bajo el registro de '{$duplicado->name}'."
                ], 422);
            }

            return DB::transaction(function () use ($request, $rfc, $name, $email, $phone) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                $cliente = Cliente::create([
                    'referencia_id'   => 0,
                    'user_id'         => $ownerId,
                    'tipo'            => ($request->input('visita.resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                    'name'            => $name,
                    'rfc'             => $rfc,
                    'nivel_educativo' => implode(', ', $request->input('plantel.niveles')),
                    'contacto'        => strtoupper($request->input('plantel.director')),
                    'telefono'        => $phone,
                    'email'           => $email,
                    'direccion'       => strtoupper($request->input('plantel.direccion')),
                    'estado_id'       => $request->input('plantel.estado_id'),
                    'latitud'         => $request->input('plantel.latitud'),
                    'longitud'        => $request->input('plantel.longitud'),
                    'status'          => 'activo'
                ]);

                // GUARDAR CLIENTE EN LA OTRA BASE DE DATOS
                $id = \DB::connection('mysql_inventario')->table('clientes')
                    ->insertGetId([
                        'user_id'         => 0,
                        'tipo'            => ($request->input('visita.resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                        'name'            => $name,
                        'rfc'             => $rfc,
                        'contacto'        => strtoupper($request->input('plantel.director')),
                        'telefono'        => $phone,
                        'email'           => $email,
                        'direccion'       => strtoupper($request->input('plantel.direccion')), 
                        'estado_id'       => $request->input('plantel.estado_id'),
                        'created_at'      => Carbon::now(),
                        'updated_at'      => Carbon::now()
                    ]);
                
                $cliente->update(['referencia_id' => $id]);
                // FIN GUARDAR CLIENTE EN LA OTRA BASE DE DATOS

                $visita = Visita::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $cliente->id,
                    'nombre_plantel'          => $cliente->name,
                    'rfc_plantel'             => $cliente->rfc,
                    'nivel_educativo_plantel' => $cliente->nivel_educativo,
                    'direccion_plantel'       => $cliente->direccion,
                    'estado_id'               => $cliente->estado_id,
                    'latitud'                 => $cliente->latitud,
                    'longitud'                => $cliente->longitud,
                    'telefono_plantel'        => $cliente->telefono,
                    'email_plantel'           => $cliente->email,
                    'director_plantel'        => $cliente->contacto,
                    'fecha'                   => $request->input('visita.fecha'),
                    'persona_entrevistada'    => strtoupper($request->input('visita.persona_entrevistada')),
                    'cargo'                   => strtoupper($request->input('visita.cargo')),
                    'libros_interes'          => $request->input('visita.libros_interes'),
                    'comentarios'             => strtoupper($request->input('visita.comentarios')),
                    'resultado_visita'        => $request->input('visita.resultado_visita'),
                    'proxima_visita_estimada' => $request->input('visita.proxima_visita'),
                    'proxima_accion'          => $request->input('visita.proxima_accion') ?? 'visita',
                    'es_primera_visita'       => true,
                ]);

                return response()->json(['message' => "Registro exitoso.", 'visita_id' => $visita->id], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo en storePrimeraVisita: " . $e->getMessage());
            return response()->json(['message' => 'Error técnico al procesar el alta.'], 500);
        }
    }


    /**
     * Registro de Visita Subsecuente (Seguimiento).
     */
    public function storeSeguimiento(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'           => 'required|exists:clientes,id',
            'fecha'                => 'required|date',
            'persona_entrevistada' => 'required|string',
            'cargo'                => 'required|string',
            'libros_interes'       => 'required', 
            'resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                $cliente = Cliente::findOrFail($validated['cliente_id']);

                $visita = Visita::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $cliente->id,
                    'nombre_plantel'          => $cliente->name,
                    'rfc_plantel'             => $cliente->rfc,
                    'nivel_educativo_plantel' => $cliente->nivel_educativo,
                    'direccion_plantel'       => $cliente->direccion,
                    'estado_id'               => $cliente->estado_id,
                    'fecha'                   => $validated['fecha'],
                    'persona_entrevistada'    => strtoupper($validated['persona_entrevistada']),
                    'cargo'                   => strtoupper($validated['cargo']),
                    'libros_interes'          => is_string($request->libros_interes) ? json_decode($request->libros_interes, true) : $request->libros_interes,
                    'comentarios'             => strtoupper($request->comentarios),
                    'resultado_visita'        => $validated['resultado_visita'],
                    'proxima_visita_estimada' => $request->proxima_visita,
                    'proxima_accion'          => $request->proxima_accion ?? 'visita',
                    'es_primera_visita'       => false,
                ]);

                if ($validated['resultado_visita'] === 'compra') {
                    $cliente->update(['tipo' => 'CLIENTE']);
                    \DB::connection('mysql_inventario')->table('clientes')
                        ->where('id', $cliente->referencia_id)->update(['tipo' => 'CLIENTE']);
                } elseif ($validated['resultado_visita'] === 'rechazo') {
                    $cliente->update(['status' => 'inactivo']);
                    \DB::connection('mysql_inventario')->table('clientes')
                        ->where('id', $cliente->referencia_id)->update(['status' => 'inactivo']);
                }

                return response()->json(['message' => 'Seguimiento registrado correctamente.'], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo en storeSeguimiento: " . $e->getMessage());
            return response()->json(['message' => 'Error técnico al procesar el seguimiento.'], 500);
        }
    }

    /**
     * Detalle de una bitácora con logs de auditoría incluidos.
     * CORREGIDO: Se añade try-catch y verificación de usuario para evitar 500.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Sesión expirada.'], 401);
            }

            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $visita = Visita::where('id', $id)
                ->where('user_id', $ownerId)
                ->with(['cliente', 'estado', 'logs.user'])
                ->first();

            if (!$visita) {
                return response()->json(['message' => 'Bitácora no encontrada o sin permisos de acceso.'], 404);
            }

            return response()->json($visita);
        } catch (\Exception $e) {
            Log::error("Error en VisitaController@show para ID {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error interno al recuperar el detalle.',
                'debug' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Actualización de Visita con registro de auditoría.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'No autorizado.'], 401);
            }

            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $visita = Visita::where('id', $id)->where('user_id', $ownerId)->firstOrFail();

            // REGLA: Solo una modificación permitida para mantener integridad académica
            if ($visita->modificaciones_realizadas >= 1) {
                return response()->json(['message' => 'Esta intervención ya cuenta con un ajuste previo y está bloqueada.'], 403);
            }

            $validated = $request->validate([
                'persona_entrevistada' => 'required|string|max:255',
                'cargo'                => 'required|string|max:255',
                'comentarios'          => 'required|string|min:20',
                'resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
                'motivo_cambio'        => 'required|string|min:10',
                'plantel'              => 'required|array',
                'libros_interes'       => 'required|array'
            ]);

            return DB::transaction(function () use ($visita, $request) {
                // 1. Guardar log ANTES del cambio
                VisitaLog::create([
                    'visita_id' => $visita->id,
                    'user_id'   => Auth::id(),
                    'snapshot_anterior' => $visita->toArray(),
                    'motivo_cambio'     => strtoupper($request->motivo_cambio)
                ]);

                // 2. Actualización de Identidad (Si es primera visita)
                if ($visita->es_primera_visita) {
                    $plantel = $request->plantel;
                    $visita->nombre_plantel = strtoupper($plantel['name']);
                    $visita->rfc_plantel = strtoupper($plantel['rfc']);
                    $visita->direccion_plantel = strtoupper($plantel['direccion']);
                    $visita->estado_id = $plantel['estado_id'];
                    $visita->nivel_educativo_plantel = is_array($plantel['niveles']) ? implode(', ', $plantel['niveles']) : $plantel['niveles'];
                    $visita->latitud = $plantel['latitud'];
                    $visita->longitud = $plantel['longitud'];
                    $visita->telefono_plantel = $plantel['telefono'];
                    $visita->email_plantel = strtolower($plantel['email']);
                    $visita->director_plantel = strtoupper($plantel['director']);
                    
                    // Sincronización con el Cliente Maestro
                    if ($visita->cliente_id) {
                        $cliente = Cliente::where('id', $visita->cliente_id)->first();
                        $cliente->update([
                            'tipo' => ($request->input('resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                            'name' => $visita->nombre_plantel,
                            'rfc' => $visita->rfc_plantel,
                            'contacto' => $visita->director_plantel,
                            'telefono' => $visita->telefono_plantel,
                            'email' => $visita->email_plantel,
                            'direccion' => $visita->direccion_plantel,
                            'estado_id' => $request->input('plantel.estado_id'),
                            'status' => ($request->input('resultado_visita') === 'rechazo') ? 'inactivo' : 'activo'
                        ]);
                    }

                    // // MODIFICAR CLIENTE EN ME
                    \DB::connection('mysql_inventario')->table('clientes')
                        ->where('id', $cliente->referencia_id)->update([
                            'tipo'            => ($request->input('resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                            'name'            => $visita->nombre_plantel,
                            'rfc'             => $visita->rfc_plantel,
                            'contacto'        => $visita->director_plantel,
                            'telefono'        => $visita->telefono_plantel,
                            'email'           => $visita->email_plantel,
                            'direccion'       => $visita->direccion_plantel, 
                            'estado_id'       => $request->input('plantel.estado_id'),
                            'status'          => ($request->input('resultado_visita') === 'rechazo') ? 'inactivo' : 'activo',
                            'updated_at'      => Carbon::now()
                        ]);
                    // // FIN MODIFICAR CLIENTE ME
                }

                // 3. Datos de la Intervención
                $visita->fecha = $request->fecha ?? $visita->fecha;
                $visita->persona_entrevistada = strtoupper($request->persona_entrevistada);
                $visita->cargo = strtoupper($request->cargo);
                $visita->comentarios = strtoupper($request->comentarios);
                $visita->resultado_visita = $request->resultado_visita;
                $visita->libros_interes = $request->libros_interes; 
                $visita->proxima_visita_estimada = $request->proxima_visita;
                
                // Incrementar contador de seguridad
                $visita->modificaciones_realizadas += 1;

                $visita->save();

                return response()->json(['message' => 'Expediente actualizado y registrado en bitácora de auditoría.']);
            });
        } catch (\Exception $e) {
            Log::error("Error actualizando visita: " . $e->getMessage());
            return response()->json(['message' => 'Error al sincronizar cambios.'], 500);
        }
    }
}