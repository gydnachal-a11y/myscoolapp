<?php

namespace App\Notifications;

use App\Models\DemandeAvance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NouvelleDemandeAvanceNotification extends Notification implements ShouldQueue
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
     *
     * NOTE : Signature conservée pour compatibilité avec le contrôleur.
     * Idéalement, préférer passer le modèle DemandeAvance complet :
     *   new NouvelleDemandeAvanceNotification($demande)
     */
    public function __construct(
        public User $demandeur,
        public int $montant,
        public int $demandeId,
        public ?DemandeAvance $demande = null
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

        // Ajouter 'mail' si l'administrateur a un email
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
        $montantUsd = (float) $this->montant;
        $montantFc  = $this->demande
            ? (float) $this->demande->montant_demande_fc
            : round($montantUsd * $this->getTauxChange(), 0);

        $motifPreview = $this->demande
            ? \Illuminate\Support\Str::limit($this->demande->motif, 100)
            : null;

        $sessionLibelle = $this->demande?->session?->libelle;

        return [
            'type'           => 'nouvelle_demande_avance',
            'demande_id'     => $this->demandeId,
            'demandeur_id'   => $this->demandeur->id,
            'demandeur_nom'  => $this->demandeur->name,
            'demandeur_email'=> $this->demandeur->email,
            'montant_usd'    => $montantUsd,
            'montant_fc'     => $montantFc,
            'motif_preview'  => $motifPreview,
            'session'        => $sessionLibelle,
            'message'        => sprintf(
                "Nouvelle demande d'avance de %s : %s $",
                $this->demandeur->name,
                number_format($montantUsd, 0, ',', ' ')
            ),
            'url'            => route('admin.demandes-avance.show', ['demande' => $this->demandeId]),
            'url_list'       => route('admin.demandes-avance.index'),
            'icon'           => 'fa-hand-holding-dollar',
            'color'          => 'primary',
            'priorite'       => 'haute',
        ];
    }

    // ============================================================
    // CONTENU — EMAIL
    // ============================================================

    /**
     * Contenu de l'email envoyé aux administrateurs.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $montantUsd = (float) $this->montant;
        $montantFc  = $this->demande
            ? (float) $this->demande->montant_demande_fc
            : round($montantUsd * $this->getTauxChange(), 0);

        $mail = (new MailMessage())
            ->subject('🔔 Nouvelle demande d\'avance : ' . $this->demandeur->name)
            ->greeting('Bonjour ' . ($notifiable->name ?? 'Administrateur') . ',')
            ->line('Une nouvelle demande d\'avance sur salaire vient d\'être soumise et nécessite votre attention.')
            ->line('---')
            ->line('**Informations du demandeur :**')
            ->line('• Nom : ' . $this->demandeur->name)
            ->line('• Email : ' . ($this->demandeur->email ?? '—'));

        if ($this->demandeur->matricule ?? null) {
            $mail->line('• Matricule : ' . $this->demandeur->matricule);
        }

        if ($this->demandeur->section ?? null) {
            $mail->line('• Section : ' . $this->demandeur->section->nom);
        }

        $mail->line('---')
             ->line('**Détails de la demande :**')
             ->line('• Référence : #' . $this->demandeId)
             ->line('• Montant demandé : ' . number_format($montantUsd, 0, ',', ' ') . ' $ (≈ ' . number_format($montantFc, 0, ',', ' ') . ' FC)');

        if ($this->demande?->session?->libelle) {
            $mail->line('• Session : ' . $this->demande->session->libelle);
        }

        if ($this->demande?->created_at) {
            $mail->line('• Soumise le : ' . $this->demande->created_at->translatedFormat('d F Y à H:i'));
        }

        if ($this->demande?->motif) {
            $mail->line('---')
                 ->line('**Motif :**')
                 ->line('> ' . \Illuminate\Support\Str::limit($this->demande->motif, 300));
        }

        $mail->line('---')
             ->line('Merci de traiter cette demande dans les plus brefs délais.')
             ->action('Voir la demande', route('admin.demandes-avance.show', ['demande' => $this->demandeId]))
             ->line('Vous pouvez également consulter toutes les demandes en attente.')
             ->salutation('L\'équipe administrative');

        return $mail;
    }

    // ============================================================
    // UTILITAIRES
    // ============================================================

    /**
     * Récupère le taux de change (avec fallback si non configuré).
     */
    private function getTauxChange(): float
    {
        try {
            return app(\App\Services\AvanceService::class)->getTauxChange();
        } catch (\Throwable $e) {
            return 2800.0; // Fallback
        }
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
            'nouvelle-demande-avance',
            'demande-avance:' . $this->demandeId,
            'user:' . $this->demandeur->id,
            'type:nouvelle',
        ];
    }
}