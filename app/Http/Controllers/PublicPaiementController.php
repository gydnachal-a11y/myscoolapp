<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\SalleDeClasse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicPaiementController extends Controller
{
    public function index(Request $request): View
    {
        $salleId = $request->integer('salle_id') ?: null;
        $periode = $request->integer('periode') ?: null;

        /* ---------- Salles ---------- */
        $salles = SalleDeClasse::query()
            ->with('section:id,nom')
            ->orderBy('nom')
            ->get(['id', 'nom', 'section_id', 'mode_paiement']);

        $salle = $salleId
            ? SalleDeClasse::with('section:id,nom')->find($salleId)
            : null;

        /* ---------- Année active ---------- */
        $anneeActive = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();

        /* ---------- Type de période = mode de la salle ---------- */
        $typePeriode = $salle?->mode_paiement; // 'mensuel' ou 'tranche'

        /* ---------- Périodes ayant déjà des paiements ---------- */
        $periodesDisponibles = collect();

        if ($salle && $anneeActive && $typePeriode) {
            $periodesDisponibles = Paiement::query()
                ->where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->where('type_periode', $typePeriode)
                ->whereNotNull('periode')
                ->distinct()
                ->orderBy('periode')
                ->pluck('periode')
                ->map(fn ($p) => (int) $p)
                ->values();
        }

        /* ---------- Si période non valide → reset ---------- */
        if ($periode && !$periodesDisponibles->contains($periode)) {
            $periode = null;
        }

        /* ---------- Données ---------- */
        $paiements  = collect();
        $inscrits   = collect();
        $nonPayants = collect();

        if ($salle && $periode && $anneeActive) {
            /* Élèves inscrits */
            $inscrits = Inscription::query()
                ->with(['eleve:id,nom,postnom,prenom,sexe,photo'])
                ->where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->get();

            /* Paiements de la période */
            $paiements = Paiement::query()
                ->with(['eleve:id,nom,postnom,prenom,sexe,photo'])
                ->where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->where('periode', $periode)
                ->where('type_periode', $typePeriode)
                ->latest('created_at')
                ->get();

            /* Élèves sans paiement */
            $payeIds = $paiements->pluck('eleve_id')->unique()->all();
            $nonPayants = $inscrits->filter(
                fn ($ins) => !in_array($ins->eleve_id, $payeIds, true)
            );
        }

        return view('public.paiements', compact(
            'salles',
            'salle',
            'salleId',
            'periode',
            'typePeriode',
            'anneeActive',
            'periodesDisponibles',
            'paiements',
            'inscrits',
            'nonPayants'
        ));
    }
}