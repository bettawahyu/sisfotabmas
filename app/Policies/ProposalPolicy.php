<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;

class ProposalPolicy
{
    /**
     * The team, LPPM and faculty heads can open a proposal. Reviewers get their
     * own blind view in the review module, never this page.
     */
    public function view(User $user, Proposal $proposal): bool
    {
        return $proposal->isKetua($user->dosen)
            || $proposal->isAnggotaDosen($user->dosen)
            || $user->hasRole(Role::ADMIN_LPPM, Role::PIMPINAN_UNIT);
    }

    /**
     * Only the lead edits, and only while the proposal is a draft or returned.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        return $proposal->isKetua($user->dosen) && $proposal->bisaDiubah();
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $this->update($user, $proposal) && $proposal->riwayatStatus()->doesntExist();
    }
}
