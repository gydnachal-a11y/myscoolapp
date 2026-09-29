<?php

namespace App\Services;

use App\Models\CourSalle;
use App\Models\Eleve;
use App\Models\Note;
use App\Models\Option;
use App\Models\PeriodeNote;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NoteService
{
    // ============================================================
    // RÔLES ADMIN (source unique de vérité)
    // ============================================================

    /**
     * Liste des rôles considérés comme "admin" pour l'accès global aux notes.
     * Centralisé ici pour être aligné avec le NoteController.
     */
    private const ADMIN_ROLES = [
        User::ROLE_SUPER_ADMIN,
        User::ROLE_ADMIN,
        User::ROLE_DIRECTEUR,
        User::ROLE_SECRETAIRE,
        User::ROLE_COMPTABLE,
    ];

    /**
     * Vérifie si l'utilisateur a un rôle administratif étendu.
     */
    private function isAdmin(User $user): bool
    {
        return $user->hasAnyRole(self::ADMIN_ROLES);
    }

    // ============================================================
    // FILTRES DE RÉFÉRENCE (sessions, sections, options)
    // ============================================================

    public function getSessionsPourFiltre(): Collection
    {
        return Session::orderBy('nom')->get(['id', 'nom']);
    }

    public function getSectionsPourFiltre(): Collection
    {
        return Section::orderBy('nom')->get(['id', 'nom', 'session_id']);
    }

    public function getOptionsPourFiltre(): Collection
    {
        return Option::orderBy('nom')->get(['id', 'nom']);
    }

    // ============================================================
    // SALLES AUTORISÉES
    // ============================================================

    public function getSallesAutoriseesAvecFiltres(User $user, array $filtres = []): Collection
    {
        $query = SalleDeClasse::query();

        if (!$this->isAdmin($user)) {
            $salleIds = CourSalle::where('titulaire_id', $user->id)
                ->distinct()
                ->pluck('salle_classe_id');
            $query->whereIn('id', $salleIds);
        }

        if (!empty($filtres['session_id'])) {
            $query->whereHas('section', fn ($q) => $q->where('session_id', $filtres['session_id']));
        }

        if (!empty($filtres['section_id'])) {
            $query->where('section_id', $filtres['section_id']);
        }

        if (!empty($filtres['option_id'])) {
            $query->where('option_id', $filtres['option_id']);
        }

        return $query->orderBy('nom')->get(['id', 'nom', 'section_id', 'option_id']);
    }

    public function getSallesAutorisees(User $user): Collection
    {
        return $this->getSallesAutoriseesAvecFiltres($user);
    }

    // ============================================================
    // FILTRES SUR LA REQUÊTE DE NOTES
    // ============================================================

    public function appliquerFiltresNote(Builder $query, array $filtres): Builder
    {
        if (!empty($filtres['session_id'])) {
            $query->whereHas('courSalle.salle.section', fn ($q) => $q->where('session_id', $filtres['session_id']));
        }

        if (!empty($filtres['section_id'])) {
            $query->whereHas('courSalle.salle', fn ($q) => $q->where('section_id', $filtres['section_id']));
        }

        if (!empty($filtres['salle_id'])) {
            $query->whereHas('courSalle', fn ($q) => $q->where('salle_classe_id', $filtres['salle_id']));
        }

        if (!empty($filtres['option_id'])) {
            $query->whereHas('courSalle.salle', fn ($q) => $q->where('option_id', $filtres['option_id']));
        }

        return $query;
    }

    // ============================================================
    // COURS-SALLES
    // ============================================================

    public function getCoursSallesForUser(User $user, ?int $salleId = null): Collection
    {
        $query = CourSalle::with(['cour', 'salle', 'ponderation'])
            ->whereHas('cour')
            ->whereHas('salle');

        if (!$this->isAdmin($user)) {
            $query->where('titulaire_id', $user->id);
        }

        if ($salleId) {
            $query->where('salle_classe_id', $salleId);
        }

        return $query->get();
    }

    public function getCoursParSalle(int $salleId, User $user): Collection
    {
        return $this->getCoursSallesForUser($user, $salleId);
    }

    // ============================================================
    // ÉLÈVES
    // ============================================================

    public function getElevesParSalle(int $salleId, ?int $anneeId = null): Collection
    {
        return Eleve::query()
            ->whereHas('inscriptions', function ($q) use ($salleId, $anneeId) {
                $q->where('salle_classe_id', $salleId)
                  ->when($anneeId, fn ($query) => $query->where('annee_scolaire_id', $anneeId));
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'postnom']);
    }

    // ============================================================
    // NOTES EXISTANTES (grille de saisie)
    // ============================================================

    /**
     * Récupère les notes d'une salle/période groupées par élève → cours.
     */
    public function getNotesParSalleEtPeriode(int $salleId, int $periodeId): Collection
    {
        return Note::with([
            'eleve:id,nom,prenom,postnom',
            'courSalle.cour:id,nom',
            'courSalle.ponderation:id,nom,valeur',
        ])
            ->where('periode_note_id', $periodeId)
            ->whereHas('courSalle', fn ($q) => $q->where('salle_classe_id', $salleId))
            ->get(['id', 'eleve_id', 'cour_salle_id', 'periode_note_id', 'note', 'appreciation', 'statut'])
            ->groupBy('eleve_id')
            ->map(fn ($items) => $items->keyBy('cour_salle_id'));
    }

    /**
     * Prépare la grille de saisie pour un enseignant (ou admin).
     */
    public function preparerGrilleSaisie(
        int $salleId,
        int $periodeId,
        User $user,
        ?int $courSalleId = null,
        ?int $excludeCourSalleId = null
    ): ?array {
        $eleves = $this->getElevesParSalle($salleId);
        $cours  = $this->getCoursSallesForUser($user, $salleId);

        if ($courSalleId) {
            $cours = $cours->filter(fn ($cs) => $cs->id == $courSalleId);
        }

        if ($eleves->isEmpty() || $cours->isEmpty()) {
            return null;
        }

        $notesExistantes = $this->getNotesParSalleEtPeriode($salleId, $periodeId);

        $notesPrete = [];
        foreach ($eleves as $eleve) {
            $notesPrete[$eleve->id] = [];
            foreach ($cours as $cour) {
                $notesPrete[$eleve->id][$cour->id] = $notesExistantes[$eleve->id][$cour->id] ?? null;
            }
        }

        return [
            'eleves' => $eleves,
            'cours'  => $cours,
            'notes'  => $notesPrete,
        ];
    }

    // ============================================================
    // SAUVEGARDE EN MASSE (BATCH UPSERT)
    // ============================================================

    /**
     * Sauvegarde une grille de notes en batch (upsert).
     *
     * ⚡ Optimisation : au lieu de faire N `updateOrCreate` (N+1 requêtes),
     *    on prépare un tableau et on fait un seul `upsert()`.
     *
     * @throws ValidationException
     */
    public function sauvegarderGrilleNotes(
        int $periodeId,
        array $notesData,
        User $user,
        string $statut = Note::STATUT_PUBLIE,
        ?int $courSalleId = null
    ): void {
        if (empty($notesData)) {
            return;
        }

        // 1) Charger tous les cours-salles en 1 requête
        $courSalleIds = array_unique(array_column($notesData, 'cour_salle_id'));
        $coursSalles  = CourSalle::whereIn('id', $courSalleIds)
            ->with('ponderation')
            ->get(['id', 'titulaire_id', 'ponderation_id'])
            ->keyBy('id');

        // 2) Valider en mémoire (sans requête SQL)
        $now = now();
        $rows = [];

        foreach ($notesData as $item) {
            $courSalle = $coursSalles->get($item['cour_salle_id']);

            if (!$courSalle) {
                throw ValidationException::withMessages([
                    'cour_salle_id' => "Le cours spécifié n'existe pas (ID : {$item['cour_salle_id']}).",
                ]);
            }

            if ($courSalle->titulaire_id != $user->id && !$this->isAdmin($user)) {
                throw ValidationException::withMessages([
                    'cour_salle_id' => "Vous n'êtes pas autorisé à noter ce cours.",
                ]);
            }

            $pointMax = $courSalle->ponderation->valeur ?? 20;
            $this->validerNote($item['note'] ?? null, (float) $pointMax);

            $rows[] = [
                'eleve_id'        => $item['eleve_id'],
                'cour_salle_id'   => $item['cour_salle_id'],
                'periode_note_id' => $periodeId,
                'note'            => $item['note'] ?? null,
                'appreciation'    => $item['appreciation'] ?? null,
                'saisie_par'      => $user->id,
                'statut'          => $statut,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        // 3) Transaction : 1 DELETE + 1 UPSERT
        DB::transaction(function () use ($periodeId, $courSalleId, $rows) {
            if ($courSalleId) {
                Note::where('cour_salle_id', $courSalleId)
                    ->where('periode_note_id', $periodeId)
                    ->where('statut', Note::STATUT_BROUILLON)
                    ->delete();
            }

            // `upsert` : insert si nouveau, update sinon (basé sur la clé unique composite)
            Note::upsert(
                $rows,
                ['eleve_id', 'cour_salle_id', 'periode_note_id'],  // clés uniques
                ['note', 'appreciation', 'saisie_par', 'statut', 'updated_at']  // colonnes à mettre à jour
            );
        });

        Log::debug('Notes enregistrées en masse', [
            'user_id'      => $user->id,
            'periode_id'   => $periodeId,
            'statut'       => $statut,
            'nombre_notes' => count($rows),
        ]);
    }

    // ============================================================
    // VALIDATION
    // ============================================================

    /**
     * Valide une note avec un point maximum personnalisable.
     *
     * @throws ValidationException
     */
    public function validerNote($note, ?float $pointMax = 20): void
    {
        if ($note !== null && ($note < 0 || $note > $pointMax)) {
            throw ValidationException::withMessages([
                'note' => "La note doit être comprise entre 0 et {$pointMax}.",
            ]);
        }
    }

    // ============================================================
    // CALCULS (méthodes privées mutualisées)
    // ============================================================

    /**
     * Moyenne pondérée à partir d'une collection de notes.
     * Formule : Σ(note × coeff) / Σ(coeff)
     */
    private function calculerMoyenneDepuisNotes(Collection $notes): ?float
    {
        if ($notes->isEmpty()) {
            return null;
        }

        $totalPondere = 0.0;
        $totalCoeffs  = 0.0;

        foreach ($notes as $note) {
            if ($note->note !== null && $note->courSalle?->ponderation) {
                $coeff         = $note->courSalle->ponderation->valeur ?? 1;
                $totalPondere += $note->note * $coeff;
                $totalCoeffs  += $coeff;
            }
        }

        return $totalCoeffs > 0
            ? round($totalPondere / $totalCoeffs, 2)
            : null;
    }

    /**
     * Pourcentage de réussite à partir d'une collection de notes.
     *
     * ✅ CORRECTION BUG : utilise maintenant la SOMME PONDÉRÉE (note × coeff)
     * pour être cohérent avec la moyenne pondérée.
     *
     * Formule : Σ(note × coeff) × 100 / (20 × Σ(coeff))
     */
    private function calculerPourcentageDepuisNotes(Collection $notes): ?float
    {
        if ($notes->isEmpty()) {
            return null;
        }

        $sommePonderee     = 0.0;
        $totalCoeffs       = 0.0;

        foreach ($notes as $note) {
            if ($note->note !== null && $note->courSalle?->ponderation) {
                $coeff          = $note->courSalle->ponderation->valeur ?? 1;
                $sommePonderee += $note->note * $coeff;   // ✅ CORRECTION
                $totalCoeffs   += $coeff;
            }
        }

        if ($totalCoeffs == 0) {
            return null;
        }

        return round(($sommePonderee * 100) / ($totalCoeffs * 20), 2);
    }

    // ============================================================
    // MOYENNE & POURCENTAGE (public API)
    // ============================================================

    public function calculerMoyennePonderee(int $eleveId, int $periodeId): ?float
    {
        return $this->calculerMoyenneDepuisNotes(
            $this->getNotesPublieesElevePeriode($eleveId, $periodeId)
        );
    }

    public function calculerPourcentage(int $eleveId, int $periodeId): ?float
    {
        return $this->calculerPourcentageDepuisNotes(
            $this->getNotesPublieesElevePeriode($eleveId, $periodeId)
        );
    }

    /**
     * Notes publiées d'un élève pour une période.
     */
    private function getNotesPublieesElevePeriode(int $eleveId, int $periodeId): Collection
    {
        return Note::where('eleve_id', $eleveId)
            ->where('periode_note_id', $periodeId)
            ->where('statut', Note::STATUT_PUBLIE)
            ->whereNotNull('note')
            ->with('courSalle.ponderation')
            ->get(['note', 'cour_salle_id']);
    }

    // ============================================================
    // BULLETIN
    // ============================================================

    public function getBulletin(int $eleveId, int $periodeId): array
    {
        $notes = Note::where('eleve_id', $eleveId)
            ->where('periode_note_id', $periodeId)
            ->where('statut', Note::STATUT_PUBLIE)
            ->whereNotNull('note')
            ->with([
                'courSalle.cour:id,nom',
                'courSalle.ponderation:id,valeur',
            ])
            ->get(['note', 'appreciation', 'cour_salle_id']);

        $details       = [];
        $totalPondere  = 0.0;
        $totalCoeffs   = 0.0;

        foreach ($notes as $note) {
            $coeff = $note->courSalle->ponderation->valeur ?? 1;

            $details[] = [
                'cour'         => $note->courSalle->cour->nom,
                'coefficient'  => $coeff,
                'note'         => $note->note,
                'appreciation' => $note->appreciation,
                'pondere'      => round($note->note * $coeff, 2),
            ];

            $totalPondere += $note->note * $coeff;
            $totalCoeffs  += $coeff;
        }

        $moyenne = $totalCoeffs > 0
            ? round($totalPondere / $totalCoeffs, 2)
            : null;

        return [
            'notes'       => $details,
            'moyenne'     => $moyenne,
            'total_notes' => $notes->count(),
        ];
    }

    public function getNotesEleve(int $eleveId, int $periodeId): Collection
    {
        return Note::where('eleve_id', $eleveId)
            ->where('periode_note_id', $periodeId)
            ->where('statut', Note::STATUT_PUBLIE)
            ->with([
                'courSalle.cour:id,nom',
                'courSalle.ponderation:id,valeur',
            ])
            ->orderBy('cour_salle_id')
            ->get(['note', 'appreciation', 'cour_salle_id']);
    }

    // ============================================================
    // MOYENNE ANNUELLE (optimisée — 1 seule requête)
    // ============================================================

    /**
     * Moyenne générale d'un élève sur toutes les périodes d'une année.
     *
     * ⚡ Optimisation : au lieu de N requêtes (une par période),
     *    on fait UNE seule requête groupée et on calcule en mémoire.
     */
    public function getMoyenneAnnuelle(int $eleveId, int $anneeId): ?float
    {
        $periodes = PeriodeNote::where('annee_scolaire_id', $anneeId)->pluck('id');

        if ($periodes->isEmpty()) {
            return null;
        }

        // 1 seule requête pour toutes les notes de toutes les périodes
        $notes = Note::where('eleve_id', $eleveId)
            ->whereIn('periode_note_id', $periodes)
            ->where('statut', Note::STATUT_PUBLIE)
            ->whereNotNull('note')
            ->with('courSalle.ponderation')
            ->get(['note', 'cour_salle_id', 'periode_note_id'])
            ->groupBy('periode_note_id');

        // Calcul de la moyenne par période
        $moyennes = [];
        foreach ($periodes as $periodeId) {
            $notesPeriode = $notes->get($periodeId, collect());
            $moy = $this->calculerMoyenneDepuisNotes($notesPeriode);
            if ($moy !== null) {
                $moyennes[] = $moy;
            }
        }

        return count($moyennes) > 0
            ? round(array_sum($moyennes) / count($moyennes), 2)
            : null;
    }

    // ============================================================
    // BROUILLONS
    // ============================================================

    public function getBrouillonsEnCours(User $user): Collection
    {
        return Note::where('statut', Note::STATUT_BROUILLON)
            ->whereHas('courSalle', fn ($q) => $q->where('titulaire_id', $user->id))
            ->with([
                'courSalle.cour:id,nom',
                'courSalle.salle:id,nom',
                'periodeNote:id,nom',
            ])
            ->get()
            ->groupBy('cour_salle_id');
    }

    // ============================================================
    // CLASSEMENT (optimisé : 1 requête + tri en mémoire)
    // ============================================================

    public function calculerClassement(int $salleId, int $periodeId): array
    {
        $eleves = $this->getElevesParSalle($salleId);

        // 1 seule requête pour toutes les notes publiées de la salle/période
        $notes = Note::where('periode_note_id', $periodeId)
            ->where('statut', Note::STATUT_PUBLIE)
            ->whereNotNull('note')
            ->whereHas('courSalle', fn ($q) => $q->where('salle_classe_id', $salleId))
            ->with('courSalle.ponderation')
            ->get(['eleve_id', 'note', 'cour_salle_id'])
            ->groupBy('eleve_id');

        $classement = [];
        foreach ($eleves as $eleve) {
            $notesEleve = $notes->get($eleve->id, collect());

            $classement[] = [
                'eleve'       => $eleve,
                'moyenne'     => $this->calculerMoyenneDepuisNotes($notesEleve),
                'pourcentage' => $this->calculerPourcentageDepuisNotes($notesEleve),
            ];
        }

        // Tri par pourcentage décroissant (null en dernier)
        usort($classement, function ($a, $b) {
            if ($a['pourcentage'] === $b['pourcentage']) return 0;
            if ($a['pourcentage'] === null) return 1;
            if ($b['pourcentage'] === null) return -1;
            return $b['pourcentage'] <=> $a['pourcentage'];
        });

        // Attribution des rangs (ex-aequo = même rang)
        $rang              = 0;
        $dernierPourcentage = null;

        foreach ($classement as $index => $item) {
            if ($item['pourcentage'] !== $dernierPourcentage) {
                $rang               = $index + 1;
                $dernierPourcentage = $item['pourcentage'];
            }
            $classement[$index]['rang'] = $rang;
        }

        return $classement;
    }

    // ============================================================
    // NOMBRE DE COURS PUBLIÉS
    // ============================================================

    public function getNombreCoursPubliesParPeriode(
        int $periodeId,
        ?int $salleId = null,
        ?int $userId = null
    ): int {
        $query = Note::where('periode_note_id', $periodeId)
            ->where('statut', Note::STATUT_PUBLIE)
            ->whereNotNull('note');

        if ($salleId) {
            $query->whereHas('courSalle', fn ($q) => $q->where('salle_classe_id', $salleId));
        }

        if ($userId) {
            $query->whereHas('courSalle', fn ($q) => $q->where('titulaire_id', $userId));
        }

        return $query->distinct('cour_salle_id')->count('cour_salle_id');
    }
}