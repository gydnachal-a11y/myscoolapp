<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Eleve extends Model
{
    // ==========================================
    // CONSTANTES
    // ==========================================

    public const SEXE_MASCULIN = 'M';
    public const SEXE_FEMININ  = 'F';

    public const SEXES = [
        self::SEXE_MASCULIN,
        self::SEXE_FEMININ,
    ];

    public const SEXES_LIBELLES = [
        self::SEXE_MASCULIN => 'Masculin',
        self::SEXE_FEMININ  => 'Féminin',
    ];

    protected const MATRICULE_PREFIX = 'ELEV';
    protected const MATRICULE_PAD    = 4;

    // ==========================================
    // CONFIGURATION
    // ==========================================

    protected $fillable = [
        'nom',
        'postnom',
        'prenom',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'adresse',
        'inscrit',
        'photo',
        'maladie_chronique',
        'allergies',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'inscrit'        => 'boolean',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    protected $appends = [
        'nom_complet',
        'initiales',
        'age',
        'sexe_libelle',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function responsables(): HasMany
    {
        return $this->hasMany(Responsable::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function inscriptionActive(): HasOne
    {
        return $this->hasOne(Inscription::class)
            ->whereHas('anneeScolaire', fn (Builder $q) => $q->where('cloturee', false))
            ->latest('id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    public function paiementsFraisSupplementaires(): HasMany
    {
        return $this->hasMany(PaiementFraisSupplementaire::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeRecherche(Builder $query, ?string $recherche): Builder
    {
        $recherche = trim((string) $recherche);

        if ($recherche === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($recherche): void {
            $q->where('nom', 'LIKE', "%{$recherche}%")
              ->orWhere('postnom', 'LIKE', "%{$recherche}%")
              ->orWhere('prenom', 'LIKE', "%{$recherche}%");
        });
    }

    public function scopeSexe(Builder $query, ?string $sexe): Builder
    {
        return $sexe ? $query->where('sexe', $sexe) : $query;
    }

    public function scopeMasculin(Builder $query): Builder
    {
        return $query->where('sexe', self::SEXE_MASCULIN);
    }

    public function scopeFeminin(Builder $query): Builder
    {
        return $query->where('sexe', self::SEXE_FEMININ);
    }

    public function scopeInscrits(Builder $query): Builder
    {
        return $query->where('inscrit', true);
    }

    public function scopeNonInscrits(Builder $query): Builder
    {
        return $query->where('inscrit', false);
    }

    public function scopeAgeEntre(Builder $query, int $ageMin, int $ageMax): Builder
    {
        return $query->whereBetween('date_naissance', [
            now()->subYears($ageMax)->startOfDay(),
            now()->subYears($ageMin)->endOfDay(),
        ]);
    }

    public function scopeAnneeNaissance(Builder $query, int $annee): Builder
    {
        return $query->whereYear('date_naissance', $annee);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Nom complet : NOM Postnom Prénom.
     * Utilisable via $eleve->nomComplet ou $eleve->nom_complet.
     */
    protected function nomComplet(): Attribute
    {
        return Attribute::get(
            fn (): string => implode(' ', array_filter([
                $this->nom,
                $this->postnom,
                $this->prenom,
            ])) ?: 'Inconnu'
        );
    }

    /**
     * Nom complet inversé : Prénom Nom.
     */
    protected function nomCompletInverse(): Attribute
    {
        return Attribute::get(
            fn (): string => implode(' ', array_filter([
                $this->prenom,
                $this->nom,
            ])) ?: 'Inconnu'
        );
    }

    /**
     * Initiales (ex: "JD" pour Jean Dupont).
     */
    protected function initiales(): Attribute
    {
        return Attribute::get(function (): string {
            $init = strtoupper(
                mb_substr((string) $this->prenom, 0, 1) .
                mb_substr((string) $this->nom, 0, 1)
            );

            return $init !== '' ? $init : '?';
        });
    }

    /**
     * Âge calculé depuis la date de naissance.
     */
    protected function age(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->date_naissance?->age);
    }

    /**
     * Libellé du sexe.
     */
    protected function sexeLibelle(): Attribute
    {
        return Attribute::get(
            fn (): string => self::SEXES_LIBELLES[$this->sexe] ?? '—'
        );
    }

    /**
     * URL de la photo (avec fallback).
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->photo && file_exists(public_path('storage/' . $this->photo))) {
                return asset('storage/' . $this->photo);
            }

            return asset('images/default-avatar.png');
        });
    }

    // ⚠️ SUPPRIMÉ : public function getNomCompletAttribute()
    //    Il entrait en conflit avec nomComplet() ci-dessus → boucle infinie.
    //    `$appends = ['nom_complet']` fonctionne déjà avec l'accesseur moderne.

    public function getEstInscritAttribute(): bool
    {
        return (bool) $this->inscrit;
    }

    public function getNombreInscriptionsAttribute(): int
    {
        return $this->inscriptions()->count();
    }

    // ==========================================
    // MÉTHODES UTILITAIRES
    // ==========================================

    public function derniereInscription(): ?Inscription
    {
        return $this->inscriptions()->latest('id')->first();
    }

    public function getDerniereAnneeScolaireAttribute(): ?AnneeScolaire
    {
        return $this->derniereInscription()?->anneeScolaire;
    }

    public function marquerCommeInscrit(): static
    {
        $this->update(['inscrit' => true]);

        return $this;
    }

    public function marquerCommeDesinscrit(): static
    {
        $this->update(['inscrit' => false]);

        return $this;
    }

    /**
     * Génère un matricule unique (format ELEV-YYYY-NNNN) avec verrouillage.
     */
    public static function genererMatricule(): string
    {
        $year   = now()->year;
        $prefix = self::MATRICULE_PREFIX;

        return DB::transaction(function () use ($year, $prefix): string {
            $last = self::whereYear('created_at', $year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('matricule');

            $lastNumber = 0;

            if ($last && str_contains($last, '-')) {
                $lastNumber = (int) last(explode('-', $last));
            }

            $newNumber = str_pad(
                (string) ($lastNumber + 1),
                self::MATRICULE_PAD,
                '0',
                STR_PAD_LEFT
            );

            return "{$prefix}-{$year}-{$newNumber}";
        });
    }
}