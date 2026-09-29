<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExportService
{
    /**
     * Exporte une collection dans le format demandé.
     *
     * @param  string                    $resource  Identifiant de ressource (ex: 'paiements-principaux')
     * @param  string                    $format    pdf|csv|xml|word
     * @param  Collection                $items     Collection Eloquent à exporter
     * @param  array                     $extra     Données supplémentaires pour les vues PDF
     */
    public function exporter(string $resource, string $format, Collection $items, array $extra = []): Response
    {
        $config = $this->getConfig($resource);

        return match ($format) {
            'pdf'  => $this->exportPdf($config, $items, $extra),
            'csv'  => $this->exportCsv($config, $items),
            'xml'  => $this->exportXml($config, $items),
            'word' => $this->exportWord($config, $items),
            default => throw new \InvalidArgumentException("Format non supporté : {$format}"),
        };
    }

    private function exportPdf(array $config, Collection $items, array $extra): Response
    {
        $pdf = Pdf::loadView($config['view'], array_merge(
            ['items' => $items, $config['var'] => $items],
            $extra
        ));

        return $pdf->download($this->filename($config['slug'], 'pdf'));
    }

    private function exportCsv(array $config, Collection $items): Response
    {
        $headers = $config['headers'];

        return response()->streamDownload(function () use ($items, $headers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($handle, $headers);

            foreach ($items as $item) {
                fputcsv($handle, $config['row']($item));
            }

            fclose($handle);
        }, $this->filename($config['slug'], 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportXml(array $config, Collection $items): Response
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><items/>');

        foreach ($items as $item) {
            $node = $xml->addChild('item');
            foreach ($config['xml']($item) as $key => $value) {
                $node->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return response($xml->asXML(), 200, [
            'Content-Type'        => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($config['slug'], 'xml') . '"',
        ]);
    }

    private function exportWord(array $config, Collection $items): Response
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle($config['title'], 1);

        $table = $section->addTable(['borderSize' => 6, 'cellMargin' => 80]);

        // En-têtes
        $table->addRow();
        foreach ($config['headers'] as $header) {
            $table->addCell(2000)->addText($header, ['bold' => true]);
        }

        // Lignes
        foreach ($items as $item) {
            $table->addRow();
            foreach ($config['row']($item) as $cell) {
                $table->addCell(2000)->addText((string) $cell);
            }
        }

        $writer  = IOFactory::createWriter($phpWord, 'Word2007');
        $tmpFile = tempnam(sys_get_temp_dir(), 'export_') . '.docx';
        $writer->save($tmpFile);

        return response()
            ->download($tmpFile, $this->filename($config['slug'], 'docx'))
            ->deleteFileAfterSend(true);
    }

    private function filename(string $slug, string $ext): string
    {
        return "{$slug}_" . now()->format('Ymd_His') . ".{$ext}";
    }

    /**
     * Configuration par ressource.
     */
    private function getConfig(string $resource): array
    {
        return match ($resource) {
            'paiements-principaux' => [
                'slug'    => 'paiements',
                'title'   => 'Liste des paiements',
                'view'    => 'admin.info-paiements.exports.pdf_paiements',
                'var'     => 'paiements',
                'headers' => ['Élève', 'Payé (USD)', 'Payé (FC)', 'Restant (USD)', 'Restant (FC)', 'Statut', 'Date'],
                'row'     => fn ($p) => [
                    trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')),
                    $p->montant_paye_usd,
                    $p->montant_paye_fc,
                    $p->montant_restant_usd,
                    $p->montant_restant_fc,
                    $p->statut,
                    $p->date_paiement?->format('d/m/Y') ?? '',
                ],
                'xml' => fn ($p) => [
                    'eleve'        => trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')),
                    'paye_usd'     => $p->montant_paye_usd,
                    'paye_fc'      => $p->montant_paye_fc,
                    'restant_usd'  => $p->montant_restant_usd,
                    'restant_fc'   => $p->montant_restant_fc,
                    'statut'       => $p->statut,
                    'date'         => $p->date_paiement?->format('Y-m-d') ?? '',
                ],
            ],
            'paiements-salaires' => [
                'slug'    => 'paiements-salaires',
                'title'   => 'Paiement des salaires',
                'view'    => 'admin.paiement-salaires.pdf',
                'var'     => 'paiements',
                'headers' => ['ID', 'Employé', 'Mois', 'Attendu USD', 'Payé USD', 'Restant USD', 'Statut', 'Date'],
                'row'     => fn ($p) => [
                    $p->id,
                    $p->user?->name ?? '',
                    $p->moisScolaire?->nom_mois ?? '',
                    $p->montant_attendu_usd,
                    $p->montant_paye_usd,
                    $p->montant_restant_usd,
                    $p->statut_label,
                    $p->date_paiement?->format('d/m/Y') ?? '',
                ],
                'xml' => fn ($p) => [
                    'id'           => $p->id,
                    'employe'      => $p->user?->name ?? '',
                    'mois'         => $p->moisScolaire?->nom_mois ?? '',
                    'attendu_usd'  => $p->montant_attendu_usd,
                    'paye_usd'     => $p->montant_paye_usd,
                    'restant_usd'  => $p->montant_restant_usd,
                    'statut'       => $p->statut,
                    'date'         => $p->date_paiement?->format('Y-m-d') ?? '',
                ],
            ],
            'paiements-frais-supplementaires' => [
                'slug'    => 'paiements-frais-supplementaires',
                'title'   => 'Paiements frais supplémentaires',
                'view'    => 'exports.paiement-frais-supplementaires.pdf',
                'var'     => 'paiements',
                'headers' => ['Élève', 'Frais', 'Montant USD', 'Montant FC', 'Date'],
                'row'     => fn ($p) => [
                    trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')),
                    $p->fraisSupplementaire?->libelle ?? '',
                    $p->montant_paye_usd,
                    $p->montant_paye_fc,
                    $p->date_paiement?->format('d/m/Y') ?? '',
                ],
                'xml' => fn ($p) => [
                    'eleve'       => trim(($p->eleve?->nom ?? '') . ' ' . ($p->eleve?->prenom ?? '')),
                    'frais'       => $p->fraisSupplementaire?->libelle ?? '',
                    'montant_usd' => $p->montant_paye_usd,
                    'montant_fc'  => $p->montant_paye_fc,
                    'date'        => $p->date_paiement?->format('Y-m-d') ?? '',
                ],
            ],
            default => throw new \InvalidArgumentException("Ressource d'export inconnue : {$resource}"),
        };
    }
}