<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AvanceSalaire;
use App\Models\DemandeAvance;
use App\Models\SessionAvance;
use App\Models\User;
use App\Notifications\NouvelleDemandeAvanceNotification;
use App\Services\AvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class DemandeAvanceController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const PER_PAGE               = 15;
    private const MOTIF_MIN_LENGTH       = 10;
    private const MOTIF_MAX_LENGTH       = 1000;
    private const MONTANT_MIN            = 1;
    private const STATUTS_AVANCE_ACTIVE  = ['en_attente', 'partiellement_remboursee'];
    private const ROLES_ADMIN            = ['super_admin', 'admin'];

    /** Clé de cache du compteur de notifications non vues (partagée avec DashboardController). */
    private const CACHE_KEY_NOTIFS = 'notifs_non_vues_user_';

    public function __construct(
        protected AvanceService $avanceService,
    ) {}

    // ============================================================
    // INDEX — LISTE DES DEMANDES DU MEMBRE
    // ============================================================

    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        /* ---------------------------------------------------------
         | ✅ FIX PRINCIPAL — Éteint la cloche de notification
         |
         | Marque toutes les demandes comme "vues" → le compteur
         | `notifs_non_vues_user_X` passe à 0.
         | --------------------------------------------------------- */
        $marquees = DemandeAvance::marquerToutesNotifieesVuesPour($user->id);

        if ($marquees > 0) {
            // Invalide le cache pour forcer le recalcul au prochain chargement
            Cache::forget(self::CACHE_KEY_NOTIFS . $user->id);

            Log::info('Notifications demandes marquées comme vues', [
                'user_id' => $user->id,
                'count'   => $marquees,
            ]);
        }

        /* ---------------------------------------------------------
         | 1. Demandes paginées
         | --------------------------------------------------------- */
        $demandes = DemandeAvance::where('user_id', $user->id)
            ->with(['session:id,libelle', 'traitePar:id,name', 'avance:id,montant_avance_usd'])
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE);

        /* ---------------------------------------------------------
         | 2. Compteurs par statut (1 requête groupée)
         | --------------------------------------------------------- */
        $countsRaw = DemandeAvance::where('user_id', $user->id)
            ->selectRaw(
                'COUNT(CASE WHEN statut = ? THEN 1 END) as en_attente,
                 COUNT(CASE WHEN statut = ? THEN 1 END) as validee,
                 COUNT(CASE WHEN statut = ? THEN 1 END) as refusee',
                [
                    DemandeAvance::STATUT_EN_ATTENTE,
                    DemandeAvance::STATUT_VALIDEE,
                    DemandeAvance::STATUT_REFUSEE,
                ]
            )
            ->first();

        $counts = [
            'en_attente' => (int) ($countsRaw->en_attente ?? 0),
            'validee'    => (int) ($countsRaw->validee    ?? 0),
            'refusee'    => (int) ($countsRaw->refusee    ?? 0),
        ];

        /* ---------------------------------------------------------
         | 3. Montant total demandé
         | --------------------------------------------------------- */
        $montantTotalUSD = (float) DemandeAvance::where('user_id', $user->id)
            ->sum('montant_demande_usd');

        /* ---------------------------------------------------------
         | 4. État de la session
         | --------------------------------------------------------- */
        $sessionActive     = SessionAvance::getActive();
        $aDemandeEnAttente = $counts['en_attente'] > 0;
        $peutDemander      = $sessionActive !== null && ! $aDemandeEnAttente;

        /* ---------------------------------------------------------
         | 5. Avances en cours
         | --------------------------------------------------------- */
        $avancesEnCours = AvanceSalaire::where('user_id', $user->id)
            ->whereIn('statut', self::STATUTS_AVANCE_ACTIVE)
            ->orderByDesc('date_avance')
            ->get();

        /* ---------------------------------------------------------
         | 6. Situation financière
         | --------------------------------------------------------- */
        $salaireMensuel = $this->avanceService->getSalaireMensuelUsd($user);
        $detteTotale    = $this->avanceService->getDetteTotale($user);
        $limiteEmprunt  = $this->avanceService->getLimiteEmprunt($user);
        $tauxChange     = $this->avanceService->getTauxChange();

        return view('member.demandes-avance.index', compact(
            'demandes',
            'counts',
            'montantTotalUSD',
            'sessionActive',
            'peutDemander',
            'aDemandeEnAttente',
            'avancesEnCours',
            'salaireMensuel',
            'detteTotale',
            'limiteEmprunt',
            'tauxChange'
        ));
    }

    // ============================================================
    // CREATE — FORMULAIRE
    // ============================================================

    public function create(): View|RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /* ---------------------------------------------------------
         | Vérification : session ouverte
         | --------------------------------------------------------- */
        $session = SessionAvance::getActive();
        if (! $session) {
            return redirect()
                ->route('member.demandes-avance.index')
                ->with('error', "Aucune session de demande d'avance n'est ouverte.");
        }

        /* ---------------------------------------------------------
         | Vérification : pas de demande en attente
         | --------------------------------------------------------- */
        if (DemandeAvance::where('user_id', $user->id)->enAttente()->exists()) {
            return redirect()
                ->route('member.demandes-avance.index')
                ->with('error', 'Vous avez déjà une demande en attente de traitement.');
        }

        /* ---------------------------------------------------------
         | Données financières
         | --------------------------------------------------------- */
        $salaireMensuel       = $this->avanceService->getSalaireMensuelUsd($user);
        $detteTotale          = $this->avanceService->getDetteTotale($user);
        $limiteEmprunt        = $this->avanceService->getLimiteEmprunt($user);
        $montantMaxDisponible = max(0, $limiteEmprunt - $detteTotale);
        $tauxChange           = $this->avanceService->getTauxChange();

        return view('member.demandes-avance.create', compact(
            'session',
            'salaireMensuel',
            'detteTotale',
            'limiteEmprunt',
            'montantMaxDisponible',
            'tauxChange'
        ));
    }

    // ============================================================
    // STORE — ENREGISTREMENT
    // ============================================================

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        /* ---------------------------------------------------------
         | 1. Vérification : session ouverte
         | --------------------------------------------------------- */
        $session = SessionAvance::getActive();
        if (! $session) {
            return back()
                ->withInput()
                ->with('error', "La session de demande d'avance est fermée.");
        }

        /* ---------------------------------------------------------
         | 2. Vérification : pas de demande en attente
         | --------------------------------------------------------- */
        if (DemandeAvance::where('user_id', $user->id)->enAttente()->exists()) {
            return back()
                ->withInput()
                ->with('error', 'Vous avez déjà une demande en attente de traitement.');
        }

        /* ---------------------------------------------------------
         | 3. Validation
         | --------------------------------------------------------- */
        $data = $request->validate([
            'montant_demande_usd' => [
                'required',
                'numeric',
                'min:' . self::MONTANT_MIN,
            ],
            'motif' => [
                'required',
                'string',
                'min:' . self::MOTIF_MIN_LENGTH,
                'max:' . self::MOTIF_MAX_LENGTH,
            ],
        ], [
            'montant_demande_usd.required' => 'Le montant est obligatoire.',
            'montant_demande_usd.min'      => 'Le montant doit être supérieur à 0.',
            'montant_demande_usd.numeric'  => 'Le montant doit être un nombre valide.',
            'motif.required'               => 'Le motif est obligatoire.',
            'motif.min'                    => 'Le motif doit contenir au moins :min caractères.',
            'motif.max'                    => 'Le motif ne peut pas dépasser :max caractères.',
        ]);

        $montant = (int) round((float) $data['montant_demande_usd']);

        /* ---------------------------------------------------------
         | 4. Vérification : limite d'emprunt
         | --------------------------------------------------------- */
        if (! $this->avanceService->peutEmprunter($user, $montant)) {
            return back()
                ->withInput()
                ->with('error', sprintf(
                    'Montant non autorisé. Limite : %s $, dette actuelle : %s $, disponible : %s $.',
                    number_format($this->avanceService->getLimiteEmprunt($user), 0, ',', ' '),
                    number_format($this->avanceService->getDetteTotale($user), 0, ',', ' '),
                    number_format($this->avanceService->getResteDisponible($user), 0, ',', ' ')
                ));
        }

        $taux = $this->avanceService->getTauxChange();

        /* ---------------------------------------------------------
         | 5. Création (transaction)
         | --------------------------------------------------------- */
        try {
            $demande = DB::transaction(function () use ($user, $session, $montant, $data, $taux): DemandeAvance {
                $demande = DemandeAvance::create([
                    'user_id'             => $user->id,
                    'session_avance_id'   => $session->id,
                    'montant_demande_usd' => $montant,
                    'montant_demande_fc'  => (int) round($montant * $taux),
                    'taux_applique'       => $taux,
                    'motif'               => $data['motif'],
                    'statut'              => DemandeAvance::STATUT_EN_ATTENTE,
                ]);

                /* -----------------------------------------
                 | Notification aux admins
                 | ----------------------------------------- */
                $admins = User::whereHas('roles', function ($q): void {
                    $q->whereIn('name', self::ROLES_ADMIN);
                })->get();

                if ($admins->isNotEmpty()) {
                    Notification::send(
                        $admins,
                        new NouvelleDemandeAvanceNotification($user, $montant, $demande->id)
                    );
                }

                return $demande;
            });

            /* ---------------------------------------------------------
             | 6. ✅ Invalide le cache de notifications du user
             |
             | Sa nouvelle demande n'a pas encore été vue → le badge
             | doit apparaître au prochain chargement du dashboard.
             | --------------------------------------------------------- */
            Cache::forget(self::CACHE_KEY_NOTIFS . $user->id);

            /* ---------------------------------------------------------
             | 7. Log HORS transaction
             | --------------------------------------------------------- */
            Log::info('Demande d\'avance soumise', [
                'demande_id'  => $demande->id,
                'user_id'     => $user->id,
                'montant_usd' => $montant,
                'session_id'  => $session->id,
            ]);

            return redirect()
                ->route('member.demandes-avance.index')
                ->with('success', 'Votre demande a été soumise avec succès. Vous serez notifié dès son traitement.');

        } catch (Throwable $e) {
            Log::error('Erreur soumission demande d\'avance', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la soumission. Veuillez réessayer.');
        }
    }
}