<?php

namespace App\Notifications;

use App\Models\DemandeAvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemandeAvanceRefuseeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Nombre de tentatives en cas d'échec.
     */
    public int $tries = 3;

    /**
     * Délai entre les tentatives (en secondes).
     */
    public int $backoff = 60;

    /**
     * Constructeur.
     */
    public function __construct(
        public DemandeAvance $demande
    ) {}

    // ============================================================
    // CANAUX DE DIFFUSION
    // ============================================================

    /**
     * Canaux utilisés pour la notification.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Ajouter 'mail' si l'utilisateur a un email
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    // ============================================================
    // CONTENU — BASE DE DONNÉES
    // ============================================================

    /**
     * Contenu de la notification stockée en base.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $montantUsd = (float) $this->demande->montant_demande_usd;

        return [
            'type'         => 'demande_avance_refusee',
            'demande_id'   => $this->demande->id,
            'montant_usd'  => $montantUsd,
            'motif_refus'  => $this->demande->motif_refus,
            'message'      => sprintf(
                "Votre demande d'avance de %s $ a été refusée. Motif : %s",
                number_format($montantUsd, 0, ',', ' '),
                $this->demande->motif_refus
            ),
            'traite_le'    => $this->demande->traite_le?->toIso8601String(),
            'url'          => route('member.demandes-avance.index'),
            'icon'         => 'fa-circle-xmark',
            'color'        => 'danger',
        ];
    }

    // ============================================================
    // CONTENU — EMAIL
    // ============================================================

    /**
     * Contenu de l'email envoyé au membre.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $montantUsd = (float) $this->demande->montant_demande_usd;
        $montantFc  = (float) $this->demande->montant_demande_fc;

        $salaireMensuel = number_format(
            (float) ($notifiable->salaire_ajuste_usd
                ?? $notifiable->salaire_mensuel_usd
                ?? $notifiable->salaire_auto_base_usd
                ?? 0),
            0, ',', ' '
        );

        return (new MailMessage())
            ->subject('Votre demande d\'avance a été refusée')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'cher membre') . ',')
            ->line("Nous avons bien reçu votre demande d'avance sur salaire et nous vous remercions de votre confiance.")
            ->line('Après étude de votre dossier, nous sommes au regret de vous informer que votre demande a été **refusée**.')
            ->line('---')
            ->line('**Détails de la demande :**')
            ->line('• Référence : #' . $this->demande->id)
            ->line('• Montant demandé : ' . number_format($montantUsd, 0, ',', ' ') . ' $ (≈ ' . number_format($montantFc, 0, ',', ' ') . ' FC)')
            ->line('• Salaire mensuel : ' . $salaireMensuel . ' $')
            ->line('• Date de la demande : ' . $this->demande->created_at->translatedFormat('d F Y à H:i'))
            ->line('---')
            ->line('**Motif du refus :**')
            ->line('> ' . $this->demande->motif_refus)
            ->line('---')
            ->line('Si vous avez des questions concernant cette décision, nous vous invitons à contacter directement l\'administration.')
            ->action('Voir mes demandes', route('member.demandes-avance.index'))
            ->line('Cordialement,')
            ->salutation('L\'équipe de ' . (config('app.name') ?? 'notre établissement'));
    }

    // ============================================================
    // CONFIGURATION DE LA FILE D'ATTENTE
    // ============================================================

    /**
     * Nom de la file d'attente.
     */
    public function queue(): string
    {
        return 'notifications';
    }

    /**
     * Tags pour le monitoring (utile avec Horizon).
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'notification',
            'demande-avance',
            'demande-avance:' . $this->demande->id,
            'user:' . $this->demande->user_id,
            'type:refusee',
        ];
    }
}