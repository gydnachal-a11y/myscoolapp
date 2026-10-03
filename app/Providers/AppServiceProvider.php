<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Annonce;
use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\DemandeAvance;
use App\Models\Eleve;
use App\Models\SiteSetting;
use App\Models\User;
use App\Observers\HomeCacheObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    // ============================================================
    // CONFIGURATION
    // ============================================================

    /** Durée de vie des compteurs (badges, messages, demandes). */
    private const COUNTERS_CACHE_TTL = 30;

    /** Environnements où le mode strict Eloquent est activé. */
    private const STRICT_ENVS = ['local', 'testing'];

    /** Clés de cache (centralisées avec sprintf). */
    private const CACHE_KEY_ADMIN_MESSAGES   = 'admin_unread_messages';
    private const CACHE_KEY_ADMIN_DEMANDES   = 'admin_demandes_avance_en_attente';
    private const CACHE_KEY_USER_ANNONCES    = 'user_%d_annonces_non_lues';
    private const CACHE_KEY_CONTACT_MESSAGES = 'contact_%d_unread_messages';

    /** Modèles dont la modification impacte la home. */
    private const HOME_OBSERVED_MODELS = [
        Eleve::class,
        User::class,
        Annonce::class,
        SiteSetting::class,
    ];

    /** Rôles considérés comme admin pour les badges. */
    private const ADMIN_ROLES = ['admin', 'super_admin'];

    // ============================================================
    // LIFECYCLE
    // ============================================================

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureDatabase();
        $this->configureModels();
        $this->configureUrl();
        $this->configureRateLimiting();
        $this->configurePasswordRules();
        $this->registerObservers();
        $this->registerViewComposers();
    }

    // ============================================================
    // 1. BASE DE DONNÉES
    // ============================================================

    private function configureDatabase(): void
    {
        // Compat MySQL < 5.7.7 / vieux MariaDB
        Schema::defaultStringLength(191);
    }

    // ============================================================
    // 2. MODÈLES ELOQUENT (mode strict)
    // ============================================================

    /**
     * ✅ Détecte les N+1 (preventLazyLoading)
     * ✅ Détecte les typos dans $fillable (preventSilentlyDiscardingAttributes)
     * ❌ NE PAS activer preventAccessingMissingAttributes : casse toutes les stats
     *    utilisant `->with('rel')->select('col1', 'col2')` sans la FK.
     *    → Utiliser `->withoutEagerLoads()` dans les stats.
     */
    private function configureModels(): void
    {
        if (! $this->app->environment(self::STRICT_ENVS)) {
            return;
        }

        Model::preventLazyLoading(true);
        Model::preventSilentlyDiscardingAttributes(true);
        Model::preventAccessingMissingAttributes(false);
    }

    // ============================================================
    // 3. URL
    // ============================================================

    private function configureUrl(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    // ============================================================
    // 4. RATE LIMITING
    // ============================================================

    private function configureRateLimiting(): void
    {
        // Login : 5 tentatives / minute par email+IP
        RateLimiter::for('login', static function (Request $request): Limit {
            $key = mb_strtolower((string) $request->input('email')) . '|' . $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // API : 60 req/min par utilisateur authentifié (ou IP)
        RateLimiter::for('api', static function (Request $request): Limit {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Actions sensibles (reset password, contact…)
        RateLimiter::for('sensitive', static function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->ip());
        });
    }

    // ============================================================
    // 5. RÈGLES DE MOT DE PASSE
    // ============================================================

    private function configurePasswordRules(): void
    {
        Password::defaults(function (): Password {
            return $this->app->isProduction()
                ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(8);
        });
    }

    // ============================================================
    // 6. OBSERVERS
    // ============================================================

    private function registerObservers(): void
    {
        foreach (self::HOME_OBSERVED_MODELS as $model) {
            $model::observe(HomeCacheObserver::class);
        }
    }

    // ============================================================
    // 7. VIEW COMPOSERS
    // ============================================================

    private function registerViewComposers(): void
    {
        // ─── Site settings (toutes les vues) ───
        View::composer('*', function ($view): void {
            $view->with('siteSettings', once(fn (): object => $this->safeSiteSettings()));
        });

        // ─── Layout admin ───
        View::composer('layouts.admin', function ($view): void {
            $user            = Auth::guard('web')->user();
            $isAuthenticated = $user instanceof User;
            $isAdmin         = $isAuthenticated && $this->isAdmin($user);

            $currentRoute   = request()->route()?->getName();
            $onMessagesPage = $currentRoute === 'admin.messages.index';

            $view->with([
                'annoncesNonLues' => $isAuthenticated
                    ? $this->countAnnoncesNonLues($user)
                    : 0,

                'unreadMessages' => ($isAdmin && ! $onMessagesPage)
                    ? $this->countAdminUnreadMessages()
                    : 0,

                'demandesAvanceEnAttente' => $isAdmin
                    ? $this->countDemandesAvanceEnAttente()
                    : 0,
            ]);
        });

        // ─── Layout contact ───
        View::composer('layouts.contact', function ($view): void {
            $contact = Auth::guard('contact')->user();

            $view->with([
                'contactUnreadMessages' => $contact instanceof Contact
                    ? $this->countContactUnreadMessages($contact)
                    : 0,
            ]);
        });
    }

    // ============================================================
    // HELPERS PRIVÉS — SITE SETTINGS
    // ============================================================

    private function safeSiteSettings(): object
    {
        try {
            return SiteSetting::getSettings();
        } catch (Throwable) {
            return (object) SiteSetting::getDefaults();
        }
    }

    // ============================================================
    // HELPERS PRIVÉS — ADMIN
    // ============================================================

    /**
     * Détecte si l'utilisateur est admin.
     * Mis en cache runtime via `once()` → 1 seul appel par requête.
     */
    private function isAdmin(User $user): bool
    {
        return once(function () use ($user): bool {
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            return $user->hasRole(self::ADMIN_ROLES);
        });
    }

    // ============================================================
    // HELPERS PRIVÉS — COMPTEURS (CACHE 30s)
    // ============================================================

    private function countAnnoncesNonLues(User $user): int
    {
        return Cache::remember(
            sprintf(self::CACHE_KEY_USER_ANNONCES, $user->id),
            self::COUNTERS_CACHE_TTL,
            static fn (): int => Annonce::query()
                ->where('est_active', true)
                ->whereDoesntHave('lecteurs', static function ($query) use ($user): void {
                    $query->where('user_id', $user->id)
                          ->whereNotNull('lu_a');
                })
                ->count()
        );
    }

    private function countAdminUnreadMessages(): int
    {
        return Cache::remember(
            self::CACHE_KEY_ADMIN_MESSAGES,
            self::COUNTERS_CACHE_TTL,
            static fn (): int => ContactMessage::query()
                ->where('from_admin', false)
                ->where(static function ($q): void {
                    $q->where('lu', false)->orWhereNull('lu');
                })
                ->count()
        );
    }

    private function countContactUnreadMessages(Contact $contact): int
    {
        return Cache::remember(
            sprintf(self::CACHE_KEY_CONTACT_MESSAGES, $contact->id),
            self::COUNTERS_CACHE_TTL,
            static fn (): int => ContactMessage::query()
                ->where('contact_id', $contact->id)
                ->where('from_admin', true)
                ->where(static function ($q): void {
                    $q->where('lu', false)->orWhereNull('lu');
                })
                ->count()
        );
    }

    private function countDemandesAvanceEnAttente(): int
    {
        return Cache::remember(
            self::CACHE_KEY_ADMIN_DEMANDES,
            self::COUNTERS_CACHE_TTL,
            static fn (): int => DemandeAvance::enAttente()->count()
        );
    }
}