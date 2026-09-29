<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cour;
use App\Models\Categorie;
use App\Models\SalleDeClasse;
use App\Models\User;
use App\Models\CourSalle;
use App\Models\Libelle;
use App\Models\Ponderation;
use App\Models\NombreHeure;
use App\Models\CreneauHoraire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourController extends Controller
{
    private const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

    // Rôles pouvant être titulaires d'un cours
    private const TITULAIRE_ROLES = ['admin', 'secretaire', 'comptable', 'enseignant', 'directeur'];

    /*
    |--------------------------------------------------------------------------
    | CRUD Cours
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $cours = Cour::with('categorie')
            ->withCount('salles')
            ->when($request->filled('search'), fn($q) => $q->where('nom', 'like', "%{$request->search}%"))
            ->when($request->filled('categorie_id'), fn($q) => $q->where('categorie_id', $request->categorie_id))
            ->orderBy('nom')
            ->paginate(15)
            ->appends($request->query());

        $categories = Categorie::orderBy('nom')->get();
        return view('admin.cours.index', compact('cours', 'categories'));
    }

    public function create()
    {
        $categories = Categorie::orderBy('nom')->get();
        return view('admin.cours.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCours($request);
        Cour::create($data);
        return redirect()->route('admin.cours.index')->with('success', 'Cours créé.');
    }

    public function show(Cour $cour)
    {
        $cour->load('categorie');
        $assignations = CourSalle::with(['salle', 'libelle', 'ponderation', 'nombreHeure', 'titulaire', 'creneauHoraire'])
            ->where('cours_id', $cour->id)
            ->get();
        return view('admin.cours.show', compact('cour', 'assignations'));
    }

    public function edit(Cour $cour)
    {
        $categories = Categorie::orderBy('nom')->get();
        return view('admin.cours.edit', compact('cour', 'categories'));
    }

    public function update(Request $request, Cour $cour)
    {
        $data = $this->validateCours($request);
        $cour->update($data);
        return redirect()->route('admin.cours.index')->with('success', 'Cours mis à jour.');
    }

    public function destroy(Cour $cour)
    {
        $cour->delete();
        return redirect()->route('admin.cours.index')->with('success', 'Cours supprimé.');
    }

    /*
    |--------------------------------------------------------------------------
    | Assignation des cours aux salles
    |--------------------------------------------------------------------------
    */

    public function assign(Cour $cour)
    {
        $data = $this->getSelectData();
        $assignations = CourSalle::with(['salle', 'libelle', 'ponderation', 'nombreHeure', 'titulaire', 'creneauHoraire'])
            ->where('cours_id', $cour->id)
            ->get();
        $jours = self::JOURS;
        return view('admin.cours.assign', array_merge($data, compact('cour', 'assignations', 'jours')));
    }

    public function storeAssign(Request $request, Cour $cour)
    {
        $data = $this->validateAssignation($request);

        // Vérifier les conflits de salle et d'enseignant
        if ($this->verifierChevauchementSalle(
            $data['salle_classe_id'],
            $data['jours'] ?? [],
            $data['creneau_horaire_id']
        )) {
            return back()->with('error', 'Conflit d\'horaire détecté pour cette salle.')->withInput();
        }

        if (!empty($data['titulaire_id']) && $this->verifierChevauchementEnseignant(
            $data['titulaire_id'],
            $data['jours'] ?? [],
            $data['creneau_horaire_id']
        )) {
            return back()->with('error', 'Conflit d\'horaire détecté pour cet enseignant.')->withInput();
        }

        if ($cour->salles()->where('salle_classe_id', $data['salle_classe_id'])->exists()) {
            return back()->with('error', 'Ce cours est déjà assigné à cette salle.')->withInput();
        }

        // Calcul du nombre de séances
        $nombreSeances = count($data['jours'] ?? []);

        DB::transaction(function () use ($cour, $data, $nombreSeances) {
            $cour->salles()->attach($data['salle_classe_id'], [
                'libelle_id' => $data['libelle_id'],
                'nombre_heure_id' => $data['nombre_heure_id'],
                'ponderation_id' => $data['ponderation_id'],
                'titulaire_id' => $data['titulaire_id'] ?? null,
                'jours' => isset($data['jours']) ? implode(',', $data['jours']) : null,
                'creneau_horaire_id' => $data['creneau_horaire_id'] ?? null,
                'nombre_seances' => $nombreSeances,
            ]);
        });

        return back()->with('success', 'Cours assigné à la salle.');
    }

    public function editAssign($assignId)
    {
        $assign = CourSalle::with('creneauHoraire')->findOrFail($assignId);
        $data = $this->getSelectData();
        $jours = self::JOURS;
        $joursSelectionnes = $assign->jours ? explode(',', $assign->jours) : [];
        return view('admin.cours.edit-assign', array_merge($data, compact('assign', 'jours', 'joursSelectionnes')));
    }

    public function updateAssign(Request $request, $assignId)
    {
        $assign = CourSalle::findOrFail($assignId);
        $data = $this->validateAssignation($request, false);
        $data['jours'] = isset($data['jours']) ? implode(',', $data['jours']) : null;
        $joursArray = $data['jours'] ? explode(',', $data['jours']) : [];

        // Vérifier les conflits de salle (en excluant l'assignation en cours)
        if ($this->verifierChevauchementSalle(
            $assign->salle_classe_id,
            $joursArray,
            $data['creneau_horaire_id'],
            $assign->id
        )) {
            return back()->with('error', 'Conflit d\'horaire détecté pour cette salle.')->withInput();
        }

        // Vérifier les conflits d'enseignant (en excluant l'assignation en cours)
        if (!empty($data['titulaire_id']) && $this->verifierChevauchementEnseignant(
            $data['titulaire_id'],
            $joursArray,
            $data['creneau_horaire_id'],
            $assign->id
        )) {
            return back()->with('error', 'Conflit d\'horaire détecté pour cet enseignant.')->withInput();
        }

        // Mise à jour du nombre de séances
        $data['nombre_seances'] = count($joursArray);

        DB::transaction(function () use ($assign, $data) {
            $assign->update($data);
        });

        return redirect()->route('admin.cours.assign', $assign->cours_id)->with('success', 'Assignation mise à jour.');
    }

    public function destroyAssign($assignId)
    {
        $assign = CourSalle::findOrFail($assignId);
        $coursId = $assign->cours_id;
        $assign->delete();
        return redirect()->route('admin.cours.assign', $coursId)->with('success', 'Assignation supprimée.');
    }

    /*
    |--------------------------------------------------------------------------
    | Méthodes privées
    |--------------------------------------------------------------------------
    */

    /**
     * Validation des champs du formulaire de cours.
     */
    private function validateCours(Request $request): array
    {
        return $request->validate([
            'nom' => 'required|string|max:255',
            'categorie_id' => 'nullable|exists:categories,id',
        ]);
    }

    /**
     * Validation des champs d'assignation.
     * @param bool $withSalle Inclure la validation de salle (pour la création)
     */
    private function validateAssignation(Request $request, bool $withSalle = true): array
    {
        $rules = [
            'libelle_id' => 'required|exists:libelles,id',
            'nombre_heure_id' => 'required|exists:nombre_heures,id',
            'ponderation_id' => 'required|exists:ponderations,id',
            'titulaire_id' => 'nullable|exists:users,id',
            'jours' => 'nullable|array',
            'jours.*' => 'in:' . implode(',', self::JOURS),
            'creneau_horaire_id' => 'required|exists:creneaux_horaires,id',
        ];

        if ($withSalle) {
            $rules['salle_classe_id'] = 'required|exists:salles_de_classe,id';
        }

        return $request->validate($rules);
    }

    /**
     * Vérifie s'il y a un chevauchement d'horaire pour une salle donnée.
     *
     * @param int $salleId
     * @param array $jours
     * @param int $creneauId
     * @param int|null $excludeAssignId
     * @return bool
     */
    private function verifierChevauchementSalle(int $salleId, array $jours, int $creneauId, ?int $excludeAssignId = null): bool
    {
        $creneau = CreneauHoraire::find($creneauId);
        if (!$creneau) {
            return true;
        }

        foreach ($jours as $jour) {
            $conflit = CourSalle::where('salle_classe_id', $salleId)
                ->where('jours', 'like', "%{$jour}%")
                ->when($excludeAssignId, fn($q) => $q->where('id', '!=', $excludeAssignId))
                ->whereHas('creneauHoraire', function ($q) use ($creneau) {
                    $q->where('heure_debut', '<', $creneau->heure_fin)
                      ->where('heure_fin', '>', $creneau->heure_debut);
                })
                ->exists();

            if ($conflit) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie s'il y a un chevauchement d'horaire pour un enseignant donné.
     *
     * @param int $titulaireId
     * @param array $jours
     * @param int $creneauId
     * @param int|null $excludeAssignId
     * @return bool
     */
    private function verifierChevauchementEnseignant(int $titulaireId, array $jours, int $creneauId, ?int $excludeAssignId = null): bool
    {
        $creneau = CreneauHoraire::find($creneauId);
        if (!$creneau) {
            return true;
        }

        foreach ($jours as $jour) {
            $conflit = CourSalle::where('titulaire_id', $titulaireId)
                ->where('jours', 'like', "%{$jour}%")
                ->when($excludeAssignId, fn($q) => $q->where('id', '!=', $excludeAssignId))
                ->whereHas('creneauHoraire', function ($q) use ($creneau) {
                    $q->where('heure_debut', '<', $creneau->heure_fin)
                      ->where('heure_fin', '>', $creneau->heure_debut);
                })
                ->exists();

            if ($conflit) {
                return true;
            }
        }
        return false;
    }

    /**
     * Données communes pour les vues d'assignation, avec cache pour les listes stables.
     */
    private function getSelectData(): array
    {
        return [
            'salles' => SalleDeClasse::with('section:id,nom')->select('id', 'nom', 'section_id')->orderBy('nom')->get(),
            'titulaires' => User::whereIn('role', self::TITULAIRE_ROLES)->select('id', 'name')->orderBy('name')->get(),
            'libelles' => Libelle::orderBy('nom')->select('id', 'nom')->get(),
            'ponderations' => Ponderation::orderBy('nom')->select('id', 'nom', 'valeur')->get(),
            'nombreHeures' => NombreHeure::orderBy('valeur')->select('id', 'libelle')->get(),
            'creneauxHoraires' => CreneauHoraire::orderBy('heure_debut')->get(),
        ];
    }
}