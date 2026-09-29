<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\AdminNewMessage;
use App\Models\Annonce;
use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\ReglementInterieur;
use App\Models\User;                       // ✅ AJOUTÉ — détection mauvais espace
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;  // ✅ AJOUTÉ
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class ExternalAuthController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const NOM_REGEX       = '/^[\pL\s\'\-]+$/u';
    private const TELEPHONE_REGEX = '/^[0-9+() -]+$/';
    private const PASSWORD_REGEX  = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$/';

    /** Nombre max de messages renvoyés à la vue chat. */
    private const MESSAGES_LIMIT = 200;

    /** Fenêtre anti-doublon pour l'envoi de messages (secondes). */
    private const MESSAGE_DEDUP_WINDOW = 5;

    /** Nombre de messages récents sur le dashboard. */
    private const DASHBOARD_MESSAGES_LIMIT = 5;

    /** TTL cache du compteur de non-lus (secondes). */
    private const UNREAD_CACHE_TTL = 30;

    /** Rate limiting connexion. */
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_MINUTES = 1;

    /** Guard utilisé pour l'authentification contact. */
    private const GUARD = 'contact';

    // ============================================================
    // INSCRIPTION
    // ============================================================

    public function showRegister(): View
    {
        // Si déjà connecté → redirection directe vers l'espace
        if (Auth::guard(self::GUARD)->check()) {
            return redirect()->route('external.dashboard');
        }

        return view('external.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $this->validateRegister($request);

        $validated['email'] = mb_strtolower($validated['email']);

        try {
            $contact = Contact::create([
                'nom'               => $validated['nom'],
                'email'             => $validated['email'],
                'telephone'         => $validated['telephone'] ?? null,
                'password'          => Hash::make($validated['password']),
                'est_responsable'   => false,
                'email_verified_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Inscription contact échouée', [
                'email_hash' => $this->hashValue($validated['email']),
                'error'      => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue. Merci de réessayer.');
        }

        Auth::guard(self::GUARD)->login($contact);
        $request->session()->regenerate();

        Log::info('Nouveau contact inscrit', [
            'contact_id' => $contact->id,
            'email_hash' => $this->hashValue($contact->email),
        ]);

        return redirect()->route('external.dashboard');
    }

    // ============================================================
    // CONNEXION
    // ============================================================

    public function showLogin(): View
    {
        // ✅ Si déjà connecté → redirection
        if (Auth::guard(self::GUARD)->check()) {
            return redirect()->route('external.dashboard');
        }

        return view('external.login');
    }

    public function login(Request $request): RedirectResponse
    {
        /* ---------------------------------------------------------
         | 1. Validation
         | --------------------------------------------------------- */
        $validated = $request->validate([
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'login.required'    => 'Veuillez saisir votre email ou téléphone.',
            'password.required' => 'Veuillez saisir votre mot de passe.',
        ]);

        /* ---------------------------------------------------------
         | 2. Normalisation
         | --------------------------------------------------------- */
        $login = trim($validated['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

        $login = $field === 'email'
            ? mb_strtolower($login)
            : preg_replace('/\s+/', '', $login);

        /* ---------------------------------------------------------
         | 3. Rate limiting
         | --------------------------------------------------------- */
        $throttleKey = 'contact_login|' . mb_strtolower($login) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::warning('Connexion contact: trop de tentatives', [
                'login_hash' => $this->hashValue($login),
                'ip'         => $request->ip(),
                'seconds'    => $seconds,
            ]);

            throw ValidationException::withMessages([
                'login' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        /* ---------------------------------------------------------
         | 4. Tentative de connexion
         | --------------------------------------------------------- */
        $remember = $request->boolean('remember');

        if (Auth::guard(self::GUARD)->attempt(
            [$field => $login, 'password' => $validated['password']],
            $remember
        )) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            Log::info('Contact connecté', [
                'contact_id' => Auth::guard(self::GUARD)->id(),
                'login_hash' => $this->hashValue($login),
            ]);

            return redirect()->intended(route('external.dashboard'));
        }

        /* ---------------------------------------------------------
         | 5. Échec — incrémente le rate limiter
         | --------------------------------------------------------- */
        RateLimiter::hit($throttleKey, self::LOGIN_DECAY_MINUTES * 60);

        /* ---------------------------------------------------------
         | 6. ✅ Détection du mauvais espace (User au lieu de Contact)
         | --------------------------------------------------------- */
        if ($field === 'email' && User::where('email', $login)->exists()) {
            Log::info('Connexion contact: tentative sur mauvais espace', [
                'login_hash' => $this->hashValue($login),
                'ip'         => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'login' => 'Cet email appartient à un compte personnel. '
                         . 'Utilisez l\'espace personnel pour vous connecter.',
            ]);
        }

        /* ---------------------------------------------------------
         | 7. Échec générique
         | --------------------------------------------------------- */
        Log::warning('Connexion contact échouée', [
            'login_hash' => $this->hashValue($login),
            'field'      => $field,
            'ip'         => $request->ip(),
        ]);

        throw ValidationException::withMessages([
            'login' => 'Identifiants incorrects.',
        ]);
    }

    // ============================================================
    // GOOGLE OAUTH
    // ============================================================

    public function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            Log::error('Google OAuth: erreur Socialite', ['error' => $e->getMessage()]);

            return redirect()
                ->route('external.login')
                ->with('error', 'Impossible de se connecter avec Google. Veuillez réessayer.');
        }

        try {
            $contact = Contact::findOrCreateFromGoogle(
                (string) $googleUser->getId(),
                (string) $googleUser->getEmail(),
                (string) $googleUser->getName(),
            );

            Auth::guard(self::GUARD)->login($contact, true);
            $request->session()->regenerate();

            Log::info('Contact connecté via Google', [
                'contact_id' => $contact->id,
            ]);

            return redirect()->intended(route('external.dashboard'));

        } catch (\RuntimeException $e) {
            // ✅ Cas métier : détournement de compte, email non vérifié, etc.
            Log::warning('Google OAuth: refus métier', [
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('external.login')
                ->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            // ✅ Autres erreurs (DB, réseau, …)
            Log::error('Google OAuth: échec finalisation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('external.login')
                ->with('error', 'Impossible de finaliser la connexion Google.');
        }
    }

    // ============================================================
    // DÉCONNEXION
    // ============================================================

    public function logout(Request $request): RedirectResponse
    {
        $contactId = Auth::guard(self::GUARD)->id();

        // ✅ Invalide le cache des non-lus du contact
        if ($contactId) {
            Cache::forget("contact_{$contactId}_unread_messages");
        }

        Auth::guard(self::GUARD)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Contact déconnecté', ['contact_id' => $contactId]);

        return redirect()->route('home');
    }

    // ============================================================
    // DASHBOARD
    // ============================================================

    public function dashboard(): View
    {
        /** @var Contact $contact */
        $contact = auth(self::GUARD)->user();

        $annonces = Annonce::query()
            ->active()
            ->whereIn('type', ['public', 'prive'])
            ->orderByDesc('date_debut')
            ->paginate(10);

        $mesMessages = ContactMessage::query()
            ->select(['id', 'sujet', 'message', 'from_admin', 'created_at'])
            ->where('contact_id', $contact->id)
            ->orderByDesc('created_at')
            ->limit(self::DASHBOARD_MESSAGES_LIMIT)
            ->get();

        return view('external.dashboard', compact('annonces', 'mesMessages'));
    }

    // ============================================================
    // ANNONCES PUBLIQUES
    // ============================================================

    public function annonces(Request $request): View
    {
        $user = auth(self::GUARD)->user();

        $annonces = Annonce::query()
            ->active()
            ->when($user, fn ($q) => $q->whereIn('type', ['public', 'prive']))
            ->when(! $user, fn ($q) => $q->where('type', 'public'))
            ->orderByDesc('date_debut')
            ->paginate(12)
            ->withQueryString();

        return view('annonces', compact('annonces', 'user'));
    }

    // ============================================================
    // MESSAGERIE
    // ============================================================

    public function messages(): View
    {
        /** @var Contact $contact */
        $contact = auth(self::GUARD)->user();

        $messages = ContactMessage::query()
            ->where('contact_id', $contact->id)
            ->orderBy('created_at', 'asc')
            ->limit(self::MESSAGES_LIMIT)
            ->get()
            ->map(fn (ContactMessage $msg): array => $this->serializeMessage($msg))
            ->all();

        return view('external.messages', compact('messages'));
    }

    public function sendMessage(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Contact $contact */
        $contact = auth(self::GUARD)->user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'sujet'   => ['nullable', 'string', 'max:255'],
        ], [
            'message.required' => 'Le message est obligatoire.',
            'message.min'      => 'Le message doit contenir au moins :min caractères.',
            'message.max'      => 'Le message ne doit pas dépasser :max caractères.',
        ]);

        /* ---------------------------------------------------------
         | Anti-doublon atomique
         | --------------------------------------------------------- */
        $dedupKey = sprintf(
            'msg_dedup_%d_%s',
            $contact->id,
            hash('sha256', $validated['message'] . '|' . floor(time() / self::MESSAGE_DEDUP_WINDOW))
        );

        if (! Cache::add($dedupKey, true, now()->addSeconds(self::MESSAGE_DEDUP_WINDOW * 2))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Message déjà envoyé récemment.',
                ], 429);
            }
            return back()->with('info', 'Votre message a déjà été envoyé récemment.');
        }

        try {
            $contactMessage = ContactMessage::create([
                'contact_id' => $contact->id,
                'nom'        => $contact->nom,
                'email'      => $contact->email,
                'sujet'      => $validated['sujet'] ?? 'Message depuis l\'espace abonné',
                'message'    => trim($validated['message']),
                'lu'         => false,
                'traite'     => false,
                'from_admin' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('Envoi message contact échoué', [
                'contact_id' => $contact->id,
                'error'      => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Impossible d\'envoyer votre message.',
                ], 500);
            }
            return back()->with('error', 'Impossible d\'envoyer votre message.');
        }

        // ✅ Invalide le cache admin pour que le badge apparaisse
        Cache::forget('admin_unread_messages');

        $this->notifyAdmin($contactMessage);

        Log::info('Message contact envoyé', [
            'contact_id' => $contact->id,
            'message_id' => $contactMessage->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $this->serializeMessage($contactMessage),
            ]);
        }

        return back()->with('success', 'Votre message a été envoyé avec succès.');
    }

    // ============================================================
    // MARQUER COMME LU
    // ============================================================

    public function markRead(Request $request): JsonResponse
    {
        /** @var Contact $contact */
        $contact = auth(self::GUARD)->user();

        $updated = ContactMessage::query()
            ->where('contact_id', $contact->id)
            ->where('from_admin', true)
            ->where(function ($q): void {
                $q->where('lu', false)->orWhereNull('lu');
            })
            ->update([
                'lu'         => true,
                'updated_at' => now(),
            ]);

        Cache::forget("contact_{$contact->id}_unread_messages");

        if ($updated > 0) {
            Log::info('Messages contact marqués lus', [
                'contact_id' => $contact->id,
                'count'      => $updated,
            ]);
        }

        return response()->json([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    // ============================================================
    // COMPTEUR DE NON-LUS (API POLLING)
    // ============================================================

    public function unreadCount(): JsonResponse
    {
        /** @var Contact $contact */
        $contact = auth(self::GUARD)->user();

        $count = Cache::remember(
            "contact_{$contact->id}_unread_messages",
            self::UNREAD_CACHE_TTL,
            fn (): int => ContactMessage::query()
                ->where('contact_id', $contact->id)
                ->where('from_admin', true)
                ->where(function ($q): void {
                    $q->where('lu', false)->orWhereNull('lu');
                })
                ->count()
        );

        return response()->json([
            'unread_messages' => (int) $count,
        ]);
    }

    // ============================================================
    // RÈGLEMENT INTÉRIEUR
    // ============================================================

    public function reglementInterieur(): View
    {
        $reglements = ReglementInterieur::query()
            ->actif()
            ->ordonne()
            ->get()
            ->groupBy('categorie');

        return view('external.reglement', [
            'regles'        => $reglements->get(ReglementInterieur::CATEGORIE_REGLE, collect()),
            'obligations'   => $reglements->get(ReglementInterieur::CATEGORIE_OBLIGATION, collect()),
            'interdictions' => $reglements->get(ReglementInterieur::CATEGORIE_INTERDICTION, collect()),
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function serializeMessage(ContactMessage $message): array
    {
        return [
            'id'         => $message->id,
            'message'    => $message->message,
            'time'       => $message->created_at->format('H:i'),
            'date'       => $message->created_at->format('d/m/Y'),
            'from_admin' => (bool) $message->from_admin,
        ];
    }

    private function notifyAdmin(ContactMessage $message): void
    {
        $adminEmail = config('mail.from.address')
            ?: config('mail.admin_address')
            ?: null;

        if (! $adminEmail) {
            Log::warning('Aucun destinataire admin configuré, notification non envoyée', [
                'message_id' => $message->id,
            ]);
            return;
        }

        try {
            Mail::to($adminEmail)->send(new AdminNewMessage($message));
        } catch (\Throwable $e) {
            Log::error('Échec envoi notification admin', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function hashValue(?string $value): string
    {
        if (! $value) {
            return '';
        }

        return substr(hash('sha256', mb_strtolower($value)), 0, 16);
    }

    private function validateRegister(Request $request): array
    {
        return $request->validate([
            'nom' => [
                'required', 'string', 'max:255',
                'regex:' . self::NOM_REGEX,
            ],
            'email' => [
                'required', 'email:rfc,dns', 'max:255',
                Rule::unique('contacts', 'email'),
            ],
            'telephone' => [
                'nullable', 'string', 'max:20',
                'regex:' . self::TELEPHONE_REGEX,
                Rule::unique('contacts', 'telephone'),
            ],
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:' . self::PASSWORD_REGEX,
            ],
        ], [
            'nom.regex'          => 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets.',
            'email.unique'       => 'Cette adresse email est déjà utilisée.',
            'telephone.regex'    => 'Le téléphone ne peut contenir que des chiffres, +, (), espaces et tirets.',
            'telephone.unique'   => 'Ce numéro de téléphone est déjà utilisé.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.regex'     => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
        ]);
    }
}