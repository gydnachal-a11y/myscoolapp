<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\Echeance;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\SalleDeClasse;
use Illuminate\Support\Carbon;

class EcheanceService
{
    /**
     * Génère les échéances pour tous les élèves inscrits dans une année scolaire active.
     */
    public function genererPourAnneeActive(): int
    {
        $annee = AnneeScolaire::where('cloturee', false)->latest('date_debut')->first();
        if (!$annee) {
            return 0;
        }

        $taux = $this->getTauxChange();
        $compteur = 0;

        $inscriptions = Inscription::with(['eleve', 'salleDeClasse'])
            ->where('annee_scolaire_id', $annee->id)
            ->get();

        foreach ($inscriptions as $inscription) {
            $eleve = $inscription->eleve;
            $salle = $inscription->salleDeClasse;

            if (!$eleve || !$salle) {
                continue;
            }

            if ($salle->mode_paiement === 'mensuel') {
                $compteur += $this->genererMensuel($eleve, $salle, $annee, $taux);
            } elseif ($salle->mode_paiement === 'tranche') {
                $compteur += $this->genererTranches($eleve, $salle, $annee, $taux);
            }
        }

        return $compteur;
    }

    /**
     * Génère les échéances mensuelles.
     */
    private function genererMensuel(Eleve $eleve, SalleDeClasse $salle, AnneeScolaire $annee, float $taux): int
    {
        $debut = Carbon::parse($annee->date_debut);
        $fin = Carbon::parse($annee->date_fin);
        $montantMensuel = $salle->frais_scolarite_mensuel;

        if (!$montantMensuel || $montantMensuel <= 0) {
            return 0;
        }

        $compteur = 0;
        while ($debut->lte($fin)) {
            $periode = $debut->format('Y-m');
            $this->createEcheance($eleve, $annee, $salle, 'mensuel', $periode, $montantMensuel, $taux, $debut->copy()->startOfMonth());
            $compteur++;
            $debut->addMonth();
        }

        return $compteur;
    }

    /**
     * Génère les échéances par tranches.
     */
    private function genererTranches(Eleve $eleve, SalleDeClasse $salle, AnneeScolaire $annee, float $taux): int
    {
        $nombreTranches = $salle->nombre_tranches ?? 1;
        if ($nombreTranches <= 0) {
            return 0;
        }

        $total = $salle->frais_annuel > 0
            ? $salle->frais_annuel
            : ($salle->frais_scolarite_mensuel * $this->compterMois($annee));

        if ($total <= 0) {
            return 0;
        }

        $montantParTranche = round($total / $nombreTranches, 2);
        $compteur = 0;
        $date = Carbon::parse($annee->date_debut);

        for ($i = 1; $i <= $nombreTranches; $i++) {
            $periode = 'Tranche ' . $i;
            $this->createEcheance($eleve, $annee, $salle, 'tranche', $periode, $montantParTranche, $taux, $date->copy());
            $compteur++;
            $date->addMonths(ceil($this->compterMois($annee) / $nombreTranches));
        }

        return $compteur;
    }

    /**
     * Crée une échéance si elle n'existe pas déjà.
     */
    private function createEcheance(
        Eleve $eleve,
        AnneeScolaire $annee,
        SalleDeClasse $salle,
        string $type,
        string $periode,
        float $montantUsd,
        float $taux,
        Carbon $dateEcheance
    ): void {
        $existe = Echeance::where([
            'eleve_id' => $eleve->id,
            'annee_scolaire_id' => $annee->id,
            'salle_classe_id' => $salle->id,
            'type_periode' => $type,
            'periode' => $periode,
        ])->exists();

        if (!$existe) {
            Echeance::create([
                'eleve_id' => $eleve->id,
                'annee_scolaire_id' => $annee->id,
                'salle_classe_id' => $salle->id,
                'type_periode' => $type,
                'periode' => $periode,
                'montant_usd' => $montantUsd,
                'montant_fc' => $montantUsd * $taux,
                'est_paye' => false,
                'date_echeance' => $dateEcheance,
            ]);
        }
    }

    private function compterMois(AnneeScolaire $annee): int
    {
        $debut = Carbon::parse($annee->date_debut);
        $fin = Carbon::parse($annee->date_fin);

        return $debut->diffInMonths($fin) + 1;
    }

    private function getTauxChange(): float
    {
        $source = Devise::where('code', 'USD')->first();
        $cible = Devise::where('code', 'CDF')->first();

        return ($source && $cible) ? $source->tauxVers($cible) : 2800;
    }
}