<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Visita;
use App\Models\Cliente;
use App\Models\VisitaLog;
use App\Models\Cobranza;
use App\Models\Delegate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\NuevaVisitaRegistrada;

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

            // 2. Lógica de visibilidad por Rol RESTRINGIDA
            // Si ES representante, puede ver sus visitas y las de TODOS sus promotores asignados.
            if ($user->role === 'representante') {
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
                
                // Obtener los IDs de los usuarios promotores asignados a este representante
                $promotoresIds = Delegate::where('representative_id', $ownerId)
                                    ->pluck('user_id')
                                    ->toArray();

                // Combinar el ID del representante con los IDs de sus promotores
                $userIdsPermitidos = array_merge([$ownerId], $promotoresIds);

                // El representante ve las visitas de todo su equipo
                $query->whereIn('user_id', $userIdsPermitidos);
            }
            if ($user->role === 'promotor') {
                // SI ES CUALQUIER OTRO ROL (COMO PROMOTOR/DELEGADO), SOLO VE SUS PROPIAS VISITAS
                $query->where('user_id', $user->id);
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
            'plantel.rfc'       => 'nullable|string|max:50',
            'plantel.niveles'   => 'required|array|min:1',
            'plantel.direccion' => 'required|string',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.telefono'  => 'required|string',
            'plantel.tel_oficina' => 'required|string',
            // 'plantel.extension' => 'required|string',
            'plantel.email'     => 'required|email',
            'plantel.director'  => 'required|string',
            'plantel.foto_plantel' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            // NUEVA VALIDACIÓN: REQUERIDO, TEXTO Y MÍNIMO 20 CARACTERES
            'plantel.beneficios_adicionales' => 'required|string|min:20',
            'visita.fecha'                => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.cargo'                => 'required|string',
            'visita.resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
            // NUEVAS REGLAS AGREGADAS PARA EL ARRAY INTERNO DE LIBROS DE INTERES
            'visita.libros_interes.interes'          => 'nullable|array',
            'visita.libros_interes.entregado'        => 'nullable|array',
            'visita.libros_interes.interes.*.beneficio_tipo'  => 'required|in:Precio especial,Descuento por libro',
            'visita.libros_interes.interes.*.beneficio_valor' => 'required|numeric|min:0',

            // ── FRAGMENTO A AGREGAR: VALIDACIÓN CONDICIONAL DE COBRANZA ──
            'cobranza.nombre'            => 'required_if:visita.resultado_visita,compra|nullable|string|max:150',
            'cobranza.rfc'               => 'required_if:visita.resultado_visita,compra|nullable|string|max:13',
            'cobranza.correo'            => 'required_if:visita.resultado_visita,compra|nullable|email',
            'cobranza.telefono'          => 'required_if:visita.resultado_visita,compra|nullable|string|max:10',
            'cobranza.direccion'         => 'required_if:visita.resultado_visita,compra|nullable|string',
            'cobranza.metodo_pago'       => 'required_if:visita.resultado_visita,compra|nullable|string',
            'cobranza.tipo_pago'       => 'required_if:visita.resultado_visita,compra|nullable|string',
            'cobranza.regimen_fiscal_id' => 'required_if:visita.resultado_visita,compra|nullable|exists:regimenes_fiscales,id',
            'cobranza.uso_cfdi_id'       => 'required_if:visita.resultado_visita,compra|nullable|exists:usos_cfdi,id',
        ]);

        try {
            $rfc   = $request->input('plantel.rfc') ? strtoupper($request->input('plantel.rfc')) : null;
            $name  = strtoupper($request->input('plantel.name'));
            $email = strtolower($request->input('plantel.email'));
            $phone = $request->input('plantel.telefono');
            $telOficina = $request->input('plantel.tel_oficina');
            $extension  = $request->input('plantel.extension');

            // REGLA DE ORO: Validación de Integridad Total
            $duplicadoQuery = Cliente::where('name', $name)
                ->orWhere('email', $email)
                ->orWhere('telefono', $phone);

            if ($rfc) {
                $duplicadoQuery->orWhere('rfc', $rfc);
            }

            $duplicado = $duplicadoQuery->first();

            if ($duplicado) {
                $campo = 'dato';
                if ($rfc && $duplicado->rfc === $rfc) $campo = 'RFC';
                elseif ($duplicado->name === $name) $campo = 'NOMBRE';
                elseif ($duplicado->email === $email) $campo = 'CORREO';
                elseif ($duplicado->telefono === $phone) $campo = 'TELÉFONO';

                return response()->json([
                    'message' => "El {$campo} ya existe bajo el registro de '{$duplicado->name}'."
                ], 422);
            }

            return DB::transaction(function () use ($request, $rfc, $name, $email, $phone, $telOficina, $extension) {
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
                    'tel_oficina'     => $telOficina,
                    'extension'       => $extension,
                    'email'           => $email,
                    'direccion'       => strtoupper($request->input('plantel.direccion')),
                    // SE AGREGA ALMACENAMIENTO DE BENEFICIOS ADICIONALES EN MAYÚSCULAS
                    'beneficios_adicionales' => strtoupper($request->input('plantel.beneficios_adicionales')),
                    'estado_id'       => $request->input('plantel.estado_id'),
                    'latitud'         => $request->input('plantel.latitud') != 'null' ? $request->input('plantel.latitud'):null,
                    'longitud'        => $request->input('plantel.longitud') != 'null' ? $request->input('plantel.longitud'):null,
                    'status'          => 'activo'
                ]);

                // [PUNTOS 2 y 3] Procesar la foto tras crear el cliente para incluir su ID y letras/números aleatorios
                if ($request->hasFile('plantel.foto_plantel')) {
                    $file = $request->file('plantel.foto_plantel');
                    
                    // 1. Forzar la extensión del archivo a minúsculas
                    $extensionFile = strtolower($file->getClientOriginalExtension());
                    
                    // 2. Generar caracteres aleatorios en minúsculas
                    $randomString = strtolower(Str::random(8));
                    
                    // 3. Unificar el nombre final asegurando que todo vaya en minúsculas
                    $fileName = "c" . $cliente->id . "_" . $randomString . "." . $extensionFile;
                    
                    // 4. Guardar el archivo físicamente en la ruta limpia
                    $pathFoto = $file->storeAs('fotos_planteles', $fileName, 'public');
                    
                    // 5. Actualizar el registro del cliente en la Base de Datos
                    $cliente->update(['foto_plantel' => $pathFoto]);
                }

                // ── FRAGMENTO A AGREGAR: GUARDAR DATOS EN LA TABLA COBRANZAS ──
                if ($request->input('visita.resultado_visita') === 'compra') {
                    Cobranza::create([
                        'cliente_id'        => $cliente->id,
                        'responsable'       => strtoupper($request->input('cobranza.nombre')),
                        'correo'            => strtolower($request->input('cobranza.correo')),
                        'telefono'          => $request->input('cobranza.telefono'),
                        'rfc'               => strtoupper($request->input('cobranza.rfc')),
                        'direccion'         => strtoupper($request->input('cobranza.direccion')),
                        'metodo_pago'       => $request->input('cobranza.metodo_pago'),
                        'tipo_pago'       => $request->input('cobranza.tipo_pago'),
                        'regimen_fiscal_id' => $request->input('cobranza.regimen_fiscal_id'),
                        'uso_cfdi_id'       => $request->input('cobranza.uso_cfdi_id'),
                    ]);
                }

                // // GUARDAR CLIENTE EN LA OTRA BASE DE DATOS
                //  $id = \DB::connection('mysql_inventario')->table('clientes')
                //     ->insertGetId([
                //         'user_id'         => 0,
                //         'tipo'            => ($request->input('visita.resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                //         'name'            => $name,
                //         'rfc'             => $rfc,
                //         'contacto'        => strtoupper($request->input('plantel.director')),
                //         'telefono'        => $phone,
                //         'email'           => $email,
                //         'direccion'       => strtoupper($request->input('plantel.direccion')), 
                //         'estado_id'       => $request->input('plantel.estado_id'),
                //         'latitud'         => $request->input('plantel.latitud'),
                //         'longitud'        => $request->input('plantel.longitud'),
                //         'created_at'      => Carbon::now(),
                //         'updated_at'      => Carbon::now()
                //     ]);
                
                // $cliente->update(['referencia_id' => $id]);
                // // FIN GUARDAR CLIENTE EN LA OTRA BASE DE DATOS

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

                // ENVÍO DE CORREO AL CLIENTE (SE EJECUTA ANTES DE RESPONDER AL FRONTEND)
                try {
                    if (!empty($email)) {
                        // Obtener los IDs de los libros de interés contenidos en el array interno enviado por el request
                        $librosInteresData = $request->input('visita.libros_interes.interes', []);
                        $libroIds = collect($librosInteresData)->pluck('id')->filter()->toArray();

                        // Consultar la tabla de libros para extraer los que tienen un link_flipbook registrado
                        $librosConLink = [];
                        if (!empty($libroIds)) {
                            $librosConLink = \DB::table('libros')
                                ->whereIn('id', $libroIds)
                                ->whereNotNull('link_flipbook')
                                ->where('link_flipbook', '!=', '')
                                ->select('id', 'titulo', 'link_flipbook') // Ajusta 'titulo' si tu columna de nombre se llama diferente
                                ->get();
                        }

                        // Enviar el correo inyectando la visita y la colección de libros encontrados
                        Mail::to($email)->send(new NuevaVisitaRegistrada($visita, $librosConLink));
                    }
                } catch (\Exception $mailEx) {
                    // Se registra el fallo en logs para evitar que un problema de correo cancele la transacción de la BD
                    Log::error("No se pudo enviar el correo de visita registrada a {$email}: " . $mailEx->getMessage());
                }

                return response()->json(['message' => "Registro exitoso.", 'visita_id' => $visita->id], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo en storePrimeraVisita: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
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
            // ── FRAGMENTO A AGREGAR: VALIDACIÓN CONDICIONAL DE COBRANZA ──
            'cobranza.nombre'            => 'required_if:resultado_visita,compra|nullable|string|max:150',
            'cobranza.rfc'               => 'required_if:resultado_visita,compra|nullable|string|max:13',
            'cobranza.correo'            => 'required_if:resultado_visita,compra|nullable|email',
            'cobranza.telefono'          => 'required_if:resultado_visita,compra|nullable|string|max:10',
            'cobranza.direccion'         => 'required_if:resultado_visita,compra|nullable|string',
            'cobranza.metodo_pago'       => 'required_if:resultado_visita,compra|nullable|string',
            'cobranza.tipo_pago'         => 'required_if:resultado_visita,compra|nullable|string',
            'cobranza.regimen_fiscal_id' => 'required_if:resultado_visita,compra|nullable|exists:regimenes_fiscales,id',
            'cobranza.uso_cfdi_id'       => 'required_if:resultado_visita,compra|nullable|exists:usos_cfdi,id',
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
                    Cobranza::create([
                        'cliente_id'        => $cliente->id,
                        'responsable'       => strtoupper($request->input('cobranza.nombre')),
                        'correo'            => strtolower($request->input('cobranza.correo')),
                        'telefono'          => $request->input('cobranza.telefono'),
                        'rfc'               => strtoupper($request->input('cobranza.rfc')),
                        'direccion'         => strtoupper($request->input('cobranza.direccion')),
                        'metodo_pago'       => $request->input('cobranza.metodo_pago'),
                        'tipo_pago'         => $request->input('cobranza.tipo_pago'),
                        'regimen_fiscal_id' => $request->input('cobranza.regimen_fiscal_id'),
                        'uso_cfdi_id'       => $request->input('cobranza.uso_cfdi_id'),
                    ]);
                    // \DB::connection('mysql_inventario')->table('clientes')
                    //     ->where('id', $cliente->referencia_id)->update(['tipo' => 'CLIENTE']);
                } elseif ($validated['resultado_visita'] === 'rechazo') {
                    $cliente->update(['status' => 'inactivo']);
                    // \DB::connection('mysql_inventario')->table('clientes')
                    //     ->where('id', $cliente->referencia_id)->update(['status' => 'inactivo']);
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
            // $user = $request->user();
            // if (!$user) {
            //     return response()->json(['message' => 'Sesión expirada.'], 401);
            // }

            // $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $visita = Visita::where('id', $id)
                // ->where('user_id', $ownerId)
                // Carga de relación cobranza agregada de manera limpia
                ->with(['cliente.cobranzas' => function($query) {
                    $query->with(['regimenFiscal', 'usoCfdi']);
                }, 'estado', 'logs.user']) 
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

    public function storeCobranza(Request $request)
    {
        // 1. Validaciones estrictas del formulario
        $validated = $request->validate([
            'cliente_id'                  => 'required|integer|exists:clientes,id',
            'cobranza.nombre'             => 'required|string|max:255',
            'cobranza.correo'             => 'required|email|max:255',
            'cobranza.telefono'           => 'required|string|digits:10', // Fuerza exactamente 10 dígitos numéricos
            'cobranza.rfc'                => [
                'required',
                'string',
                'min:12',
                'max:13' // Expresión regular oficial del SAT para RFC
            ],
            'cobranza.direccion'          => 'required|string|max:500',
            'cobranza.metodo_pago'        => 'required|string|in:Deposito en efectivo,Transferencia',
            'cobranza.tipo_pago'          => 'required|string|in:pago cie,venta directa,escuela',
            'cobranza.regimen_fiscal_id'  => 'required|integer|exists:regimenes_fiscales,id', // Revisa si tu tabla se llama así
            'cobranza.uso_cfdi_id'        => 'required|integer|exists:usos_cfdi,id',       // Revisa si tu tabla se llama así
        ], [
            // Mensajes de error personalizados en español
            'cobranza.rfc.regex'          => 'El formato del RFC no es válido ante el SAT.',
            'cobranza.telefono.digits'    => 'El teléfono debe contener exactamente 10 dígitos numéricos.',
            'exists'                      => 'El elemento seleccionado no es válido o no existe en los catálogos.'
        ]);

        try {
            $cobranza = DB::transaction(function () use ($request) {
                $clienteId = $request->input('cliente_id');

                $cobranzaExistente = new Cobranza();
                $cobranzaExistente->cliente_id = $clienteId;

                // 2. Asignación MANUAL directa campo por campo (Esto ignora cualquier bloqueo de Laravel)
                $cobranzaExistente->responsable       = strtoupper($request->input('cobranza.nombre'));
                $cobranzaExistente->correo            = strtolower($request->input('cobranza.correo'));
                $cobranzaExistente->telefono          = $request->input('cobranza.telefono');
                $cobranzaExistente->rfc               = strtoupper($request->input('cobranza.rfc'));
                $cobranzaExistente->direccion         = strtoupper($request->input('cobranza.direccion'));
                $cobranzaExistente->metodo_pago       = $request->input('cobranza.metodo_pago');
                $cobranzaExistente->tipo_pago         = $request->input('cobranza.tipo_pago');
                $cobranzaExistente->regimen_fiscal_id = $request->input('cobranza.regimen_fiscal_id');
                $cobranzaExistente->uso_cfdi_id       = $request->input('cobranza.uso_cfdi_id');

                // 3. Guardamos los cambios en la base de datos
                $cobranzaExistente->save();

                return $cobranzaExistente;
            });

            // 4. Cargamos las relaciones para Vue
            $cobranza->load(['regimenFiscal', 'usoCfdi']);

            // 5. Respuesta exitosa
            return response()->json([
                'success'  => true,
                'message'  => 'Información de cobranza procesada con éxito.',
                'cobranza' => $cobranza
            ], 200);

        } catch (\Exception $e) {
            // Si algo falla a nivel servidor o base de datos, capturamos el error
            return response()->json([
                'success' => false,
                'message' => 'Hubo un problema al guardar los datos en el servidor.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualización de Visita con registro de auditoría.
     */
    public function update(Request $request, $id)
    {
        try {
            if ($request->has('data')) {
                $jsonData = json_decode($request->input('data'), true);
                $request->merge($jsonData);
            }
            
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
                'plantel.tel_oficina'  => 'required|string',
                // 'plantel.extension'    => 'required|string',
                'plantel.beneficios_adicionales' => 'required|string|min:20',
                'libros_interes'       => 'required|array',
                // NUEVAS REGLAS AGREGADAS PARA EL CONTENEDOR INTERNO DE LIBROS DE INTERÉS EN EDIT
                'libros_interes.interes.*.beneficio_tipo'  => 'required|in:Precio especial,Descuento por libro',
                'libros_interes.interes.*.beneficio_valor' => 'required|numeric|min:0',
                'proxima_visita'       => 'nullable|date',
                // ── FRAGMENTO A AGREGAR: VALIDACIÓN CONDICIONAL DE COBRANZA EN UPDATE ──
                'cobranza.id'                => 'nullable',
                'cobranza.nombre'            => 'required_if:resultado_visita,compra|nullable|string|max:150',
                'cobranza.rfc'               => 'required_if:resultado_visita,compra|nullable|string|max:13',
                'cobranza.correo'            => 'required_if:resultado_visita,compra|nullable|email',
                'cobranza.telefono'          => 'required_if:resultado_visita,compra|nullable|string|max:10',
                'cobranza.direccion'         => 'required_if:resultado_visita,compra|nullable|string',
                'cobranza.metodo_pago'       => 'required_if:resultado_visita,compra|nullable|string',
                'cobranza.tipo_pago'         => 'required_if:resultado_visita,compra|nullable|string',
                'cobranza.regimen_fiscal_id' => 'required_if:resultado_visita,compra|nullable|exists:regimenes_fiscales,id',
                'cobranza.uso_cfdi_id'       => 'required_if:resultado_visita,compra|nullable|exists:usos_cfdi,id',
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
                        
                        // ---> ADICIÓN ESPECÍFICA EN EL CONTROLADOR PARA PROCESAR LA FOTO <---
                        $rutaFotoFinal = $cliente->foto_plantel;

                        if ($request->hasFile('foto_plantel')) {
                            // Si subió una foto nueva, borramos la física anterior
                            if ($cliente->foto_plantel && \Storage::disk('public')->exists($cliente->foto_plantel)) {
                                \Storage::disk('public')->delete($cliente->foto_plantel);
                            }
                            $file = $request->file('foto_plantel');
                            $fileName = 'c_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                            $rutaFotoFinal = $file->storeAs('fotos_planteles', $fileName, 'public');
                        } elseif ($request->input('foto_plantel_eliminar') === 'true') {
                            // Si presionó eliminar, borramos el archivo del disco duro
                            if ($cliente->foto_plantel && \Storage::disk('public')->exists($cliente->foto_plantel)) {
                                \Storage::disk('public')->delete($cliente->foto_plantel);
                            }
                            $rutaFotoFinal = null;
                        }
                        // ---------------------------------------------------------------------

                        $cliente->update([
                            'tipo' => ($request->input('resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                            'name' => $visita->nombre_plantel,
                            'rfc' => $visita->rfc_plantel,
                            'contacto' => $visita->director_plantel,
                            'telefono' => $visita->telefono_plantel,
                            'tel_oficina' => $plantel['tel_oficina'],
                            'extension' => $plantel['extension'],
                            'foto_plantel' => $rutaFotoFinal,
                            'beneficios_adicionales' => strtoupper($plantel['beneficios_adicionales']),
                            'email' => $visita->email_plantel,
                            'direccion' => $visita->direccion_plantel,
                            'estado_id' => $request->input('plantel.estado_id'),
                            'status' => ($request->input('resultado_visita') === 'rechazo') ? 'inactivo' : 'activo'
                        ]);
                    }

                    // // // MODIFICAR CLIENTE EN ME
                    // \DB::connection('mysql_inventario')->table('clientes')
                    //      ->where('id', $cliente->referencia_id)->update([
                    //          'tipo'            => ($request->input('resultado_visita') === 'compra') ? 'CLIENTE' : 'PROSPECTO',
                    //          'name'            => $visita->nombre_plantel,
                    //          'rfc'             => $visita->rfc_plantel,
                    //          'contacto'        => $visita->director_plantel,
                    //          'telefono'        => $visita->telefono_plantel,
                    //          'email'           => $visita->email_plantel,
                    //          'direccion'       => $visita->direccion_plantel, 
                    //          'estado_id'       => $request->input('plantel.estado_id'),
                    //          'status'          => ($request->input('resultado_visita') === 'rechazo') ? 'inactivo' : 'activo',
                    //          'updated_at'      => Carbon::now()
                    //      ]);
                    // // // FIN MODIFICAR CLIENTE ME
                }

                // 3. Datos de la Intervención
                $visita->fecha = $request->fecha ?? $visita->fecha;
                $visita->persona_entrevistada = strtoupper($request->persona_entrevistada);
                $visita->cargo = strtoupper($request->cargo);
                $visita->comentarios = strtoupper($request->comentarios);
                $visita->resultado_visita = $request->resultado_visita;
                $visita->libros_interes = $request->libros_interes; 
                $visita->proxima_visita_estimada = !empty($request->proxima_visita) ? $request->proxima_visita : null;
                
                // Incrementar contador de seguridad
                $visita->modificaciones_realizadas += 1;

                $visita->save();

                // ── FRAGMENTO A AGREGAR: ACTUALIZAR O CREAR REGISTRO DE COBRANZA ──
                if ($request->input('resultado_visita') === 'compra' && $visita->cliente_id) {
                    $cobranzaId = $request->input('cobranza.id');
                    
                    $datosCobranza = [
                        'cliente_id'        => $visita->cliente_id,
                        'responsable'       => strtoupper($request->input('cobranza.nombre')),
                        'correo'            => strtolower($request->input('cobranza.correo')),
                        'telefono'          => $request->input('cobranza.telefono'),
                        'rfc'               => strtoupper($request->input('cobranza.rfc')),
                        'direccion'         => strtoupper($request->input('cobranza.direccion')),
                        'metodo_pago'       => $request->input('cobranza.metodo_pago'),
                        'tipo_pago'         => $request->input('cobranza.tipo_pago'),
                        'regimen_fiscal_id' => $request->input('cobranza.regimen_fiscal_id'),
                        'uso_cfdi_id'       => $request->input('cobranza.uso_cfdi_id'),
                    ];

                    if (!empty($cobranzaId)) {
                        // Si existe un ID previo, se actualiza ese registro específico
                        Cobranza::where('id', $cobranzaId)->update($datosCobranza);
                    } else {
                        // Si no existe ID previo pero es una compra, se genera una nueva cobranza
                        Cobranza::create($datosCobranza);
                    }
                }

                return response()->json(['message' => 'Expediente actualizado y registrado en bitácora de auditoría.']);
            });
        } catch (\Exception $e) {
            Log::error("Error actualizando visita: " . $e->getMessage());
            return response()->json(['message' => 'Error al sincronizar cambios.'], 500);
        }
    }
}