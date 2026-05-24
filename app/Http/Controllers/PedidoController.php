<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente; 
use App\Models\Pedido; 
use App\Models\PedidoDetalle; 
use App\Models\PedidoReceptor;
use App\Models\Libro;
use App\Models\PedidoLog;
use App\Models\CodigoPostal;
use App\Models\Delegate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    /**
     * Proxy para Dipomex.
     */
    public function proxyDipomex(Request $request)
    {
        $cp = $request->query('cp');

        if (!$cp || strlen($cp) !== 5) {
            return response()->json(['error' => true, 'message' => 'Código Postal inválido'], 400);
        }

        try {
            // Buscamos todos los asentamientos con ese CP
            $resultados = CodigoPostal::where('d_codigo', $cp)->get();

            if ($resultados->isEmpty()) {
                return response()->json(['error' => true, 'message' => 'No encontrado'], 404);
            }

            // Tomamos el primer registro para obtener Estado y Municipio (son iguales para un mismo CP)
            $primerRegistro = $resultados->first();

            // Extraemos todas las colonias del set de resultados
            // Usamos 'd_asenta' que es el nombre de tu columna para la colonia
            $colonias = $resultados->pluck('d_asenta')->toArray();

            // Construimos la respuesta EXACTA que espera tu Vue.js
            return response()->json([
                'codigo_postal' => [
                    'estado'    => $primerRegistro->d_estado,
                    'municipio' => $primerRegistro->D_mnpio, // Nota: Usé D_mnpio como pusiste en tu tabla
                    'colonias'  => $colonias
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error Dipomex: " . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Error al conectar con el servicio postal'], 500);
        }
    }

    /**
     * Valida la unicidad global de un receptor en tiempo real.
     */
    public function checkReceiverIntegrity(Request $request)
    {
        $rfc = $request->query('rfc');
        $correo = $request->query('correo');
        $telefono = $request->query('telefono');
        $nombre = $request->query('nombre');

        $user = Auth::user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        $query = PedidoReceptor::query();

        if ($rfc) {
            $query->where('rfc', strtoupper(trim($rfc)));
        } elseif ($correo) {
            $query->where('correo', strtolower(trim($correo)));
        } elseif ($telefono) {
            $query->where('telefono', trim($telefono));
        } elseif ($nombre) {
            $query->where('nombre', strtoupper(trim($nombre)));
        } else {
            return response()->json(['message' => 'Faltan criterios de búsqueda'], 400);
        }

        $existente = $query->first();

        if (!$existente) {
            return response()->json(['status' => 'success', 'available' => true]);
        }

        $isPrivate = ($existente->user_id !== $ownerId);

        return response()->json([
            'status' => 'conflict',
            'available' => false,
            'is_private' => $isPrivate,
            'nombre' => $existente->nombre,
            'message' => $isPrivate 
                ? 'ESTE DATO YA PERTENECE A OTRO REPRESENTANTE Y NO ES ACCESIBLE PARA TI.' 
                : 'YA TIENES ESTE REGISTRO EN TU AGENDA DE MIS RECEPTORES.'
        ]);
    }

    /**
     * Listado de pedidos.
     */
    public function index(Request $request)
    {
        try {
            // $user = $request->user();
            // $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
            if(auth()->user()->role == 'representante'){
                $user = auth()->user();

                // 1. Obtener los IDs de los usuarios promotores asignados a este representante
                $promotoresIds = Delegate::where('representative_id', $user->id)
                                    ->pluck('user_id')
                                    ->toArray();

                // 2. Incluir el ID del propio representante en la lista de búsqueda
                $userIdsPermitidos = array_merge([$user->id], $promotoresIds);

                // 3. Filtrar los pedidos que pertenezcan al representante o a sus promotores
                $pedidos = Pedido::whereIn('user_id', $userIdsPermitidos)
                            ->with(['cliente', 'detalles.libro', 'user']) 
                            ->orderBy('created_at', 'desc')
                            ->paginate(15);
            } else {
                $pedidos = Pedido::where('user_id', auth()->user()->id)
                            ->with(['cliente', 'detalles.libro']) 
                            ->orderBy('created_at', 'desc')
                            ->paginate(15);
            }
            
            return response()->json($pedidos);
        } catch (\Exception $e) {
            Log::error("Error index pedidos: " . $e->getMessage());
            return response()->json(['message' => 'Error al listar pedidos.'], 500);
        }
    }

    /**
     * Detalle técnico de un pedido.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $pedido = Pedido::with(['cliente', 'detalles.libro', 'receptor', 'logs.user']) 
                        ->where('id', $id)
                        ->where('user_id', $ownerId)
                        ->firstOrFail();
                        
            return response()->json($pedido);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido no localizado.'], 404);
        } catch (\Exception $e) {
            Log::error("Error show pedido: " . $e->getMessage());
            return response()->json(['message' => 'Error interno.'], 500);
        }
    }

    /**
     * Obtiene la última empresa de paquetería sugerida por el usuario autenticado.
     */
    public function getUltimaPaqueteria(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['ultima_paqueteria' => null], 401);
            }

            // Buscar el último pedido del usuario donde se haya rellenado el campo de paquetería sugerida
            $ultimoPedido = Pedido::where('user_id', $user->id)
                ->whereNotNull('paqueteria_nombre')
                ->where('paqueteria_nombre', '!=', '')
                ->orderBy('id', 'desc')
                ->first();

            return response()->json([
                'ultima_paqueteria' => $ultimoPedido ? $ultimoPedido->paqueteria_nombre : null
            ]);
        } catch (\Exception $e) {
            return response()->json(['ultima_paqueteria' => null, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Registro de pedido con Doble Escritura y Validación de Unicidad Cuádruple.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'clientId' => 'required|exists:clientes,id',
            'prioridad' => 'required|in:baja,media,alta',
            'receiverType' => 'required|in:cliente,nuevo,existente',
            'receptor_id'  => 'nullable|required_if:receiverType,existente|exists:pedido_receptores,id',
            
            // Datos del Receptor
            'receiver.persona_recibe' => 'required_if:receiverType,nuevo|string|max:255',
            'receiver.rfc' => 'required_if:receiverType,nuevo|string|min:12|max:13',
            'receiver.regimen_fiscal' => 'nullable|string|required_if:receiverType,nuevo', 
            'receiver.telefono' => 'required_if:receiverType,nuevo|string',
            'receiver.correo' => 'required_if:receiverType,nuevo|email',
            'receiver.cp' => 'required_if:receiverType,nuevo|string|size:5',
            'receiver.estado' => 'required_if:receiverType,nuevo|string', 
            'receiver.municipio' => 'required_if:receiverType,nuevo|string',
            'receiver.colonia' => 'required_if:receiverType,nuevo|string',
            'receiver.calle_num' => 'required_if:receiverType,nuevo|string',
            
            // Logística
            'logistics.deliveryOption' => 'required|in:paqueteria,recoleccion,entrega',
            'logistics.paqueteria_nombre' => 'nullable|required_if:logistics.deliveryOption,paqueteria|string',
            'logistics.comentarios_logistica' => 'nullable|string|max:255',
            'comments' => 'nullable|string|max:1000',

            // Materiales
            'items' => 'required|array|min:1',
            'items.*.bookId' => 'required|exists:libros,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric',
            'items.*.sub_type' => 'required|string',      
            'items.*.tipo_material' => 'required|string', 
        ]);

        try {
            return DB::transaction(function () use ($validatedData, $request) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
                
                // VALIDACIÓN DE INTEGRIDAD GLOBAL
                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $validatedData['receiver'];
                    $rfcNorm      = strtoupper(trim($r['rfc']));
                    $nombreNorm   = strtoupper(trim($r['persona_recibe']));
                    $correoNorm   = strtolower(trim($r['correo']));
                    $telefonoNorm = trim($r['telefono']);

                    $duplicado = PedidoReceptor::where('rfc', $rfcNorm)
                        ->orWhere('nombre', $nombreNorm)
                        ->orWhere('correo', $correoNorm)
                        ->orWhere('telefono', $telefonoNorm)
                        ->first();

                    if ($duplicado) {
                        $campo = 'dato';
                        if ($duplicado->rfc === $rfcNorm) $campo = 'RFC';
                        elseif ($duplicado->nombre === $nombreNorm) $campo = 'NOMBRE';
                        elseif ($duplicado->correo === $correoNorm) $campo = 'CORREO';
                        elseif ($duplicado->telefono === $telefonoNorm) $campo = 'TELÉFONO';

                        $esPropio = ($duplicado->user_id === $ownerId);
                        $msjExtra = $esPropio ? "Ya tienes este registro en tu agenda." : "Este dato pertenece a otro representante.";

                        throw new \Exception("INTEGRIDAD: El {$campo} ingresado ya existe en el sistema. {$msjExtra}");
                    }
                }

                // Cálculos de Totales
                $totalQuantity = collect($validatedData['items'])->sum('quantity');
                $totalAmount = collect($validatedData['items'])->sum(function($item) {
                    return $item['quantity'] * ($item['price'] ?? 0);
                });

                // Lógica de Receptor y Dirección
                $regimenFull = $validatedData['receiver']['regimen_fiscal'] ?? null;
                $receptorId = $validatedData['receptor_id'] ?? null;
                $direccionFormateada = '';
                $receptor = null;

                $cliente = Cliente::findOrFail($validatedData['clientId']);
                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $validatedData['receiver'];
                    $direccionFormateada = "{$r['calle_num']}, COL. {$r['colonia']}, {$r['municipio']}, {$r['estado']}, CP {$r['cp']}";
                    
                    $receptor = PedidoReceptor::create([
                        'user_id'                 => $ownerId,
                        'cliente_id'              => $validatedData['clientId'],
                        'nombre'                  => strtoupper($r['persona_recibe']),
                        'rfc'                     => strtoupper($r['rfc']),
                        'receiver_regimen_fiscal' => strtoupper($regimenFull), 
                        'telefono'                => $r['telefono'],
                        'correo'                  => strtolower($r['correo']),
                        'direccion'               => strtoupper($direccionFormateada)
                    ]);
                    $receptorId = $receptor->id;
                } elseif ($validatedData['receiverType'] === 'existente') {
                    $receptor = PedidoReceptor::findOrFail($receptorId);
                    $direccionFormateada = $receptor->direccion;
                    $regimenFull = $receptor->receiver_regimen_fiscal;
                } else {
                    $direccionFormateada = $cliente->direccion;
                    $regimenFull = $cliente->regimen_fiscal;
                }

                $dbReceiverType = ($validatedData['receiverType'] === 'existente') ? 'nuevo' : $validatedData['receiverType'];

                // Escritura Local
                $pedido = Pedido::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $validatedData['clientId'],
                    'prioridad'               => $validatedData['prioridad'],
                    'tipo_pedido'             => $request->tipo_pedido ?? 'normal',
                    'receptor_id'             => $receptorId,
                    'receiver_type'           => $dbReceiverType,
                    'receiver_regimen_fiscal' => $regimenFull ? strtoupper($regimenFull) : null,
                    'delivery_option'         => $validatedData['logistics']['deliveryOption'] === 'entrega' ? 'none' : $validatedData['logistics']['deliveryOption'],
                    'paqueteria_nombre'       => strtoupper($request->input('logistics.paqueteria_nombre') ?? ''),
                    'commentary_delivery_option' => strtoupper($request->input('logistics.comentarios_logistica') ?? ''),
                    'delivery_address'        => strtoupper($direccionFormateada),
                    'comments'                => strtoupper($validatedData['comments'] ?? 'ORDEN PROCESADA'), 
                    'status'                  => 'PENDIENTE',
                    'total_quantity'          => $totalQuantity,
                    'total'                   => $totalAmount,
                    'estado'                  => 'proceso',
                    'actualizado_por'         => strtoupper($user->name),
                ]);
                $numero_referencia = 'REP-' . Carbon::now()->format('ymd') . '-' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT);
                $pedido->update(['numero_referencia' => $numero_referencia]);

                //  INICIA PARA GUARDAR PEDIDO EN ME
                // Escritura Inventario
                try {
                    $dbInventario = DB::connection('mysql_inventario');
                    $informacion1 = $this->text_to_html($receptorId, $cliente, $receptor, $validatedData, $request, strtoupper($validatedData['comments']));

                    $idInventario = $dbInventario->table('pedidos')->insertGetId([
                        'numero_referencia' => $numero_referencia,
                        'user_id'         => 0,
                        'cliente_id'      => $cliente->referencia_id,
                        'total_quantity'  => $totalQuantity,
                        'total'           => $totalAmount,
                        'total_solicitar' => 0,
                        'estado'          => 'proceso',
                        'informacion'     => $informacion1,
                        'actualizado_por' => strtoupper($user->name),
                        'created_at'      => now(),
                        'updated_at'      => now()
                    ]);
                } catch (\Exception $eEx) {
                    Log::error("Error insert cabecera inventario: " . $eEx->getMessage());
                    throw new \Exception("Fallo en sincronización de inventario.");
                }
                //  FIN PARA GUARDAR PEDIDO EN ME

                // Registro de ítems
                foreach ($validatedData['items'] as $item) {
                    $tipoPeticion = ($item['tipo_material'] === 'promocion') 
                        ? (str_contains(strtolower($item['sub_type']), 'demo') ? 'demo' : 'profesor') 
                        : 'alumno';

                    //  INICIA PARA GUARDAR DETALLES DE PEDIDO EN ME
                    $libro = Libro::find($item['bookId']);
                    $informacion2 = "
                        <div style='font-family: sans-serif; font-size: 0.9rem;'>
                            <ul style='list-style: none; padding-left: 0;'>
                                <li><strong>TIPO:</strong> " . $item['tipo_material'] . "</li>
                                <li><strong>FORMATO:</strong> " . $item['sub_type'] . "</li>
                            </ul>
                        </div>";
                    $dbInventario->table('peticiones')->insert([
                        'pedido_id'  => $idInventario,
                        'pack_id'    => null, 
                        'libro_id'   => $libro->referencia_id,
                        'tipo'       => null,
                        'informacion' => $informacion2,
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'] ?? 0,
                        'total'      => $item['quantity'] * ($item['price'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    //  FIN PARA GUARDAR DETALLES DE PEDIDO EN ME
                    
                    PedidoDetalle::create([
                        'pedido_id'       => $pedido->id,
                        'libro_id'        => $item['bookId'],
                        'tipo'            => $item['tipo_material'], 
                        'tipo_licencia'   => $item['sub_type'],
                        'cantidad'        => $item['quantity'],
                        'precio_unitario' => $item['price'] ?? 0,
                        'costo_total'     => $item['quantity'] * ($item['price'] ?? 0)
                    ]);
                }

                return response()->json(['message' => 'Pedido registrado con éxito.', 'order_id' => $pedido->numero_referencia], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo general store pedido: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // COLOCAR LA INFORMACION EN HTML
    public function text_to_html($receptorId, $cliente, $receptor, $validatedData, $request, $comentarios){
        // 1. Determinamos cuál es la fuente de datos
        if (is_null($receptorId)) {
            // Si no hay receptorId, usamos los datos del cliente
            $fuente = [
                'contacto' => $cliente->contacto,
                'rfc'      => $cliente->rfc,
                'regimen'  => $cliente->receiver_regimen_fiscal,
                'correo'   => $cliente->email,
                'telefono' => $cliente->telefono,
                'direccion'=> $cliente->direccion,
            ];
        } else {
            // Si hay receptorId, usamos los datos del receptor (debe estar cargado en $receptor)
            $fuente = [
                'contacto' => $receptor->nombre,
                'rfc'      => $receptor->rfc,
                'regimen'  => $receptor->receiver_regimen_fiscal,
                'correo'   => $receptor->correo,
                'telefono' => $receptor->telefono,
                'direccion'=> $receptor->direccion,
            ];
        }

        // 2. Construimos el bloque HTML
        return "<div style='font-family: sans-serif; font-size: 0.9rem;'>
                <h6><b>Creado por:</b> " . auth()->user()->full_name . "</h6>
                <h6><b>Comentarios del pedido:</b></h6>
                <p>" . $comentarios . "</p>
                <hr>
                <div class='row'>
                    <div class='col'>
                        <ul style='list-style: none; padding-left: 0;'>
                            <li><strong>Prioridad de atención:</strong> " . mb_strtoupper($validatedData['prioridad'], 'UTF-8') . "</li>
                            <li><strong>Método de envío:</strong> " . ($validatedData['logistics']['deliveryOption'] === 'entrega' ? 'CLIENTE RECOGE' : mb_strtoupper($validatedData['logistics']['deliveryOption'], 'UTF-8')) . "</li>
                            <li><strong>Paquetería sugerida:</strong> " . mb_strtoupper($request->input('logistics.paqueteria_nombre') ?? 'N/A', 'UTF-8') . "</li>
                            <li><strong>Comentarios para entrega:</strong> " . mb_strtoupper($request->input('logistics.comentarios_logistica') ?? 'SIN COMENTARIOS', 'UTF-8') . "</li>
                        </ul>
                    </div>
                    <div class='col'>
                        <h6><b>DATOS DE ENVÍO</b></h6>
                        <ul style='list-style: none; padding-left: 0;'>
                            <li><strong>Contacto:</strong> " . mb_strtoupper($fuente['contacto'], 'UTF-8') . "</li>
                            <li><strong>RFC:</strong> " . mb_strtoupper($fuente['rfc'], 'UTF-8') . "</li>
                            <li><strong>Régimen fiscal:</strong> " . mb_strtoupper($fuente['regimen'], 'UTF-8') . "</li>
                            <li><strong>Ccorreo electrónico:</strong> " . $fuente['correo'] . "</li>
                            <li><strong>Teléfono:</strong> " . $fuente['telefono'] . "</li>
                            <li><strong>Dirección de envío:</strong> " . mb_strtoupper($fuente['direccion'], 'UTF-8') . "</li>
                        </ul>
                    </div>
                </div>
            </div>";
    }

    /**
     * Actualización de pedido con Validación de Integridad Completa.
     */
   public function update(Request $request, $id)
    {
        $pedido = Pedido::with('detalles.libro')->findOrFail($id);

        if ($pedido->status !== 'PENDIENTE') {
            return response()->json(['message' => "No se puede modificar un pedido en estado {$pedido->status}"], 403);
        }

        $validatedData = $request->validate([
            'clientId' => 'required|exists:clientes,id',
            'prioridad' => 'required|in:baja,media,alta',
            'receiverType' => 'required|in:cliente,nuevo,existente',
            'receptor_id'  => 'nullable|required_if:receiverType,existente|exists:pedido_receptores,id',
            'items' => 'required|array|min:1',
            'motivo_cambio' => 'required|string|min:10',
            'comments' => 'nullable|string', 
            'logistics.comentarios_logistica' => 'nullable|string',
            'logistics.deliveryOption' => 'required|in:paqueteria,recoleccion,entrega',
        ]);

        try {
            return DB::transaction(function () use ($pedido, $request, $validatedData) {
                $user = Auth::user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                // Log de Auditoría (Antes del cambio)
                PedidoLog::create([
                    'pedido_id' => $pedido->id,
                    'user_id' => $user->id,
                    'snapshot_anterior' => [
                        'cabecera' => $pedido->makeHidden(['detalles'])->toArray(),
                        'detalles' => $pedido->detalles->toArray()
                    ],
                    'motivo_cambio' => strtoupper($request->motivo_cambio)
                ]);

                // VARIABLES DE IDENTIDAD Y ENVÍO (Mapeadas al esquema real)
                $receptorId = $request->receptor_id;
                $direccionFormateada = $pedido->delivery_address;
                $regimenFull = $pedido->receiver_regimen_fiscal;
                $receptor = null;

                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $request->input('receiver');
                    $rfcNorm = strtoupper(trim($r['rfc']));
                    
                    // Validar duplicado excluyendo el actual asignado
                    $duplicado = PedidoReceptor::where('rfc', $rfcNorm)->where('id', '!=', $pedido->receptor_id)->first();
                    if ($duplicado) throw new \Exception("INTEGRIDAD: El RFC ingresado ya existe en otro registro.");

                    $direccionFormateada = "{$r['calle_num']}, COL. {$r['colonia']}, {$r['municipio']}, {$r['estado']}, CP {$r['cp']}";
                    $regimenFull = $r['regimen_fiscal'] ?? $pedido->receiver_regimen_fiscal;
                    
                    $receptor = PedidoReceptor::updateOrCreate(
                        ['rfc' => $rfcNorm, 'user_id' => $ownerId],
                        [
                            'cliente_id' => $validatedData['clientId'],
                            'nombre' => strtoupper($r['persona_recibe']),
                            'receiver_regimen_fiscal' => strtoupper($regimenFull),
                            'telefono' => $r['telefono'],
                            'correo' => strtolower($r['correo']),
                            'direccion' => strtoupper($direccionFormateada)
                        ]
                    );
                    $receptorId = $receptor->id;

                } elseif ($validatedData['receiverType'] === 'existente') {
                    $receptor = PedidoReceptor::findOrFail($receptorId);
                    $direccionFormateada = $receptor->direccion;
                    $regimenFull = $receptor->receiver_regimen_fiscal;
                } else {
                    $cliente = Cliente::findOrFail($validatedData['clientId']);
                    $direccionFormateada = $cliente->direccion;
                    $regimenFull = $cliente->regimen_fiscal;
                }

                $totalQuantity = collect($validatedData['items'])->sum('quantity');
                $totalAmount = collect($validatedData['items'])->sum(fn($i) => $i['quantity'] * ($i['price'] ?? 0));

                // ACTUALIZACIÓN MAESTRA (Solo columnas existentes en el esquema proporcionado)
                $pedido->update([
                    'cliente_id'              => $validatedData['clientId'],
                    'prioridad'               => $validatedData['prioridad'],
                    'receptor_id'             => $receptorId,
                    'receiver_type'           => $validatedData['receiverType'] === 'existente' ? 'nuevo' : $validatedData['receiverType'],
                    'receiver_regimen_fiscal' => $regimenFull ? strtoupper($regimenFull) : $pedido->receiver_regimen_fiscal,
                    'delivery_address'        => strtoupper($direccionFormateada),
                    'delivery_option'         => $request->input('logistics.deliveryOption') === 'entrega' ? 'none' : $request->input('logistics.deliveryOption'),
                    'paqueteria_nombre'       => strtoupper($request->input('logistics.paqueteria_nombre') ?? ''),
                    'commentary_delivery_option' => strtoupper($request->input('logistics.comentarios_logistica') ?? ''),
                    'comments'                => strtoupper($request->input('comments', $pedido->comments)),
                    'total_quantity'          => $totalQuantity,
                    'total'                   => $totalAmount,
                    'actualizado_por'         => strtoupper($user->name),
                ]);

                // INICIAR ACTUALIZAR PEDIDO EN ME
                $dbInventario = DB::connection('mysql_inventario');
                $informacion1 = $this->text_to_html($receptorId, $cliente, $receptor, $validatedData, $request, strtoupper($request->input('comments', $pedido->comments)));

                $dbInventario->table('pedidos')->where('numero_referencia', $pedido->numero_referencia)->update([
                        'cliente_id'      => $cliente->referencia_id,
                        'total_quantity'  => $totalQuantity,
                        'total'           => $totalAmount,
                        'informacion'     => $informacion1,
                        'actualizado_por' => strtoupper($user->name),
                        'updated_at'      => now()
                    ]);
                // FIN ACTUALIZAR PEDIDO EN ME

                // Actualizar detalles de materiales
                $pedido->detalles()->delete();
                // OBTENER ID DE PEDIDO EN ME
                $pedido_me = $dbInventario->table('pedidos')->where('numero_referencia', $pedido->numero_referencia)->first();
                $dbInventario->table('peticiones')->where('pedido_id', $pedido_me->id)->update(['deleted_at' => now()]);
                foreach ($request->items as $item) {
                    PedidoDetalle::create([
                        'pedido_id'       => $pedido->id,
                        'libro_id'        => $item['bookId'],
                        'tipo'            => $item['tipo_material'],
                        'tipo_licencia'   => $item['sub_type'],
                        'cantidad'        => $item['quantity'],
                        'precio_unitario' => $item['price'] ?? 0,
                        'costo_total'     => $item['quantity'] * ($item['price'] ?? 0)
                    ]);

                    // INICIA PARA GUARDAR DETALLES DE PEDIDO EN ME
                    $libro = Libro::find($item['bookId']);
                    $informacion2 = "
                        <div class='info-logistica' style='font-family: Arial, sans-serif;'>
                            <p><strong>TIPO:</strong> " . $item['tipo_material'] . "</p>
                            <p><strong>FORMATO:</strong> " . $item['sub_type'] . "</p>
                        </div>";

                    $dbInventario->table('peticiones')->insert([
                        'pedido_id'  => $pedido_me->id,
                        'pack_id'    => null, 
                        'libro_id'   => $libro->referencia_id,
                        'tipo'       => null,
                        'informacion' => $informacion2,
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'] ?? 0,
                        'total'      => $item['quantity'] * ($item['price'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    //  FIN PARA GUARDAR DETALLES DE PEDIDO EN ME
                }

                return response()->json(['message' => 'Expediente actualizado correctamente.'], 200);
            });
        } catch (\Exception $e) {
            Log::error("Error en update pedido: " . $e->getMessage());
            return response()->json(['message' => 'Fallo al procesar: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Subida de factura.
     */
    public function uploadFactura(Request $request, $id)
    {
        $request->validate(['factura' => 'required|file|mimes:pdf|max:4096']);
        $pedido = Pedido::findOrFail($id);
        $path = $request->file('factura')->store('facturas/pedidos', 'public');
        $pedido->update(['factura_path' => $path]);
        return response()->json(['message' => 'Factura adjuntada con éxito', 'url' => $pedido->factura_url]);
    }
}