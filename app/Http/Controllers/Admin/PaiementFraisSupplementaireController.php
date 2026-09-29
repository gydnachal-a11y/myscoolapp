<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaiementFraisSupplementaireRequest;
use App\Http\Requests\UpdatePaiementFraisSupplementaireRequest;
use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\Eleve;
use App\Models\FraisSupplementaire;
use App\Models\Inscription;
use App\Models\PaiementFraisSupplementaire;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Services\PaiementFraisSupplementaireService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PaiementFraisSupplementaireController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const CACHE_TTL        = 3600;
    private const PER_PAGE_DEFAULT = 20;
    private const PER_PAGE_MAX     = 100;
    private const PER_PAGE_MIN     = 5;
    private const TAUX_DEFAULT     = 2800.0;

    // ============================================================
    // DÉPENDANCES
    // ============================================================

    /**
     * @var PaiementFraisSupplementaireService
     */
    private PaiementFraisSupplementaireService $service;

    public function __construct(PaiementFraisSupplementaireService $service)
    {
        $this->service = $service;
    }

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'frais'     => ['nullable', 'integer', 'exists:frais_supplementaires,id'],
            'salle'     => ['nullable', 'integer', 'exists:salles_de_classe,id'],
            'recherche' => ['nullable', 'string', 'max:100'],
            'per_page'  => [
                'nullable',
                'integer',
                'min:' . self::PER_PAGE_MIN,
                'max:' . self::PER_PAGE_MAX,
            ],
        ]);

        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE_DEFAULT);

        $query = $this->buildFilteredQuery($request);

        $paiements = (clone $query)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $stats = $this->getStats($query);

        $frais      = FraisSupplementaire::orderBy('libelle')->get(['id', 'libelle']);
        $salles     = SalleDeClasse::orderBy('nom')->get(['id', 'nom']);
        $tauxChange = $this->getTauxChange();

        return view('admin.paiement-frais-supplementaires.index', array_merge([
            'paiements'  => $paiements,
            'frais'      => $frais,
            'salles'     => $salles,
            'tauxChange' => $tauxChange,
        ], $stats));
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.paiement-frais-supplementaires.create', $this->prepareFormData());
    }

    public function store(StorePaiementFraisSupplementaireRequest $request): RedirectResponse
    {
        $data  = $request->validated();
        $frais = FraisSupplementaire::findOrFail($data['frais_supplementaire_id']);

        try {
            /* ---------- Paiement groupé ---------- */
            if (!empty($data['eleve_ids']) && is_array($data['eleve_ids'])) {
                $eleveIds     = array_values(array_unique(array_map('intval', $data['eleve_ids'])));
                $montantTotal = (float) ($data['montant_paye_usd'] ?? 0);
                $commentaire  = $data['commentaire'] ?? null;

                if ($montantTotal <= 0) {
                    return back()->withInput()->with('error', 'Le montant total doit être supérieur à 0.');
                }

                $eleves = Eleve::whereIn('id', $eleveIds)->get()->all();

                $paiements = $this->service->enregistrerPaiementGroupe(
                    $eleves,
                    $frais,
                    $montantTotal,
                    $commentaire
                );

                $message = "Paiement groupé enregistré pour {$paiements->count()} élève(s).";
            }
            /* ---------- Paiement simple ---------- */
            else {
                $eleve = Eleve::findOrFail($data['eleve_id']);

                $this->service->enregistrerPaiement(
                    $eleve,
                    $frais,
                    $data['commentaire'] ?? null,
                    isset($data['montant_paye_usd']) ? (float) $data['montant_paye_usd'] : null
                );

                $message = 'Paiement enregistré avec succès.';
            }

            return redirect()
                ->route('admin.paiement-frais-supplementaires.index')
                ->with('success', $message);

        } catch (Throwable $e) {
            Log::error('Erreur store paiement frais supplémentaire', [
                'message' => $e->getMessage(),
                'data'    => $data,
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', config('app.debug')
                    ? "Erreur : {$e->getMessage()}"
                    : "Une erreur est survenue lors de l'enregistrement.");
        }
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(PaiementFraisSupplementaire $paiement): View
    {
        $data = $this->prepareFormData($paiement);
        $data['paiement'] = $paiement;

        return view('admin.paiement-frais-supplementaires.edit', $data);
    }

    public function update(
        UpdatePaiementFraisSupplementaireRequest $request,
        PaiementFraisSupplementaire $paiement
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $eleve = Eleve::findOrFail($data['eleve_id']);
            $frais = FraisSupplementaire::findOrFail($data['frais_supplementaire_id']);

            $this->service->mettreAJourPaiement(
                $paiement,
                $eleve,
                $frais,
                $data['commentaire'] ?? null,
                isset($data['montant_paye_usd']) ? (float) $data['montant_paye_usd'] : null
            );

            return redirect()
                ->route('admin.paiement-frais-supplementaires.index')
                ->with('success', 'Paiement mis à jour avec succès.');

        } catch (Throwable $e) {
            Log::error('Erreur update paiement frais supplémentaire', [
                'message' => $e->getMessage(),
                'id'      => $paiement->id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(PaiementFraisSupplementaire $paiement): RedirectResponse
    {
        try {
            $this->service->supprimerPaiement($paiement);

            return redirect()
                ->route('admin.paiement-frais-supplementaires.index')
                ->with('success', 'Paiement supprimé.');

        } catch (Throwable $e) {
            Log::error('Erreur destroy paiement frais supplémentaire', [
                'message' => $e->getMessage(),
                'id'      => $paiement->id,
            ]);

            return back()->with('error', 'Impossible de supprimer ce paiement.');
        }
    }

    // ============================================================
    // EXPORT
    // ============================================================

    public function export(Request $request, string $format): Response
    {
        if (!in_array($format, ['pdf', 'csv', 'xml', 'word'], true)) {
            return back()->with('error', 'Format non supporté.');
        }

        $paiements = $this->buildFilteredQuery($request)
            ->orderByDesc('date_paiement')
            ->with([
                'eleve:id,nom,postnom,prenom',
                'fraisSupplementaire:id,libelle',
            ])
            ->get();

        return match ($format) {
            'pdf'  => $this->exportPdf($paiements),
            'csv'  => $this->exportCsv($paiements),
            'xml'  => $this->exportXml($paiements),
            'word' => $this->exportWord($paiements),
        };
    }

    // ============================================================
    // MÉTHODES PRIVÉES — EXPORT
    // ============================================================

    private function exportPdf(Collection $paiements): Response
    {
        $pdf = Pdf::loadView('exports.paiement-frais-supplementaires.pdf', [
            'paiements'  => $paiements,
            'tauxChange' => $this->getTauxChange(),
        ]);

        return $pdf->download('paiements_frais_supplementaires.pdf');
    }

    private function exportCsv(Collection $paiements): Response
    {
        $filename = 'paiements_frais_supplementaires.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($paiements): void {
            $out = fopen('php://output', 'w');

            /* BOM UTF-8 → Excel affiche correctement les accents */
            fwrite($out, "\xEF\xBB\xBF");

            /* Séparateur ";" pour Excel FR */
            fputcsv($out, ['Élève', 'Frais', 'Montant USD', 'Montant FC', 'Date'], ';');

            foreach ($paiements as $p) {
                fputcsv($out, [
                    trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')) ?: '—',
                    $p->fraisSupplementaire?->libelle ?? '—',
                    number_format((float) $p->montant_paye_usd, 2, ',', ' '),
                    number_format((float) $p->montant_paye_fc, 0, ',', ' '),
                    $p->date_paiement?->format('d/m/Y') ?? '—',
                ], ';');
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportXml(Collection $paiements): Response
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><paiements/>');

        foreach ($paiements as $p) {
            $item = $xml->addChild('paiement');
            $item->addChild('eleve', htmlspecialchars(
                trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')) ?: '—',
                ENT_XML1
            ));
            $item->addChild('frais', htmlspecialchars(
                $p->fraisSupplementaire?->libelle ?? '—',
                ENT_XML1
            ));
            $item->addChild('montant_usd', (string) $p->montant_paye_usd);
            $item->addChild('montant_fc', (string) $p->montant_paye_fc);
            $item->addChild('date', $p->date_paiement?->format('d/m/Y') ?? '—');
        }

        return response($xml->asXML(), 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function exportWord(Collection $paiements): Response
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC']);
        $table->addRow();
        $table->addCell(2000)->addText('Élève', ['bold' => true]);
        $table->addCell(2000)->addText('Frais', ['bold' => true]);
        $table->addCell(1500)->addText('Montant USD', ['bold' => true]);
        $table->addCell(1500)->addText('Date', ['bold' => true]);

        foreach ($paiements as $p) {
            $table->addRow();
            $table->addCell(2000)->addText(
                trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')) ?: '—'
            );
            $table->addCell(2000)->addText($p->fraisSupplementaire?->libelle ?? '—');
            $table->addCell(1500)->addText(number_format((float) $p->montant_paye_usd, 2, ',', ' '));
            $table->addCell(1500)->addText($p->date_paiement?->format('d/m/Y') ?? '—');
        }

        $tempFile  = tempnam(sys_get_temp_dir(), 'export_word_');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()
            ->download($tempFile, 'paiements_frais_supplementaires.docx')
            ->deleteFileAfterSend(true);
    }

    // ============================================================
    // MÉTHODES PRIVÉES — REQUÊTES
    // ============================================================

    private function buildFilteredQuery(Request $request): Builder
    {
        $query = PaiementFraisSupplementaire::query()
            ->with(['eleve', 'fraisSupplementaire']);

        if ($request->filled('frais')) {
            $query->where('frais_supplementaire_id', (int) $request->input('frais'));
        }

        if ($request->filled('salle')) {
            $anneeActiveId = $this->getAnneeActiveId();
            $salleId       = (int) $request->input('salle');

            $query->whereHas('eleve.inscriptions', function (Builder $q) use ($salleId, $anneeActiveId): void {
                $q->where('salle_classe_id', $salleId);
                if ($anneeActiveId) {
                    $q->where('annee_scolaire_id', $anneeActiveId);
                }
            });
        }

        if ($request->filled('recherche')) {
            $search = trim((string) $request->input('recherche'));
            $query->whereHas('eleve', function (Builder $sub) use ($search): void {
                $sub->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('prenom', 'LIKE', "%{$search}%")
                    ->orWhere('postnom', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    private function getStats(Builder $query): array
    {
        $global = (clone $query)
            ->selectRaw('
                COALESCE(SUM(montant_paye_usd), 0) AS total_usd,
                COALESCE(SUM(montant_paye_fc), 0)  AS total_fc,
                COUNT(*)                           AS nb_paiements,
                COUNT(DISTINCT frais_supplementaire_id) AS nb_frais
            ')
            ->first();

        $statsParFrais = (clone $query)
            ->select(
                'frais_supplementaire_id',
                DB::raw('COALESCE(SUM(montant_paye_usd), 0) AS total_usd'),
                DB::raw('COALESCE(SUM(montant_paye_fc), 0) AS total_fc'),
                DB::raw('COUNT(*) AS count'),
                DB::raw('COALESCE(AVG(montant_paye_usd), 0) AS moyenne_usd')
            )
            ->groupBy('frais_supplementaire_id')
            ->with('fraisSupplementaire:id,libelle')
            ->get()
            ->map(fn ($item) => [
                'libelle'     => $item->fraisSupplementaire?->libelle ?? 'Frais supprimé',
                'total_usd'   => (float) $item->total_usd,
                'total_fc'    => (float) $item->total_fc,
                'count'       => (int)   $item->count,
                'moyenne_usd' => round((float) $item->moyenne_usd, 2),
            ])
            ->all();

        return [
            'totalPayeUSD'     => (float) $global->total_usd,
            'totalPayeFC'      => (float) $global->total_fc,
            'nbPaiements'      => (int)   $global->nb_paiements,
            'nbFraisConcernes' => (int)   $global->nb_frais,
            'statsParFrais'    => $statsParFrais,
        ];
    }

    // ============================================================
    // MÉTHODES PRIVÉES — FORMULAIRES
    // ============================================================

    private function prepareFormData(?PaiementFraisSupplementaire $paiement = null): array
    {
        $anneeActiveId = $this->getAnneeActiveId();

        return [
            'frais'          => $this->getFraisPourFormulaire($paiement),
            'salles'         => SalleDeClasse::query()
                ->select('id', 'nom', 'section_id')
                ->orderBy('nom')
                ->get(),
            'sections'       => Section::query()
                ->orderBy('nom')
                ->get(['id', 'nom']),
            'elevesParSalle' => $this->getElevesParSalle($anneeActiveId),
            'tauxChange'     => $this->getTauxChange(),
        ];
    }

    private function getFraisPourFormulaire(?PaiementFraisSupplementaire $paiement = null): Collection
    {
        $query = FraisSupplementaire::with(['salles:id,nom']);

        if (!$paiement) {
            $query->where('est_ouvert', true);
        }

        return $query->orderBy('libelle')->get()->map(fn ($frais) => [
            'id'                     => $frais->id,
            'libelle'                => $frais->libelle,
            'montant'                => (float) $frais->montant,
            'est_pour_toutes_salles' => (bool)  $frais->est_pour_toutes_salles,
            'salles_ids'             => $frais->salles->pluck('id')->all(),
        ]);
    }

    private function getAnneeActiveId(): ?int
    {
        return Cache::remember('annee_active_id', self::CACHE_TTL, function (): ?int {
            return AnneeScolaire::where('cloturee', false)
                ->latest('date_debut')
                ->value('id');
        });
    }

    private function getElevesParSalle(?int $anneeActiveId): array
    {
        if (!$anneeActiveId) {
            return [];
        }

        return Inscription::with('eleve:id,nom,postnom,prenom')
            ->where('annee_scolaire_id', $anneeActiveId)
            ->get()
            ->groupBy('salle_classe_id')
            ->map(fn (Collection $inscriptions) => $inscriptions
                ->map(fn ($inscription) => [
                    'id'          => $inscription->eleve?->id,
                    'nom_complet' => trim(
                        ($inscription->eleve?->nom     ?? '') . ' ' .
                        ($inscription->eleve?->postnom ?? '') . ' ' .
                        ($inscription->eleve?->prenom  ?? '')
                    ) ?: '—',
                ])
                ->filter(fn ($e) => $e['id'] !== null)
                ->values()
                ->all()
            )
            ->all();
    }

    private function getTauxChange(): float
    {
        try {
            return Devise::tauxUsdVersCdf();
        } catch (Throwable $e) {
            Log::warning('Taux de change indisponible', ['error' => $e->getMessage()]);

            return self::TAUX_DEFAULT;
        }
    }
}