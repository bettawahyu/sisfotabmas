<?php

namespace App\Models;

use App\Enums\StatusProposal;
use App\Enums\TipeAnggota;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
#[Fillable(['periode_id', 'ketua_id', 'bidang_fokus_id', 'judul', 'ringkasan', 'kata_kunci', 'cabang_ilmu_id', 'tkt_awal', 'tkt_target', 'lama_tahun', 'tahun_ke', 'proposal_induk_id'])]
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'proposal';

    /** Statuses from the first tranche onwards. */
    public const STATUS_PELAKSANAAN = [StatusProposal::Berjalan, StatusProposal::Dimonev, StatusProposal::Selesai];

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
            'nilai_akhir' => 'decimal:2',
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
     * @return BelongsTo<RumpunIlmu, $this>
     */
    public function cabangIlmu(): BelongsTo
    {
        return $this->belongsTo(RumpunIlmu::class, 'cabang_ilmu_id');
    }

    /**
     * @return HasMany<PengesahanProposal, $this>
     */
    public function pengesahan(): HasMany
    {
        return $this->hasMany(PengesahanProposal::class);
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

    /**
     * @return HasMany<PencairanDana, $this>
     */
    public function pencairan(): HasMany
    {
        return $this->hasMany(PencairanDana::class)->orderBy('termin_ke');
    }

    /**
     * @return HasMany<Penilaian, $this>
     */
    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class);
    }

    /**
     * @return HasMany<Laporan, $this>
     */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }

    /**
     * @return HasMany<Logbook, $this>
     */
    public function logbook(): HasMany
    {
        return $this->hasMany(Logbook::class)->orderBy('tanggal');
    }

    /**
     * Whether the first tranche is paid, so the work belongs under Pelaksanaan
     * rather than with the proposal.
     */
    public function sudahDilaksanakan(): bool
    {
        return in_array($this->status, self::STATUS_PELAKSANAAN, true);
    }

    /**
     * @param  Builder<Proposal>  $query
     */
    public function scopeDalamPelaksanaan(Builder $query): void
    {
        $query->whereIn('status', self::STATUS_PELAKSANAAN);
    }

    /**
     * Proposals the lecturer leads or has joined as a confirmed member.
     *
     * @param  Builder<Proposal>  $query
     */
    public function scopeTimDosen(Builder $query, Dosen $dosen): void
    {
        $query->where(fn (Builder $q) => $q->where('ketua_id', $dosen->id)
            ->orWhereHas('anggota', fn (Builder $a) => $a->where('tipe', TipeAnggota::Dosen)->where('dosen_id', $dosen->id)
                ->whereNotNull('dikonfirmasi_at')->whereNull('ditolak_at')));
    }

    /**
     * Proposals the given faculty or programme head is responsible for; see
     * User::bolehMengesahkan().
     *
     * @param  Builder<Proposal>  $query
     */
    public function scopeDalamUnit(Builder $query, User $pimpinan): void
    {
        $peran = $pimpinan->roles->firstWhere('kode', Role::PIMPINAN_UNIT);
        if ($peran === null) {
            $query->whereRaw('1 = 0');
        } elseif ($peran->pivot->prodi_id !== null) {
            $query->whereHas('ketua', fn (Builder $q) => $q->where('prodi_id', $peran->pivot->prodi_id));
        } elseif ($peran->pivot->fakultas_id !== null) {
            $query->whereHas('ketua.prodi', fn (Builder $q) => $q->where('fakultas_id', $peran->pivot->fakultas_id));
        }
    }
}
