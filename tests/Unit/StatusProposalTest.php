<?php

use App\Enums\StatusProposal;

test('a proposal only moves forward', function () {
    expect(StatusProposal::Draft->bolehMenjadiStatus(StatusProposal::Diajukan))->toBeTrue()
        ->and(StatusProposal::Ditinjau->bolehMenjadiStatus(StatusProposal::Diajukan))->toBeFalse()
        ->and(StatusProposal::Selesai->bolehMenjadi())->toBe([])
        ->and(StatusProposal::TidakDidanai->bolehMenjadi())->toBe([]);
});

test('a returned proposal can be sent again or closed', function () {
    expect(StatusProposal::Dikembalikan->bolehMenjadi())
        ->toBe([StatusProposal::Diajukan, StatusProposal::TidakDidanai]);
});

test('the happy path visits all eight main statuses in order', function () {
    $jalur = [
        StatusProposal::Draft, StatusProposal::Diajukan, StatusProposal::Ditinjau, StatusProposal::Disetujui,
        StatusProposal::Didanai, StatusProposal::Berjalan, StatusProposal::Dimonev, StatusProposal::Selesai,
    ];

    foreach (array_slice($jalur, 0, -1) as $i => $status) {
        expect($status->bolehMenjadiStatus($jalur[$i + 1]))->toBeTrue();
    }
});
