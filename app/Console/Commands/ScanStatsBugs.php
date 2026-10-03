<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScanStatsBugs extends Command
{
    protected $signature = 'stats:scan
                            {--fix : Corriger automatiquement (ajoute withoutEagerLoads + reorder)}
                            {--path=app/Http/Controllers : Dossier à scanner}';

    protected $description = 'Scanne les controllers pour détecter les bugs selectRaw + with() (MissingAttributeException)';

    /** Nombre total de problèmes détectés */
    private int $totalIssues = 0;

    /** Nombre total de corrections appliquées */
    private int $totalFixed = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  SCANNER — Bugs stats (selectRaw + with)');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $path = base_path($this->option('path'));

        if (!File::isDirectory($path)) {
            $this->error("Dossier introuvable : {$path}");
            return self::FAILURE;
        }

        $files = File::allFiles($path);
        $this->info("📁 Scan du dossier : {$path}");
        $this->info("📄 " . count($files) . " fichier(s) trouvé(s)");
        $this->newLine();

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $this->scanFile($file->getRealPath());
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════');
        if ($this->totalIssues === 0) {
            $this->info('  ✅ Aucun problème détecté !');
        } else {
            $this->warn("  ⚠️  {$this->totalIssues} problème(s) détecté(s)");
            if ($this->option('fix')) {
                $this->info("  🔧 {$this->totalFixed} correction(s) appliquée(s)");
            } else {
                $this->line('  💡 Lance avec --fix pour corriger automatiquement');
            }
        }
        $this->info('═══════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    private function scanFile(string $filePath): void
    {
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        $fileHasIssue = false;
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath);

        // Pattern 1 : ->selectRaw( ou ->select( sans withoutEagerLoads
        // sur les 20 lignes suivantes (pour détecter les chaînes longues)
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            // Détecte une ligne qui contient selectRaw( ou ->select([
            $hasSelect = preg_match('/->select(?:Raw)?\s*\(/', $line);

            if (!$hasSelect) {
                continue;
            }

            // Regarde les 15 lignes AVANT pour voir si withoutEagerLoads() est présent
            $context = implode("\n", array_slice($lines, max(0, $index - 15), 16));

            // Vérifie si on est dans une méthode "getStats" ou similaire (contexte de stats)
            $beforeContext = implode("\n", array_slice($lines, max(0, $index - 30), 31));
            $isStatsContext = preg_match('/function\s+(getStats|stats|getStatsGlobales|getStatsParMois|getStatsParPeriode|computeStats|aggregate)\w*/i', $beforeContext);

            // Vérifie si withoutEagerLoads est présent
            $hasWithoutEagerLoads = str_contains($context, 'withoutEagerLoads()');

            // Vérifie si with() est utilisé dans les 30 lignes précédentes (avec des relations réelles)
            $hasWith = preg_match('/->with\s*\(/', $beforeContext);

            if (!$hasWithoutEagerLoads && ($isStatsContext || $hasWith)) {
                if (!$fileHasIssue) {
                    $this->line("📄 <fg=cyan>{$relativePath}</>");
                    $fileHasIssue = true;
                }

                $lineTrimmed = trim($line);
                $this->line("   ⚠️  Ligne {$lineNumber} : {$lineTrimmed}");
                $this->totalIssues++;

                if ($this->option('fix')) {
                    $this->fixLine($filePath, $index, $lines, $content);
                    $this->line("      ✅ Corrigé");
                }
            }
        }

        if ($fileHasIssue) {
            $this->newLine();
        }
    }

    /**
     * Ajoute ->withoutEagerLoads() et ->reorder() avant la ligne contenant selectRaw.
     */
    private function fixLine(string $filePath, int $lineIndex, array &$lines, string &$content): void
    {
        // Cherche la ligne qui contient ->selectRaw( ou ->select([
        $targetLine = $lines[$lineIndex];

        // Détermine l'indentation
        preg_match('/^(\s*)/', $targetLine, $matches);
        $indent = $matches[1] ?? '        ';

        // Détecte si la ligne précédente finit par -> (chaînage)
        $prevLine = $lines[$lineIndex - 1] ?? '';
        $prevHasArrow = preg_match('/->\s*$/', trim($prevLine));

        if ($prevHasArrow) {
            // On insère AVANT la ligne actuelle
            $insertLine = $indent . '->withoutEagerLoads()' . "\n" .
                          $indent . '->reorder()' . "\n";
            array_splice($lines, $lineIndex, 0, [rtrim($insertLine)]);
        } else {
            // On transforme la ligne actuelle : ->selectRaw( → ->withoutEagerLoads()->reorder()->selectRaw(
            $newLine = preg_replace(
                '/(->select(?:Raw)?\s*\()/',
                '->withoutEagerLoads()->reorder()$1',
                $targetLine,
                1
            );
            $lines[$lineIndex] = $newLine;
        }

        // Sauvegarde le fichier
        File::put($filePath, implode("\n", $lines));
        $this->totalFixed++;

        // Recharge le contenu (pour ne pas fausser les index suivants)
        $content = implode("\n", $lines);
    }
}