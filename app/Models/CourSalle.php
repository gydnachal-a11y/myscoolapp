<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourSalle extends Model
{
    /**
     * Nom de la table pivot.
     *
     * @var string
     */
    protected $table = 'cours_salle';

    /**
     * Clé primaire auto-incrémentée.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Les timestamps sont gérés par Eloquent.
     *
     * @var bool
     */
    public $timestamps = true; // activé car la table contient created_at et updated_at

    /**
     * Champs assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'cours_id',
        'salle_classe_id',
        'libelle_id',
        'nombre_heure_id',
        'ponderation_id',
        'titulaire_id',
        'jours',
        'duree',
        'creneau_horaire_id',
        'nombre_seances', // ajouté pour le comptage des séances
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function cour(): BelongsTo
    {
        return $this->belongsTo(Cour::class, 'cours_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(SalleDeClasse::class, 'salle_classe_id');
    }

    public function libelle(): BelongsTo
    {
        return $this->belongsTo(Libelle::class);
    }

    public function ponderation(): BelongsTo
    {
        return $this->belongsTo(Ponderation::class);
    }

    public function nombreHeure(): BelongsTo
    {
        return $this->belongsTo(NombreHeure::class);
    }

    public function titulaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'titulaire_id');
    }

    public function creneauHoraire(): BelongsTo
    {
        return $this->belongsTo(CreneauHoraire::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------------
    */

    /**
     * Retourne la liste des jours sous forme de tableau.
     */
    public function getJoursArrayAttribute(): array
    {
        return $this->jours ? explode(',', $this->jours) : [];
    }

    /**
     * Libellé formaté du créneau horaire ou durée.
     */
    public function getDureeFormateeAttribute(): string
    {
        if ($this->relationLoaded('creneauHoraire') && $this->creneauHoraire) {
            return $this->creneauHoraire->libelle . ' (' .
                   $this->creneauHoraire->heure_debut->format('H:i') . ' - ' .
                   $this->creneauHoraire->heure_fin->format('H:i') . ')';
        }

        return $this->duree ?: '—';
    }

    /**
     * Nom du cours, avec repli si supprimé.
     */
    public function getCoursNomAttribute(): string
    {
        return $this->relationLoaded('cour') && $this->cour
            ? $this->cour->nom
            : 'Cours supprimé';
    }

    /**
     * Nom de la salle, avec repli si supprimée.
     */
    public function getSalleNomAttribute(): string
    {
        return $this->relationLoaded('salle') && $this->salle
            ? $this->salle->nom
            : 'Salle supprimée';
    }

    /**
     * Libellé combiné cours + salle.
     */
    public function getCoursSalleLabelAttribute(): string
    {
        return "{$this->cours_nom} ({$this->salle_nom})";
    }

    /**
     * Durée en heures (nombre décimal) basée sur le créneau horaire ou le champ duree.
     */
    public function getDureeHeuresAttribute(): float
    {
        if ($this->relationLoaded('creneauHoraire') && $this->creneauHoraire) {
            return $this->creneauHoraire->heure_debut->diffInMinutes($this->creneauHoraire->heure_fin) / 60;
        }

        if ($this->nombreHeure && $this->nombreHeure->valeur) {
            return (float) $this->nombreHeure->valeur;
        }

        return (float) $this->duree ?: 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Ne garde que les assignations dont le cours et la salle existent.
     */
    public function scopeValide(Builder $query): Builder
    {
        return $query->whereHas('cour')->whereHas('salle');
    }
}