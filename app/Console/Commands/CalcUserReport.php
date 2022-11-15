<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CalcUserReport extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fly:calc-user';

    /**
     * @var string
     */
    protected $description = 'Kullanıcılar için günlük hesaplama işlemlerini başlatır';

    /**
     * @return int
     */
    public function handle()
    {
        // Tüm aktif kullanıcıları çek
        $users = User::query()
            ->select(['id'])
            ->whereNotNull('document_id')
            // TODO: Aktif sütunu eklenip sorguya dahil edilecek
            ->cursor();

        foreach ($users as $user) {
            \App\Jobs\CalcUserReportJob::dispatchSync($user->id);
        }

        return Command::SUCCESS;
    }
}
