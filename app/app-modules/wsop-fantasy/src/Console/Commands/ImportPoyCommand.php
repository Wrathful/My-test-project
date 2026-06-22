<?php

namespace Modules\WsopFantasy\Console\Commands;

use Modules\WsopFantasy\Services\PoyImportService;
use Illuminate\Console\Command;

/**
 * Команда импорта POY-очков игроков WSOP Fantasy.
 */
class ImportPoyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wsop-fantasy:import-poy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import WSOP Fantasy POY scores from the configured external source';

    /**
     * Execute the console command.
     */
    public function handle(PoyImportService $importService): int
    {
        $this->info('Starting WSOP Fantasy POY scores import...');

        $count = $importService->importScores();

        $this->info("Imported scores for {$count} players.");

        return self::SUCCESS;
    }
}
