<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\CarouselImage;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SiteSettingController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const LOGO_DIRECTORY     = 'logos';
    private const CAROUSEL_DIRECTORY = 'carousel';

    /** 2048 KB = 2 Mo pour le logo. */
    private const MAX_LOGO_SIZE_KB = 2048;

    /** 5120 KB = 5 Mo pour les images carrousel. */
    private const MAX_CAROUSEL_SIZE_KB = 5120;

    /** Dimensions minimales / maximales pour éviter les abus. */
    private const MIN_DIMENSION = 50;
    private const MAX_DIMENSION = 4000;

    private const ALLOWED_LOGO_MIMES     = 'jpeg,png,jpg,gif,webp';
    private const ALLOWED_CAROUSEL_MIMES = 'jpeg,png,jpg,gif,webp';

    /** Nom du champ booléen d'affichage des liens publics. */
    private const FIELD_SHOW_PUBLIC_LINKS = 'show_public_links';

    // ============================================================
    // EDIT
    // ============================================================

    public function edit(): View
    {
        $settings = SiteSetting::getSettings();

        $carouselImages = CarouselImage::query()
            ->orderBy('ordre')
            ->orderBy('id')
            ->get();

        return view('admin.site-settings.edit', compact('settings', 'carouselImages'));
    }

    // ============================================================
    // UPDATE (texte + logo + interrupteur liens publics)
    // ============================================================

    public function update(Request $request): RedirectResponse
    {
        $validated = $this->validateSettings($request);

        // ✅ CORRECTION BUG : on utilise le MODÈLE Eloquent, pas le stdClass
        $settings = SiteSetting::getModel();

        // ✅ Normalisation du booléen : checkbox décochée = absent du POST
        $validated[self::FIELD_SHOW_PUBLIC_LINKS] = $request->boolean(self::FIELD_SHOW_PUBLIC_LINKS);

        $oldLogo = $settings->site_logo;
        $newLogo = null;

        // ---------- Gestion du logo : on stocke AVANT la DB ----------
        if ($request->hasFile('site_logo')) {
            $newLogo = $request->file('site_logo')
                ->store(self::LOGO_DIRECTORY, 'public');

            $validated['site_logo'] = $newLogo;
        }

        try {
            DB::transaction(function () use ($settings, $validated): void {
                $settings->update($validated);
            });

            // ✅ Suppression de l'ancien logo SEULEMENT après succès
            if ($newLogo && $oldLogo && $oldLogo !== $newLogo) {
                $this->deleteFileSilently($oldLogo);
            }

        } catch (Throwable $e) {
            // Rollback du fichier orphelin
            if ($newLogo) {
                $this->deleteFileSilently($newLogo);
            }

            Log::error('Échec mise à jour site settings', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);

            throw $e;
        }

        // ✅ Invalidation du cache home (le modèle invalide déjà le sien)
        HomeController::clearHomeCache();

        $this->logAction($request, 'mis à jour', 'settings');

        return redirect()
            ->route('admin.site-settings.edit')
            ->with('success', 'Paramètres mis à jour avec succès.');
    }

    // ============================================================
    // CARROUSEL — AJOUT
    // ============================================================

    public function storeCarouselImage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimetypes:' . self::ALLOWED_CAROUSEL_MIMES,
                'max:' . self::MAX_CAROUSEL_SIZE_KB,
                'dimensions:min_width=' . self::MIN_DIMENSION
                    . ',min_height=' . self::MIN_DIMENSION
                    . ',max_width=' . self::MAX_DIMENSION
                    . ',max_height=' . self::MAX_DIMENSION,
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'ordre'    => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'image.dimensions' => 'L\'image doit faire entre :min_width×:min_height et :max_width×:max_height pixels.',
        ]);

        $path = $request->file('image')
            ->store(self::CAROUSEL_DIRECTORY, 'public');

        $ordre = $validated['ordre']
            ?? ((int) CarouselImage::max('ordre')) + 1;

        $image = CarouselImage::create([
            'image_path' => $path,
            'alt_text'   => $validated['alt_text'] ?? null,
            'ordre'      => $ordre,
        ]);

        // ✅ Invalidation du cache home (le carrousel y est caché)
        HomeController::clearHomeCache();

        $this->logAction($request, 'ajoutée au carrousel', 'carousel', [
            'carousel_id' => $image->id,
            'ordre'       => $ordre,
        ]);

        return redirect()
            ->route('admin.site-settings.edit')
            ->with('success', 'Image ajoutée au carrousel.');
    }

    // ============================================================
    // CARROUSEL — SUPPRESSION
    // ============================================================

    public function destroyCarouselImage(Request $request, CarouselImage $carouselImage): RedirectResponse
    {
        $snapshot = [
            'id'         => $carouselImage->id,
            'image_path' => $carouselImage->image_path,
            'ordre'      => $carouselImage->ordre,
        ];

        // Suppression DB d'abord (source de vérité)
        $carouselImage->delete();

        // Puis fichier (silencieux si absent)
        $this->deleteFileSilently($snapshot['image_path']);

        // ✅ Invalidation du cache home
        HomeController::clearHomeCache();

        $this->logAction($request, 'supprimée du carrousel', 'carousel', $snapshot);

        return redirect()
            ->route('admin.site-settings.edit')
            ->with('success', 'Image supprimée du carrousel.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Règles de validation des paramètres du site.
     */
    private function validateSettings(Request $request): array
    {
        return $request->validate([
            'site_name'            => ['nullable', 'string', 'max:255'],
            'site_slogan'          => ['nullable', 'string', 'max:255'],
            'site_email'           => ['nullable', 'email:rfc,dns', 'max:255'],
            'site_phone'           => ['nullable', 'string', 'max:50'],
            'site_secondary_phone' => ['nullable', 'string', 'max:50'],
            'site_address'         => ['nullable', 'string', 'max:500'],
            'site_description'     => ['nullable', 'string', 'max:2000'],
            'responsable_name'     => ['nullable', 'string', 'max:255'],
            'creation_date'        => ['nullable', 'date', 'before_or_equal:today'],

            // ✅ Interrupteur liens publics
            self::FIELD_SHOW_PUBLIC_LINKS => ['nullable', 'boolean'],

            'facebook_url'  => ['nullable', 'url:http,https', 'max:255'],
            'twitter_url'   => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'youtube_url'   => ['nullable', 'url:http,https', 'max:255'],

            'site_logo' => [
                'nullable',
                'image',
                'mimetypes:' . self::ALLOWED_LOGO_MIMES,
                'max:' . self::MAX_LOGO_SIZE_KB,
                'dimensions:min_width=' . self::MIN_DIMENSION
                    . ',min_height=' . self::MIN_DIMENSION
                    . ',max_width=' . self::MAX_DIMENSION
                    . ',max_height=' . self::MAX_DIMENSION,
            ],
        ], [
            'site_email.email'              => 'L\'adresse email n\'est pas valide.',
            'creation_date.before_or_equal' => 'La date de création ne peut pas être dans le futur.',
            'facebook_url.url'              => 'L\'URL Facebook doit être une URL valide (http/https).',
            'twitter_url.url'               => 'L\'URL Twitter/X doit être une URL valide (http/https).',
            'instagram_url.url'             => 'L\'URL Instagram doit être une URL valide (http/https).',
            'youtube_url.url'               => 'L\'URL YouTube doit être une URL valide (http/https).',
            'site_logo.mimetypes'           => 'Le logo doit être au format JPEG, PNG, JPG, GIF ou WebP.',
            'site_logo.max'                 => 'Le logo ne doit pas dépasser 2 Mo.',
            'site_logo.dimensions'          => 'Le logo doit faire entre 50×50 et 4000×4000 pixels.',
        ]);
    }

    /**
     * Supprime un fichier du disque public, sans planter s'il est déjà absent.
     */
    private function deleteFileSilently(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable $e) {
            Log::warning('Échec suppression fichier', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log unifié des actions.
     */
    private function logAction(
        Request $request,
        string $action,
        string $target,
        array $extra = [],
    ): void {
        Log::info("SiteSettings — {$target} {$action}", array_merge([
            'user_id' => $request->user()?->id,
        ], $extra));
    }
}