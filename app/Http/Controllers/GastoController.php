<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Gasto;
use App\Models\Comprobante;
use App\Models\User;
use Carbon\Carbon;
use App\Models\GastoLog;
use Spatie\Dropbox\Client as DropboxClient;

class GastoController extends Controller
{
    private $allowedMimes = ['jpg', 'jpeg', 'png', 'pdf'];
    private $maxFileSize = 3072; // 3MB

    /**
     * Listado de paquetes de gastos del usuario/delegado.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['message' => 'No autenticado'], 401);
            
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
            
            $query = Gasto::where('user_id', $ownerId)->with('comprobantes');

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
            }
            
            return response()->json($query->orderBy('id', 'desc')->paginate(15));
        } catch (\Exception $e) {
            Log::error("Error en index de gastos: " . $e->getMessage());
            return response()->json(['message' => 'Error al obtener el historial de gastos'], 500);
        }
    }

    /**
     * Registro inicial de un paquete.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha'         => 'required|date',
            'estado_nombre' => 'required|string',
            'status'        => 'required|in:BORRADOR,FINALIZADO',
            'monto_total'   => 'required|numeric|min:0',
            'conceptos'     => 'required|array|min:1',
            'conceptos.*.concepto'     => 'required|string',
            'conceptos.*.monto'        => 'required|numeric|min:0',
            'conceptos.*.es_facturado' => 'required|boolean',
        ]);

        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $tieneFactura = collect($request->conceptos)->contains('es_facturado', true);

            $gasto = Gasto::create([
                'user_id'       => $ownerId,
                'fecha'         => $request->fecha,
                'estado_nombre' => strtoupper($request->estado_nombre),
                'concepto'      => strtoupper($request->estado_nombre), 
                'monto'         => $request->monto_total,
                'facturado'     => $tieneFactura,
                'detalles'      => $request->conceptos, 
                'status'        => $request->status 
            ]);

            return response()->json([
                'message' => 'Paquete registrado correctamente.',
                'gasto'   => $gasto
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear gasto:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Gestión de subida de archivos a Dropbox.
     */
    public function storeComprobante(Request $request)
    {
        $request->validate([
            'gasto_id' => 'required|exists:gastos,id',
            'files'    => 'required|array',
            'files.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:3072',
        ]);
        
        $gasto = Gasto::findOrFail($request->gasto_id);
        $user = $request->user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        if ($gasto->user_id !== $ownerId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        try {
            $accessToken = $this->getDropboxToken();
            $dropboxClient = new DropboxClient($accessToken);

            DB::beginTransaction();
            $comprobantesGuardados = [];

            foreach ($request->file('files') as $file) {
                $timestamp = Carbon::now()->format('ymd-His');
                $extension = $file->getClientOriginalExtension();
                
                $fileName = "{$timestamp}_U" . Auth::id() . "-G{$gasto->id}.{$extension}";
                $path = "/comprobantes/{$gasto->id}/{$fileName}";
                
                $dropboxClient->upload($path, file_get_contents($file->getRealPath()), 'add');

                $publicUrl = null;
                try {
                    // Intenta crear el link sin settings adicionales primero
                    $sharedResponse = $dropboxClient->createSharedLinkWithSettings($path);
                    $publicUrl = $sharedResponse['url'];
                } catch (\Exception $e) {
                    // Si falla, intentamos listar (aquí es donde suele estar el link si ya existe)
                    $links = $dropboxClient->listSharedLinks($path);
                    $publicUrl = !empty($links) ? $links[0]['url'] : null;
                }

                // Transformar a link de descarga si existe
                if ($publicUrl) {
                    $publicUrl = str_replace('dl=0', 'dl=1', $publicUrl);
                }

                $comprobante = Comprobante::create([
                    'gasto_id'  => $gasto->id,
                    'name'      => $fileName,
                    'size'      => round($file->getSize() / 1024),
                    'extension' => $extension,
                    'public_url'=> $publicUrl,
                ]);

                $comprobantesGuardados[] = $comprobante;
            }

            DB::commit();
            return response()->json(['message' => 'Sincronización de archivos exitosa.', 'comprobantes' => $comprobantesGuardados], 201);

        } catch (\Exception $e) {
            DB::rollBack();
    
            // Esto nos dirá si es un error de la librería o una respuesta de la API
            $detailedError = method_exists($e, 'getResponse') 
                ? $e->getResponse()->getBody()->getContents() 
                : $e->getMessage();

            Log::error('Error detallado de Dropbox:', ['info' => $detailedError]);
            
            return response()->json([
                'message' => 'Fallo en la comunicación.',
                'debug' => $detailedError // Solo para pruebas, quítalo en producción
            ], 500);
        }
    }

    /**
     * Detalle técnico del gasto con Auditoría.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['message' => 'No autenticado'], 401);

            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
            
            $gasto = Gasto::where('id', $id)
                        ->where('user_id', $ownerId)
                        ->with(['comprobantes', 'logs.user'])
                        ->first();

            if (!$gasto) {
                return response()->json(['message' => 'Expediente no encontrado o acceso denegado.'], 404);
            }

            return response()->json($gasto);
        } catch (\Exception $e) {
            Log::error("Error 500 en GastoController@show ID {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error técnico al recuperar el detalle.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.'
            ], 500);
        }
    }

    /**
     * Actualización con Regla de Auditoría Selectiva.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        $gasto = Gasto::where('id', $id)->where('user_id', $ownerId)->firstOrFail();

        // Reglas Base
        $rules = [
            'fecha'         => 'required|date',
            'estado_nombre' => 'required|string',
            'status'        => 'required|in:BORRADOR,FINALIZADO',
            'monto_total'   => 'required|numeric|min:0',
            'conceptos'     => 'required|array|min:1',
        ];

        /**
         * REGLA DE ORO: 
         * Solo se exige el motivo si el paquete ACTUALMENTE en la DB es FINALIZADO.
         * Si es un BORRADOR, no importa a qué estado pase, no requiere motivo.
         */
        if ($gasto->status === 'FINALIZADO') {
            $rules['motivo_cambio'] = 'required|string|min:10';
        } else {
            $rules['motivo_cambio'] = 'nullable|string';
        }

        $request->validate($rules);

        try {
            // Bloqueo de seguridad: si ya se modificó una vez después de finalizar, no permitir más.
            if ($gasto->status === 'FINALIZADO' && $gasto->modificaciones_finalizadas >= 1) {
                return response()->json([
                    'message' => 'Este expediente ya cuenta con un ajuste único posterior a su cierre y se encuentra bloqueado.'
                ], 403);
            }

            return DB::transaction(function () use ($gasto, $request) {
                $oldDetails = $gasto->detalles;

                /**
                 * REGLA: Solo generar LOG si el paquete ya estaba FINALIZADO.
                 * Si es borrador, se sobreescribe sin generar auditoría de cambio.
                 */
                if ($gasto->status === 'FINALIZADO') {
                    GastoLog::create([
                        'gasto_id' => $gasto->id,
                        'user_id' => Auth::id(),
                        'snapshot_anterior' => [
                            'cabecera' => $gasto->makeHidden(['detalles', 'comprobantes', 'logs'])->toArray(),
                            'detalles' => $oldDetails
                        ],
                        'motivo_cambio' => strtoupper($request->motivo_cambio)
                    ]);

                    // Incrementamos el contador de modificaciones de paquetes cerrados
                    $gasto->modificaciones_finalizadas += 1;
                }

                $tieneFactura = collect($request->conceptos)->contains('es_facturado', true);

                $gasto->update([
                    'fecha'         => $request->fecha,
                    'estado_nombre' => strtoupper($request->estado_nombre),
                    'monto'         => $request->monto_total,
                    'facturado'     => $tieneFactura,
                    'detalles'      => $request->conceptos,
                    'status'        => $request->status,
                    'modificaciones_finalizadas' => $gasto->modificaciones_finalizadas
                ]);

                return response()->json(['message' => 'Expediente actualizado correctamente.', 'gasto' => $gasto]);
            });
        } catch (\Exception $e) {
            Log::error('Error al actualizar gasto:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error técnico en la sincronización.'], 500);
        }
    }

    /**
     * Eliminación total (Incluye archivos en Dropbox).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $gasto = Gasto::with('comprobantes')->where('id', $id)->where('user_id', $ownerId)->firstOrFail();

            return DB::transaction(function () use ($gasto, $request) {
                // Registro de eliminación siempre genera log (opcional, según tu política)
                GastoLog::create([
                    'gasto_id' => $gasto->id,
                    'user_id' => Auth::id(),
                    'snapshot_anterior' => [
                        'tipo_log'     => 'ELIMINACION_TOTAL',
                        'cabecera'     => $gasto->makeHidden(['detalles', 'comprobantes', 'logs'])->toArray(),
                        'detalles'     => $gasto->detalles,
                        'comprobantes' => $gasto->comprobantes->toArray() 
                    ],
                    'motivo_cambio' => strtoupper($request->query('motivo') ?? 'ELIMINACIÓN TOTAL POR EL USUARIO')
                ]);

                $accessToken = $this->getDropboxToken();
                $dropboxClient = new DropboxClient($accessToken);

                foreach ($gasto->comprobantes as $comprobante) {
                    $path = "/comprobantes/{$gasto->id}/{$comprobante->name}";
                    try {
                        $dropboxClient->delete($path);
                    } catch (\Exception $e) {
                        Log::warning("Archivo no encontrado en Dropbox: " . $path);
                    }
                }

                $gasto->comprobantes()->delete();
                $gasto->delete();

                return response()->json(['message' => 'Registro y archivos eliminados de forma permanente.']);
            });

        } catch (\Exception $e) {
            Log::error("Error en eliminación total de gasto: " . $e->getMessage());
            return response()->json(['message' => 'Fallo al procesar la eliminación.'], 500);
        }
    }

    /**
     * Centraliza la obtención del token de Dropbox.
     */
    private function getDropboxToken()
    {
        $response = Http::asForm()->post('https://api.dropbox.com/oauth2/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => env('DROPBOX_REFRESH_TOKEN'),
            'client_id'     => env('DROPBOX_APP_KEY'),
            'client_secret' => env('DROPBOX_APP_SECRET'),
        ]);

        if (!$response->successful()) {
            throw new \Exception('No se pudo refrescar el token de Dropbox.');
        }

        return $response->json()['access_token'];
    }
}