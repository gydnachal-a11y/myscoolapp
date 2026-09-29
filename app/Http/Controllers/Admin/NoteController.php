<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourSalle;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\PeriodeNote;
use App\Models\SalleDeClasse;
use App\Services\NoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NoteController extends Controller
{
    public function __construct(
        private NoteService $noteService
    ) {}

    // ============================================================
    // INDEX — Consultation des notes
    // ============================================================

    public function index(Request $request): View
    {
        $user    = auth()->user();
        $isAdmin = $this->isAdminContext($user);

        // Récupération des filtres
        $periodeId = $request->input('periode_note_id');
        $salleId   = $request->input('salle_id');
        $eleveId   = $request->input('eleve_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $optionId  = $request->input('option_id');

        Log::debug('notes.index : filtres', [
            'user_id' => $user->id, 'is_admin' => $isAdmin,
            'periode' => $periodeId, 'salle' => $salleId, 'eleve' => $eleveId,
        ]);

        // Données de référence
        $periodes = PeriodeNote::orderByDesc('date_debut')->get();
        $sessions = $this->noteService->getSessionsPourFiltre();
        $sections = $this->noteService->getSectionsPourFiltre();
        $options  = $this->noteService->getOptionsPourFiltre();

        $salles = $this->noteService->getSallesAutoriseesAvecFiltres($user, [
            'session_id' => $sessionId,
            'section_id' => $sectionId,
            'option_id'  => $optionId,
        ]);

        // Vérifie que la salle est bien autorisée
        if ($salleId && !$salles->contains('id', $salleId)) {
            $salleId = null;
            $eleveId = null;
        }

        $eleves = $salleId
            ? $this->noteService->getElevesParSalle($salleId)
            : collect();

        // Construction de la requête
        $query = Note::with([
            'eleve',
            'courSalle.cour',
            'courSalle.ponderation',
            'courSalle.titulaire',
            'periodeNote',
        ]);

        $query = $this->noteService->appliquerFiltresNote($query, [
            'session_id' => $sessionId,
            'section_id' => $sectionId,
            'option_id'  => $optionId,
            'salle_id'   => $salleId,
        ]);

        // Restriction aux cours de l'utilisateur si non-admin
        if (!$isAdmin) {
            $query->whereHas('courSalle', fn ($q) => $q->where('titulaire_id', $user->id));
        }

        if ($periodeId) {
            $query->where('periode_note_id', $periodeId);
        }
        if ($eleveId) {
            $query->where('eleve_id', $eleveId);
        }

        $notes       = collect();
        $moyenne     = null;
        $pourcentage = null;

        if ($eleveId) {
            $notes       = $query->orderByDesc('created_at')->get();
            $moyenne     = $this->noteService->calculerMoyennePonderee($eleveId, $periodeId);
            $pourcentage = $this->noteService->calculerPourcentage($eleveId, $periodeId);

        } elseif ($isAdmin || $salleId || $periodeId) {
            $notes = $query->orderByDesc('created_at')->paginate(20);
        }

        $nombreCoursPublies = 0;
        if ($periodeId) {
            $nombreCoursPublies = $this->noteService->getNombreCoursPubliesParPeriode(
                $periodeId,
                $salleId,
                $isAdmin ? null : $user->id
            );
        }

        return view('admin.notes.index', compact(
            'periodes', 'sessions', 'sections', 'options',
            'salles', 'eleves', 'notes',
            'moyenne', 'pourcentage',
            'periodeId', 'salleId', 'eleveId',
            'sessionId', 'sectionId', 'optionId',
            'isAdmin', 'nombreCoursPublies'
        ));
    }

    // ============================================================
    // SAISIE — Formulaire groupé
    // ============================================================

    public function saisie(Request $request): View
    {
        $user        = auth()->user();
        $salleId     = $request->input('salle_id');
        $periodeId   = $request->input('periode_note_id');
        $courSalleId = $request->input('cour_salle_id');

        Log::debug('notes.saisie : entrée', [
            'user_id' => $user->id,
            'salle'   => $salleId,
            'periode' => $periodeId,
            'cour'    => $courSalleId,
        ]);

        $salles     = $this->noteService->getSallesAutorisees($user);
        $periodes   = PeriodeNote::orderByDesc('date_debut')->get();
        $brouillons = $this->noteService->getBrouillonsEnCours($user);

        $message    = null;
        $grilleData = null;

        // Vérif salle autorisée
        if ($salleId && !$salles->contains('id', $salleId)) {
            $message     = "La salle sélectionnée n'est pas valide ou vous n'y avez pas accès.";
            $salleId     = null;
            $courSalleId = null;
        }

        $coursSalles = $salleId
            ? $this->noteService->getCoursSallesForUser($user, $salleId)
            : collect();

        // Présélection auto si un seul cours
        if ($salleId && $coursSalles->count() === 1 && !$courSalleId) {
            $courSalleId = $coursSalles->first()->id;
        }

        if ($salleId && $periodeId && $courSalleId) {
            $coursSelectionne = $coursSalles->firstWhere('id', $courSalleId);

            if (!$coursSelectionne) {
                $message     = "Le cours sélectionné est invalide ou n'appartient pas à cette salle.";
                $courSalleId = null;
            } else {
                $notesExistantes = Note::where('cour_salle_id', $courSalleId)
                    ->where('periode_note_id', $periodeId)
                    ->get(['statut']);

                $aDesPubliees   = $notesExistantes->contains('statut', Note::STATUT_PUBLIE);
                $aDesBrouillons = $notesExistantes->contains('statut', Note::STATUT_BROUILLON);

                if ($aDesPubliees) {
                    $message = "Des notes publiées existent déjà pour ce cours et cette période. Modification impossible.";
                } else {
                    $grilleData = $this->noteService->preparerGrilleSaisie(
                        $salleId, $periodeId, $user, $courSalleId
                    );

                    if ($aDesBrouillons) {
                        $message = "Reprise du brouillon existant. Vous pouvez modifier les notes puis publier.";
                    }
                }
            }
        } elseif ($salleId && $periodeId && !$courSalleId) {
            $message = "Veuillez sélectionner un cours.";
        }

        return view('admin.notes.saisie', compact(
            'salles', 'periodes', 'salleId', 'periodeId',
            'courSalleId', 'coursSalles', 'grilleData', 'message', 'brouillons'
        ));
    }

    // ============================================================
    // STORE MASS — Enregistrement groupé
    // ============================================================

    public function storeMass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'salle_classe_id'       => ['required', 'exists:salles_de_classe,id'],
            'periode_note_id'       => ['required', 'exists:periode_notes,id'],
            'cour_salle_id'         => ['required', 'exists:cours_salle,id'],
            'statut'                => ['nullable', Rule::in([Note::STATUT_BROUILLON, Note::STATUT_PUBLIE])],
            'notes'                 => ['required', 'array', 'min:1'],
            'notes.*.eleve_id'      => ['required', 'exists:eleves,id'],
            'notes.*.cour_salle_id' => ['required', 'exists:cours_salle,id'],
            'notes.*.note'          => ['nullable', 'numeric', 'min:0'],
            'notes.*.appreciation'  => ['nullable', 'string', 'max:255'],
        ]);

        $user   = auth()->user();
        $statut = $data['statut'] ?? Note::STATUT_PUBLIE;

        Log::debug('notes.storeMass : entrée', [
            'user_id'  => $user->id,
            'salle'    => $data['salle_classe_id'],
            'periode'  => $data['periode_note_id'],
            'cour'     => $data['cour_salle_id'],
            'statut'   => $statut,
            'nb_notes' => count($data['notes']),
        ]);

        // ============================================================
        // ✅ CORRECTIF — Blocage de publication de notes vides
        // ============================================================
        if ($statut === Note::STATUT_PUBLIE) {
            $notesRenseignees = collect($data['notes'])
                ->filter(fn ($n) => isset($n['note']) && $n['note'] !== null && $n['note'] !== '')
                ->count();

            if ($notesRenseignees === 0) {
                Log::warning('notes.storeMass : publication refusée (aucune note saisie)', [
                    'user_id' => $user->id,
                    'cour'    => $data['cour_salle_id'],
                    'periode' => $data['periode_note_id'],
                ]);

                return back()
                    ->with('error', "Aucune note n'a été saisie. Publiez au moins une note ou enregistrez en brouillon.")
                    ->withInput();
            }
        }

        // ✅ Autorisation
        if (!$this->peutSaisirNote($user, (int) $data['cour_salle_id'])) {
            Log::warning('notes.storeMass : accès refusé', [
                'user_id' => $user->id, 'cour_salle_id' => $data['cour_salle_id'],
            ]);
            return back()->with('error', "Vous n'êtes pas autorisé à saisir des notes pour ce cours.")->withInput();
        }

        $courSalle = CourSalle::with('ponderation')->findOrFail($data['cour_salle_id']);

        if ((int) $courSalle->salle_classe_id !== (int) $data['salle_classe_id']) {
            return back()->with('error', "Le cours sélectionné n'appartient pas à cette salle.")->withInput();
        }

        // Blocage si notes publiées déjà existantes
        $aDesPubliees = Note::where('cour_salle_id', $data['cour_salle_id'])
            ->where('periode_note_id', $data['periode_note_id'])
            ->where('statut', Note::STATUT_PUBLIE)
            ->exists();

        if ($aDesPubliees) {
            return back()->with('error', 'Des notes publiées existent déjà pour ce cours et cette période. Modification impossible.')->withInput();
        }

        // Vérification élève/salle en 1 requête
        $elevesIds     = collect($data['notes'])->pluck('eleve_id')->unique()->all();
        $elevesValides = Inscription::where('salle_classe_id', $data['salle_classe_id'])
            ->whereIn('eleve_id', $elevesIds)
            ->pluck('eleve_id')
            ->all();

        $elevesInvalides = array_diff($elevesIds, $elevesValides);
        if (!empty($elevesInvalides)) {
            return back()
                ->with('error', 'Certains élèves n\'appartiennent pas à cette salle : ' . implode(', ', $elevesInvalides))
                ->withInput();
        }

        // Vérification note max
        $pointMax = $courSalle->ponderation->valeur ?? 20;
        foreach ($data['notes'] as $noteData) {
            if (isset($noteData['note']) && $noteData['note'] > $pointMax) {
                return back()->with('error', "Une note dépasse le maximum autorisé ($pointMax).")->withInput();
            }
        }

        try {
            $this->noteService->sauvegarderGrilleNotes(
                $data['periode_note_id'],
                $data['notes'],
                $user,
                $statut,
                $data['cour_salle_id']
            );

            Log::debug('notes.storeMass : succès', ['statut' => $statut]);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('notes.storeMass : exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return back()->with('error', "Une erreur est survenue lors de l'enregistrement.")->withInput();
        }

        $message = $statut === Note::STATUT_BROUILLON
            ? 'Brouillon enregistré avec succès.'
            : 'Notes publiées avec succès.';

        return redirect()->route($this->getSaisieRouteName())->with('success', $message);
    }

    // ============================================================
    // CRUD individuel
    // ============================================================

    public function create(Request $request): View
    {
        $user     = auth()->user();
        $salleId  = $request->input('salle_id');

        $salles      = $this->noteService->getSallesAutorisees($user);
        $periodes    = PeriodeNote::orderByDesc('date_debut')->get();
        $coursSalles = $this->noteService->getCoursSallesForUser($user, $salleId);

        $eleves = $salleId
            ? $this->noteService->getElevesParSalle($salleId)
            : collect();

        return view('admin.notes.create', compact('eleves', 'periodes', 'coursSalles', 'salles', 'salleId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'eleve_id'         => ['required', 'exists:eleves,id'],
            'cour_salle_id'    => ['required', 'exists:cours_salle,id'],
            'periode_note_id'  => ['required', 'exists:periode_notes,id'],
            'note'             => ['nullable', 'numeric', 'min:0'],
            'appreciation'     => ['nullable', 'string', 'max:255'],
            'statut'           => ['required', Rule::in([Note::STATUT_BROUILLON, Note::STATUT_PUBLIE])],
        ]);

        $user = auth()->user();

        // ✅ CORRECTIF — Blocage publication d'une note vide
        if ($data['statut'] === Note::STATUT_PUBLIE && ($data['note'] === null || $data['note'] === '')) {
            return back()
                ->with('error', "Impossible de publier une note vide. Saisissez une valeur ou enregistrez en brouillon.")
                ->withInput();
        }

        if (!$this->peutSaisirNote($user, (int) $data['cour_salle_id'])) {
            return back()->with('error', "Vous n'êtes pas autorisé à saisir des notes pour ce cours.")->withInput();
        }

        $courSalle = CourSalle::with('ponderation')->findOrFail($data['cour_salle_id']);

        if (!$this->eleveAppartientSalle((int) $data['eleve_id'], (int) $courSalle->salle_classe_id)) {
            return back()->with('error', "L'élève sélectionné n'appartient pas à la salle de ce cours.")->withInput();
        }

        $existe = Note::where('eleve_id', $data['eleve_id'])
            ->where('cour_salle_id', $data['cour_salle_id'])
            ->where('periode_note_id', $data['periode_note_id'])
            ->exists();

        if ($existe) {
            return back()->with('error', 'Une note existe déjà pour cet élève, ce cours et cette période.')->withInput();
        }

        $pointMax = $courSalle->ponderation->valeur ?? 20;
        if (isset($data['note']) && $data['note'] > $pointMax) {
            return back()->with('error', "La note ne peut pas dépasser $pointMax.")->withInput();
        }

        try {
            $this->noteService->validerNote($data['note'] ?? null, $pointMax);
            $data['saisie_par'] = $user->id;
            Note::create($data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route($this->getIndexRouteName())->with('success', 'Note créée avec succès.');
    }

    public function edit(Note $note): View
    {
        $user = auth()->user();

        if (!$this->peutModifierNote($user, $note)) {
            abort(403);
        }

        $periodes    = PeriodeNote::orderByDesc('date_debut')->get();
        $coursSalles = $this->noteService->getCoursSallesForUser($user);
        $salles      = $this->noteService->getSallesAutorisees($user);
        $eleves      = $this->noteService->getElevesParSalle($note->courSalle->salle_classe_id);

        return view('admin.notes.edit', compact('note', 'eleves', 'periodes', 'coursSalles', 'salles'));
    }

    public function update(Request $request, Note $note): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->peutModifierNote($user, $note)) {
            abort(403);
        }

        $data = $request->validate([
            'eleve_id'        => ['required', 'exists:eleves,id'],
            'cour_salle_id'   => ['required', 'exists:cours_salle,id'],
            'periode_note_id' => ['required', 'exists:periode_notes,id'],
            'note'            => ['nullable', 'numeric', 'min:0'],
            'appreciation'    => ['nullable', 'string', 'max:255'],
            'statut'          => ['required', Rule::in([Note::STATUT_BROUILLON, Note::STATUT_PUBLIE])],
        ]);

        // ✅ CORRECTIF — Blocage publication d'une note vide
        if ($data['statut'] === Note::STATUT_PUBLIE && ($data['note'] === null || $data['note'] === '')) {
            return back()
                ->with('error', "Impossible de publier une note vide. Saisissez une valeur ou enregistrez en brouillon.")
                ->withInput();
        }

        $courSalle = CourSalle::with('ponderation')->findOrFail($data['cour_salle_id']);

        if (!$this->eleveAppartientSalle((int) $data['eleve_id'], (int) $courSalle->salle_classe_id)) {
            return back()->with('error', "L'élève sélectionné n'appartient pas à la salle de ce cours.")->withInput();
        }

        $existe = Note::where('eleve_id', $data['eleve_id'])
            ->where('cour_salle_id', $data['cour_salle_id'])
            ->where('periode_note_id', $data['periode_note_id'])
            ->where('id', '!=', $note->id)
            ->exists();

        if ($existe) {
            return back()->with('error', 'Une autre note existe déjà pour cet élève, ce cours et cette période.')->withInput();
        }

        $pointMax = $courSalle->ponderation->valeur ?? 20;
        if (isset($data['note']) && $data['note'] > $pointMax) {
            return back()->with('error', "La note ne peut pas dépasser $pointMax.")->withInput();
        }

        $note->update($data);

        return redirect()->route($this->getIndexRouteName())->with('success', 'Note mise à jour avec succès.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        if (!$this->peutModifierNote(auth()->user(), $note)) {
            abort(403);
        }
        $note->delete();
        return back()->with('success', 'Note supprimée avec succès.');
    }

    // ============================================================
    // BULLETIN
    // ============================================================

    public function bulletin(Request $request): View
    {
        [$eleve, $periode, $notes, $moyenne, $pourcentage] = $this->preparerBulletin($request);

        return view('admin.notes.bulletin', compact('eleve', 'periode', 'notes', 'moyenne', 'pourcentage'));
    }

    public function exportBulletinPdf(Request $request)
    {
        [$eleve, $periode, $notes, $moyenne, $pourcentage] = $this->preparerBulletin($request);

        $pdf = Pdf::loadView('admin.notes.bulletin_pdf', compact('eleve', 'periode', 'notes', 'moyenne', 'pourcentage'));

        return $pdf->download('bulletin_' . $eleve->nom_complet . '_' . $periode->nom . '.pdf');
    }

    public function printBulletin(Request $request): View
    {
        [$eleve, $periode, $notes, $moyenne, $pourcentage] = $this->preparerBulletin($request);

        return view('admin.notes.bulletin_print', compact('eleve', 'periode', 'notes', 'moyenne', 'pourcentage'));
    }

    // ============================================================
    // CLASSEMENT
    // ============================================================

    public function classement(Request $request): View
    {
        $user      = auth()->user();
        $salleId   = $request->input('salle_id');
        $periodeId = $request->input('periode_note_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $optionId  = $request->input('option_id');

        $sessions = $this->noteService->getSessionsPourFiltre();
        $sections = $this->noteService->getSectionsPourFiltre();
        $options  = $this->noteService->getOptionsPourFiltre();
        $periodes = PeriodeNote::orderByDesc('date_debut')->get();

        $salles = $this->noteService->getSallesAutoriseesAvecFiltres($user, [
            'session_id' => $sessionId,
            'section_id' => $sectionId,
            'option_id'  => $optionId,
        ]);

        // Formulaire de sélection
        if (!$salleId || !$periodeId) {
            return view('admin.notes.classement_select', compact(
                'sessions', 'sections', 'options', 'salles', 'periodes',
                'salleId', 'periodeId', 'sessionId', 'sectionId', 'optionId'
            ));
        }

        if (!$salles->contains('id', $salleId)) {
            abort(403);
        }

        $salle   = SalleDeClasse::findOrFail($salleId);
        $periode = PeriodeNote::findOrFail($periodeId);

        $classement = $this->noteService->calculerClassement($salleId, $periodeId);

        return view('admin.notes.classement', compact('salle', 'periode', 'classement'));
    }

    public function printClassement(Request $request): View
    {
        $salleId   = $request->input('salle_id');
        $periodeId = $request->input('periode_note_id');

        $this->autoriserSalle((int) $salleId);

        $salle      = SalleDeClasse::findOrFail($salleId);
        $periode    = PeriodeNote::findOrFail($periodeId);
        $classement = $this->noteService->calculerClassement($salleId, $periodeId);

        return view('admin.notes.classement_print', compact('salle', 'periode', 'classement'));
    }

    // ============================================================
    // ENDPOINTS JSON
    // ============================================================

    public function getSalleData(Request $request): JsonResponse
    {
        $salleId = $request->input('salle_id');

        if (!$salleId) {
            return response()->json(['error' => 'Salle non spécifiée'], 400);
        }

        $this->autoriserSalle((int) $salleId);

        $salle = SalleDeClasse::find($salleId);
        if (!$salle) {
            return response()->json(['error' => 'Salle introuvable'], 404);
        }

        $user = auth()->user();

        return response()->json([
            'salle'       => $salle,
            'coursSalles' => $this->noteService->getCoursSallesForUser($user, $salleId),
            'eleves'      => $this->noteService->getElevesParSalle($salleId),
        ]);
    }

    public function getElevesParSalle(Request $request): JsonResponse
    {
        $salleId = $request->input('salle_id');

        if (!$salleId) {
            return response()->json(['error' => 'Salle non spécifiée'], 400);
        }

        $this->autoriserSalle((int) $salleId);

        return response()->json([
            'eleves' => $this->noteService->getElevesParSalle($salleId),
        ]);
    }

    public function getNotesEleve(Request $request): JsonResponse
    {
        $eleveId   = $request->input('eleve_id');
        $periodeId = $request->input('periode_note_id');

        if (!$eleveId || !$periodeId) {
            return response()->json(['error' => 'Paramètres manquants'], 400);
        }

        $this->autoriserEleve((int) $eleveId);

        return response()->json([
            'notes'       => $this->noteService->getNotesEleve($eleveId, $periodeId),
            'moyenne'     => $this->noteService->calculerMoyennePonderee($eleveId, $periodeId),
            'pourcentage' => $this->noteService->calculerPourcentage($eleveId, $periodeId),
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function isAdminContext($user): bool
    {
        if (!request()->routeIs('admin.*')) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->hasRole('admin')
            || $user->hasRole('super_admin');
    }

    private function preparerBulletin(Request $request): array
    {
        $eleveId   = $request->input('eleve_id');
        $periodeId = $request->input('periode_note_id');

        $this->autoriserEleve((int) $eleveId);

        $eleve   = Eleve::findOrFail($eleveId);
        $periode = PeriodeNote::findOrFail($periodeId);

        return [
            $eleve,
            $periode,
            $this->noteService->getNotesEleve($eleveId, $periodeId),
            $this->noteService->calculerMoyennePonderee($eleveId, $periodeId),
            $this->noteService->calculerPourcentage($eleveId, $periodeId),
        ];
    }

    private function autoriserSalle(int $salleId): void
    {
        $user = auth()->user();

        if ($this->isAdminContext($user)) {
            return;
        }

        $sallesAutorisees = $this->noteService->getSallesAutorisees($user)->pluck('id')->all();

        if (!in_array($salleId, $sallesAutorisees, true)) {
            abort(403, 'Accès non autorisé à cette salle.');
        }
    }

    private function autoriserEleve(int $eleveId): void
    {
        $user = auth()->user();

        if ($this->isAdminContext($user)) {
            return;
        }

        $sallesAutorisees = $this->noteService->getSallesAutorisees($user)->pluck('id')->all();

        $existe = Inscription::where('eleve_id', $eleveId)
            ->whereIn('salle_classe_id', $sallesAutorisees)
            ->exists();

        if (!$existe) {
            abort(403, 'Accès non autorisé à cet élève.');
        }
    }

    private function eleveAppartientSalle(int $eleveId, int $salleId): bool
    {
        return Inscription::where('eleve_id', $eleveId)
            ->where('salle_classe_id', $salleId)
            ->exists();
    }

    private function peutSaisirNote($user, int $courSalleId): bool
    {
        if ($this->isAdminContext($user)) {
            return true;
        }

        return CourSalle::where('id', $courSalleId)
            ->where('titulaire_id', $user->id)
            ->exists();
    }

    private function peutModifierNote($user, Note $note): bool
    {
        if ($this->isAdminContext($user)) {
            return true;
        }

        return $note->courSalle
            && (int) $note->courSalle->titulaire_id === (int) $user->id;
    }

    private function getIndexRouteName(): string
    {
        return request()->routeIs('admin.*') ? 'admin.notes.index' : 'member.notes.index';
    }

    private function getSaisieRouteName(): string
    {
        return request()->routeIs('admin.*') ? 'admin.notes.saisie' : 'member.notes.saisie';
    }
}