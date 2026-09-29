<?php

namespace App\Services;

use App\Models\Eleve;
use App\Models\Inscription;

class InfoEleveService
{
    /**
     * Récupère toutes les données nécessaires pour la fiche d'un élève.
     * Inclut : infos personnelles, inscriptions, paiements principaux, frais supplémentaires.
     *
     * @param  int  $eleveId
     * @return array
     */
    public function getFicheComplete(int $eleveId): array
    {
        $eleve = Eleve::with([
            'inscriptions' => function ($query) {
                $query->orderByDesc('date_inscription');
            },
            'inscriptions.salleDeClasse',
            'inscriptions.anneeScolaire',
            'paiements' => function ($query) {
                $query->orderByDesc('date_paiement');
            },
            'paiements.anneeScolaire',
            'paiements.salleClasse',
            'paiementsFraisSupplementaires' => function ($query) {
                $query->orderByDesc('date_paiement');
            },
            'paiementsFraisSupplementaires.fraisSupplementaire',
        ])->findOrFail($eleveId);

        // Totaux
        $totalPayePrincipalUsd = $eleve->paiements->sum('montant_paye_usd');
        $totalRestantPrincipalUsd = $eleve->paiements->sum('montant_restant_usd');
        $totalPayeFraisSuppUsd = $eleve->paiementsFraisSupplementaires->sum('montant_paye_usd');

        // Inscription active : première non clôturée, sinon la plus récente
        $inscriptionActive = $eleve->inscriptions
            ->filter(fn (Inscription $ins) => $ins->anneeScolaire && !$ins->anneeScolaire->cloturee)
            ->first();

        if (!$inscriptionActive) {
            $inscriptionActive = $eleve->inscriptions->first();
        }

        return [
            'eleve' => $eleve,
            'inscriptionActive' => $inscriptionActive,
            'totalPayePrincipalUsd' => $totalPayePrincipalUsd,
            'totalRestantPrincipalUsd' => $totalRestantPrincipalUsd,
            'totalPayeFraisSuppUsd' => $totalPayeFraisSuppUsd,
        ];
    }
}