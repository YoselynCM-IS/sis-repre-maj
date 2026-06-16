<?php

namespace App\Http\Controllers;

use App\Models\RegimenFiscal;
use Illuminate\Http\JsonResponse;

class RegimenFiscalController extends Controller
{
    /**
     * Obtiene el listado completo de regímenes fiscales.
     */
    public function index(): JsonResponse
    {
        // Traemos todos los regímenes ordenados ascendentemente por su código
        $regimenes = RegimenFiscal::orderBy('codigo', 'asc')->get();

        return response()->json($regimenes, 200);
    }
}