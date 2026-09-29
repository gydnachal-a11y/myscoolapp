<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReglementInterieur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReglementInterieurController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const CATEGORIES = ['regle', 'obligation', 'interdiction'];
    private const STATUTS    = ['actif', 'inactif'];
    private const PER_PAGE   = 15;
    private const ORDRE_DEFAUT = 9999;

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        /* ---------- Validation des filtres ---------- */
        $validated = $request->validate([
            'categorie' => ['nullable', 'in:' . implode(',', self::CATEGORIES)],
            'statut'    => ['nullable', 'in:' . implode(',', self::STATUTS)],
            'search'    => ['nullable', 'string', 'max:100'],
            'per_page'  => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $categorie = $validated['categorie'] ?? null;
        $statut    = $validated['statut']    ?? null;
        $search    = trim((string) ($validated['search'] ?? ''));
        $perPage   = (int) ($validated['per_page'] ?? self::PER_PAGE);

        /* ---------- Échappement des wildcards LIKE ---------- */
        $searchEscaped = $search !== ''
            ? str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search)
            : null;

        /* ---------- Requête filtrée ---------- */
        $reglements = ReglementInterieur::query()
            ->when($categorie !== null, fn ($q) => $q->where('categorie', $categorie))
            ->when($statut !== null,    fn ($q) => $q->where('est_actif', $statut === 'actif'))
            ->when($searchEscaped !== null, fn ($q) => $q->where('titre', 'like', "%{$searchEscaped}%"))
            ->orderByRaw('COALESCE(ordre, ?) ASC', [self::ORDRE_DEFAUT])
            ->orderBy('titre')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.reglements.index', compact('reglements'));
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.reglements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateReglement($request);

        // ✅ Gestion explicite du booléen (checkbox décochée → false)
        $data['est_actif'] = $request->boolean('est_actif');
        $data['ordre']     = $data['ordre'] ?? self::ORDRE_DEFAUT;

        $reglement = ReglementInterieur::create($data);

        $this->logAction($request, 'créé', $reglement);

        return redirect()
            ->route('admin.reglements.index')
            ->with('success', 'Règlement ajouté avec succès.');
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(ReglementInterieur $reglement): View
    {
        return view('admin.reglements.edit', compact('reglement'));
    }

    public function update(Request $request, ReglementInterieur $reglement): RedirectResponse
    {
        $data = $this->validateReglement($request);

        // ✅ Idem : on force la valeur du booléen
        $data['est_actif'] = $request->boolean('est_actif');
        $data['ordre']     = $data['ordre'] ?? self::ORDRE_DEFAUT;

        // Capture de l'état avant pour le log
        $before = $reglement->only(['titre', 'categorie', 'ordre', 'est_actif']);

        $reglement->update($data);

        $this->logAction($request, 'mis à jour', $reglement, [
            'avant'  => $before,
            'apres'  => $reglement->only(['titre', 'categorie', 'ordre', 'est_actif']),
            'changes'=> array_keys($reglement->getChanges()),
        ]);

        return redirect()
            ->route('admin.reglements.index')
            ->with('success', 'Règlement mis à jour.');
    }

    // ============================================================
    // TOGGLE ACTIVE
    // ============================================================

    public function toggleActive(Request $request, ReglementInterieur $reglement): RedirectResponse
    {
        $reglement->update(['est_actif' => ! $reglement->est_actif]);

        $etat = $reglement->est_actif ? 'activé' : 'désactivé';

        $this->logAction($request, $etat, $reglement);

        return back()->with('success', "Règlement {$etat}.");
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Request $request, ReglementInterieur $reglement): RedirectResponse
    {
        $snapshot = $reglement->only(['id', 'titre', 'categorie']);

        $reglement->delete();

        $this->logAction($request, 'supprimé', $reglement, ['snapshot' => $snapshot]);

        return redirect()
            ->route('admin.reglements.index')
            ->with('success', 'Règlement supprimé.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Règles de validation communes à store() et update().
     */
    private function validateReglement(Request $request): array
    {
        return $request->validate([
            'titre'     => ['required', 'string', 'max:255'],
            'contenu'   => ['required', 'string', 'max:20000'],
            'categorie' => ['required', 'in:' . implode(',', self::CATEGORIES)],
            'ordre'     => ['nullable', 'integer', 'min:0', 'max:9999'],
            'est_actif' => ['sometimes', 'boolean'],
        ], [
            'titre.required'     => 'Le titre est obligatoire.',
            'titre.max'          => 'Le titre ne doit pas dépasser :max caractères.',
            'contenu.required'   => 'Le contenu est obligatoire.',
            'contenu.max'        => 'Le contenu est trop long (max :max caractères).',
            'categorie.required' => 'La catégorie est obligatoire.',
            'categorie.in'       => 'La catégorie sélectionnée est invalide.',
            'ordre.integer'      => 'L\'ordre doit être un nombre entier.',
            'ordre.min'          => 'L\'ordre ne peut pas être négatif.',
            'ordre.max'          => 'L\'ordre ne peut pas dépasser :max.',
        ]);
    }

    /**
     * Log unifié des actions sur les règlements.
     */
    private function logAction(
        Request $request,
        string $action,
        ReglementInterieur $reglement,
        array $extra = []
    ): void {
        Log::info("Règlement intérieur {$action}", array_merge([
            'reglement_id' => $reglement->id,
            'titre'        => $reglement->titre,
            'categorie'    => $reglement->categorie,
            'user_id'      => $request->user()?->id,
        ], $extra));
    }
}