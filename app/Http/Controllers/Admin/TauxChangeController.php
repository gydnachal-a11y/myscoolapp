<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use App\Models\TauxChange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TauxChangeController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const DEVISE_SOURCE_CODE = 'USD';
    private const DEVISE_CIBLE_CODE  = 'CDF';
    private const TAUX_DEFAUT        = 2800.0;

    // ============================================================
    // EDIT
    // ============================================================

    public function edit(): View
    {
        [$source, $cible] = $this->getDevises();

        $taux = TauxChange::query()
            ->where('devise_source_id', $source->id)
            ->where('devise_cible_id', $cible->id)
            ->value('taux') ?? self::TAUX_DEFAUT;

        return view('admin.taux.edit', [
            'deviseSource' => $source,
            'deviseCible'  => $cible,
            'taux'         => (float) $taux,
        ]);
    }

    // ============================================================
    // UPDATE
    // ============================================================

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'taux' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ], [
            'taux.required' => 'Le taux est obligatoire.',
            'taux.numeric'  => 'Le taux doit être un nombre.',
            'taux.min'      => 'Le taux ne peut pas être négatif.',
            'taux.max'      => 'Le taux est trop élevé.',
        ]);

        [$source, $cible] = $this->getDevises();

        TauxChange::updateOrCreate(
            [
                'devise_source_id' => $source->id,
                'devise_cible_id'  => $cible->id,
            ],
            ['taux' => (float) $data['taux']]
        );

        /* Invalide les caches de taux */
        Cache::forget('taux_change');
        Cache::forget('taux_change_usd_cdf');
        Cache::forget('taux_change:' . $source->code . '_to_' . $cible->code);

        return back()->with('success', sprintf(
            'Taux mis à jour : 1 %s = %s %s',
            $source->code,
            number_format((float) $data['taux'], 2, ',', ' '),
            $cible->code
        ));
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Récupère les devises source et cible (avec fallback 404 propre).
     *
     * @return array{0: Devise, 1: Devise}
     */
    private function getDevises(): array
    {
        $source = Devise::where('code', self::DEVISE_SOURCE_CODE)->first();
        $cible  = Devise::where('code', self::DEVISE_CIBLE_CODE)->first();

        if (!$source || !$cible) {
            abort(500, sprintf(
                "Les devises %s et %s doivent exister en base.",
                self::DEVISE_SOURCE_CODE,
                self::DEVISE_CIBLE_CODE
            ));
        }

        return [$source, $cible];
    }
}