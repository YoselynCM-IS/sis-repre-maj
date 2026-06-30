<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PedidoController; 
use App\Http\Controllers\SearchController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\VisitaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegimenFiscalController;


// Rutas Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/request-ticket', [TicketController::class, 'storeRecoveryTicket']);

// Rutas Protegidas (Requieren Token)
Route::middleware('auth:sanctum')->group(function () {
    
    // Información de sesión básica
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    
    // Pedidos
    Route::get('/pedidos/ultima-paqueteria', [PedidoController::class, 'getUltimaPaqueteria']);
    Route::get('/pedidos', [PedidoController::class, 'index']); 
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']); 
    Route::get('/proxy/dipomex', [PedidoController::class, 'proxyDipomex']);
    Route::put('/pedidos/{id}', [PedidoController::class, 'update']);
    Route::post('pedidos/store-guia', [PedidoController::class, 'storeGuia']); 
    // Ruta exclusiva para actualizar el estatus de un pedido e insertar en el historial
    Route::post('/pedidos/{id}/update-status', [PedidoController::class, 'updateStatus']);

    // Gastos
    Route::get('/gastos', [GastoController::class, 'index']); 
    Route::post('/gastos/comprobante', [GastoController::class, 'storeComprobante']); 
    Route::get('/gastos/{id}', [GastoController::class, 'show']); 
    Route::post('/gastos-nuevos', [GastoController::class, 'store']);
    Route::put('/gastos/{id}', [GastoController::class, 'update']);
    
    // Buscadores y Datos Maestros Globales
    Route::get('/search/clientes', [SearchController::class, 'searchClientes']);
    Route::get('/search/libros', [SearchController::class, 'searchLibros']);
    Route::get('/search/niveles', [SearchController::class, 'getNiveles']); 
    Route::get('/search/series', [SearchController::class, 'getSeries']);  
    Route::get('/estados', [SearchController::class, 'getEstados']);
    Route::get('/search/receptores/rfc', [SearchController::class, 'searchReceptorByRFC']);
    Route::get('/search/receptores', [SearchController::class, 'searchReceptores']);
    Route::get('/search/receptores/check-rfc', [SearchController::class, 'checkRfcUniqueness']);

    // Visitas y Prospectos
    Route::get('/visitas', [VisitaController::class, 'index']);
    Route::post('/visitas/primera', [VisitaController::class, 'storePrimeraVisita']);
    Route::get('/visitas/{id}', [VisitaController::class, 'show']);
    Route::post('/visitas/seguimiento', [VisitaController::class, 'storeSeguimiento']); 
    Route::get('/search/prospectos', [SearchController::class, 'searchProspectos']);
    Route::get('/clientes/{cliente_id}/historial', [VisitaController::class, 'historialPorCliente']);
    Route::put('/visitas/{id}', [VisitaController::class, 'update']); 
    Route::post('/cobranzas', [VisitaController::class, 'storeCobranza']);
    
    // --- SECCIÓN DE PERFIL ---
    Route::prefix('profile')->group(function () {
        Route::get('/myprofile', [ProfileController::class, 'show']); // Responde a /api/profile
        Route::get('/estados', [SearchController::class, 'getEstados']); // <--- ESTA FALTABA AQUÍ
        Route::put('/', [ProfileController::class, 'update']); 
        Route::put('/tools', [ProfileController::class, 'updateTools']); 
        Route::put('/password', [ProfileController::class, 'updatePassword']); 
        
        // Gestión de Delegados
        Route::post('/delegates', [ProfileController::class, 'addDelegate']);
        Route::delete('/delegates/{id}', [ProfileController::class, 'removeDelegate']);
    });

    // REGIMENES FISCALES
    Route::get('/regimenes-fiscales', [RegimenFiscalController::class, 'index']);
    Route::get('/usos-cfdi', [RegimenFiscalController::class, 'getUsosCfdi']);

});