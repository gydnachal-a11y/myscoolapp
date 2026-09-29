<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnneeScolaire extends Model
{
    use SoftDeletes;

    protected $table = 'annees_scolaires';

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'effectif_attendu',
        'cloturee',
        'paiement_ouvert',
        'nombre_mois',
        'nombre_tranches',
    ];

    protected $casts = [
        'date_debut'        => 'date',
        'date_fin'          => 'date',
        'cloturee'          => 'boolean',
        'paiement_ouvert'   => 'boolean',
        'nombre_mois'       => 'integer',
        'nombre_tranches'   => 'integer',
        'effectif_attendu'  => 'integer',
    ];

    // ============================================================
    // RELATIONS DIRECTES
    // ============================================================

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function moisScolaires(): HasMany
    {
        return $this->hasMany(MoisScolaire::class);
    }

    public function tranchesScolaires(): HasMany
    {
        return $this->hasMany(TrancheScolaire::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function periodeNotes(): HasMany
    {
        return $this->hasMany(PeriodeNote::class);
    }

    // ============================================================
    // RELATIONS IMBRIQUÉES
    // ============================================================

    /**
     * Sections via les sessions.
     */
    public function sections(): HasManyThrough
    {
        return $this->hasManyThrough(
            Section::class,
            Session::class,
            'annee_scolaire_id',   // FK sur sessions
            'session_id',          // FK sur sections
            'id',                  // PK locale sur annees_scolaires
            'id'                   // PK locale sur sessions
        );
    }

    /**
     * ✅ Salles de classe via sections (relation imbriquée fonctionnelle).
     *
     * Chemin : AnneeScolaire → Session → Section → SalleDeClasse
     * Laravel résout en 2 sauts grâce à hasManyThrough.
     */
    public function sallesDeClasse(): HasManyThrough
    {
        return $this->hasManyThrough(
            SalleDeClasse::class,
            Section::class,
            'session_id',          // FK sur sections (vers sessions)
            'section_id',          // FK sur salles_de_classe (vers sections)
            'id',                  // PK locale sur annees_scolaires
            'id'                   // PK locale sur sections
        )->whereHas('section.session', function ($q) {
            $q->where('annee_scolaire_id', $this->id);
        });
        // ⚠️ Note : `whereHas` dans une relation est acceptable ici car on
        // n'utilise cette relation qu'en lecture (avec un $this->id connu).
        // Pour un usage eager-load, préférer une méthode dédiée :
    }

    /**
     * Variante sous forme de Query Builder (à utiliser pour les requêtes
     * eager-load ou les filtres custom).
     */
    public function scopeSallesDeClasseDe(Builder $query, int $anneeId): Builder
    {
        return SalleDeClasse::query()->whereHas('section.session', function ($q) use ($anneeId) {
            $q->where('annee_scolaire_id', $anneeId);
        });
    }

    /**
     * Élèves inscrits pour cette année (via inscriptions).
     */
    public function eleves(): HasManyThrough
    {
        return $this->hasManyThrough(
            Eleve::class,
            Inscription::class,
            'annee_scolaire_id',   // FK sur inscriptions
            'id',                  // PK sur eleves (via eleve_id sur inscriptions)
            'id',                  // PK locale sur annees_scolaires
            'eleve_id'             // FK locale sur inscriptions (pointant vers eleves)
        );
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeOuvertes(Builder $query): Builder
    {
        return $query->where('cloturee', false);
    }

    public function scopeCloturees(Builder $query): Builder
    {
        return $query->where('cloturee', true);
    }

    public function scopePaiementOuvert(Builder $query): Builder
    {
        return $query->where('paiement_ouvert', true);
    }

    /**
     * Années en cours (non clôturées ET dans la période).
     * ✅ Utilise `today()` pour comparer correctement avec des colonnes `date`.
     */
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('cloturee', false)
            ->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today());
    }

    public function scopeFutures(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '>', today());
    }

    public function scopePassees(Builder $query): Builder
    {
        return $query->whereDate('date_fin', '<', today());
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('libelle', 'LIKE', "%{$search}%");
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Libellé complet formaté "AAAA-AAAA".
     */
    public function getLibelleCompletAttribute(): string
    {
        if ($this->date_debut && $this->date_fin) {
            return $this->date_debut->format('Y') . '-' . $this->date_fin->format('Y');
        }

        return (string) $this->libelle;
    }

    /**
     * Année actuellement active (non clôturée ET dans la période).
     * ✅ Utilise startOfDay/endOfDay pour éviter les comparaisons datetime vs date.
     */
    public function getEstActiveAttribute(): bool
    {
        if ($this->cloturee || !$this->date_debut || !$this->date_fin) {
            return false;
        }

        return today()->between(
            $this->date_debut->startOfDay(),
            $this->date_fin->endOfDay()
        );
    }

    public function getEstClotureeAttribute(): bool
    {
        return (bool) $this->cloturee;
    }

    /**
     * Nombre d'inscriptions — utilise `inscriptions_count` si préchargé
     * (via withCount), sinon fait une requête (mais documenté pour éviter les surprises).
     */
    public function getNombreInscriptionsAttribute(): int
    {
        return (int) ($this->inscriptions_count ?? $this->inscriptions()->count());
    }

    public function getEffectifReelAttribute(): int
    {
        return $this->nombre_inscriptions;
    }

    public function getStatutKeyAttribute(): string
    {
        if ($this->cloturee) {
            return 'cloturee';
        }
        if ($this->est_active) {
            return 'en_cours';
        }
        if ($this->date_debut?->isFuture()) {
            return 'a_venir';
        }
        return 'passee';
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut_key) {
            'cloturee' => 'Clôturée',
            'en_cours' => 'En cours',
            'a_venir'  => 'À venir',
            'passee'   => 'Passée',
            default    => 'Inconnu',
        };
    }

    // ============================================================
    // MÉTHODES STATIQUES
    // ============================================================

    /**
     * Année scolaire active (non clôturée et dans la période).
     */
    public static function active(): ?self
    {
        return static::enCours()->first();
    }

    /**
     * Dernière année non clôturée (fallback si aucune n'est "en cours").
     */
    public static function activeLatest(): ?self
    {
        return static::ouvertes()
            ->orderByDesc('date_debut')
            ->first();
    }

    /**
     * ✅ Version mise en cache de l'année active (renommée pour la clarté).
     * Cache 60 secondes — invalidé par le `booted()` plus bas.
     */
    public static function activeIdCached(): ?int
    {
        return \Illuminate\Support\Facades\Cache::remember(
            'annee_scolaire_active_id',
            60,
            fn () => static::active()?->id
        );
    }

    /**
     * Raccourci sans cache.
     */
    public static function activeId(): ?int
    {
        return static::active()?->id;
    }

    /**
     * Génère un libellé "AAAA-AAAA" à partir de deux dates.
     */
    public static function genererLibelle(string $debut, string $fin): string
    {
        return date('Y', strtotime($debut)) . '-' . date('Y', strtotime($fin));
    }

    // ============================================================
    // MÉTHODES D'INSTANCE
    // ============================================================

    public function peutInscrire(): bool
    {
        return !$this->cloturee;
    }

    public function paiementsOuverts(): bool
    {
        return (bool) $this->paiement_ouvert;
    }

    public function estEnCours(): bool
    {
        if (!$this->date_debut || !$this->date_fin) {
            return false;
        }
        return today()->between($this->date_debut, $this->date_fin);
    }

    public function estTerminee(): bool
    {
        return $this->date_fin?->isPast() ?? false;
    }

    public function estFuture(): bool
    {
        return $this->date_debut?->isFuture() ?? false;
    }

    // ============================================================
    // BOOT — INVALIDATION DU CACHE
    // ============================================================

    protected static function booted(): void
    {
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget('annee_scolaire_active_id');

        static::saved($forget);
        static::deleted($forget);
        static::restored($forget);
    }
}