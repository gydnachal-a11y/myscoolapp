<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\Option;
use App\Models\Cour;
use App\Models\Categorie;
use App\Models\Fonction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransitionService
{
    /**
     * Exécute la clôture de l'année courante et prépare la suivante.
     *
     * @param AnneeScolaire $ancienneAnnee
     * @param array $options
     * @return array{success: bool, message: string}
     */
    public function executer(AnneeScolaire $ancienneAnnee, array $options): array
    {
        DB::beginTransaction();

        try {
            // 1. Marquer l'ancienne année comme clôturée
            $ancienneAnnee->update(['cloturee' => true, 'paiement_ouvert' => false]);

            // 2. Déterminer la nouvelle année
            $nouvelleAnnee = $this->obtenirNouvelleAnnee($options);

            // 3. Appliquer les transferts ou réinitialisation
            if (!empty($options['reset_complet'])) {
                // Réinitialisation complète : on ne transfert rien, on vide les données transactionnelles
                $this->reinitialiserDonnees($nouvelleAnnee);
                $message = 'Année clôturée, nouvelle année réinitialisée.';
            } else {
                // Transferts sélectifs
                if (!empty($options['transferer_eleves'])) {
                    $this->transfererEleves($ancienneAnnee, $nouvelleAnnee);
                }
                if (!empty($options['transferer_config'])) {
                    $this->transfererConfiguration();
                }
                if (!empty($options['transferer_cours'])) {
                    $this->transfererCours();
                }
                if (!empty($options['transferer_personnel'])) {
                    $this->transfererPersonnel();
                }
                $message = 'Année clôturée, transferts effectués vers ' . $nouvelleAnnee->libelle;
            }

            // 4. Générer les périodes (mois et tranches) pour la nouvelle année
            (new PeriodeService())->genererPourAnnee($nouvelleAnnee);

            DB::commit();
            return ['success' => true, 'message' => $message];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la transition : ' . $e->getMessage()];
        }
    }

    /**
     * Retourne l'année cible, en la créant si demandé.
     */
    private function obtenirNouvelleAnnee(array $options): AnneeScolaire
    {
        if (!empty($options['nouvelle_annee_id'])) {
            return AnneeScolaire::findOrFail($options['nouvelle_annee_id']);
        }

        if (!empty($options['creer_nouvelle_annee'])) {
            // Création automatique basée sur l'année précédente
            $derniere = AnneeScolaire::orderBy('date_fin', 'desc')->first();
            $newDateDebut = $derniere ? $derniere->date_fin->addDay() : now()->startOfYear();
            $newDateFin = $newDateDebut->copy()->addYear();
            $libelle = $newDateDebut->format('Y') . '-' . $newDateFin->format('Y');

            return AnneeScolaire::create([
                'libelle' => $libelle,
                'date_debut' => $newDateDebut,
                'date_fin' => $newDateFin,
                'effectif_attendu' => 0,
                'cloturee' => false,
                'paiement_ouvert' => false,
                'nombre_mois' => 12,
                'nombre_tranches' => 3,
            ]);
        }

        throw new \Exception('Aucune nouvelle année sélectionnée ou créée.');
    }

    /**
     * Transfère les élèves (promotion) : réinscrit les élèves dans la même salle ou la salle supérieure.
     */
    private function transfererEleves(AnneeScolaire $ancienne, AnneeScolaire $nouvelle): void
    {
        // Récupérer les inscriptions de l'ancienne année
        $inscriptions = Inscription::where('annee_scolaire_id', $ancienne->id)->get();

        foreach ($inscriptions as $inscription) {
            $eleve = $inscription->eleve;
            $salleActuelle = $inscription->salleDeClasse;

            // Choisir la salle cible : salle supérieure si définie, sinon même salle
            $salleCible = $salleActuelle->salleSuperieure ?? $salleActuelle;

            // Vérifier que l'élève n'est pas déjà inscrit dans la nouvelle année
            $dejaInscrit = Inscription::where('eleve_id', $eleve->id)
                ->where('annee_scolaire_id', $nouvelle->id)
                ->exists();

            if (!$dejaInscrit && $salleCible) {
                Inscription::create([
                    'eleve_id' => $eleve->id,
                    'annee_scolaire_id' => $nouvelle->id,
                    'salle_classe_id' => $salleCible->id,
                    'date_inscription' => now(),
                    'reduction_frais' => 0,
                    'frais_inscription_final' => $salleCible->frais_inscription,
                    'frais_annuel_final' => $salleCible->frais_annuel,
                ]);
            }
        }
    }

    /**
     * Réinitialise les données transactionnelles de la nouvelle année (si elles existent).
     */
    private function reinitialiserDonnees(AnneeScolaire $annee): void
    {
        Inscription::where('annee_scolaire_id', $annee->id)->delete();
        \App\Models\Paiement::where('annee_scolaire_id', $annee->id)->delete();
        \App\Models\Echeance::where('annee_scolaire_id', $annee->id)->delete();
    }

    /**
     * Transfère la configuration (sessions, sections, salles, options). 
     * Ici, on considère que ces tables sont globales, donc on ne les réinitialise pas.
     * On peut simplement laisser intact, car elles ne dépendent pas de l'année.
     */
    private function transfererConfiguration(): void
    {
        // Pas de duplication nécessaire : les salles, sections, options restent globales.
        // On pourrait éventuellement les recréer si besoin.
    }

    /**
     * Transfère les cours et catégories (globaux).
     */
    private function transfererCours(): void
    {
        // Idem, global. Aucun transfert nécessaire.
    }

    /**
     * Transfère le personnel (global).
     */
    private function transfererPersonnel(): void
    {
        // Idem, global.
    }
}