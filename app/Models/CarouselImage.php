<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CarouselImage extends Model
{
    protected $table = 'carousel_images';

    protected $fillable = [
        'image_path',
        'alt_text',
        'ordre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'ordre'     => 'integer',
    ];

    /**
     * Attributs virtuels automatiquement inclus lors de la sérialisation JSON.
     * (utile si vous exposez le modèle via API — évite d'ajouter `image_url` à la main)
     */
    protected $appends = ['image_url'];

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * URL publique complète de l'image.
     * Retourne '' si aucun chemin n'est défini (évite asset('storage/') tout seul).
     */
    public function getImageUrlAttribute(): string
    {
        if (empty($this->image_path)) {
            return '';
        }

        // Si le chemin est déjà une URL absolue (http/https), on la renvoie telle quelle
        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset('storage/' . ltrim($this->image_path, '/'));
    }

    /**
     * Alt text avec fallback pour ne jamais avoir d'attribut alt vide (SEO + a11y).
     */
    public function getAltTextAttribute($value): string
    {
        return $value ?: 'Image du carrousel';
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Uniquement les images actives.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Tri par ordre d'affichage.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('ordre')->orderBy('id');
    }

    // ============================================================
    // SÉRIALISATION POUR CACHE / VUE
    // ============================================================

    /**
     * Représentation "propre" sous forme d'array pur (scalaires uniquement).
     *
     * ⚠️ Essentiel pour PHP 8.5 : jamais d'objet dans le cache.
     * Cette méthode renvoie UNIQUEMENT ce dont la vue a besoin.
     */
    public function toCarouselArray(): array
    {
        return [
            'image_url' => $this->image_url,
            'alt_text'  => $this->alt_text,
        ];
    }
}