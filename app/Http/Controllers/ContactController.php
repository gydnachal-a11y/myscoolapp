<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\AdminNewMessage;   // ✅ Unifié avec ExternalAuthController
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const MAX_MESSAGE_LENGTH = 5000;
    private const MAX_NAME_LENGTH    = 120;
    private const MAX_SUBJECT_LENGTH = 200;

    /** Délai minimum (en secondes) entre le chargement du formulaire et sa soumission. */
    private const MIN_FORM_FILL_TIME = 3;

    // ============================================================
    // SHOW — FORMULAIRE DE CONTACT
    // ============================================================

    public function show(): View
    {
        return view('contact');
    }

    // ============================================================
    // STORE — TRAITEMENT DU FORMULAIRE
    // ============================================================

    public function store(Request $request): RedirectResponse
    {
        /* ---------------------------------------------------------
         | 1. Anti-spam : honeypot + time-check
         | --------------------------------------------------------- */
        if ($this->isSpam($request)) {
            Log::warning('Contact: tentative de spam bloquée', [
                'ip'         => $request->ip(),
                'agent'      => mb_substr((string) $request->userAgent(), 0, 100),
                'has_website' => $request->filled('website') || $request->filled('url'),
                'too_fast'   => $this->isTooFast($request),
            ]);

            // On fait comme si tout s'était bien passé (le bot ne saura pas)
            return $this->safeBack()
                ->with('success', 'Message envoyé avec succès.');
        }

        /* ---------------------------------------------------------
         | 2. Validation stricte
         | --------------------------------------------------------- */
        $validated = $request->validate([
            'nom'     => ['required', 'string', 'min:2', 'max:' . self::MAX_NAME_LENGTH],
            'email'   => ['required', 'email:rfc,dns', 'max:255'],
            'sujet'   => ['required', 'string', 'min:3', 'max:' . self::MAX_SUBJECT_LENGTH],
            'message' => ['required', 'string', 'min:10', 'max:' . self::MAX_MESSAGE_LENGTH],
        ], [
            'nom.required'     => 'Veuillez indiquer votre nom.',
            'nom.min'          => 'Le nom doit contenir au moins :min caractères.',
            'email.required'   => 'Veuillez indiquer votre adresse email.',
            'email.email'      => 'L\'adresse email n\'est pas valide.',
            'sujet.required'   => 'Veuillez indiquer un sujet.',
            'message.required' => 'Veuillez écrire votre message.',
            'message.min'      => 'Le message doit contenir au moins :min caractères.',
            'message.max'      => 'Le message ne doit pas dépasser :max caractères.',
        ]);

        /* ---------------------------------------------------------
         | 3. Normalisation
         | --------------------------------------------------------- */
        $contact = $request->user('contact');   // abonné connecté ou null

        $payload = [
            'contact_id' => $contact?->id,
            'nom'        => trim($validated['nom']),
            'email'      => mb_strtolower(trim($validated['email'])),
            'sujet'      => trim($validated['sujet']),
            'message'    => trim($validated['message']),
            'lu'         => false,
            'traite'     => false,
            'from_admin' => false,
        ];

        /* ---------------------------------------------------------
         | 4. Enregistrement
         | --------------------------------------------------------- */
        try {
            $message = ContactMessage::create($payload);
        } catch (\Throwable $e) {
            Log::error('Contact: échec création message', [
                'email_hash' => $this->hashEmail($payload['email']),
                'error'      => $e->getMessage(),
            ]);

            return $this->safeBack()
                ->withInput()
                ->with('error', 'Une erreur est survenue. Merci de réessayer.');
        }

        /* ---------------------------------------------------------
         | 5. Notification admin (non bloquant)
         | --------------------------------------------------------- */
        $this->notifyAdmin($message);

        Log::info('Contact: nouveau message', [
            'message_id' => $message->id,
            'email_hash' => $this->hashEmail($payload['email']),
            'contact_id' => $contact?->id,
            'source'     => $contact ? 'authenticated' : 'anonymous',
        ]);

        return $this->safeBack()
            ->with('success', 'Message envoyé avec succès. Nous vous répondrons rapidement.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Détection anti-spam : honeypot OU time-check.
     */
    private function isSpam(Request $request): bool
    {
        // Honeypot : champ caché qui doit rester vide
        if ($request->filled('website') || $request->filled('url')) {
            return true;
        }

        // Time-check : le formulaire doit avoir été rempli en ≥ MIN_FORM_FILL_TIME
        return $this->isTooFast($request);
    }

    /**
     * Le formulaire a-t-il été soumis trop rapidement ?
     */
    private function isTooFast(Request $request): bool
    {
        $formLoadedAt = (int) $request->input('_form_loaded_at', 0);

        if ($formLoadedAt <= 0) {
            return false;   // pas de timestamp → on ne peut pas juger
        }

        return (time() - $formLoadedAt) < self::MIN_FORM_FILL_TIME;
    }

    /**
     * Notifie l'administration par email (sans bloquer la requête).
     * Utilise le même Mailable que ExternalAuthController pour cohérence.
     */
    private function notifyAdmin(ContactMessage $message): void
    {
        $adminEmail = config('mail.from.address')
            ?: config('mail.admin_address')
            ?: null;

        if (! $adminEmail) {
            Log::warning('Contact: aucun destinataire admin configuré, mail non envoyé', [
                'message_id' => $message->id,
            ]);
            return;
        }

        try {
            Mail::to($adminEmail)->send(new AdminNewMessage($message));
        } catch (\Throwable $e) {
            // Le message est déjà en DB → on ne fait que logger
            Log::error('Contact: échec envoi mail admin', [
                'message_id' => $message->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retour sécurisé vers le formulaire.
     * `back()` peut échouer si l'utilisateur n'a pas de referer.
     */
    private function safeBack(): RedirectResponse
    {
        $referer = request()->headers->get('referer');

        return $referer
            ? back()
            : redirect()->route('contact');
    }

    /**
     * Hash stable d'un email pour les logs (PII-safe).
     */
    private function hashEmail(?string $email): string
    {
        if (! $email) {
            return '';
        }

        return substr(hash('sha256', mb_strtolower($email)), 0, 16);
    }
}