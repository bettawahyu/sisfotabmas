<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mitra_id', 'nama_mitra', 'jenis_mitra', 'alamat', 'kontak', 'dana_pendamping'])]
class ProposalMitra extends Model
{
    protected $table = 'proposal_mitra';

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * The partner in LPPM's register; the name and contact here are kept as
     * they stood on the proposal.
     *
     * @return BelongsTo<Mitra, $this>
     */
    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }
}
