<?php

namespace App\Console\Commands;

use App\Http\Controllers\HomeController;
use Illuminate\Console\Command;

class ClearHomeCache extends Command
{
    protected $signature = 'cache:clear-home';
    protected $description = 'Vide le cache de la page d\'accueil';

    public function handle(): int
    {
        HomeController::clearHomeCache();

        $this->info('✅ Cache de la page d\'accueil vidé avec succès.');

        return self::SUCCESS;
    }
}
