<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuranApiService;

class SyncQuranData extends Command
{
    protected $signature = 'quran:sync';
    protected $description = 'Synchronize Quran data from AlQuran Cloud API';

    public function handle(QuranApiService $quranService)
    {
        $this->info('Starting Quran data synchronization...');

        try {
            $quranService->syncQuranData();
            $this->info('Quran data synchronized successfully!');
        } catch (\Exception $e) {
            $this->error('Error synchronizing Quran data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
