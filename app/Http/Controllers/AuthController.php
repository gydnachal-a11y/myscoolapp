<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const MAX_ATTEMPTS     = 5;
    private const DECAY_MINUTES    = 1;
    private const GUARD            = 'web';
    private const DEFAULT_PASSWORD_MIN = 6;

    // ============================================================
    // FORMULAIRE
    // ============================================================

    public function showLoginForm(): View
    {
        // Si déjà connecté → redirection directe
        if (Auth::guard(self::GUARD)->check()) {
            return redirect()->to($this->redirectTo());
        }

        return view('auth.login');
    }

    // ============================================================
    // CONNEXION
    // ============================================================

    public function login(Request $request): RedirectResponse
    {
        /* ---------------------------------------------------------
         | 1. Validation
         | --------------------------------------------------------- */
        $validated = $request->validate([
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:' . self::DEFAULT_PASSWORD_MIN],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'login.required'    => 'Veuillez saisir votre identifiant.',
            'password.required' => 'Veuillez saisir votre mot de passe.',
            'password.min'      => 'Le mot de passe doit contenir au moins :min caractères.',
        ]);

        /* ---------------------------------------------------------
         | 2. Normalisation + détection email/téléphone
         | --------------------------------------------------------- */
        $login = trim($validated['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

        if ($field === 'email') {
            $login = mb_strtolower($login);
        } else {
            $login = preg_replace('/\s+/', '', $login);
        }

        /* ---------------------------------------------------------
         | 3. Rate limiting (login + IP)
         | --------------------------------------------------------- */
        $throttleKey = mb_strtolower($login) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('Auth: trop de tentatives de connexion', [
                'login_hash' => $this->hashValue($login),
                'ip'         => $request->ip(),
                'seconds'    => $seconds,
            ]);

            throw ValidationException::withMessages([
                'login' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        /* ---------------------------------------------------------
         | 4. Tentative de connexion (guard web explicite)
         | --------------------------------------------------------- */
        $credentials = [
            $field     => $login,
            'password' => $validated['password'],
        ];

        $remember = $request->boolean('remember');

        if (Auth::guard(self::GUARD)->attempt($credentials, $remember)) {
            /* -----------------------------------------
             | Succès
             | ----------------------------------------- */
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::guard(self::GUARD)->user();

            Log::info('Auth: connexion réussie', [
                'user_id'   => $user?->id,
                'role'      => $user?->role,
                'ip'        => $request->ip(),
                'has_contact' => $user?->hasContactAccount() ?? false,
            ]);

            return redirect()->intended($this->redirectTo());
        }

        /* -----------------------------------------
         | Échec — incrémente le rate limiter
         | ----------------------------------------- */
        RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

        /* ---------------------------------------------------------
         | 5. Détection du mauvais espace (Contact vs User)
         | --------------------------------------------------------- */
        if ($field === 'email' && Contact::where('email', $login)->exists()) {
            Log::info('Auth: tentative login sur mauvais espace', [
                'login_hash' => $this->hashValue($login),
                'ip'         => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'login' => 'Cet email appartient à un compte abonné. '
                         . 'Utilisez l\'espace abonné pour vous connecter.',
            ]);
        }

        /* ---------------------------------------------------------
         | 6. Échec générique
         | --------------------------------------------------------- */
        Log::warning('Auth: tentative de connexion échouée', [
            'login_hash' => $this->hashValue($login),
            'field'      => $field,
            'ip'         => $request->ip(),
        ]);

        throw ValidationException::withMessages([
            'login' => 'Identifiants incorrects.',
        ]);
    }

    // ============================================================
    // DÉCONNEXION
    // ============================================================

    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::guard(self::GUARD)->id();

        Log::info('Auth: déconnexion', [
            'user_id' => $userId,
            'ip'      => $request->ip(),
        ]);

        Auth::guard(self::GUARD)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ============================================================
    // HELPERS PRIVÉS
    // ============================================================

    /**
     * Détermine la redirection après connexion selon les rôles.
     */
    private function redirectTo(): string
    {
        $user = Auth::guard(self::GUARD)->user();

        if (! $user) {
            return route('login');
        }

        $isAdminUser = $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->hasPermissionLike('admin.%');

        // ✅ Vérification que la route existe
        $destination = $isAdminUser
            ? 'admin.statistiques.index'
            : 'dashboard.index';

        if (! \Illuminate\Support\Facades\Route::has($destination)) {
            $destination = 'home';
        }

        return route($destination);
    }

    /**
     * Hash stable d'une valeur sensible pour les logs (PII-safe).
     */
    private function hashValue(?string $value): string
    {
        if (! $value) {
            return '';
        }

        return substr(hash('sha256', mb_strtolower($value)), 0, 16);
    }
}