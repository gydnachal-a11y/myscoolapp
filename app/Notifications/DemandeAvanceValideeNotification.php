<?php

namespace App\Notifications;

use App\Models\DemandeAvance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemandeAvanceValideeNotification extends Notification implements ShouldQueue
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
        $montantFc  = (float) $this->demande->montant_demande_fc;
        $avanceId   = $this->demande->avance_id;

        return [
            'type'         => 'demande_avance_validee',
            'demande_id'   => $this->demande->id,
            'avance_id'    => $avanceId,
            'montant_usd'  => $montantUsd,
            'montant_fc'   => $montantFc,
            'mois_scolaire'=> $this->demande->avance?->moisScolaire?->nom_mois
                              ?? $this->demande->avance?->moisScolaire?->mois
                              ?? null,
            'message'      => sprintf(
                "Votre demande d'avance de %s $ a été validée. Une avance a été créée automatiquement.",
                number_format($montantUsd, 0, ',', ' ')
            ),
            'traite_le'    => $this->demande->traite_le?->toIso8601String(),
            'url'          => route('member.demandes-avance.index'),
            'icon'         => 'fa-circle-check',
            'color'        => 'success',
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
        $taux       = (float) $this->demande->taux_applique;

        $avance = $this->demande->avance;

        $moisScolaire = $avance?->moisScolaire?->nom_mois
                        ?? $avance?->moisScolaire?->mois
                        ?? 'Mois en cours';

        $detteRestante = $avance ? (float) $avance->dette_restante_usd : $montantUsd;
        $detteRestanteFc = $avance ? (float) $avance->dette_restante_fc : $montantFc;

        $mail = (new MailMessage())
            ->subject('Bonne nouvelle : votre demande d\'avance a été validée')
            ->greeting('Bonjour ' . ($notifiable->name ?? 'cher membre') . ',')
            ->line('Nous avons le plaisir de vous informer que votre demande d\'avance sur salaire a été **validée** par l\'administration.')
            ->line('---')
            ->line('**Détails de la demande :**')
            ->line('• Référence demande : #' . $this->demande->id)
            ->line('• Montant demandé : ' . number_format($montantUsd, 0, ',', ' ') . ' $ (≈ ' . number_format($montantFc, 0, ',', ' ') . ' FC)')
            ->line('• Taux de change appliqué : ' . number_format($taux, 2, ',', ' ') . ' FC/USD')
            ->line('• Date de la demande : ' . $this->demande->created_at->translatedFormat('d F Y à H:i'))
            ->line('---')
            ->line('**Avance créée automatiquement :**');

        if ($avance) {
            $mail->line('• Référence avance : #' . $avance->id)
                 ->line('• Mois scolaire : ' . $moisScolaire)
                 ->line('• Montant de l\'avance : ' . number_format((float) $avance->montant_avance_usd, 0, ',', ' ') . ' $')
                 ->line('• Dette initiale à rembourser : ' . number_format($detteRestante, 0, ',', ' ') . ' $ (≈ ' . number_format($detteRestanteFc, 0, ',', ' ') . ' FC)');
        } else {
            $mail->line('• Le montant sera enregistré dans votre dossier et déduit lors des prochains paiements de salaire.');
        }

        $mail->line('---')
             ->line('**Rappel important :** le montant sera automatiquement déduit de votre salaire selon le calendrier de remboursement défini.')
             ->action('Voir mes demandes', route('member.demandes-avance.index'))
             ->line('Merci de votre confiance et bonne continuation.')
             ->salutation('L\'équipe de ' . (config('app.name') ?? 'notre établissement'));

        return $mail;
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
            'type:validee',
        ];
    }
}