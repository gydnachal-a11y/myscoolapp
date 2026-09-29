<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\PaiementFraisSupplementaire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InfoPaiementScolaireService
{
    /**
     * Construit la requête de base pour les paiements principaux avec filtres.
     */
    public function getPaiementsPrincipauxQuery(Request $request): Builder
    {
        $query = Paiement::with(['eleve', 'salleClasse.section.session', 'salleClasse.option', 'anneeScolaire']);

        if ($request->filled('session')) {
            $query->whereHas('salleClasse.section', function ($q) use ($request) {
                $q->where('session_id', $request->session);
            });
        }

        if ($request->filled('section')) {
            $query->whereHas('salleClasse', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        if ($request->filled('salle')) {
            $query->where('salle_classe_id', $request->salle);
        }

        if ($request->filled('option')) {
            $query->whereHas('salleClasse', function ($q) use ($request) {
                $q->where('option_id', $request->option);
            });
        }

        if ($request->filled('mode')) {
            $query->where('type_periode', $request->mode);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('periode')) {
            $query->where('periode', $request->periode);
        }

        if ($request->filled('recherche')) {
            $search = trim($request->recherche);
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Construit la requête de base pour les frais supplémentaires avec filtres.
     */
    public function getFraisSupplementairesQuery(Request $request): Builder
    {
        $query = PaiementFraisSupplementaire::with(['eleve', 'fraisSupplementaire']);

        if ($request->filled('session')) {
            $query->whereHas('eleve.inscriptions.salleDeClasse.section', function ($q) use ($request) {
                $q->where('session_id', $request->session);
            });
        }

        if ($request->filled('section')) {
            $query->whereHas('eleve.inscriptions.salleDeClasse', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        if ($request->filled('salle')) {
            $query->whereHas('eleve.inscriptions', function ($q) use ($request) {
                $q->where('salle_classe_id', $request->salle);
            });
        }

        if ($request->filled('option')) {
            $query->whereHas('eleve.inscriptions.salleDeClasse', function ($q) use ($request) {
                $q->where('option_id', $request->option);
            });
        }

        if ($request->filled('eleve_id')) {
            $query->where('eleve_id', $request->eleve_id);
        }

        if ($request->filled('frais_supplementaire_id')) {
            $query->where('frais_supplementaire_id', $request->frais_supplementaire_id);
        }

        if ($request->filled('recherche')) {
            $search = trim($request->recherche);
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Charge les relations pour un reçu de paiement principal.
     */
    public function preparerRecuPaiementPrincipal(Paiement $paiement): Paiement
    {
        return $paiement->load(['eleve', 'anneeScolaire', 'salleClasse']);
    }

    /**
     * Charge les relations pour un reçu de frais supplémentaire.
     */
    public function preparerRecuFraisSupplementaire(PaiementFraisSupplementaire $paiement): PaiementFraisSupplementaire
    {
        return $paiement->load(['eleve', 'fraisSupplementaire']);
    }
}