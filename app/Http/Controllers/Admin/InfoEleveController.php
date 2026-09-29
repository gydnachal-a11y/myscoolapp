<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Option;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\Session;
use App\Services\InfoEleveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\Response;

class InfoEleveController extends Controller
{
    public function __construct(
        private InfoEleveService $infoEleveService
    ) {}

    /**
     * Liste des élèves avec filtres par option, salle, section et session.
     */
    public function index(Request $request): View
    {
        // Correction : utiliser la relation "sallesDeClasse" du modèle Option
        $options = Option::with('sallesDeClasse.section')->orderBy('nom')->get();
        $salles = SalleDeClasse::orderBy('nom')->get();
        $sections = Section::orderBy('nom')->get();
        $sessions = Session::orderBy('nom')->get();

        $query = $this->buildFilteredQuery($request);

        $inscriptions = $query->orderBy('date_inscription', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.info-eleves.index', compact(
            'options',
            'salles',
            'sections',
            'sessions',
            'inscriptions'
        ));
    }

    /**
     * Fiche individuelle de l'élève.
     */
    public function show(Eleve $eleve): View
    {
        $data = $this->infoEleveService->getFicheComplete($eleve->id);
        $data['eleve'] = $eleve;

        return view('admin.info-eleves.show', $data);
    }

    /**
     * Export PDF de la liste filtrée, triée alphabétiquement.
     */
    public function exportPdf(Request $request): Response
    {
        $inscriptions = $this->getFilteredInscriptionsSorted($request);
        $pdf = Pdf::loadView('admin.info-eleves.exports.pdf', compact('inscriptions'));
        return $pdf->download('eleves.pdf');
    }

    /**
     * Export CSV de la liste filtrée, triée alphabétiquement.
     */
    public function exportCsv(Request $request): Response
    {
        $inscriptions = $this->getFilteredInscriptionsSorted($request);

        return response()->streamDownload(function () use ($inscriptions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Élève', 'Année', 'Salle', 'Option']);
            foreach ($inscriptions as $ins) {
                fputcsv($handle, [
                    $ins->eleve->nom . ' ' . $ins->eleve->prenom,
                    $ins->anneeScolaire->libelle ?? '',
                    $ins->salleDeClasse->nom ?? '',
                    $ins->salleDeClasse->option->nom ?? '',
                ]);
            }
            fclose($handle);
        }, 'eleves.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Export XML de la liste filtrée, triée alphabétiquement.
     */
    public function exportXml(Request $request): Response
    {
        $inscriptions = $this->getFilteredInscriptionsSorted($request);
        $xml = new \SimpleXMLElement('<eleves/>');

        foreach ($inscriptions as $ins) {
            $item = $xml->addChild('eleve');
            $item->addChild('nom', htmlspecialchars($ins->eleve->nom . ' ' . $ins->eleve->prenom));
            $item->addChild('annee', htmlspecialchars($ins->anneeScolaire->libelle ?? ''));
            $item->addChild('salle', htmlspecialchars($ins->salleDeClasse->nom ?? ''));
            $item->addChild('option', htmlspecialchars($ins->salleDeClasse->option->nom ?? ''));
        }

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="eleves.xml"',
        ]);
    }

    /**
     * Export Word de la liste filtrée, triée alphabétiquement.
     */
    public function exportWord(Request $request): Response
    {
        $inscriptions = $this->getFilteredInscriptionsSorted($request);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle('Liste des élèves');

        $table = $section->addTable();
        $table->addRow();
        foreach (['Élève', 'Année', 'Salle', 'Option'] as $header) {
            $table->addCell(3000)->addText($header);
        }

        foreach ($inscriptions as $ins) {
            $table->addRow();
            $table->addCell(3000)->addText($ins->eleve->nom . ' ' . $ins->eleve->prenom);
            $table->addCell(3000)->addText($ins->anneeScolaire->libelle ?? '');
            $table->addCell(3000)->addText($ins->salleDeClasse->nom ?? '');
            $table->addCell(3000)->addText($ins->salleDeClasse->option->nom ?? '');
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $tmpFile = tempnam(sys_get_temp_dir(), 'eleves_') . '.docx';
        $writer->save($tmpFile);

        return response()
            ->download($tmpFile, 'eleves.docx')
            ->deleteFileAfterSend(true);
    }

    /**
     * Vue d'impression de la liste filtrée, triée alphabétiquement et groupée.
     */
    public function imprimer(Request $request): View
    {
        $inscriptions = $this->getInscriptionsGroupedForPrint($request);

        return view('admin.info-eleves.imprimer', compact('inscriptions'));
    }

    /**
     * Construit la requête de base avec tous les filtres.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = Inscription::with(['eleve', 'salleDeClasse.option', 'anneeScolaire']);

        // Filtre par session
        if ($request->filled('session')) {
            $query->whereHas('salleDeClasse.section', function ($q) use ($request) {
                $q->where('session_id', $request->session);
            });
        }

        // Filtre par section
        if ($request->filled('section')) {
            $query->whereHas('salleDeClasse', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        // Filtre par option
        if ($request->filled('option')) {
            $query->whereHas('salleDeClasse', function ($q) use ($request) {
                $q->where('option_id', $request->option);
            });
        }

        // Filtre par salle
        if ($request->filled('salle')) {
            $query->where('salle_classe_id', $request->salle);
        }

        // Recherche par nom ou prénom
        if ($request->filled('recherche')) {
            $search = trim($request->recherche);
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Récupère les inscriptions filtrées et triées alphabétiquement par nom d'élève.
     * Utilisé pour les exports simples (PDF, CSV, XML, Word).
     */
    private function getFilteredInscriptionsSorted(Request $request)
    {
        return $this->buildFilteredQuery($request)
            ->orderBy(
                Eleve::select('nom')->whereColumn('eleves.id', 'inscriptions.eleve_id')
            )
            ->orderBy(
                Eleve::select('prenom')->whereColumn('eleves.id', 'inscriptions.eleve_id')
            )
            ->get();
    }

    /**
     * Récupère les inscriptions filtrées, triées et groupées par section/session/salle.
     * Utilisé pour l'impression.
     */
    private function getInscriptionsGroupedForPrint(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['eleve', 'salleDeClasse.section.session', 'salleDeClasse.option', 'anneeScolaire'])
            ->orderBy(
                Eleve::select('nom')->whereColumn('eleves.id', 'inscriptions.eleve_id')
            )
            ->orderBy(
                Eleve::select('prenom')->whereColumn('eleves.id', 'inscriptions.eleve_id')
            );

        // Si un filtre salle ou option est actif, retour simple (pas de regroupement)
        if ($request->filled('salle') || $request->filled('option')) {
            return $query->get();
        }

        // Sinon, regroupement par Section -> Session -> Salle
        return $query->get()->groupBy([
            function ($inscription) {
                return $inscription->salleDeClasse->section->nom ?? 'Sans section';
            },
            function ($inscription) {
                return $inscription->salleDeClasse->section->session->nom ?? 'Sans session';
            },
            function ($inscription) {
                return $inscription->salleDeClasse->nom ?? 'Sans salle';
            },
        ]);
    }
}