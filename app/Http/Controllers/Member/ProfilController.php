<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Affiche la fiche de profil de l'utilisateur connecté (lecture seule).
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à votre profil.');
        }

        return view('member.profil.show', compact('user'));
    }

    /**
     * Affiche le formulaire de modification du profil.
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour modifier votre profil.');
        }

        return view('member.profil.edit', compact('user'));
    }

    /**
     * Met à jour les informations du profil.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Session expirée. Veuillez vous reconnecter.');
        }

        // ============================================================
        // VALIDATION
        // ============================================================
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'sexe'           => ['nullable', 'in:M,F'],
            'telephone'      => ['nullable', 'string', 'max:20'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'adresse'        => ['nullable', 'string', 'max:500'],
            'photo'          => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
        ], [
            'name.required'          => 'Le nom est obligatoire.',
            'name.max'               => 'Le nom ne peut pas dépasser 255 caractères.',
            'sexe.in'                => 'Le sexe doit être M ou F.',
            'telephone.max'          => 'Le téléphone ne peut pas dépasser 20 caractères.',
            'date_naissance.date'    => 'La date de naissance doit être une date valide.',
            'date_naissance.before'  => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'adresse.max'            => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'photo.image'            => 'Le fichier doit être une image.',
            'photo.mimes'            => 'L\'image doit être au format jpeg, png, jpg, gif, svg ou webp.',
            'photo.max'              => 'L\'image ne doit pas dépasser 2 Mo.',
        ]);

        try {
            // ============================================================
            // MISE À JOUR DES CHAMPS AUTORISÉS
            // ============================================================
            $user->fill([
                'name'           => $validated['name'],
                'sexe'           => $validated['sexe'] ?? $user->sexe,
                'telephone'      => $validated['telephone'] ?? $user->telephone,
                'date_naissance' => $validated['date_naissance'] ?? $user->date_naissance,
                'adresse'        => $validated['adresse'] ?? $user->adresse,
            ]);

            // ============================================================
            // GESTION DE LA PHOTO
            // ============================================================
            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo si elle existe
                if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                    Storage::disk('public')->delete($user->photo);
                }

                // Enregistrer la nouvelle photo
                $path = $request->file('photo')->store('photos/users', 'public');
                $user->photo = $path;
            }

            $user->save();

            Log::info('Profil mis à jour', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return redirect()
                ->route('member.profil.edit')
                ->with('success', 'Votre profil a été mis à jour avec succès.');

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la mise à jour du profil', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.');
        }
    }
}