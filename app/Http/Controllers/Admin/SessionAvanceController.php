<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeAvance;
use App\Models\SessionAvance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SessionAvanceController extends Controller
{
    // ============================================================
    // LISTE DES SESSIONS
    // ============================================================

    /**
     * Affiche la liste paginée des sessions.
     */
    public function index(Request $request): View
    {
        $statut = $request->get('statut');      // ouverte / fermee / expiree / a_venir
        $search = $request->get('recherche');

        $query = SessionAvance::query()
            ->with('createur')
            ->withCount([
                'demandes',
                'demandes as demandes_attente_count' => fn ($q) => $q->enAttente(),
            ]);

        // Filtre par statut
        if ($statut) {
            $query->where(function ($q) use ($statut) {
                match ($statut) {
                    'ouverte' => $q->where('est_active', true)
                                   ->whereDate('date_debut', '<=', today())
                                   ->whereDate('date_fin', '>=', today()),
                    'fermee'  => $q->where('est_active', false),
                    'expiree' => $q->whereDate('date_fin', '<', today()),
                    'a_venir' => $q->whereDate('date_debut', '>', today()),
                    default   => $q,
                };
            });
        }

        // Filtre par recherche
        if ($search) {
            $query->where('libelle', 'LIKE', "%{$search}%");
        }

        $sessions = $query->latest('date_debut')
            ->paginate(20)
            ->appends($request->query());

        $sessionActive = SessionAvance::getActive();

        // ============================================================
        // Statistiques globales — 4 requêtes légères (COUNT uniquement)
        // au lieu de charger TOUTES les sessions en mémoire.
        // ============================================================
        $stats = [
            'total'    => SessionAvance::count(),
            'ouverte'  => $sessionActive ? 1 : 0,
            'fermees'  => SessionAvance::where('est_active', false)->count(),
            'demandes' => DemandeAvance::whereNotNull('session_avance_id')->count(),
            'attente'  => DemandeAvance::enAttente()->whereNotNull('session_avance_id')->count(),
        ];

        return view('admin.session-avances.index', compact(
            'sessions',
            'sessionActive',
            'stats',
            'statut',
            'search'
        ));
    }

    // ============================================================
    // CRÉATION
    // ============================================================

    /**
     * Affiche le formulaire de création d'une session.
     */
    public function create(): View
    {
        return view('admin.session-avances.create');
    }

    /**
     * Enregistre une nouvelle session.
     */
    public function store(Request $request): RedirectResponse
    {
        // ============================================================
        // VALIDATION
        // ============================================================
        $data = $request->validate([
            'libelle'    => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
        ], [
            'libelle.required'        => 'Le libellé est obligatoire.',
            'libelle.max'             => 'Le libellé ne peut pas dépasser 255 caractères.',
            'date_debut.required'     => 'La date de début est obligatoire.',
            'date_debut.date'         => 'La date de début doit être une date valide.',
            'date_fin.required'       => 'La date de fin est obligatoire.',
            'date_fin.date'           => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        // ============================================================
        // VÉRIFICATIONS MÉTIER
        // ============================================================

        // 1. Une seule session ouverte à la fois
        if (SessionAvance::active()->exists()) {
            return back()
                ->withInput()
                ->with('error', "Une session est déjà ouverte. Fermez-la avant d'en créer une nouvelle.");
        }

        // 2. Empêcher le chevauchement de dates avec une session existante
        $chevauchement = SessionAvance::where(function ($q) use ($data) {
            $q->whereBetween('date_debut', [$data['date_debut'], $data['date_fin']])
              ->orWhereBetween('date_fin', [$data['date_debut'], $data['date_fin']])
              ->orWhere(function ($q2) use ($data) {
                  $q2->where('date_debut', '<=', $data['date_debut'])
                     ->where('date_fin', '>=', $data['date_fin']);
              });
        })->exists();

        if ($chevauchement) {
            return back()
                ->withInput()
                ->with('error', 'Les dates de cette session chevauchent une session existante.');
        }

        // ============================================================
        // CRÉATION
        // ============================================================
        try {
            $session = DB::transaction(function () use ($data) {
                $data['created_by'] = auth()->id();
                $data['est_active'] = true;

                return SessionAvance::create($data);
            });

            Log::info("Session d'avance créée", [
                'session_id' => $session->id,
                'libelle'    => $session->libelle,
                'date_debut' => $session->date_debut,
                'date_fin'   => $session->date_fin,
                'admin_id'   => auth()->id(),
            ]);

            return redirect()
                ->route('admin.session-avances.index')
                ->with('success', "Session « {$session->libelle} » créée avec succès.");

        } catch (\Throwable $e) {
            Log::error("Erreur lors de la création d'une session d'avance", [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création. Veuillez réessayer.');
        }
    }

    // ============================================================
    // DÉTAIL
    // ============================================================

    /**
     * Affiche le détail d'une session avec ses demandes.
     *
     * ⚠️ Le paramètre doit s'appeler $session_avance pour matcher
     * la route resource admin/session-avances/{session_avance}.
     */
    public function show(Request $request, SessionAvance $session_avance): View
    {
        $session_avance->load('createur');

        $statut = $request->get('statut');

        $demandesQuery = $session_avance->demandes()
            ->with(['user', 'traitePar'])
            ->orderByDesc('created_at');

        if ($statut && in_array($statut, ['en_attente', 'validee', 'refusee'], true)) {
            $demandesQuery->where('statut', $statut);
        }

        $demandes = $demandesQuery->paginate(20)->appends($request->query());

        // ============================================================
        // Statistiques de la session — 1 seule requête SQL agrégée
        // (au lieu de 5 requêtes séparées)
        // ============================================================
        $raw = $session_avance->demandes()
            ->selectRaw('
                COUNT(*) AS total,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) AS en_attente,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) AS validee,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) AS refusee,
                COALESCE(SUM(montant_demande_usd), 0) AS montant_total
            ', ['en_attente', 'validee', 'refusee'])
            ->first();

        $sessionStats = [
            'total'         => (int) ($raw->total ?? 0),
            'en_attente'    => (int) ($raw->en_attente ?? 0),
            'validee'       => (int) ($raw->validee ?? 0),
            'refusee'       => (int) ($raw->refusee ?? 0),
            'montant_total' => (float) ($raw->montant_total ?? 0),
        ];

        return view('admin.session-avances.show', [
            'session'      => $session_avance,
            'demandes'     => $demandes,
            'sessionStats' => $sessionStats,
            'statut'       => $statut,
        ]);
    }

    // ============================================================
    // FERMETURE
    // ============================================================

    /**
     * Ferme manuellement une session ouverte.
     *
     * ⚠️ Le paramètre doit s'appeler $session_avance pour matcher
     * la route PATCH admin/session-avances/{session_avance}/fermer.
     */
    public function fermer(SessionAvance $session_avance): RedirectResponse
    {
        if (!$session_avance->est_active) {
            return back()->with('info', 'Cette session est déjà fermée.');
        }

        try {
            DB::transaction(function () use ($session_avance) {
                $session_avance->update(['est_active' => false]);
            });

            Log::info("Session d'avance fermée", [
                'session_id' => $session_avance->id,
                'libelle'    => $session_avance->libelle,
                'admin_id'   => auth()->id(),
            ]);

            return back()->with('success', "Session « {$session_avance->libelle} » fermée avec succès.");

        } catch (\Throwable $e) {
            Log::error("Erreur lors de la fermeture d'une session d'avance", [
                'session_id' => $session_avance->id,
                'error'      => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Une erreur est survenue lors de la fermeture. Veuillez réessayer.');
        }
    }
}