<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInscriptionRequest;
use App\Http\Requests\UpdateInscriptionRequest;
use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Option;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\Session;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class InscriptionController extends Controller
{
    protected const PER_PAGE = 15;
    protected const DEFAULT_EXCHANGE_RATE = 2800;
    protected const MAX_ELEVES_PER_BATCH = 50;

    /**
     * Liste des inscriptions avec filtres, tri et exports.
     */
    public function index(Request $request)
    {
        $salles = SalleDeClasse::orderBy('nom')->get();
        $annees = AnneeScolaire::orderBy('date_debut', 'desc')->get();
        $options = Option::orderBy('nom')->get();                   // Ajout options pour filtre et affichage
        $tauxChange = $this->getTauxUsdCdf();                       // Ajout taux de change pour conversion FC

        $inscriptions = $this->buildFilteredQuery($request)
            ->paginate(self::PER_PAGE)
            ->appends($request->except('page'));

        return view('admin.inscriptions.index', compact(
            'inscriptions',
            'salles',
            'annees',
            'options',
            'tauxChange'
        ));
    }

    /**
     * Formulaire de création.
     */
    public function create()
    {
        return view('admin.inscriptions.create', $this->buildFormData());
    }

    /**
     * Enregistrement d'une ou plusieurs inscriptions.
     */
    public function store(StoreInscriptionRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $eleveIds = $validated['eleve_ids'] ?? [$validated['eleve_id']] ?? [];
            $anneeId = $validated['annee_scolaire_id'];
            $salleId = $validated['salle_classe_id'];
            $dateInscription = $validated['date_inscription'];
            $reduction = (float) ($validated['reduction_frais'] ?? 0);
            $action = $validated['action'] ?? 'save';
            $redoublement = (bool) ($validated['redoublement'] ?? false);

            $annee = AnneeScolaire::findOrFail($anneeId);
            $salle = SalleDeClasse::findOrFail($salleId);

            $this->validateInscriptionConditions($annee, $salle, $eleveIds, $action, $redoublement);

            $result = $this->processInscriptions($eleveIds, $annee, $salle, $dateInscription, $reduction, $action, $redoublement);

            if ($result['created'] === 0) {
                DB::rollBack();
                return back()->withInput()->with('error', implode(' ', $result['errors']));
            }

            DB::commit();

            $message = "{$result['created']} inscription(s) créée(s) avec succès.";
            if (!empty($result['errors'])) {
                $message .= ' Attention : ' . implode(' ', $result['errors']);
            }

            Log::info('Inscriptions multiples créées', [
                'count'    => $result['created'],
                'eleves'   => $eleveIds,
                'annee_id' => $anneeId,
                'salle_id' => $salleId,
                'user_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.inscriptions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création des inscriptions', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            return back()->withInput()->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    /**
     * Formulaire de modification.
     */
    public function edit(Inscription $inscription)
    {
        $data = $this->buildFormData();
        $data['inscription'] = $inscription->loadMissing(['eleve', 'salleDeClasse.section.session', 'salleDeClasse.option', 'anneeScolaire']);
        $data['salles'] = SalleDeClasse::with('section.session', 'option')->orderBy('nom')->get();
        $data['eleve_actuel'] = $inscription->eleve;

        return view('admin.inscriptions.edit', $data);
    }

    /**
     * Mise à jour d'une inscription.
     */
    public function update(UpdateInscriptionRequest $request, Inscription $inscription): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $result = $this->processValidatedData($request->validated(), $inscription->id);
            if ($result instanceof RedirectResponse) {
                return $result;
            }

            $inscription->update($result);

            $this->updateInscriptionStatus($inscription->eleve_id, $inscription->annee_scolaire_id);

            DB::commit();

            Log::info('Inscription mise à jour', [
                'inscription_id' => $inscription->id,
                'eleve_id'       => $result['eleve_id'],
                'annee_id'       => $result['annee_scolaire_id'],
                'user_id'        => auth()->id(),
            ]);

            return redirect()
                ->route('admin.inscriptions.index')
                ->with('success', 'Inscription modifiée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour de l\'inscription', [
                'inscription_id' => $inscription->id,
                'error'          => $e->getMessage(),
                'user_id'        => auth()->id(),
            ]);
            return back()->withInput()->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    /**
     * Suppression d'une inscription.
     */
    public function destroy(Inscription $inscription): RedirectResponse
    {
        try {
            $eleveId = $inscription->eleve_id;
            $anneeId = $inscription->annee_scolaire_id;

            $inscription->delete();

            $this->updateInscriptionStatus($eleveId, $anneeId);

            Log::info('Inscription supprimée', [
                'inscription_id' => $inscription->id,
                'eleve_id'       => $eleveId,
                'annee_id'       => $anneeId,
                'user_id'        => auth()->id(),
            ]);

            return redirect()
                ->route('admin.inscriptions.index')
                ->with('success', 'Inscription supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de l\'inscription', [
                'inscription_id' => $inscription->id,
                'error'          => $e->getMessage(),
                'user_id'        => auth()->id(),
            ]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    /**
     * Vide complètement la table des inscriptions (admin uniquement).
     */
    public function truncate(): RedirectResponse
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Seul un administrateur peut effectuer cette action.');
        }

        Inscription::truncate();
        Eleve::query()->update(['inscrit' => false]);

        Log::warning('Toutes les inscriptions ont été supprimées', [
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('admin.inscriptions.index')
            ->with('success', 'Toutes les inscriptions ont été supprimées avec succès.');
    }

    // ==========================================
    // Exports & Impression
    // ==========================================

    public function exportPdf(Request $request)
    {
        $inscriptions = $this->getFilteredInscriptions($request);
        $pdf = Pdf::loadView('admin.inscriptions.pdf', compact('inscriptions'));

        return $pdf->download('inscriptions.pdf');
    }

    public function exportCsv(Request $request)
    {
        $inscriptions = $this->getFilteredInscriptions($request);

        return response()->stream(
            function () use ($inscriptions) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Élève', 'Année', 'Salle', 'Option', 'Frais inscription', 'Frais annuel', 'Date inscription']);
                foreach ($inscriptions as $ins) {
                    fputcsv($file, [
                        $ins->eleve->nom . ' ' . $ins->eleve->prenom,
                        $ins->anneeScolaire->libelle,
                        $ins->salleDeClasse->nom,
                        $ins->salleDeClasse->option->nom ?? '',
                        $ins->frais_inscription_final,
                        $ins->frais_annuel_final,
                        $ins->date_inscription->format('d/m/Y'),
                    ]);
                }
                fclose($file);
            },
            200,
            [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="inscriptions.csv"',
            ]
        );
    }

    public function exportXml(Request $request)
    {
        $inscriptions = $this->getFilteredInscriptions($request);
        $xml = new \SimpleXMLElement('<inscriptions/>');
        foreach ($inscriptions as $ins) {
            $item = $xml->addChild('inscription');
            $item->addChild('eleve', $ins->eleve->nom . ' ' . $ins->eleve->prenom);
            $item->addChild('annee', $ins->anneeScolaire->libelle);
            $item->addChild('salle', $ins->salleDeClasse->nom);
            $item->addChild('option', $ins->salleDeClasse->option->nom ?? '');
            $item->addChild('frais_inscription', $ins->frais_inscription_final);
            $item->addChild('frais_annuel', $ins->frais_annuel_final);
            $item->addChild('date', $ins->date_inscription->format('d/m/Y'));
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="inscriptions.xml"');
    }

    public function exportDoc(Request $request)
    {
        $inscriptions = $this->getFilteredInscriptions($request);
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $logoPath = Setting::getValue('ecole_logo');
        if ($logoPath && file_exists(public_path('storage/' . $logoPath))) {
            $section->addImage(public_path('storage/' . $logoPath), ['width' => 60, 'alignment' => 'left']);
        }
        $section->addText(Setting::getValue('ecole_nom', 'Mon École'), ['bold' => true, 'size' => 16]);
        $section->addText(Setting::getValue('ecole_adresse') . ' | ' . Setting::getValue('ecole_telephone'));
        $section->addText('Liste des inscriptions', ['bold' => true, 'size' => 14, 'spaceAfter' => 300]);

        $table = $section->addTable();
        foreach (['Élève', 'Année', 'Salle', 'Option', 'Frais inscription', 'Frais annuel', 'Date'] as $header) {
            $table->addCell(1800)->addText($header, ['bold' => true]);
        }
        foreach ($inscriptions as $ins) {
            $table->addRow();
            $table->addCell(1800)->addText($ins->eleve->nom . ' ' . $ins->eleve->prenom);
            $table->addCell(1800)->addText($ins->anneeScolaire->libelle);
            $table->addCell(1800)->addText($ins->salleDeClasse->nom);
            $table->addCell(1800)->addText($ins->salleDeClasse->option->nom ?? '');
            $table->addCell(1800)->addText(number_format($ins->frais_inscription_final, 0) . ' $');
            $table->addCell(1800)->addText(number_format($ins->frais_annuel_final, 0) . ' $');
            $table->addCell(1800)->addText($ins->date_inscription->format('d/m/Y'));
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'doc');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return response()->download($tempFile, 'inscriptions.docx')->deleteFileAfterSend();
    }

    public function imprimer(Request $request)
    {
        $inscriptions = $this->getFilteredInscriptions($request);
        return view('admin.inscriptions.imprimer', compact('inscriptions'));
    }

    // ==========================================
    // API : Récupération des élèves disponibles
    // ==========================================

    /**
     * Retourne la liste des élèves non inscrits pour l'année donnée.
     * Si aucune année n'est spécifiée, prend l'année active (non clôturée).
     */
    public function getElevesDisponibles(Request $request): JsonResponse
    {
        $anneeId = $request->input('annee_id');

        if (!$anneeId) {
            $annee = AnneeScolaire::where('cloturee', false)
                ->orderBy('date_debut', 'desc')
                ->first();

            if ($annee) {
                $anneeId = $annee->id;
            } else {
                return response()->json([]);
            }
        }

        $annee = AnneeScolaire::find($anneeId);
        if (!$annee) {
            return response()->json(['error' => 'Année invalide'], 404);
        }

        // Utiliser le scope 'nonInscrits' pour une requête rapide
        $eleves = Eleve::nonInscrits()
            ->select('id', 'nom', 'prenom', 'date_naissance')
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get()
            ->map(fn($e) => [
                'id'             => $e->id,
                'nom_complet'    => $e->nom . ' ' . $e->prenom,
                'date_naissance' => $e->date_naissance?->format('Y-m-d') ?? 'Inconnue',
                'age'            => $e->date_naissance?->age,
            ]);

        return response()->json($eleves);
    }

    // ==========================================
    // Méthodes privées
    // ==========================================

    /**
     * Construit les données communes aux formulaires create/edit.
     */
    private function buildFormData(): array
    {
        $anneeActive = AnneeScolaire::where('cloturee', false)
            ->orderBy('date_debut', 'desc')
            ->first();

        return [
            'annees'        => AnneeScolaire::where('cloturee', false)->orderBy('date_debut', 'desc')->get(),
            'sessions'      => Session::orderBy('nom')->get(),
            'sections'      => Section::orderBy('nom')->get(),
            'salles'        => $this->getSallesFormatted(),
            'taux'          => $this->getTauxUsdCdf(),
            'anneeActiveId' => $anneeActive?->id,
        ];
    }

    /**
     * Retourne les salles formatées avec les relations nécessaires.
     */
    private function getSallesFormatted(): array
    {
        return SalleDeClasse::with(['section.session', 'option'])
            ->orderBy('nom')
            ->get()
            ->map(fn($s) => [
                'id'                      => $s->id,
                'nom'                     => $s->nom,
                'section'                 => $s->section->nom ?? 'N/A',
                'session'                 => $s->section->session->nom ?? 'N/A',
                'session_id'              => $s->section->session_id ?? null,
                'section_id'              => $s->section_id,
                'option'                  => $s->option->nom ?? null,
                'age_min'                 => $s->age_min,
                'age_max'                 => $s->age_max,
                'frais_inscription'       => $s->frais_inscription,
                'frais_annuel'            => $s->frais_annuel,
                'frais_scolarite_mensuel' => $s->frais_scolarite_mensuel ?? 0,
                'frais_par_tranche'       => $s->frais_par_tranche ?? 0,
                'nombre_tranches'         => $s->nombre_tranches ?? 1,
                'mode_paiement'           => $s->mode_paiement,
            ])
            ->toArray();
    }

    /**
     * Retourne la collection filtrée à partir de la requête.
     */
    private function getFilteredInscriptions(Request $request): Collection
    {
        return $this->buildFilteredQuery($request)->get();
    }

    /**
     * Construit la requête avec filtres et tri.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        return Inscription::with(['eleve', 'salleDeClasse.option', 'anneeScolaire'])
            ->when($request->filled('salle'), fn($q, $id) => $q->where('salle_classe_id', $id))
            ->when($request->filled('option'), function ($q) use ($request) {
                $q->whereHas('salleDeClasse', fn($sub) => $sub->where('option_id', $request->option));
            })
            ->when($request->filled('annee'), fn($q, $id) => $q->where('annee_scolaire_id', $id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = "%{$request->search}%";
                $q->whereHas('eleve', function ($sub) use ($search) {
                    $sub->where('nom', 'like', $search)
                        ->orWhere('prenom', 'like', $search);
                });
            })
            ->when($request->input('tri') === 'alpha', function ($q) {
                $q->orderBy(
                    Eleve::select('nom')->whereColumn('eleves.id', 'inscriptions.eleve_id')
                )->orderBy(
                    Eleve::select('prenom')->whereColumn('eleves.id', 'inscriptions.eleve_id')
                );
            }, fn($q) => $q->orderBy('date_inscription', 'desc'));
    }

    /**
     * Vérifie les conditions préalables à l'inscription.
     */
    private function validateInscriptionConditions(AnneeScolaire $annee, SalleDeClasse $salle, array $eleveIds, string $action, bool $redoublement): void
    {
        if ($annee->cloturee) {
            throw new \Exception('Année scolaire clôturée.');
        }

        $currentCount = Inscription::where('salle_classe_id', $salle->id)
            ->where('annee_scolaire_id', $annee->id)
            ->count();
        $availableSlots = $salle->capacite_max - $currentCount;
        if (count($eleveIds) > $availableSlots) {
            throw new \Exception("Capacité insuffisante : il reste {$availableSlots} place(s).");
        }

        $duplicateIds = Inscription::whereIn('eleve_id', $eleveIds)
            ->where('annee_scolaire_id', $annee->id)
            ->pluck('eleve_id')
            ->toArray();

        if (!empty($duplicateIds)) {
            $duplicateNames = Eleve::whereIn('id', $duplicateIds)
                ->get()
                ->map(fn($e) => $e->nom . ' ' . $e->prenom)
                ->join(', ');
            throw new \Exception("Les élèves suivants sont déjà inscrits pour l'année {$annee->libelle} : {$duplicateNames}.");
        }

        $eleves = Eleve::whereIn('id', $eleveIds)->get()->keyBy('id');
        $errors = [];
        foreach ($eleveIds as $id) {
            $eleve = $eleves->get($id);
            if (!$eleve) {
                throw new \Exception("L'élève ID {$id} est introuvable.");
            }
            if (!$eleve->date_naissance) {
                $errors[] = "Date de naissance manquante pour {$eleve->nom} {$eleve->prenom}.";
                continue;
            }
            $age = $eleve->date_naissance->age;
            if ($age < $salle->age_min || $age > $salle->age_max) {
                if ($action !== 'force' && !$redoublement) {
                    $errors[] = "Âge de {$eleve->nom} {$eleve->prenom} ({$age} ans) hors tranche ({$salle->age_min}-{$salle->age_max} ans).";
                }
            }
        }
        if (!empty($errors)) {
            throw new \Exception(implode(' | ', $errors));
        }
    }

    /**
     * Traite les inscriptions pour la liste d'élèves donnée.
     */
    private function processInscriptions(array $eleveIds, AnneeScolaire $annee, SalleDeClasse $salle, string $dateInscription, float $reduction, string $action, bool $redoublement): array
    {
        $created = 0;
        $errors = [];
        $batchSize = self::MAX_ELEVES_PER_BATCH;

        foreach (array_chunk($eleveIds, $batchSize) as $chunk) {
            foreach ($chunk as $eleveId) {
                $eleve = Eleve::find($eleveId);
                if (!$eleve) {
                    $errors[] = "Élève ID {$eleveId} introuvable.";
                    continue;
                }

                if ($eleve->date_naissance) {
                    $age = $eleve->date_naissance->age;
                    if ($age < $salle->age_min || $age > $salle->age_max) {
                        if ($action !== 'force' && !$redoublement) {
                            $errors[] = "Âge de {$eleve->nom} {$eleve->prenom} ({$age} ans) hors tranche.";
                            continue;
                        }
                    }
                } else {
                    $errors[] = "Date de naissance manquante pour {$eleve->nom} {$eleve->prenom}.";
                    continue;
                }

                Inscription::create([
                    'eleve_id'                => $eleveId,
                    'annee_scolaire_id'       => $annee->id,
                    'salle_classe_id'         => $salle->id,
                    'date_inscription'        => $dateInscription,
                    'reduction_frais'         => $reduction,
                    'frais_inscription_final' => max(0, $salle->frais_inscription - $reduction),
                    'frais_annuel_final'      => max(0, $salle->frais_annuel - $reduction),
                ]);

                // Marquer l'élève comme inscrit
                $eleve->marquerCommeInscrit();
                $created++;
            }
        }

        return [
            'created' => $created,
            'errors'  => $errors,
        ];
    }

    /**
     * Traite les données validées pour une seule inscription (édition).
     */
    private function processValidatedData(array $data, ?int $excludeId = null)
    {
        $annee = AnneeScolaire::findOrFail($data['annee_scolaire_id']);
        $eleve = Eleve::findOrFail($data['eleve_id']);
        $salle = SalleDeClasse::findOrFail($data['salle_classe_id']);

        if ($annee->cloturee) {
            return back()->with('error', 'Année scolaire clôturée.')->withInput();
        }

        if ($this->isDuplicateInscription($eleve->id, $annee->id, $excludeId)) {
            return back()
                ->with('error', "L'élève {$eleve->nom} {$eleve->prenom} est déjà inscrit pour l'année {$annee->libelle}.")
                ->withInput();
        }

        if ($redirect = $this->validateAgeConstraint($eleve, $salle, $data)) {
            return $redirect;
        }

        if ($redirect = $this->validateRoomCapacity($salle->id, $annee->id, $salle->capacite_max, $excludeId)) {
            return $redirect;
        }

        $reduction = (float) ($data['reduction_frais'] ?? 0);

        return [
            'eleve_id'                => $eleve->id,
            'annee_scolaire_id'       => $annee->id,
            'salle_classe_id'         => $salle->id,
            'date_inscription'        => $data['date_inscription'],
            'reduction_frais'         => $reduction,
            'frais_inscription_final' => max(0, $salle->frais_inscription - $reduction),
            'frais_annuel_final'      => max(0, $salle->frais_annuel - $reduction),
        ];
    }

    /**
     * Met à jour le statut d'inscription d'un élève pour une année donnée.
     */
    private function updateInscriptionStatus(int $eleveId, int $anneeId): void
    {
        $hasActive = Inscription::where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeId)
            ->exists();

        if (!$hasActive) {
            Eleve::where('id', $eleveId)->update(['inscrit' => false]);
        } else {
            Eleve::where('id', $eleveId)->update(['inscrit' => true]);
        }
    }

    private function isDuplicateInscription(int $eleveId, int $anneeId, ?int $excludeId = null): bool
    {
        return Inscription::where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeId)
            ->when($excludeId, fn($q, $id) => $q->where('id', '!=', $id))
            ->exists();
    }

    private function validateAgeConstraint(Eleve $eleve, SalleDeClasse $salle, array $data): ?RedirectResponse
    {
        if (!$eleve->date_naissance) {
            return back()->with('error', "Date de naissance requise.")->withInput();
        }

        $age = $eleve->date_naissance->age;
        if ($age >= $salle->age_min && $age <= $salle->age_max) {
            return null;
        }

        if (($data['action'] ?? '') === 'force' || !empty($data['redoublement'])) {
            return null;
        }

        return back()->with('alerte_age', sprintf(
            "Âge (%d ans) hors tranche (%d - %d ans).",
            $age,
            $salle->age_min,
            $salle->age_max
        ))->withInput();
    }

    private function validateRoomCapacity(int $salleId, int $anneeId, int $capaciteMax, ?int $excludeId = null): ?RedirectResponse
    {
        $count = Inscription::where('salle_classe_id', $salleId)
            ->where('annee_scolaire_id', $anneeId)
            ->when($excludeId, fn($q, $id) => $q->where('id', '!=', $id))
            ->count();

        if ($count >= $capaciteMax) {
            return back()->with('error', "Capacité maximale atteinte ({$capaciteMax}/{$capaciteMax}).")->withInput();
        }

        return null;
    }

    private function getTauxUsdCdf(): float
    {
        $source = Devise::where('code', 'USD')->first();
        $cible  = Devise::where('code', 'CDF')->first();

        return ($source && $cible) ? $source->tauxVers($cible) : self::DEFAULT_EXCHANGE_RATE;
    }
}