<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One endorsement decision by a faculty or programme head.
 */
#[Fillable(['oleh', 'keputusan', 'catatan'])]
class PengesahanProposal extends Model
{
    public const DISETUJUI = 'disetujui';

    public const DITOLAK = 'ditolak';

    public const UPDATED_AT = null;

    protected $table = 'pengesahan_proposal';

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengesah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
