<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalleDeClasse;
use Illuminate\Http\JsonResponse;

class SalleDetailsController extends Controller
{
    /**
     * Détails légers des salles (frais d'inscription + frais annuel).
     * Remplace la closure initialement définie dans routes/web.php.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(
            SalleDeClasse::query()
                ->select('id', 'frais_inscription', 'frais_annuel')
                ->get()
        );
    }
}