<?php

namespace App\Console\Commands;

use Carbon\Carbon;
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
        $asOf = Carbon::today()->toDateString();

        $data = DB::connection('mso-backup')
            ->select("
                SELECT
                    kre_kantor AS kantor,
                    kre_baki_debet AS baki_debet,
                    HitungKreditKolek(kre_rekening, ?) AS kolek
                FROM data_kredit_master
                WHERE kre_status = 2;
            ", [$asOf]);

        Cache::put('dashboard:kolek', $data, now()->addDay());

        $this->info('Cache refreshed!');
    }
}
