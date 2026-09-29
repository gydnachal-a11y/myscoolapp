<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $table = 'site_settings';

    protected $fillable = [
        'site_name',
        'site_slogan',
        'site_logo',
        'site_email',
        'site_phone',
        'site_secondary_phone',
        'site_address',
        'site_description',
        'responsable_name',
        'creation_date',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'youtube_url',
        'show_public_links',
    ];

    protected $casts = [
        'creation_date'     => 'date',
        'show_public_links' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
        'logo_html',
    ];

    // ============================================================
    // VALEURS PAR DÉFAUT
    // ============================================================

    public const DEFAULT_SITE_NAME   = 'Gydn-Site-Scool';
    public const DEFAULT_SLOGAN      = 'Former les leaders de demain';
    public const DEFAULT_DESCRIPTION = 'École moderne offrant un enseignement de qualité.';
    public const DEFAULT_RESPONSABLE = 'Direction Gydn';
    public const DEFAULT_EMAIL       = 'gydnachal@gmail.com';
    public const DEFAULT_PHONE       = '08933388000';
    public const DEFAULT_ADDRESS     = 'Kinshasa, RDC';

    // ============================================================
    // CACHE
    // ============================================================

    /** Clé de cache versionnée (incrémenter à chaque changement de structure). */
    private const CACHE_KEY = 'site_settings_v3';   // ⬆️ v3

    /** TTL long — le cache est invalidé par les hooks de modèle. */
    private const CACHE_TTL = 86400;   // 24 h (invalidé sur write de toute façon)

    // ============================================================
    // API PUBLIQUE — LECTURE
    // ============================================================

    /**
     * Paramètres prêts pour la vue (stdClass + Carbon rehydraté).
     * Usage : `$settings->site_name`, `$settings->creation_date?->format('Y')`.
     */
    public static function getSettings(): object
    {
        $data = self::getSettingsRaw();

        if (!empty($data['creation_date']) && is_string($data['creation_date'])) {
            try {
                $data['creation_date'] = Carbon::parse($data['creation_date']);
            } catch (\Throwable) {
                $data['creation_date'] = null;
            }
        }

        return (object) $data;
    }

    /**
     * Paramètres en ARRAY pur (scalaires uniquement) — pour le cache.
     * ⚠️ Ne contient JAMAIS d'objet (Carbon, Eloquent, stdClass).
     */
    public static function getSettingsRaw(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $model = self::query()->first();

            if (!$model) {
                return self::getDefaults();
            }

            return [
                'site_name'            => $model->site_name,
                'site_slogan'          => $model->site_slogan,
                'site_logo'            => $model->site_logo,
                'logo_url'             => $model->logo_url,
                'logo_html'            => $model->logo_html,
                'site_email'           => $model->site_email,
                'site_phone'           => $model->site_phone,
                'site_secondary_phone' => $model->site_secondary_phone,
                'site_address'         => $model->site_address,
                'site_description'     => $model->site_description,
                'responsable_name'     => $model->responsable_name,
                'creation_date'        => $model->creation_date?->toIso8601String(),
                'facebook_url'         => $model->facebook_url,
                'twitter_url'          => $model->twitter_url,
                'instagram_url'        => $model->instagram_url,
                'youtube_url'          => $model->youtube_url,
                'show_public_links'    => (bool) $model->show_public_links,
            ];
        });
    }

    /**
     * Renvoie le MODÈLE Eloquent (pour update/save côté admin).
     *
     * ✅ Indispensable : `getSettings()` renvoie un stdClass
     *    sur lequel on NE PEUT PAS appeler `update()`.
     *
     * Crée un enregistrement vide si aucun n'existe, pour que
     * `$model->update([...])` fonctionne toujours.
     */
    public static function getModel(): self
    {
        return self::query()->firstOrCreate([]);
    }

    /**
     * Paramètres par défaut si aucun enregistrement en base.
     */
    public static function getDefaults(): array
    {
        return [
            'site_name'            => self::DEFAULT_SITE_NAME,
            'site_slogan'          => self::DEFAULT_SLOGAN,
            'site_logo'            => null,
            'logo_url'             => '',
            'logo_html'            => '<span class="text-2xl font-extrabold text-indigo-600">Gydn</span>',
            'site_email'           => self::DEFAULT_EMAIL,
            'site_phone'           => self::DEFAULT_PHONE,
            'site_secondary_phone' => null,
            'site_address'         => self::DEFAULT_ADDRESS,
            'site_description'     => self::DEFAULT_DESCRIPTION,
            'responsable_name'     => self::DEFAULT_RESPONSABLE,
            'creation_date'        => null,
            'facebook_url'         => null,
            'twitter_url'          => null,
            'instagram_url'        => null,
            'youtube_url'          => null,
            'show_public_links'    => true,
        ];
    }

    // ============================================================
    // ACCESSORS (API moderne Laravel 9+)
    // ============================================================

    protected function siteName(): Attribute
    {
        return Attribute::get(fn ($value): string => $value ?: self::DEFAULT_SITE_NAME);
    }

    protected function siteSlogan(): Attribute
    {
        return Attribute::get(fn ($value): string => $value ?: self::DEFAULT_SLOGAN);
    }

    protected function siteDescription(): Attribute
    {
        return Attribute::get(fn ($value): string => $value ?: self::DEFAULT_DESCRIPTION);
    }

    protected function responsableName(): Attribute
    {
        return Attribute::get(fn ($value): string => $value ?: self::DEFAULT_RESPONSABLE);
    }

    protected function siteEmail(): Attribute
    {
        return Attribute::get(fn ($value): string => $value ?: self::DEFAULT_EMAIL);
    }

    protected function sitePhone(): Attribute
    {
        return Attribute::get(fn ($value): string => $value ?: self::DEFAULT_PHONE);
    }

    /**
     * URL publique du logo (vide si absent OU fichier manquant).
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (!$this->site_logo) {
                return '';
            }

            // ✅ Vérifie que le fichier existe réellement sur le disque
            if (!Storage::disk('public')->exists($this->site_logo)) {
                return '';
            }

            return asset('storage/' . $this->site_logo);
        });
    }

    /**
     * HTML du logo — prêt à insérer dans une vue.
     * ✅ URL échappée (htmlspecialchars) contre toute injection.
     */
    protected function logoHtml(): Attribute
    {
        return Attribute::get(function (): string {
            $url = $this->logo_url;

            if ($url !== '') {
                return sprintf(
                    '<img src="%s" alt="%s" class="h-10">',
                    e($url),
                    e($this->site_name),
                );
            }

            return '<span class="text-2xl font-extrabold text-indigo-600">Gydn</span>';
        });
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function hasSocialLinks(): bool
    {
        return (bool) (
            $this->facebook_url
            || $this->twitter_url
            || $this->instagram_url
            || $this->youtube_url
        );
    }

    public function hasLogo(): bool
    {
        return $this->logo_url !== '';
    }

    /**
     * ✅ Vérifie si les liens publics (élèves, paiements, proclamation,
     *    enregistrement) doivent être affichés sur la page d'accueil.
     */
    public function shouldShowPublicLinks(): bool
    {
        return (bool) $this->show_public_links;
    }

    // ============================================================
    // INVALIDATION DU CACHE
    // ============================================================

    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            self::clearCache();

            Log::info('SiteSetting modifié', [
                'id'      => $setting->id,
                'changes' => array_keys($setting->getChanges()),
            ]);
        });

        static::deleted(function (self $setting): void {
            self::clearCache();

            Log::warning('SiteSetting supprimé', ['id' => $setting->id]);
        });
    }

    /**
     * Vide tous les caches dépendant des paramètres du site.
     *
     * ✅ Invalide :
     *    - le cache propre au modèle (site_settings_v3)
     *    - le cache de la page d'accueil (home_page_data_v4)
     *
     * ⚠️ Appelé automatiquement à chaque save/delete.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        // Invalide la home (où les settings sont aussi cachés)
        if (class_exists(\App\Http\Controllers\HomeController::class)) {
            \App\Http\Controllers\HomeController::clearHomeCache();
        }
    }

    /**
     * Force le rechargement et retourne les paramètres frais.
     */
    public static function refreshCache(): object
    {
        self::clearCache();

        return self::getSettings();
    }
}