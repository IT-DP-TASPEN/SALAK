<?php

namespace Tests\Unit;

use App\Models\ProdukLending;
use App\Support\ProyeksiLendingFormData;
use Mockery;
use PHPUnit\Framework\TestCase;

class ProyeksiLendingFormDataTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_with_full_form_payload(): void
    {
        $productId = 123;

        Mockery::mock('alias:' . ProdukLending::class)
            ->shouldReceive('find')
            ->once()
            ->with($productId)
            ->andReturn((object) ['produk_nama' => 'Reguler']);

        $input = [
            'lending_nama_debitur' => 'SUGIATI',
            'lending_tanggal_lahir_debitur' => '1983-05-12',
            'lending_no_hp_debitur' => '082229971612',
            'lending_kre_rekening' => null,
            'lending_sumber_pembayaran' => 1,
            'lending_status_dapem' => 2,
            'lending_gaji_pokok' => '1311100',
            'lending_gaji_bersih' => '1553600',
            'lending_status_kerja' => 3,
            'lending_notas' => 'NOTAS123',
            'lending_agent' => 5,
            'lending_jenis_pengajuan' => 'BARU',
            'lending_tipe_pengajuan' => 'Dapem sudah di Bank DP TASPEN',
            'lending_mitra_bayar_takeover' => null,
            'lending_nominal_pelunasan_takeover' => null,
            'lending_tanggal_rencana_takeover' => null,
            'lending_nama_koperasi_takeover' => null,
            'lending_produk' => $productId,
            'lending_plafond' => '20000000',
            'lending_pot_provisi_percent' => '0',
            'lending_pot_provisi' => '0',
            'lending_pot_admin_percent' => '3',
            'lending_pot_admin' => '600000',
            'lending_pot_premi' => '192600',
            'lending_pot_premi_percent' => '0.963',
            'lending_pot_premi_extra' => '0',
            'lending_pot_premi_extra_percent' => '0',
            'lending_bundling_bpjs' => '403200',
            'lending_bunga_muka' => '0',
            'lending_bunga_percent' => '15',
            'lending_jkw' => '24',
            'lending_sistem_bunga' => 'Anuitas',
            'lending_tanggal_realisasi' => '2025-10-01',
            'lending_tanggal_rencana_bayar' => '2025-10-02',
            'lending_saldo_tab_mengendap_bulan' => '1',
            'lending_angsuran_muka_bulan' => '2',
            'lending_with_bpjs' => 'YA',
            'lending_angsuran_fasilitas_aktif' => [
                // ['lending_nominal_angsuran' => '2450903'],
                // ['lending_nominal_angsuran' => '1260782'],
            ],
        ];

        $result = ProyeksiLendingFormData::calculate($input);

        $this->assertSame('2027-10-01', $result['lending_tanggal_jatuh_tempo']);
        $this->assertCount(1, $result['lending_angsuran_fasilitas_aktif']);
        $this->assertEqualsWithDelta(969_733, $result['lending_saldo_tab_mengendap'], 0.0001);
        $this->assertEqualsWithDelta(1_939_466, $result['lending_angsuran_muka'], 0.0001);
        // $this->assertEqualsWithDelta(28_859_346, $result['lending_booking_bersih'], 0.0001);
        $this->assertEqualsWithDelta(62.42, $result['lending_dsr'], 0.0001);
        $this->assertArrayNotHasKey('lending_angsuran_muka_bulan', $result);
        $this->assertArrayNotHasKey('lending_saldo_tab_mengendap_bulan', $result);
        $this->assertArrayNotHasKey('lending_with_bpjs', $result);
    }

    public function test_calculate_handles_diskonto_product(): void
    {
        $productId = 456;

        Mockery::mock('alias:' . ProdukLending::class)
            ->shouldReceive('find')
            ->once()
            ->with($productId)
            ->andReturn((object) ['produk_nama' => 'DISKONTO']);

        $input = [
            'lending_produk' => $productId,
            'lending_bunga_percent' => 9,
            'lending_jkw' => 6,
            'lending_plafond' => 5_000_000,
        ];

        $result = ProyeksiLendingFormData::calculate($input);

        $this->assertEqualsWithDelta(37_500, $result['lending_angsuran_fasilitas_aktif'][0]['lending_nominal_angsuran'], 0.0001);
        $this->assertEqualsWithDelta(225_000, $result['lending_bunga_muka'], 0.0001);
        $this->assertEqualsWithDelta(4_775_000, $result['lending_booking_bersih'], 0.0001);
        $this->assertSame(0, $result['lending_dsr']);
    }

    public function test_calculate_handles_flate_installment(): void
    {
        $productId = 789;

        Mockery::mock('alias:' . ProdukLending::class)
            ->shouldReceive('find')
            ->once()
            ->with($productId)
            ->andReturn((object) ['produk_nama' => 'Reguler']);

        $input = [
            'lending_produk' => $productId,
            'lending_sistem_bunga' => 'Flate',
            'lending_bunga_percent' => 6,
            'lending_jkw' => 10,
            'lending_plafond' => 10_000_000,
        ];

        $result = ProyeksiLendingFormData::calculate($input);

        $this->assertEqualsWithDelta(1_050_000, $result['lending_angsuran_fasilitas_aktif'][0]['lending_nominal_angsuran'], 0.0001);
        $this->assertEqualsWithDelta(10_000_000, $result['lending_booking_bersih'], 0.0001);
        $this->assertSame(0, $result['lending_dsr']);
    }
}
