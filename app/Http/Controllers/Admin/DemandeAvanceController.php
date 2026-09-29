<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeAvance;
use App\Models\MoisScolaire;
use App\Notifications\DemandeAvanceRefuseeNotification;
use App\Notifications\DemandeAvanceValideeNotification;
use App\Services\AvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DemandeAvanceController extends Controller
{
    public function __construct(protected AvanceService $avanceService) {}

    // ============================================================
    // LISTE DES DEMANDES
    // ============================================================

    /**
     * Liste paginée des demandes filtrées par statut.
     */
    public function index(Request $request): View
    {
        $statut = $request->get('statut', DemandeAvance::STATUT_EN_ATTENTE);

        // Validation du statut
        if (!in_array($statut, [
            DemandeAvance::STATUT_EN_ATTENTE,
            DemandeAvance::STATUT_VALIDEE,
            DemandeAvance::STATUT_REFUSEE,
        ], true)) {
            $statut = DemandeAvance::STATUT_EN_ATTENTE;
        }

        // Requête avec relations
        $query = DemandeAvance::with(['user', 'session', 'traitePar', 'avance'])
            ->where('statut', $statut);

        // Recherche par nom/email du membre
        if ($request->filled('recherche')) {
            $search = trim($request->recherche);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Total pour le pied de tableau
        $montantTotalUSD = (float) (clone $query)->sum('montant_demande_usd');

        $demandes = $query->orderByDesc('created_at')
            ->paginate(20)
            ->appends($request->query());

        // Compteurs globaux
        $counts = [
            'en_attente' => DemandeAvance::enAttente()->count(),
            'validee'    => DemandeAvance::validee()->count(),
            'refusee'    => DemandeAvance::refusee()->count(),
        ];

        // Données contextuelles
        $tauxChange    = $this->avanceService->getTauxChange();
        $moisScolaires = MoisScolaire::orderBy('mois')->get();

        return view('admin.demandes-avance.index', compact(
            'demandes',
            'statut',
            'counts',
            'montantTotalUSD',
            'tauxChange',
            'moisScolaires'
        ));
    }

    // ============================================================
    // DÉTAIL D'UNE DEMANDE
    // ============================================================

    /**
     * Affiche le détail d'une demande avec les données du membre.
     */
    public function show(DemandeAvance $demande): View
    {
        $demande->load(['user', 'session', 'traitePar', 'avance.moisScolaire']);

        $user          = $demande->user;
        $moisScolaires = MoisScolaire::orderBy('mois')->get();

        // Si l'utilisateur a été supprimé, on renvoie des valeurs par défaut
        if (!$user) {
            return view('admin.demandes-avance.show', [
                'demande'       => $demande,
                'salaire'       => 0,
                'detteTotale'   => 0,
                'limite'        => 0,
                'moisScolaires' => $moisScolaires,
            ]);
        }

        $salaire     = $this->avanceService->getSalaireMensuelUsd($user);
        $detteTotale = $this->avanceService->getDetteTotale($user);
        $limite      = $this->avanceService->getLimiteEmprunt($user);

        return view('admin.demandes-avance.show', compact(
            'demande',
            'salaire',
            'detteTotale',
            'limite',
            'moisScolaires'
        ));
    }

    // ============================================================
    // VALIDATION D'UNE DEMANDE
    // ============================================================

    /**
     * Valide une demande et crée l'avance automatiquement.
     */
    public function valider(Request $request, DemandeAvance $demande): RedirectResponse
    {
        // Vérification du statut
        if (!$demande->isEnAttente()) {
            return $this->redirectToList('error', 'Cette demande a déjà été traitée.');
        }

        // Vérification de l'utilisateur
        if (!$demande->user) {
            return $this->redirectToList('error', 'Impossible de valider : l\'utilisateur n\'existe plus.');
        }

        // Validation des données
        $data = $request->validate([
            'mois_scolaire_id' => 'required|exists:mois_scolaires,id',
            'date_avance'      => 'required|date',
            'commentaire'      => 'nullable|string|max:500',
        ], [
            'mois_scolaire_id.required' => 'Le mois scolaire est obligatoire.',
            'mois_scolaire_id.exists'   => 'Le mois scolaire sélectionné est invalide.',
            'date_avance.required'      => 'La date de l\'avance est obligatoire.',
            'date_avance.date'          => 'La date de l\'avance doit être une date valide.',
            'commentaire.max'           => 'Le commentaire ne peut pas dépasser 500 caractères.',
        ]);

        try {
            $avance = DB::transaction(function () use ($demande, $data) {
                // Création de l'avance via le service (règles métier incluses)
                $avance = $this->avanceService->creerAvance($demande->user, [
                    'montant_avance_usd' => $demande->montant_demande_usd,
                    'mois_scolaire_id'   => $data['mois_scolaire_id'],
                    'date_avance'        => $data['date_avance'],
                    'motif'              => $demande->motif,
                    'commentaire'        => $data['commentaire'] ?? 'Validée via demande membre.',
                ]);

                // Mise à jour de la demande
                $demande->update([
                    'statut'     => DemandeAvance::STATUT_VALIDEE,
                    'traite_par' => auth()->id(),
                    'traite_le'  => now(),
                    'avance_id'  => $avance->id,
                ]);

                // Notification du membre
                $demande->user->notify(new DemandeAvanceValideeNotification($demande));

                return $avance;
            });

            // Log HORS transaction (pour garantir la cohérence)
            Log::info('Demande d\'avance validée', [
                'demande_id'   => $demande->id,
                'avance_id'    => $avance->id,
                'admin_id'     => auth()->id(),
                'montant_usd'  => $demande->montant_demande_usd,
            ]);

            return $this->redirectToList('success', 'Demande validée et avance créée automatiquement.');

        } catch (\DomainException $e) {
            // Erreur métier (limite dépassée, etc.)
            Log::warning('Validation refusée par règle métier', [
                'demande_id' => $demande->id,
                'raison'     => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            // Erreur technique imprévue
            Log::error('Erreur validation demande d\'avance', [
                'demande_id' => $demande->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la validation. Veuillez réessayer.');
        }
    }

    // ============================================================
    // REFUS D'UNE DEMANDE
    // ============================================================

    /**
     * Refuse une demande avec motif obligatoire.
     */
    public function refuser(Request $request, DemandeAvance $demande): RedirectResponse
    {
        // Vérification du statut
        if (!$demande->isEnAttente()) {
            return $this->redirectToList('error', 'Cette demande a déjà été traitée.');
        }

        // Validation des données
        $data = $request->validate([
            'motif_refus' => 'required|string|min:10|max:1000',
        ], [
            'motif_refus.required' => 'Le motif de refus est obligatoire.',
            'motif_refus.min'      => 'Le motif doit contenir au moins 10 caractères.',
            'motif_refus.max'      => 'Le motif ne peut pas dépasser 1000 caractères.',
        ]);

        try {
            DB::transaction(function () use ($demande, $data) {
                $demande->update([
                    'statut'      => DemandeAvance::STATUT_REFUSEE,
                    'motif_refus' => $data['motif_refus'],
                    'traite_par'  => auth()->id(),
                    'traite_le'   => now(),
                ]);

                // Notification du membre
                if ($demande->user) {
                    $demande->user->notify(new DemandeAvanceRefuseeNotification($demande));
                }
            });

            // Log HORS transaction
            Log::info('Demande d\'avance refusée', [
                'demande_id' => $demande->id,
                'admin_id'   => auth()->id(),
                'motif'      => $data['motif_refus'],
            ]);

            return $this->redirectToList('success', 'Demande refusée. Le membre a été notifié.');

        } catch (\Throwable $e) {
            Log::error('Erreur refus demande d\'avance', [
                'demande_id' => $demande->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors du refus. Veuillez réessayer.');
        }
    }

    // ============================================================
    // MÉTHODE PRIVÉE : REDIRECTION VERS LA LISTE
    // ============================================================

    /**
     * Redirige vers la liste des demandes sur l'onglet "En attente".
     *
     * @param  string  $type  'success' ou 'error'
     * @param  string  $message
     */
    private function redirectToList(string $type, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.demandes-avance.index', ['statut' => DemandeAvance::STATUT_EN_ATTENTE])
            ->with($type, $message);
    }
}