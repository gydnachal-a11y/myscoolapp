<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    // ============================================================
    // ABONNEMENT
    // ============================================================

    /**
     * Abonne un visiteur à la newsletter.
     */
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:newsletter_subscribers,email',
            ],
        ], [
            'email.required' => 'Veuillez saisir une adresse email.',
            'email.email'    => 'L\'adresse email n\'est pas valide.',
            'email.unique'   => 'Cette adresse email est déjà abonnée.',
        ]);

        // Normalisation (Laravel a déjà trim, on force juste le lowercase)
        $email = mb_strtolower($validated['email']);

        try {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition : un autre process a inséré le même email
            if ($e->getCode() === '23000') {
                return $this->respond(
                    $request,
                    success: false,
                    message: 'Cette adresse email est déjà abonnée.',
                );
            }

            Log::error('Newsletter: échec inscription', [
                'email_hash' => $this->hashEmail($email),
                'error'      => $e->getMessage(),
            ]);

            return $this->respond(
                $request,
                success: false,
                message: 'Une erreur est survenue. Merci de réessayer.',
                httpCode: 500,
            );
        }

        Log::info('Newsletter: nouvel abonné', [
            'subscriber_id' => $subscriber->id,
            'email_hash'    => $this->hashEmail($email),
        ]);

        return $this->respond(
            $request,
            success: true,
            message: 'Abonnement réussi ! Merci de votre intérêt.',
        );
    }

    // ============================================================
    // DÉSABONNEMENT
    // ============================================================

    /**
     * Désabonne un contact (via lien signé).
     *
     * Utilisé par le lien en bas des emails BulkEmail.
     * La route doit être protégée par `->middleware('signed')`.
     */
    public function unsubscribe(Request $request, Contact $contact): View
    {
        // ✅ Route signée = impossible à falsifier
        // ✅ On vérifie que le contact existe (route model binding)

        if ($contact->newsletter_opt_in) {
            $contact->update(['newsletter_opt_in' => false]);

            Log::info('Newsletter: désabonnement', [
                'contact_id' => $contact->id,
            ]);
        }

        return view('newsletter.unsubscribed', [
            'contact' => $contact,
            'alreadyUnsubscribed' => ! $contact->newsletter_opt_in,
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Réponse unifiée : JSON si AJAX, redirect sinon.
     */
    private function respond(
        Request $request,
        bool $success,
        string $message,
        int $httpCode = 200,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
            ], $success ? $httpCode : ($httpCode === 200 ? 422 : $httpCode));
        }

        return $this->safeBack()
            ->with($success ? 'success' : 'error', $message);
    }

    /**
     * Retour sécurisé (fallback si pas de referer).
     */
    private function safeBack(): RedirectResponse
    {
        $referer = request()->headers->get('referer');

        return $referer
            ? back()
            : redirect()->route('home');
    }

    /**
     * Hash PII-safe pour les logs (RGPD-friendly).
     */
    private function hashEmail(?string $email): string
    {
        if (! $email) {
            return '';
        }

        return substr(hash('sha256', mb_strtolower($email)), 0, 16);
    }
}