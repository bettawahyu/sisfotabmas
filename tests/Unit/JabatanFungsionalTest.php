<?php

use App\Enums\JabatanFungsional;

test('ranks typed as free text are read into the national ranks', function (?string $teks, ?JabatanFungsional $hasil) {
    expect(JabatanFungsional::dariTeks($teks))->toBe($hasil);
})->with([
    ['Lektor Kepala', JabatanFungsional::LektorKepala],
    ['  lektor  kepala ', JabatanFungsional::LektorKepala],
    ['LK', JabatanFungsional::LektorKepala],
    ['lektor', JabatanFungsional::Lektor],
    ['Asisten Ahli', JabatanFungsional::AsistenAhli],
    ['Prof.', JabatanFungsional::GuruBesar],
    ['Guru Besar', JabatanFungsional::GuruBesar],
    ['Tenaga Pengajar', JabatanFungsional::TenagaPengajar],
    ['lektor_kepala', JabatanFungsional::LektorKepala],
    ['Dekan', null],
    ['', null],
    [null, null],
]);

test('ranks compare by their place on the ladder', function () {
    expect(JabatanFungsional::LektorKepala->setidaknya(JabatanFungsional::Lektor))->toBeTrue()
        ->and(JabatanFungsional::Lektor->setidaknya(JabatanFungsional::Lektor))->toBeTrue()
        ->and(JabatanFungsional::AsistenAhli->setidaknya(JabatanFungsional::Lektor))->toBeFalse()
        ->and(JabatanFungsional::TenagaPengajar->jenjang())->toBe(1)
        ->and(JabatanFungsional::GuruBesar->jenjang())->toBe(5);
});
