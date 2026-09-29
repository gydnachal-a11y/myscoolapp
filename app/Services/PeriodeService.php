<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Models\TrancheScolaire;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodeService
{
    /**
     * Tableau des noms de mois en français.
     */
    private const NOMS_MOIS = [
        1  => 'Janvier',
        2  => 'Février',
        3  => 'Mars',
        4  => 'Avril',
        5  => 'Mai',
        6  => 'Juin',
        7  => 'Juillet',
        8  => 'Août',
        9  => 'Septembre',
        10 => 'Octobre',
        11 => 'Novembre',
        12 => 'Décembre',
    ];

    /**
     * Génère les mois et tranches pour une année scolaire donnée.
     */
    public function genererPourAnnee(AnneeScolaire $annee): void
    {
        DB::transaction(function () use ($annee) {
            $this->genererMois($annee);
            $this->genererTranches($annee);
        });
    }

    /**
     * Génère les mois scolaires pour une année donnée.
     */
    public function genererMois(AnneeScolaire $annee): void
    {
        // Supprimer les anciens mois
        MoisScolaire::where('annee_scolaire_id', $annee->id)->delete();

        $debut = Carbon::parse($annee->date_debut)->startOfMonth();
        $fin = Carbon::parse($annee->date_fin)->endOfMonth();

        while ($debut->lte($fin)) {
            MoisScolaire::create([
                'annee_scolaire_id' => $annee->id,
                'mois' => $debut->format('Y-m'),
                'nom_mois' => self::NOMS_MOIS[$debut->month] ?? $debut->translatedFormat('F'),
                'date_debut' => $debut->copy(),
                'date_fin' => $debut->copy()->endOfMonth(),
            ]);

            $debut->addMonth();
        }
    }

    /**
     * Génère les tranches scolaires pour une année donnée.
     */
    public function genererTranches(AnneeScolaire $annee): void
    {
        // Supprimer les anciennes tranches
        TrancheScolaire::where('annee_scolaire_id', $annee->id)->delete();

        $debut = Carbon::parse($annee->date_debut);
        $fin = Carbon::parse($annee->date_fin);
        $nombreTranches = (int) ($annee->nombre_tranches ?? 1);
        $nombreMois = (int) ($annee->nombre_mois ?? $debut->diffInMonths($fin) + 1);

        if ($nombreTranches <= 0 || $nombreMois <= 0) {
            return;
        }

        $moisParTranche = (int) ceil($nombreMois / $nombreTranches);

        $trancheDebut = $debut->copy();
        for ($i = 1; $i <= $nombreTranches; $i++) {
            $trancheFin = $trancheDebut->copy()->addMonths($moisParTranche)->subDay();
            if ($trancheFin->gt($fin)) {
                $trancheFin = $fin->copy();
            }

            TrancheScolaire::create([
                'annee_scolaire_id' => $annee->id,
                'tranche' => 'Tranche ' . $i,
                'date_debut' => $trancheDebut->copy(),
                'date_fin' => $trancheFin->copy(),
            ]);

            $trancheDebut = $trancheFin->copy()->addDay();
        }
    }
}