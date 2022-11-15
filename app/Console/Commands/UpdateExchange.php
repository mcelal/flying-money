<?php

namespace App\Console\Commands;

use App\Services\TcmbService;
use Illuminate\Console\Command;

class UpdateExchange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = new TcmbService();

        $call = $service->getTcmbData();

        if (! $call) {
            $this->error('Kur bilgileri güncellenemedi !');

            return Command::FAILURE;
        }

        $this->info('Kur bilgileri başarıyla güncellendi.');

        return Command::SUCCESS;
    }
}
