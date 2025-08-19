<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshKreditKolek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:refresh-kredit-kolek';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache refresh for Kredit Kolek';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = DB::connection('mso-backup')
            ->select("
                SELECT
                    t.kre_kantor AS kantor,
                    t.kre_baki_debet AS baki_debet,
                    t.kolek AS kolek
                FROM (
                    SELECT 
                        kre_kantor,
                        kre_baki_debet,
                        HitungKreditKolek(kre_rekening, CURDATE()) AS kolek
                    FROM data_kredit_master
                    WHERE kre_status = 2
                ) t;
            ");

        Cache::put('dashboard:kolek', $data, now()->addDay());

        $this->info('Cache refreshed!');
    }
}
