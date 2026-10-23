<?php

use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;
use App\Services\Sinta;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

function dosenSinta(array $isi = []): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::DOSEN);
    Dosen::factory()->create($isi + ['user_id' => $user->id, 'sinta_id' => '6012345']);

    return $user;
}

function halamanSinta(): string
{
    return file_get_contents(base_path('tests/Fixtures/sinta-profil.html'));
}

test('the SINTA profile page gives the score, h-index and citations of Scopus and Scholar', function () {
    expect(app(Sinta::class)->bacaProfil(halamanSinta()))->toBe([
        'skor_sinta' => 1234.5,
        'h_index_scopus' => 5,
        'h_index_scholar' => 17,
        'sitasi_scopus' => 87,
        'sitasi_scholar' => 1502,
    ]);
});

test('a page without the profile figures is refused rather than read as zeros', function () {
    app(Sinta::class)->bacaProfil('<html><body><p>Maintenance</p></body></html>');
})->throws(RuntimeException::class);

test('a lecturer syncs their SINTA figures and waits two weeks for the next sync', function () {
    Http::fake(['*/authors/profile/6012345' => Http::response(halamanSinta())]);
    $user = dosenSinta();

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertOk()->assertSee('Perbarui dari SINTA');

    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))
        ->assertRedirect(route('profil-dosen.edit'))->assertSessionHas('status');

    $dosen = $user->dosen->fresh();
    expect((float) $dosen->skor_sinta)->toBe(1234.5)
        ->and($dosen->h_index_scopus)->toBe(5)
        ->and($dosen->h_index_scholar)->toBe(17)
        ->and($dosen->sitasi_scopus)->toBe(87)
        ->and($dosen->sitasi_scholar)->toBe(1502)
        ->and($dosen->sinkron_terakhir_at)->not->toBeNull();

    // The button gives way to the date it comes back, and a direct request is refused.
    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertDontSee('Perbarui dari SINTA')
        ->assertSee('Sinkron berikutnya tersedia mulai');
    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))->assertSessionHas('peringatan');
    Http::assertSentCount(1);

    $this->travel(Dosen::JEDA_SINKRON_SINTA)->days();
    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertSee('Perbarui dari SINTA');
});

test('a failed sync keeps the figures already stored', function () {
    Http::fake(['*' => Http::response('', 503)]);
    $user = dosenSinta(['skor_sinta' => 100, 'h_index_scholar' => 4]);

    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))->assertSessionHas('gagal');

    $dosen = $user->dosen->fresh();
    expect((float) $dosen->skor_sinta)->toBe(100.0)
        ->and($dosen->h_index_scholar)->toBe(4)
        ->and($dosen->sinkron_terakhir_at)->toBeNull();
});

test('a lecturer without a SINTA ID is asked to fill it in and SINTA is not called', function () {
    Http::fake();
    $user = dosenSinta(['sinta_id' => null]);

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertDontSee('Perbarui dari SINTA');
    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))->assertSessionHas('gagal');
    Http::assertNothingSent();
});

test('only lecturers sync; LPPM only sees the figures', function () {
    $lppm = User::factory()->create();
    $lppm->assignRole(Role::ADMIN_LPPM);

    $this->actingAs($lppm)->post(route('profil-dosen.sinkron-sinta'))->assertForbidden();
});

test('a pasted SINTA profile link is stored as its ID, and a new ID clears the old figures', function () {
    $user = dosenSinta(['skor_sinta' => 100, 'sinkron_terakhir_at' => now()]);

    $this->actingAs($user)->put(route('profil-dosen.update'), [
        'nama' => 'Ani', 'sinta_id' => 'https://sinta.kemdiktisaintek.go.id/authors/profile/6099999',
    ])->assertSessionHasNoErrors();

    $dosen = $user->dosen->fresh();
    expect($dosen->sinta_id)->toBe('6099999')
        ->and($dosen->skor_sinta)->toBeNull()
        ->and($dosen->sinkron_terakhir_at)->toBeNull();

    $this->actingAs($user)->put(route('profil-dosen.update'), ['nama' => 'Ani', 'sinta_id' => 'abc'])
        ->assertSessionHasErrors('sinta_id');
});

test('the request names the app and goes to the configured SINTA address', function () {
    config(['services.sinta.url' => 'https://sinta.contoh.test/']);
    Http::fake(['*' => Http::response(halamanSinta())]);

    app(Sinta::class)->ambilMetrik('6012345');

    Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://sinta.contoh.test/authors/profile/6012345'
        && str_contains($r->header('User-Agent')[0], 'sinkron profil SINTA'));
});
