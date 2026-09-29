<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnonceRequest;
use App\Http\Requests\UpdateAnnonceRequest;
use App\Models\Annonce;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnnonceController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    public const TYPE_PUBLIC = 'public';
    public const TYPE_PRIVE  = 'prive';

    private const IMAGE_DIRECTORY = 'annonces';

    private const ALLOWED_IMAGE_MIMES = 'jpeg,png,jpg,gif,webp';
    private const MAX_IMAGE_SIZE_KB   = 2048;
    private const PER_PAGE            = 10;

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        /* ---------- Validation des filtres ---------- */
        $validated = $request->validate([
            'statut'  => ['nullable', 'in:0,1'],
            'type'    => ['nullable', 'in:' . self::TYPE_PUBLIC . ',' . self::TYPE_PRIVE],
            'search'  => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $statut  = $validated['statut']  ?? null;
        $type    = $validated['type']    ?? null;
        $search  = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE);

        /* ---------- Requête filtrée ---------- */
        $annonces = Annonce::query()
            ->when($statut !== null, fn ($q) => $q->where('est_active', (bool) $statut))
            ->when($type !== null,   fn ($q) => $q->where('type', $type))
            ->when($search !== '',   fn ($q) => $q->where('titre', 'LIKE', "%{$search}%"))
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        /* ---------- Statistiques (1 requête) ---------- */
        $stats = Annonce::query()
            ->selectRaw('
                COUNT(*)                                                AS total,
                SUM(CASE WHEN est_active = 1 THEN 1 ELSE 0 END)         AS actives,
                SUM(CASE WHEN est_active = 0 THEN 1 ELSE 0 END)         AS inactives,
                SUM(CASE WHEN type = ? THEN 1 ELSE 0 END)               AS publiques,
                SUM(CASE WHEN type = ? THEN 1 ELSE 0 END)               AS privees
            ', [self::TYPE_PUBLIC, self::TYPE_PRIVE])
            ->first();

        return view('admin.annonces.index', [
            'annonces'       => $annonces,
            'totalAnnonces'  => (int)   ($stats->total      ?? 0),
            'totalActives'   => (int)   ($stats->actives    ?? 0),
            'totalInactives' => (int)   ($stats->inactives  ?? 0),
            'totalPubliques' => (int)   ($stats->publiques  ?? 0),
            'totalPrivees'   => (int)   ($stats->privees    ?? 0),
        ]);
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.annonces.create');
    }

    public function store(StoreAnnonceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['est_active'] = $request->boolean('est_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'));
        }

        $annonce = Annonce::create($data);

        $this->logAction($request, 'créée', $annonce);

        return redirect()
            ->route('admin.annonces.index')
            ->with('success', 'Annonce créée avec succès.');
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(Annonce $annonce): View
    {
        return view('admin.annonces.edit', compact('annonce'));
    }

    public function update(UpdateAnnonceRequest $request, Annonce $annonce): RedirectResponse
    {
        $data = $request->validated();
        $data['est_active'] = $request->boolean('est_active');

        if ($request->hasFile('image')) {
            $this->deleteImage($annonce->image);
            $data['image'] = $this->storeImage($request->file('image'));
        }

        $annonce->update($data);

        $this->logAction($request, 'mise à jour', $annonce);

        return redirect()
            ->route('admin.annonces.index')
            ->with('success', 'Annonce mise à jour avec succès.');
    }

    // ============================================================
    // TOGGLE ACTIVE
    // ============================================================

    public function toggleActive(Request $request, Annonce $annonce): RedirectResponse
    {
        $annonce->update(['est_active' => ! $annonce->est_active]);

        $etat = $annonce->est_active ? 'activée' : 'désactivée';

        $this->logAction($request, $etat, $annonce);

        return back()->with('success', "Annonce {$etat} avec succès.");
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Request $request, Annonce $annonce): RedirectResponse
    {
        $titre = $annonce->titre;

        $this->deleteImage($annonce->image);
        $annonce->delete();

        $this->logAction($request, 'supprimée', $annonce, ['titre' => $titre]);

        return redirect()
            ->route('admin.annonces.index')
            ->with('success', 'Annonce supprimée avec succès.');
    }

    // ============================================================
    // UPLOAD IMAGE (TinyMCE)
    // ============================================================

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'image',
                'mimes:' . self::ALLOWED_IMAGE_MIMES,
                'max:' . self::MAX_IMAGE_SIZE_KB,
            ],
        ], [
            'file.required' => 'Aucun fichier reçu.',
            'file.image'    => 'Le fichier doit être une image.',
            'file.mimes'    => 'Format accepté : jpeg, png, jpg, gif, webp.',
            'file.max'      => 'L\'image ne doit pas dépasser 2 Mo.',
        ]);

        $path = $this->storeImage($request->file('file'));

        return response()->json([
            'location' => asset('storage/' . $path),
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Stocke une image et retourne le chemin relatif.
     */
    private function storeImage(UploadedFile $file): string
    {
        return $file->store(self::IMAGE_DIRECTORY, 'public');
    }

    /**
     * Supprime une image du stockage si elle existe.
     */
    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Log unifié des actions sur les annonces.
     */
    private function logAction(
        Request $request,
        string $action,
        Annonce $annonce,
        array $extra = []
    ): void {
        Log::info("Annonce {$action}", array_merge([
            'annonce_id' => $annonce->id,
            'titre'      => $annonce->titre,
            'user_id'    => $request->user()?->id,
        ], $extra));
    }
}