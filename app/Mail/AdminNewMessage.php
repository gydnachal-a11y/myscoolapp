<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Nombre de tentatives en cas d'échec SMTP.
     */
    public int $tries = 3;

    /**
     * Délai entre les tentatives (en secondes).
     */
    public int $backoff = 60;

    /**
     * Timeout par tentative (secondes).
     */
    public int $timeout = 30;

    public function __construct(
        public readonly ContactMessage $contactMessage,
    ) {}

    /**
     * Enveloppe : sujet, expéditeur, reply-to.
     */
    public function envelope(): Envelope
    {
        $isAnonymous = $this->contactMessage->contact_id === null;

        return new Envelope(
            subject: sprintf(
                '[%s] %s — %s',
                $isAnonymous ? 'Message anonyme' : 'Message abonné',
                $this->contactMessage->nom ?: 'Inconnu',
                $this->contactMessage->sujet,
            ),
            replyTo: [
                new Address(
                    $this->contactMessage->email,
                    $this->contactMessage->nom ?: $this->contactMessage->email,
                ),
            ],
        );
    }

    /**
     * Contenu de l'email.
     */
    public function build(): self
    {
        return $this
            ->markdown('emails.admin-new-message')
            ->with([
                // ⚠️ On utilise le nom complet pour éviter le conflit
                //    avec la propriété interne $message de Symfony.
                'contactMessage' => $this->contactMessage,
                'isAnonymous'    => $this->contactMessage->contact_id === null,
                'adminUrl'       => route('admin.messages.index'),
            ]);
    }
}