<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

/* ============================================================
 | HELPERS INTERNES — extraction 100% sûre (aucun accesseur)
 ============================================================ */

if (!function_exists('_eleve_nom_complet')) {
    function _eleve_nom_complet(?object $eleve): string
    {
        if (!$eleve) {
            return 'Inconnu';
        }

        $parts = array_filter([
            $eleve->nom    ?? null,
            $eleve->postnom ?? null,
            $eleve->prenom ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return $parts ? implode(' ', $parts) : 'Inconnu';
    }
}

if (!function_exists('_eleve_initiales')) {
    function _eleve_initiales(?object $eleve): string
    {
        if (!$eleve) {
            return '?';
        }

        $prenom = (string) ($eleve->prenom ?? '');
        $nom    = (string) ($eleve->nom    ?? '');

        $init = strtoupper(
            mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)
        );

        return $init !== '' ? $init : '?';
    }
}

if (!function_exists('_eleve_sexe')) {
    function _eleve_sexe(?object $eleve): array
    {
        $code = $eleve->sexe ?? null;

        $libelle = match ($code) {
            'M'     => 'Masculin',
            'F'     => 'Féminin',
            default => '—',
        };

        return ['code' => $code, 'libelle' => $libelle];
    }
}

/* ============================================================
 | INSCRIPTIONS
 ============================================================ */

if (!function_exists('inscription_row_data')) {
    function inscription_row_data(object $inscription): array
    {
        $eleve = $inscription->eleve ?? null;

        $nomComplet = _eleve_nom_complet($eleve);
        $initiales  = _eleve_initiales($eleve);
        $sexe       = _eleve_sexe($eleve);

        /* Date d'inscription */
        $dateIns = $inscription->date_inscription_formatee ?? null;
        if ($dateIns === null && !empty($inscription->date_inscription)) {
            try {
                $dateIns = Carbon::parse($inscription->date_inscription)->format('d/m/Y');
            } catch (\Throwable) {
                $dateIns = '—';
            }
        }
        $dateIns ??= '—';

        /* Frais */
        $frais = $inscription->frais_inscription_formates ?? null;
        if ($frais === null && isset($inscription->frais_inscription_final)) {
            $frais = number_format((float) $inscription->frais_inscription_final, 2, ',', ' ') . ' USD';
        }
        $frais ??= '—';

        /* Année scolaire */
        $anneeLibelle = '—';
        if (!empty($inscription->anneeScolaire)) {
            $anneeLibelle = $inscription->anneeScolaire->libelle ?? '—';
        } elseif (isset($inscription->annee_scolaire)) {
            $anneeLibelle = is_object($inscription->annee_scolaire)
                ? ($inscription->annee_scolaire->libelle ?? '—')
                : (string) $inscription->annee_scolaire;
        } elseif (!empty($inscription->annee_libelle)) {
            $anneeLibelle = $inscription->annee_libelle;
        }

        return [
            'nomComplet'  => $nomComplet,
            'initiales'   => $initiales,
            'sexeCode'    => $sexe['code'],
            'sexeLibelle' => $sexe['libelle'],
            'dateIns'     => $dateIns,
            'frais'       => $frais,
            'anneeLibelle' => $anneeLibelle,
        ];
    }
}

/* ============================================================
 | PAIEMENTS
 ============================================================ */

if (!function_exists('paiement_row_data')) {
    function paiement_row_data(object $paiement): array
    {
        $eleve = $paiement->eleve ?? null;

        $nomComplet = _eleve_nom_complet($eleve);
        $initiales  = _eleve_initiales($eleve);
        $sexe       = _eleve_sexe($eleve);

        /* Montants */
        $montantPaye = isset($paiement->montant_paye_usd)
            ? (float) $paiement->montant_paye_usd
            : 0.0;

        $montantAttendu = isset($paiement->montant_attendu_usd)
            ? (float) $paiement->montant_attendu_usd
            : 0.0;

        $montantPayeFormate    = number_format($montantPaye, 2, ',', ' ') . ' USD';
        $montantAttenduFormate = number_format($montantAttendu, 2, ',', ' ') . ' USD';

        /* Reste */
        $reste        = max(0.0, $montantAttendu - $montantPaye);
        $resteFormate = number_format($reste, 2, ',', ' ') . ' USD';

        /* Statut */
        $statut = 'partiel';
        if ($montantPaye <= 0) {
            $statut = 'impaye';
        } elseif ($montantAttendu > 0 && $montantPaye >= ($montantAttendu - 0.01)) {
            $statut = 'complet';
        }

        $statutLibelle = match ($statut) {
            'complet' => 'Payé',
            'partiel' => 'Partiel',
            default   => 'Impayé',
        };

        /* Date */
        $datePaiement = '—';
        $raw = $paiement->date_paiement ?? $paiement->created_at ?? null;
        if ($raw) {
            try {
                $datePaiement = Carbon::parse($raw)->format('d/m/Y');
            } catch (\Throwable) {
                $datePaiement = '—';
            }
        }

        return [
            'nomComplet'           => $nomComplet,
            'initiales'            => $initiales,
            'sexeCode'             => $sexe['code'],
            'sexeLibelle'          => $sexe['libelle'],
            'montantPaye'          => $montantPaye,
            'montantAttendu'       => $montantAttendu,
            'montantPayeFormate'   => $montantPayeFormate,
            'montantAttenduFormate' => $montantAttenduFormate,
            'reste'                => $reste,
            'resteFormate'         => $resteFormate,
            'statut'               => $statut,
            'statutLibelle'        => $statutLibelle,
            'datePaiement'         => $datePaiement,
        ];
    }
}

/* ============================================================
 | UTILITAIRES
 ============================================================ */

if (!function_exists('mois_libelle')) {
    function mois_libelle(int $numero): string
    {
        return match ($numero) {
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
            default => "Période $numero",
        };
    }
}