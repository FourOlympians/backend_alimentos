<?php

namespace App\Http\Controllers;

use App\Models\CondicionMedica;
use Illuminate\Http\JsonResponse;

/**
 * CondicionController
 *
 * GET /api/condiciones   → catálogo de condiciones médicas
 */
class CondicionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(CondicionMedica::orderBy('nombre')->get());
    }
}
