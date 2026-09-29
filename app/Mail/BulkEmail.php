<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BulkEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // ============================================================
    // CONFIGURATION QUEUE
    // ============================================================

    /** Nombre de tentatives en cas d'échec SMTP. */
    public int $tries = 3;

    /** Délai entre les tentatives (secondes). */
    public int $backoff = 60;

    /** Timeout par tentative (secondes). */
    public int $timeout = 30;

    // ============================================================
    // CONSTRUCTEUR
    // ============================================================

    public function __construct(
        public readonly string  $emailSubject,
        public readonly string  $emailContent,
        public readonly ?string $replyToEmail   = null,
        public readonly ?string $replyToName    = null,
        public readonly ?string $unsubscribeUrl = null,
    ) {}

    // ============================================================
    // ENVELOPPE
    // ============================================================

    public function envelope(): Envelope
    {
        $replyTo = $this->replyToEmail
            ? [new Address($this->replyToEmail, $this->replyToName ?? $this->replyToEmail)]
            : [];

        return new Envelope(
            subject: $this->emailSubject,
            replyTo: $replyTo,
        );
    }

    // ============================================================
    // CONTENU
    // ============================================================

    public function build(): self
    {
        // ✅ Chargé une seule fois par Mailable (via cache modèle),
        //    pas par email envoyé → évite 1000 requêtes DB sur 1000 emails.
        $settings = SiteSetting::getSettings();

        return $this
            ->markdown('emails.bulk')
            ->with([
                // ⚠️ On évite `subject` qui entre en conflit avec la
                //    propriété interne de Symfony Mailer.
                'emailSubject'   => $this->emailSubject,
                'content'        => $this->emailContent,

                // Contexte du site (passé en amont)
                'siteName'       => $settings->site_name ?? config('app.name'),
                'siteLogo'       => $settings->site_logo ?? null,

                // RGPD — lien de désinscription signé
                'unsubscribeUrl' => $this->unsubscribeUrl,

                // Métadonnées
                'sentAt'         => now(),
            ]);
    }
}