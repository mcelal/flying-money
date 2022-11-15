<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SheetsServices;
use App\Services\TcmbService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalcUserReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $USER_ID;

    protected User|null $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($USER_ID)
    {
        $this->USER_ID = (int) $USER_ID;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->user = User::find($this->USER_ID);

        if (!$this->user) {
            return;
        }

        // Kur bilgileri servisi
        $tcmb = new TcmbService();

        // Kullanıcının maaş bilgisi
        $salary = $this->user->salary;

        // Veriyi hazırla
        $data = [
            'document_id' => $this->user->document_id,
            'date'        => now()->format('Y-m-d'),
            'list'        => [
                'USD' => $tcmb->calcSalary($salary, 'USD'),
                'EUR' => $tcmb->calcSalary($salary, 'EUR'),
                'GBP' => $tcmb->calcSalary($salary, 'GBP'),
            ],
            'salary' => $salary,
        ];

        // Kullanıcının excel dosyasına yaz
        $sheet = new SheetsServices();
        $sheet->addDayInfo($data);
    }
}
