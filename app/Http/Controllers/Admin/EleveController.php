<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Http\Requests\StoreEleveRequest;
use App\Http\Requests\UpdateEleveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EleveController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * Affiche la liste des élèves non inscrits avec statistiques dynamiques.
     */
    public function index(Request $request)
    {
        // Construction de la requête filtrée (uniquement les élèves non inscrits)
        $query = $this->buildFilteredQuery($request)
            ->nonInscrits(); // 🔥 NOUVEAU : filtre les élèves disponibles

        // Statistiques dynamiques (basées sur les filtres)
        $totalEleves = $query->count();
        $totalGarcons = (clone $query)->where('sexe', Eleve::SEXE_MASCULIN)->count();
        $totalFilles  = (clone $query)->where('sexe', Eleve::SEXE_FEMININ)->count();

        // Pagination
        $eleves = $query->orderBy('nom')
            ->orderBy('prenom')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return view('admin.eleves.index', compact('eleves', 'totalEleves', 'totalGarcons', 'totalFilles'));
    }

    /**
     * Construit la requête filtrée pour les élèves.
     */
    private function buildFilteredQuery(Request $request)
    {
        return Eleve::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->input('search'));
                return $q->where(function ($sub) use ($search) {
                    $sub->where('nom', 'like', "%{$search}%")
                        ->orWhere('postnom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('sexe'), fn($q) => $q->where('sexe', $request->input('sexe')));
    }

    /**
     * Formulaire de création d'un élève.
     */
    public function create()
    {
        return view('admin.eleves.create');
    }

    /**
     * Enregistre un nouvel élève et ses responsables.
     */
    public function store(StoreEleveRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Gestion de la photo
            if ($request->hasFile('photo')) {
                $path = $this->storePhoto($request->file('photo'));
                if ($path) {
                    $data['photo'] = $path;
                } else {
                    throw new \Exception('Échec du stockage de la photo.');
                }
            }

            // Création de l'élève (par défaut, inscrit = false)
            $eleve = Eleve::create($data);
            if (!$eleve) {
                throw new \Exception('Échec de la création de l\'élève.');
            }

            // Synchronisation des responsables
            $this->syncResponsables($eleve, $data['responsables'] ?? []);

            DB::commit();

            Log::info('Élève créé avec succès', [
                'eleve_id' => $eleve->id,
                'nom'      => $eleve->nom,
                'prenom'   => $eleve->prenom,
                'user_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.eleves.index')
                ->with('success', 'Élève créé avec succès.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'élève', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le détail d'un élève.
     */
    public function show(Eleve $eleve)
    {
        $eleve->load('responsables');
        return view('admin.eleves.show', compact('eleve'));
    }

    /**
     * Formulaire de modification d'un élève.
     */
    public function edit(Eleve $eleve)
    {
        $eleve->load('responsables');
        return view('admin.eleves.edit', compact('eleve'));
    }

    /**
     * Met à jour un élève et ses responsables.
     */
    public function update(UpdateEleveRequest $request, Eleve $eleve)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Gestion de la photo
            if ($request->hasFile('photo')) {
                $this->deletePhoto($eleve->photo);
                $path = $this->storePhoto($request->file('photo'));
                if ($path) {
                    $data['photo'] = $path;
                } else {
                    throw new \Exception('Échec du stockage de la nouvelle photo.');
                }
            }

            // Mise à jour de l'élève
            $eleve->fill($data);
            if (!$eleve->save()) {
                throw new \Exception('Échec de la mise à jour de l\'élève.');
            }

            // Synchronisation des responsables
            $this->syncResponsables($eleve, $data['responsables'] ?? []);

            DB::commit();

            Log::info('Élève mis à jour avec succès', [
                'eleve_id' => $eleve->id,
                'nom'      => $eleve->nom,
                'prenom'   => $eleve->prenom,
                'user_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.eleves.index')
                ->with('success', 'Élève mis à jour avec succès.');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'élève', [
                'eleve_id' => $eleve->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
                'user_id'  => auth()->id(),
            ]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un élève et sa photo si existante.
     */
    public function destroy(Eleve $eleve)
    {
        try {
            // Vérifier si l'élève a des inscriptions
            if ($eleve->inscriptions()->exists()) {
                return back()->with('error', 'Cet élève a des inscriptions. Supprimez d\'abord les inscriptions.');
            }

            $this->deletePhoto($eleve->photo);
            $eleve->responsables()->delete();
            $eleve->delete();

            Log::info('Élève supprimé', [
                'eleve_id' => $eleve->id,
                'nom'      => $eleve->nom,
                'prenom'   => $eleve->prenom,
                'user_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.eleves.index')
                ->with('success', 'Élève supprimé avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'élève', [
                'eleve_id' => $eleve->id,
                'error'    => $e->getMessage(),
                'user_id'  => auth()->id(),
            ]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    // ==========================================
    // MÉTHODES PRIVÉES
    // ==========================================

    /**
     * Stocke une photo et retourne le chemin.
     */
    private function storePhoto($file): ?string
    {
        try {
            $path = $file->store('eleves/photos', 'public');
            return $path ?: null;
        } catch (\Exception $e) {
            Log::error('Échec du stockage de la photo', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Supprime une photo si elle existe.
     */
    private function deletePhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Synchronise les responsables d'un élève (supprime tous puis recrée).
     */
    private function syncResponsables(Eleve $eleve, array $responsables): void
    {
        // Supprimer tous les responsables existants
        $eleve->responsables()->delete();

        if (empty($responsables)) {
            return;
        }

        foreach ($responsables as $responsable) {
            // S'assurer que 'vivant' est un booléen
            if (isset($responsable['vivant'])) {
                $responsable['vivant'] = (bool) $responsable['vivant'];
            }
            // Ne pas inclure l'ID s'il est présent (pour éviter les conflits)
            unset($responsable['id']);
            $eleve->responsables()->create($responsable);
        }
    }
}