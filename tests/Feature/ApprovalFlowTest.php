<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\BranchOffice;
use App\Models\CashFlow;
use App\Models\CashFlowKind;
use App\Models\Jabatan;
use App\Models\JenisFunding;
use App\Models\MitraBayar;
use App\Models\PerusahaanAsuransi;
use App\Models\ProdukFunding;
use App\Models\ProdukLending;
use App\Models\ProyeksiFunding;
use App\Models\ProyeksiLending;
use App\Models\SumberPembayaranLending;
use App\Models\StatusDapem;
use App\Models\StatusKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private BranchOffice $branch;
    private User $user;
    private Agent $agent;
    private ProdukFunding $produkFunding;
    private CashFlowKind $cashFlowKind;
    private ProdukLending $produkLending;
    private SumberPembayaranLending $sumberPembayaran;
    private StatusKerja $statusKerja;
    private StatusDapem $statusDapem;
    private MitraBayar $mitraBayar;
    private PerusahaanAsuransi $perusahaanAsuransi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = BranchOffice::create([
            'branch_code' => '01',
            'branch_code_fincloud' => '001',
            'branch_name' => 'Kantor Pusat',
            'branch_saldo_aba_blokir' => 0,
        ]);

        $jabatan = Jabatan::create([
            'jabatan_nama' => 'Agent Senior',
            'jabatan_target' => 0,
        ]);

        $this->agent = Agent::create([
            'agent_nama' => 'Agent One',
            'agent_mso_code' => 'AG001',
            'agent_jabatan' => $jabatan->id,
            'agent_branch_office' => $this->branch->id,
        ]);

        $this->user = User::factory()->create([
            'username' => 'user-' . Str::random(5),
            'branch_office_id' => $this->branch->id,
        ]);

        $jenisFunding = JenisFunding::create([
            'jenis_funding_nama' => 'Deposito',
        ]);

        $this->produkFunding = ProdukFunding::create([
            'produk_jenis' => $jenisFunding->id,
            'produk_nama' => 'Deposito Platinum',
        ]);

        $this->cashFlowKind = CashFlowKind::create([
            'kind_name' => 'Setoran',
            'kind_type' => 'Cash In',
            'kind_description' => 'Cash in transaction',
            'kind_pusat_only' => false,
        ]);

        $this->produkLending = ProdukLending::create([
            'produk_nama' => 'Kredit Pensiun',
        ]);

        $this->sumberPembayaran = SumberPembayaranLending::create([
            'sumber_nama' => 'Payroll',
        ]);

        $this->statusKerja = StatusKerja::create([
            'kerja_nama' => 'PENSIUN ASN',
        ]);

        $this->statusDapem = StatusDapem::create([
            'dapem_nama' => 'Aktif',
        ]);

        $this->mitraBayar = MitraBayar::create([
            'mitra_nama' => 'Mitra Bayar A',
        ]);

        $this->perusahaanAsuransi = PerusahaanAsuransi::create([
            'asuransi_npwp' => '01.234.567.8-999.000',
            'asuransi_nama' => 'Asuransi Prima',
            'asuransi_alamat' => 'Jl. Contoh No. 1',
            'asuransi_telepon' => '0211234567',
        ]);
    }

    public function test_creating_funding_projection_generates_pending_approval(): void
    {
        $funding = $this->createFunding();

        $this->assertNotNull($funding->approval);
        $this->assertSame('Pending', $funding->approval->approval_status);
    }

    public function test_updating_funding_projection_resets_approval_to_pending(): void
    {
        $funding = $this->createFunding();
        $funding->approval->update(['approval_status' => 'Approved']);

        $funding->update([
            'funding_nominal' => 2_000_000,
            'funding_nominal_bersih' => 1_900_000,
        ]);

        $this->assertSame('Pending', $funding->fresh()->approval->approval_status);
    }

    public function test_creating_lending_projection_generates_pending_approval(): void
    {
        $lending = $this->createLending();

        $this->assertNotNull($lending->approval);
        $this->assertSame('Pending', $lending->approval->approval_status);
    }

    public function test_updating_lending_projection_resets_approval_to_pending(): void
    {
        $lending = $this->createLending();
        $lending->approval->update(['approval_status' => 'Approved']);

        $lending->update([
            'lending_plafond' => 21_000_000,
        ]);

        $this->assertSame('Pending', $lending->fresh()->approval->approval_status);
    }

    public function test_creating_cash_flow_generates_pending_approval(): void
    {
        $cashFlow = $this->createCashFlow();

        $this->assertNotNull($cashFlow->approval);
        $this->assertSame('Pending', $cashFlow->approval->approval_status);
    }

    public function test_updating_cash_flow_resets_approval_to_pending(): void
    {
        $cashFlow = $this->createCashFlow();
        $cashFlow->approval->update(['approval_status' => 'Approved']);

        $cashFlow->update([
            'cash_keterangan' => 'Updated cash flow',
        ]);

        $this->assertSame('Pending', $cashFlow->fresh()->approval->approval_status);
    }

    private function createFunding(): ProyeksiFunding
    {
        return ProyeksiFunding::create([
            'funding_tanggal' => Carbon::today()->toDateString(),
            'funding_kantor' => $this->branch->id,
            'funding_agent' => $this->agent->id,
            'funding_petugas' => $this->user->id,
            'funding_produk' => $this->produkFunding->id,
            'funding_deposito_jenis' => 'Baru',
            'funding_nasabah_nama' => 'Nasabah A',
            'funding_nominal' => 1_500_000,
            'funding_nominal_bersih' => 1_500_000,
        ]);
    }

    private function createLending(): ProyeksiLending
    {
        $today = Carbon::today();

        return ProyeksiLending::create([
            'lending_tanggal' => $today->toDateString(),
            'lending_kantor' => $this->branch->id,
            'lending_agent' => $this->agent->id,
            'lending_petugas' => $this->user->id,
            'lending_nama_debitur' => 'Debitur Satu',
            'lending_notas' => 'NOTAS-001',
            'lending_jenis_pengajuan' => 'BARU',
            'lending_tipe_pengajuan' => 'Takeover',
            'lending_produk' => $this->produkLending->id,
            'lending_sumber_pembayaran' => $this->sumberPembayaran->id,
            'lending_status_dapem' => $this->statusDapem->id,
            'lending_status_kerja' => $this->statusKerja->id,
            'lending_gaji_pokok' => 7_000_000,
            'lending_gaji_bersih' => 5_000_000,
            'lending_plafond' => 20_000_000,
            'lending_pelunasan_pokok' => 0,
            'lending_pelunasan_bunga' => 0,
            'lending_booking_bersih' => 18_000_000,
            'lending_tanggal_realisasi' => $today->copy()->addWeek()->toDateString(),
            'lending_jkw' => 120,
            'lending_tanggal_jatuh_tempo' => $today->copy()->addYears(1)->toDateString(),
            'lending_tanggal_rencana_bayar' => $today->copy()->addMonth()->toDateString(),
            'lending_tanggal_rencana_takeover' => $today->copy()->addMonths(2)->toDateString(),
            'lending_mitra_bayar_takeover' => $this->mitraBayar->id,
            'lending_nama_koperasi_takeover' => 'Koperasi B',
            'lending_pot_provisi' => 50_000,
            'lending_pot_provisi_percent' => 2.50,
            'lending_pot_admin' => 25_000,
            'lending_pot_admin_percent' => 1.00,
            'lending_pot_premi' => 40_000,
            'lending_pot_premi_percent' => 0,
            'lending_pot_premi_extra' => 10_000,
            'lending_pot_premi_extra_percent' => 0,
            'lending_bunga_muka' => 0,
            'lending_bundling_bpjs' => 0,
            'lending_saldo_tab_mengendap' => 500_000,
            'lending_angsuran_muka' => 100_000,
            'lending_nominal_pelunasan_takeover' => 1_500_000,
            'lending_kre_rekening' => '1234567890',
            'lending_tanggal_lahir_debitur' => $today->copy()->subYears(50)->toDateString(),
            'lending_no_hp_debitur' => '08123456789',
            'lending_notas_debitur' => 'NOTAS-D',
            'lending_sistem_bunga' => 'Flate',
            'lending_bunga_percent' => 12.50,
            'lending_angsuran_fasilitas_aktif' => [['jenis' => 'Kredit Rumah', 'angsuran' => 1_000_000]],
            'lending_dsr' => 35.5,
            'lending_asuransi_perusahaan' => $this->perusahaanAsuransi->id,
        ]);
    }

    private function createCashFlow(): CashFlow
    {
        return CashFlow::create([
            'cash_kind' => $this->cashFlowKind->id,
            'cash_kantor' => $this->branch->id,
            'cash_user' => $this->user->id,
            'cash_tanggal' => Carbon::today()->toDateString(),
            'cash_keterangan' => 'Setoran harian',
            'cash_jumlah' => 500_000,
        ]);
    }
}
