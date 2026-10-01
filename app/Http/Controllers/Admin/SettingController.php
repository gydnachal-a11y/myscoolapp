<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    /** Dossiers de stockage. */
    private const LOGO_DIRECTORY = 'logos';

    /** Tailles max (en Ko). */
    private const MAX_LOGO_SIZE_KB = 2048;

    /** Clés autorisées pour les paramètres texte. */
    private const TEXT_KEYS = [
        'ecole_nom',
        'ecole_adresse',
        'ecole_telephone',
        'ecole_email',
    ];

    /** Clé du canvas d'en-tête. */
    private const CANVAS_KEY = 'canvas_header';

    /** Clé du logo. */
    private const LOGO_KEY = 'ecole_logo';

    // ============================================================
    // PARAMÈTRES SIMPLES (TEXTE + LOGO)
    // ============================================================

    /**
     * Formulaire de paramètres.
     */
    public function edit(): View
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('admin.settings.edit', compact('settings'));
    }

    /**
     * Met à jour les paramètres texte + logo.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $this->validateSettings($request);

        $ancienLogo = Setting::getValue(self::LOGO_KEY);
        $nouveauLogo = null;

        // ✅ On stocke AVANT la DB (fichier orphelin si rollback)
        if ($request->hasFile(self::LOGO_KEY)) {
            $nouveauLogo = $request->file(self::LOGO_KEY)
                ->store(self::LOGO_DIRECTORY, 'public');
        }

        try {
            // ----- Champs texte -----
            foreach (self::TEXT_KEYS as $key) {
                if (array_key_exists($key, $data)) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => $data[$key]]
                    );
                }
            }

            // ----- Logo -----
            if ($nouveauLogo !== null) {
                Setting::updateOrCreate(
                    ['key' => self::LOGO_KEY],
                    ['value' => $nouveauLogo]
                );
            }

            // ✅ Supprime l'ancien logo SEULEMENT après succès
            if ($nouveauLogo && $ancienLogo && $ancienLogo !== $nouveauLogo) {
                $this->deleteFileSilently($ancienLogo);
            }

            $this->logAction($request, 'Paramètres généraux mis à jour');

            return back()->with('success', 'Paramètres mis à jour.');

        } catch (Throwable $e) {
            // Rollback du fichier orphelin
            if ($nouveauLogo) {
                $this->deleteFileSilently($nouveauLogo);
            }

            Log::error('Échec mise à jour paramètres', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // ÉDITEUR CANVAS
    // ============================================================

    /**
     * Éditeur canvas (interface visuelle).
     */
    public function canvas(): View
    {
        return view('admin.settings.canvas');
    }

    /**
     * Sauvegarde du canvas (JSON).
     */
    public function storeCanvas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'canvas_data' => ['required', 'json', 'max:500000'], // 500 KB max
        ], [
            'canvas_data.required' => 'Aucune donnée à enregistrer.',
            'canvas_data.json'     => 'Le format des données est invalide.',
            'canvas_data.max'      => 'La mise en page est trop volumineuse (500 KB max).',
        ]);

        try {
            Setting::updateOrCreate(
                ['key' => self::CANVAS_KEY],
                ['value' => $data['canvas_data']]
            );

            $this->logAction($request, 'Canvas en-tête mis à jour');

            return back()->with('success', 'Mise en page enregistrée.');

        } catch (Throwable $e) {
            Log::error('Échec sauvegarde canvas', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible d\'enregistrer la mise en page.');
        }
    }

    /**
     * Chargement du canvas (JSON).
     */
    public function loadCanvas(): JsonResponse
    {
        $json = Setting::getValue(self::CANVAS_KEY, '{}');

        // ✅ Sécurité : si la valeur n'est pas un JSON valide, on renvoie un objet vide
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            $decoded = new \stdClass(); // objet vide → "{}"
        }

        return response()->json($decoded);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Validation des paramètres simples.
     */
    private function validateSettings(Request $request): array
    {
        return $request->validate([
            'ecole_nom'       => ['required', 'string', 'max:255'],
            'ecole_adresse'   => ['nullable', 'string', 'max:255'],
            'ecole_telephone' => ['nullable', 'string', 'max:50'],
            'ecole_email'     => ['nullable', 'email:rfc', 'max:100'],

            self::LOGO_KEY => [
                'nullable',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:' . self::MAX_LOGO_SIZE_KB,
            ],
        ], [
            'ecole_nom.required' => 'Le nom de l\'école est obligatoire.',
            'ecole_email.email'  => 'L\'adresse email n\'est pas valide.',
            self::LOGO_KEY . '.mimetypes' => 'Le logo doit être au format JPEG, PNG ou WebP.',
            self::LOGO_KEY . '.max'       => 'Le logo ne doit pas dépasser 2 Mo.',
        ]);
    }

    /**
     * Supprime un fichier du disque public, sans planter s'il est absent.
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
     * Log unifié.
     */
    private function logAction(Request $request, string $message): void
    {
        Log::info($message, [
            'user_id' => $request->user()?->id,
            'ip'      => $request->ip(),
        ]);
    }
}