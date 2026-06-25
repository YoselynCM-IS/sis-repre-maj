<?php

namespace App\Http\Controllers;

use App\Models\RegimenFiscal;
use App\Models\UsosCfdi;
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

    public function getUsosCfdi()
    {
        try {
            $usos = UsosCfdi::orderBy('c_UsoCFDI', 'asc')->get();
            return response()->json($usos, 200);
        } catch (\Exception $e) {
            \Log::error("Error en getUsosCfdi: " . $e->getMessage());
            return response()->json(['message' => 'Error al obtener el catálogo de Usos de CFDI.'], 500);
        }
    }
}