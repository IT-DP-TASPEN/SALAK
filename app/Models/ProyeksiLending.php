<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProyeksiLending extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'lending_tanggal' => 'datetime',
        'lending_tanggal_realisasi' => 'datetime',
        'lending_tanggal_jatuh_tempo' => 'datetime',
        'lending_tanggal_rencana_takeover' => 'datetime',
        'lending_tanggal_lahir_debitur' => 'datetime',
        'lending_sumber_pembayaran' => 'string',
        'lending_status_kerja' => 'string',
        'lending_produk' => 'string',
        'lending_angsuran_fasilitas_aktif' => 'array',
        'lending_dsr' => 'double',
        'lending_gaji_pokok' => 'decimal:2',
        'lending_gaji_bersih' => 'decimal:2',
    ];

    // booted method to set default values for certain fields
    protected static function boot()
    {
        parent::boot();

        static::created(function ($lending) {
            $approval = new ProyeksiLendingApproval();
            $approval->approval_lending = $lending->id;
            $approval->approval_status = 'Pending';
            $approval->save();
        });
    }

    public function statusDapem(): BelongsTo
    {
        return $this->belongsTo(StatusDapem::class, 'lending_status_dapem', 'id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(ProdukLending::class, 'lending_produk', 'id');
    }

    public function sumberPembayaran(): BelongsTo
    {
        return $this->belongsTo(SumberPembayaranLending::class, 'lending_sumber_pembayaran', 'id');
    }

    public function statusKerja(): BelongsTo
    {
        return $this->belongsTo(StatusKerja::class, 'lending_status_kerja', 'id');
    }

    public function mitraBayarTakeover(): BelongsTo
    {
        return $this->belongsTo(MitraBayar::class, 'lending_mitra_bayar_takeover', 'id');
    }

    public function branchOffice(): BelongsTo
    {
        return $this->belongsTo(BranchOffice::class, 'lending_kantor', 'id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'lending_agent', 'id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lending_petugas', 'id');
    }

    public function approval(): HasOne
    {
        return $this->hasOne(ProyeksiLendingApproval::class, 'approval_lending', 'id');
    }

    public function progress(): HasOne
    {
        return $this->hasOne(ProyeksiLendingProgress::class, 'progress_lending', 'id');
    }

    public function perusahaanAsuransi(): BelongsTo
    {
        return $this->belongsTo(PerusahaanAsuransi::class, 'lending_asuransi_perusahaan', 'id');
    }
}
