<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Contact;
use App\Models\CourSalle;
use App\Models\Devise;
use App\Models\Fonction;
use App\Models\Role;
use App\Models\SalaireHoraire;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const PER_PAGE = 15;
    private const DEFAULT_HOURLY_RATE_USD = 3.0;
    private const DEFAULT_EXCHANGE_RATE = 2800;
    private const PHOTO_DIRECTORY = 'personnel/photos';

    /** Clé de cache pour le taux de change. */
    private const CACHE_KEY_EXCHANGE_RATE = 'admin_exchange_rate_usd_cdf';

    // ============================================================
    // INDEX — LISTE DU PERSONNEL
    // ============================================================

    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['fonction', 'section', 'roles', 'contact:id,nom,email'])
            ->search($request->input('search'))
            ->ofRole($request->input('role'))
            ->when($request->filled('fonction_id'), fn ($q) => $q->where('fonction_id', $request->integer('fonction_id')))
            ->when($request->filled('section_id'),  fn ($q) => $q->where('section_id', $request->integer('section_id')))
            ->when($request->input('contact_link') === 'with',    fn ($q) => $q->withContactAccount())
            ->when($request->input('contact_link') === 'without', fn ($q) => $q->withoutContactAccount())
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $fonctions = Fonction::orderBy('nom')->get();
        $sections  = Section::orderBy('nom')->get();
        $roles     = Role::orderBy('name')->get(['id', 'name', 'label']);

        $totalPersonnel = User::count();
        $totalWithContact = User::withContactAccount()->count();

        $statsParSection = Section::withCount('users')->orderBy('nom')->get();

        return view('admin.users.index', compact(
            'users',
            'fonctions',
            'sections',
            'roles',
            'totalPersonnel',
            'totalWithContact',
            'statsParSection',
        ));
    }

    // ============================================================
    // CREATE — FORMULAIRE DE CRÉATION
    // ============================================================

    public function create(): View
    {
        $fonctions = Fonction::orderBy('nom')->get();
        $sections  = Section::orderBy('nom')->get();
        $roles     = Role::orderBy('name')->get(['id', 'name', 'label']);

        // ✅ Contacts disponibles pour liaison (exclut ceux déjà liés)
        $contacts = $this->availableContacts();

        return view('admin.users.create', compact(
            'fonctions',
            'sections',
            'roles',
            'contacts',
        ));
    }

    // ============================================================
    // STORE — ENREGISTREMENT
    // ============================================================

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $user = DB::transaction(function () use ($request): User {
                $data = $request->validated();

                // ✅ Le matricule est généré côté serveur, jamais accepté du client
                unset($data['matricule']);
                $data['matricule'] = User::generateUniqueMatricule();

                Log::info('Création personnel', [
                    'email_hash' => $this->hashValue($data['email'] ?? ''),
                    'matricule'  => $data['matricule'],
                    'has_contact' => ! empty($data['contact_id']),
                    'actor_id'   => auth()->id(),
                ]);

                // Upload de la photo
                if ($request->hasFile('photo')) {
                    $path = $this->storePhoto($request->file('photo'));
                    if ($path) {
                        $data['photo'] = $path;
                    }
                }

                $user = User::create($data);

                // Synchronisation des rôles many-to-many
                $roleIds = $request->filled('roles')
                    ? Role::filterExistingIds($request->input('roles'))
                    : [];

                if (! empty($roleIds)) {
                    $user->roles()->sync($roleIds);
                }

                // Synchronisation de la colonne `role`
                $this->syncRoleColumn($user, $roleIds, $data['role'] ?? null, persist: false);

                return $user;
            });

            Log::info('Personnel créé', [
                'user_id'    => $user->id,
                'matricule'  => $user->matricule,
                'contact_id' => $user->contact_id,
                'actor_id'   => auth()->id(),
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Personnel créé avec succès.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());

        } catch (Throwable $e) {
            Log::error('Échec création personnel', [
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création.');
        }
    }

    // ============================================================
    // SHOW — DÉTAIL D'UN MEMBRE
    // ============================================================

    public function show(User $user): View
    {
        $user->load(['fonction', 'section', 'roles', 'contact']);

        $assignations = CourSalle::query()
            ->with(['salle', 'cour', 'nombreHeure'])
            ->where('titulaire_id', $user->id)
            ->get();

        $heuresTotales = $assignations->sum(fn ($a) => (int) ($a->nombreHeure->valeur ?? 0));

        $salaireHoraire = SalaireHoraire::query()
            ->where('actif', true)
            ->orderByDesc('updated_at')
            ->first();

        $tauxHoraireUsd = $salaireHoraire
            ? (float) $salaireHoraire->taux_usd
            : self::DEFAULT_HOURLY_RATE_USD;

        $tauxChange = $this->getExchangeRate();

        $salaireAutoBaseUsd = $heuresTotales * $tauxHoraireUsd;
        $salaireAutoBaseFc  = $salaireAutoBaseUsd * $tauxChange;

        return view('admin.users.show', compact(
            'user',
            'assignations',
            'heuresTotales',
            'tauxHoraireUsd',
            'tauxChange',
            'salaireAutoBaseUsd',
            'salaireAutoBaseFc',
        ));
    }

    // ============================================================
    // EDIT — FORMULAIRE DE MODIFICATION
    // ============================================================

    public function edit(User $user): View
    {
        $fonctions = Fonction::orderBy('nom')->get();
        $sections  = Section::orderBy('nom')->get();
        $roles     = Role::orderBy('name')->get(['id', 'name', 'label']);
        $userRoles = $user->roles->pluck('id')->toArray();

        // ✅ Contacts disponibles + le contact actuellement lié (même s'il est pris)
        $contacts = $this->availableContacts($user);

        return view('admin.users.edit', compact(
            'user',
            'fonctions',
            'sections',
            'roles',
            'userRoles',
            'contacts',
        ));
    }

    // ============================================================
    // UPDATE — MISE À JOUR
    // ============================================================

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $updated = DB::transaction(function () use ($request, $user): User {
                $data = $request->validated();

                Log::info('Mise à jour personnel', [
                    'user_id'    => $user->id,
                    'email_hash' => $this->hashValue($data['email'] ?? $user->email),
                    'actor_id'   => auth()->id(),
                ]);

                // Le matricule n'est jamais modifiable
                unset($data['matricule']);

                // Mot de passe optionnel
                if (empty($data['password'])) {
                    unset($data['password']);
                }

                // Photo : supprime l'ancienne si nouvelle
                if ($request->hasFile('photo')) {
                    $this->deletePhotoSilently($user->photo);

                    $path = $this->storePhoto($request->file('photo'));
                    if ($path) {
                        $data['photo'] = $path;
                    }
                }

                $user->fill($data)->save();

                // Synchronisation des rôles
                $roleIds = $request->filled('roles')
                    ? Role::filterExistingIds($request->input('roles'))
                    : [];

                $user->roles()->sync($roleIds);

                // Synchronisation de la colonne `role`
                $this->syncRoleColumn($user, $roleIds, $data['role'] ?? null, persist: false);

                return $user->fresh();
            });

            Log::info('Personnel mis à jour', [
                'user_id'    => $updated->id,
                'matricule'  => $updated->matricule,
                'contact_id' => $updated->contact_id,
                'actor_id'   => auth()->id(),
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Personnel mis à jour avec succès.');

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());

        } catch (Throwable $e) {
            Log::error('Échec mise à jour personnel', [
                'user_id'  => $user->id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // DESTROY — SUPPRESSION
    // ============================================================

    public function destroy(User $user): RedirectResponse
    {
        try {
            // Sécurité : ne peut pas se supprimer soi-même
            if ($user->id === auth()->id()) {
                return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            }

            if ($user->coursEnseignes()->exists()) {
                return back()->with('error', 'Cet utilisateur est titulaire de cours. Supprimez d\'abord les assignations.');
            }

            if ($user->paiementSalaires()->exists()) {
                return back()->with('error', 'Cet utilisateur a des paiements de salaire associés.');
            }

            // Suppression de la photo
            $this->deletePhotoSilently($user->photo);

            DB::transaction(function () use ($user): void {
                $user->roles()->detach();
                $user->delete();
            });

            Log::info('Personnel supprimé', [
                'user_id'   => $user->id,
                'matricule' => $user->matricule,
                'actor_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Personnel supprimé avec succès.');

        } catch (Throwable $e) {
            Log::error('Échec suppression personnel', [
                'user_id'  => $user->id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * ✅ Retourne les contacts disponibles pour liaison.
     *
     * Exclut les contacts déjà liés à un autre User.
     * Si `$currentUser` est fourni, inclut son contact actuel.
     */
    private function availableContacts(?User $currentUser = null): \Illuminate\Support\Collection
    {
        $linkedIds = User::query()
            ->whereNotNull('contact_id')
            ->when($currentUser, fn ($q) => $q->where('id', '!=', $currentUser->id))
            ->pluck('contact_id');

        return Contact::query()
            ->where(function ($q) use ($linkedIds, $currentUser): void {
                $q->whereNotIn('id', $linkedIds);

                if ($currentUser?->contact_id) {
                    $q->orWhere('id', $currentUser->contact_id);
                }
            })
            ->orderBy('nom')
            ->get(['id', 'nom', 'email']);
    }

    /**
     * Stocke une photo dans le disque public.
     */
    private function storePhoto($file): ?string
    {
        try {
            $path = $file->store(self::PHOTO_DIRECTORY, 'public');

            return $path ?: null;
        } catch (Throwable $e) {
            Log::error('Échec stockage photo', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Supprime une photo silencieusement (sans planter si absente).
     */
    private function deletePhotoSilently(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable $e) {
            Log::warning('Échec suppression photo', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Récupère le taux de change USD → CDF (avec cache 1h).
     */
    private function getExchangeRate(): float
    {
        return cache()->remember(
            self::CACHE_KEY_EXCHANGE_RATE,
            now()->addHour(),
            function (): float {
                $deviseSource = Devise::where('code', 'USD')->first();
                $deviseCible  = Devise::where('code', 'CDF')->first();

                if ($deviseSource && $deviseCible) {
                    return (float) $deviseSource->tauxVers($deviseCible);
                }

                return self::DEFAULT_EXCHANGE_RATE;
            }
        );
    }

    /**
     * Met à jour la colonne `role` de l'utilisateur.
     *
     * Priorité :
     *   1. Rôle principal fourni par le formulaire
     *   2. Premier rôle many-to-many
     *   3. Fallback : 'secretaire'
     *
     * @param bool $persist Si false, la sauvegarde est déléguée à l'appelant
     *                      (utile dans une transaction).
     */
    private function syncRoleColumn(
        User $user,
        array $roleIds,
        ?string $roleFromRequest = null,
        bool $persist = true,
    ): void {
        if ($roleFromRequest) {
            $user->role = $roleFromRequest;
        } elseif (! empty($roleIds)) {
            $primaryRole = Role::find($roleIds[0]);
            if ($primaryRole) {
                $user->role = $primaryRole->name;
            }
        } else {
            $user->role = User::ROLE_SECRETAIRE;
        }

        if ($persist) {
            $user->save();
        }
    }

    /**
     * Hash stable d'une valeur sensible (logs PII-safe).
     */
    private function hashValue(?string $value): string
    {
        if (! $value) {
            return '';
        }

        return substr(hash('sha256', mb_strtolower($value)), 0, 16);
    }
}