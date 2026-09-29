<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Devise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviseController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================

    public function index(): View
    {
        $devises = Devise::query()
            ->orderByDesc('est_defaut')
            ->orderBy('code')
            ->get();

        return view('admin.devises.index', compact('devises'));
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.devises.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDevise($request);

        $devise = Devise::create($data);

        return redirect()
            ->route('admin.devises.index')
            ->with('success', "Devise « {$devise->code} » créée avec succès.");
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(Devise $devise): View
    {
        return view('admin.devises.show', compact('devise'));
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(Devise $devise): View
    {
        return view('admin.devises.edit', compact('devise'));
    }

    public function update(Request $request, Devise $devise): RedirectResponse
    {
        $data = $this->validateDevise($request, $devise->id);

        $devise->update($data);

        return redirect()
            ->route('admin.devises.index')
            ->with('success', "Devise « {$devise->code} » mise à jour.");
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Devise $devise): RedirectResponse
    {
        // Empêcher la suppression d'une devise utilisée par un taux
        if ($devise->tauxSources()->exists() || $devise->tauxCibles()->exists()) {
            return back()->with('error', "Impossible : cette devise est utilisée dans un ou plusieurs taux de change.");
        }

        // Empêcher la suppression de la devise par défaut
        if ($devise->est_defaut) {
            return back()->with('error', "Impossible de supprimer la devise par défaut.");
        }

        $code = $devise->code;
        $devise->delete();

        return redirect()
            ->route('admin.devises.index')
            ->with('success', "Devise « {$code} » supprimée.");
    }

    // ============================================================
    // VALIDATION
    // ============================================================

    private function validateDevise(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                'uppercase',
                Rule::unique('devises', 'code')->ignore($ignoreId),
            ],
            'nom'        => ['required', 'string', 'max:255'],
            'symbole'    => ['nullable', 'string', 'max:10'],
            'est_defaut' => ['nullable', 'boolean'],
        ], [
            'code.required'  => 'Le code est obligatoire.',
            'code.unique'    => 'Une devise avec ce code existe déjà.',
            'code.max'       => 'Le code ne peut dépasser :max caractères.',
            'nom.required'   => 'Le nom est obligatoire.',
            'nom.max'        => 'Le nom ne peut dépasser :max caractères.',
        ]);
    }
}