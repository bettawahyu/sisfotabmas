<?php

namespace App\Models;

use App\Enums\StatusProposal;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['status_dari', 'status_ke', 'oleh', 'catatan'])]
class ProposalStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'proposal_status_log';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_dari' => StatusProposal::class,
            'status_ke' => StatusProposal::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
