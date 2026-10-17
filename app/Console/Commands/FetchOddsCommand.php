<?php

namespace App\Console\Commands;

use App\Services\OddsFetcher;
use Illuminate\Console\Command;
use Throwable;

class FetchOddsCommand extends Command
{
    protected $signature = 'odds:fetch {--force : Ignorer le cache de la réponse API}';
    protected $description = 'Récupérer les cotes de football et recalculer les analyses';

    public function handle(OddsFetcher $fetcher): int
    {
        try {
            $count = $fetcher->fetch((bool) $this->option('force'));
            $this->info("{$count} ligne(s) de cotes synchronisée(s).");
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}