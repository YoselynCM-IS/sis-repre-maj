<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Libro;
use App\Models\Estado;
use App\Models\Pais;
use App\Models\PedidoReceptor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Buscador de Instituciones (Clientes, Distribuidores y Prospectos).
     * Se utiliza tanto en Pedidos como en el módulo de Visitas.
     */
    public function searchClientes(Request $request)
    {   
        $query = $request->input('query');
        $includeProspectos = $request->boolean('include_prospectos');

        if (empty($query) || strlen($query) < 3) return response()->json([]);

        try {
            $user = Auth::user();
            // Identificamos al dueño de la cuenta (soporte para delegados)
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $builder = Cliente::select(
                    'id', 'name', 'tipo', 'direccion', 'contacto', 'telefono', 'email',
                    'rfc', 'regimen_fiscal', 'cp', 'municipio', 'colonia', 'calle_num', 'estado_id'
                )
                ->where('name', 'like', "%{$query}%")
                ->where('status', 'activo');

            if($user->role == 'promotor'){
                $builder->where('user_id', $ownerId); // <--- REGLA DE ORO: Filtro de propiedad
            }

            if (!$includeProspectos) {
                $builder->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR']);
            }

            return response()->json($builder->limit(10)->get());

        } catch (\Exception $e) {
            Log::error("Error en SearchController@searchClientes: " . $e->getMessage());
            return response()->json(['message' => 'Error al buscar instituciones'], 500);
        }
    }

    /**
     * Buscador de Materiales Educativos (Libros).
     * Permite filtrado dinámico por Serie y Estatus.
     */
    public function searchLibros(Request $request)
    {
        $query = $request->input('query');
        $serieId = $request->input('serie_id');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $builder = Libro::select('id', 'titulo', 'ISBN', 'editorial', 'type', 'serie_id')
                ->where('titulo', 'like', "%{$query}%")
                ->where('estado', 'activo');

            // Filtrado opcional por serie
            if ($serieId && $serieId !== 'otro') {
                $builder->where('serie_id', $serieId);
            }

            return response()->json($builder->limit(15)->get());

        } catch (\Exception $e) {
            Log::error("Error en SearchController@searchLibros: " . $e->getMessage());
            return response()->json(['message' => 'Error al buscar libros'], 500);
        }
    }

    /**
     * Buscador de Agenda de Receptores con Aislamiento de Privacidad.
     * REGLA: Un representante solo puede ver y buscar sus propios receptores.
     */
    public function searchReceptores(Request $request)
    {
        $query = $request->input('query');
        $user = Auth::user();
        
        // Soporte para delegados: Identificamos quién es el dueño efectivo de la cuenta
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            // Aislamiento: El filtro 'user_id' impide ver registros ajenos
            $receptores = PedidoReceptor::where('user_id', $ownerId)
                ->where(function($q) use ($query) {
                    $q->where('rfc', 'like', "%{$query}%")
                      ->orWhere('nombre', 'like', "%{$query}%")
                      ->orWhere('correo', 'like', "%{$query}%")
                      ->orWhere('telefono', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();

            return response()->json($receptores);
        } catch (\Exception $e) {
            Log::error("Error en SearchController@searchReceptores: " . $e->getMessage());
            return response()->json(['message' => 'Error en búsqueda de agenda'], 500);
        }
    }

    /**
     * Validación de Unicidad Global de Receptores (RFC, Correo, Teléfono, Nombre).
     * REGLA: Busca en toda la base de datos para prevenir duplicados globales y 
     * marca el registro como privado si pertenece a otro representante.
     */
  public function checkRfcUniqueness(Request $request)
    {
        $rfc = $request->query('rfc');
        $correo = $request->query('correo') ?? $request->query('email');
        $telefono = $request->query('telefono') ?? $request->query('phone');
        $name = $request->query('nombre') ?? $request->query('name');
        
        $user = Auth::user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        // 1. Intentar encontrar conflicto en la tabla maestra de Clientes/Prospectos
        $conflicto = null;
        $fuente = 'clientes';

        if ($rfc) $conflicto = Cliente::where('rfc', strtoupper($rfc))->first();
        elseif ($correo) $conflicto = Cliente::where('email', strtolower($correo))->first();
        elseif ($telefono) $conflicto = Cliente::where('telefono', $telefono)->first();
        elseif ($name) $conflicto = Cliente::where('name', strtoupper($name))->first();

        // 2. Si no hay conflicto en clientes, buscar en la agenda de Receptores
        if (!$conflicto) {
            $fuente = 'receptores';
            if ($rfc) $conflicto = PedidoReceptor::where('rfc', strtoupper($rfc))->first();
            elseif ($correo) $conflicto = PedidoReceptor::where('correo', strtolower($correo))->first();
            elseif ($telefono) $conflicto = PedidoReceptor::where('telefono', $telefono)->first();
            elseif ($name) $conflicto = PedidoReceptor::where('nombre', strtoupper($name))->first();
        }

        if (!$conflicto) {
            return response()->json(['status' => 'success', 'available' => true]);
        }

        // Determinar si el registro le pertenece al usuario actual
        $isPrivate = ($conflicto->user_id !== $ownerId);
        $nombreConflicto = $conflicto->name ?? $conflicto->nombre;

        return response()->json([
            'status' => 'conflict',
            'available' => false,
            'is_private' => $isPrivate,
            'id' => $conflicto->id,
            'nombre' => $nombreConflicto,
            'message' => $isPrivate 
                ? 'ESTE DATO PERTENECE A OTRO REPRESENTANTE Y NO ES ACCESIBLE PARA TI.' 
                : 'YA TIENES REGISTRADO ESTE DATO COMO "' . strtoupper($nombreConflicto) . '".'
        ]);
    }

    /**
     * Catálogos base para formularios (Niveles, Series y Estados).
     */
    public function getNiveles()
    {
        try {
            return response()->json(DB::table('niveles_educativos')->select('id', 'nombre')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getSeries()
    {
        try {
            return response()->json(DB::table('series')->select('id', 'serie as nombre', 'nivel_educativo_id')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getEstados()
    {
        try {
            return response()->json(Estado::orderBy('estado', 'asc')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getPaises()
    {
        try {
            return response()->json(Pais::orderBy('nombre', 'asc')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getEstadosByPais($pais_id)
    {
        // Trae los estados que pertenezcan al pais_id enviado
        $estados = Estado::where('pais_id', $pais_id)
            ->orderBy('estado', 'asc')
            ->get(['id', 'estado']);

        return response()->json($estados);
    }
}