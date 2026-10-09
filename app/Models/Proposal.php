<?php

namespace App\Models;

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A research or community service proposal. Status changes go through
 * App\Services\AlurProposal so every move is checked and logged.
 */
#[Fillable(['periode_id', 'ketua_id', 'bidang_fokus_id', 'judul', 'ringkasan', 'kata_kunci', 'rumpun_ilmu', 'tkt_awal', 'tkt_target', 'lama_tahun', 'tahun_ke', 'proposal_induk_id'])]
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'proposal';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusProposal::class,
            'dikirim_at' => 'datetime',
            'diajukan_at' => 'datetime',
            'batas_perbaikan_at' => 'datetime',
            'disahkan_at' => 'datetime',
            'tgl_sk' => 'date',
            'tgl_kontrak' => 'date',
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
        ];
    }

    /**
     * @return BelongsTo<PeriodeHibah, $this>
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeHibah::class, 'periode_id');
    }

    /**
     * @return BelongsTo<Dosen, $this>
     */
    public function ketua(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'ketua_id');
    }

    /**
     * @return BelongsTo<BidangFokus, $this>
     */
    public function bidangFokus(): BelongsTo
    {
        return $this->belongsTo(BidangFokus::class);
    }

    /**
     * @return HasMany<ProposalAnggota, $this>
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(ProposalAnggota::class);
    }

    /**
     * @return HasMany<ProposalMitra, $this>
     */
    public function mitra(): HasMany
    {
        return $this->hasMany(ProposalMitra::class);
    }

    /**
     * @return HasMany<RabItem, $this>
     */
    public function rab(): HasMany
    {
        return $this->hasMany(RabItem::class);
    }

    /**
     * @return HasMany<Luaran, $this>
     */
    public function luaran(): HasMany
    {
        return $this->hasMany(Luaran::class);
    }

    /**
     * @return HasMany<ProposalStatusLog, $this>
     */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(ProposalStatusLog::class)->latest('id');
    }

    /**
     * @return MorphMany<Berkas, $this>
     */
    public function berkas(): MorphMany
    {
        return $this->morphMany(Berkas::class, 'pemilik');
    }

    /**
     * The latest uploaded proposal substance (PDF from the LPPM template).
     *
     * @return MorphOne<Berkas, $this>
     */
    public function berkasSubstansi(): MorphOne
    {
        return $this->morphOne(Berkas::class, 'pemilik')
            ->where('kategori', Berkas::SUBSTANSI)
            ->latestOfMany();
    }

    public function isKetua(?Dosen $dosen): bool
    {
        return $dosen !== null && $this->ketua_id === $dosen->id;
    }

    public function isAnggotaDosen(?Dosen $dosen): bool
    {
        return $dosen !== null && $this->anggota
            ->where('tipe', TipeAnggota::Dosen)
            ->where('dosen_id', $dosen->id)
            ->whereNull('ditolak_at')
            ->isNotEmpty();
    }

    /**
     * Whether the lead can edit content: a draft not yet sent, or a returned proposal.
     */
    public function bisaDiubah(): bool
    {
        return $this->status === StatusProposal::Draft && $this->dikirim_at === null
            || $this->status === StatusProposal::Dikembalikan;
    }

    public function menungguPengesahan(): bool
    {
        return $this->status === StatusProposal::Draft && $this->dikirim_at !== null;
    }

    public function totalRab(): int
    {
        return (int) $this->rab->sum('total');
    }
}
