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

function aktifkanApiSinta(): void
{
    config(['services.sinta.api' => [
        'url' => 'http://apisinta.contoh.test',
        'env' => 'prod',
        'uniq' => 'kampus123',
        'username' => 'lppm',
        'password' => 'rahasia',
    ]]);
}

/** An author profile answer; the manual shows no fields, so these names are a guess the mapper must cope with. */
function jawabanProfilSinta(): array
{
    return ['data' => [
        'sinta_id' => 6012345,
        'name' => 'Ani Lestari',
        'sinta_score_v3_overall' => 1234.5,
        'sinta_score_v3_3yr' => 456,
        'affil_score_v3' => 789,
        'scopus' => ['h_index' => 5, 'citation' => 87, 'cited_document' => 9],
        'gscholar' => ['h_index' => 17, 'citation' => 1502],
        'wos' => ['h_index' => 2],
    ]];
}

test('author figures are found in the API answer whatever their nesting', function () {
    expect(Sinta::petakan(jawabanProfilSinta()))->toBe([
        'skor_sinta' => 1234.5,
        'h_index_scopus' => 5,
        'h_index_scholar' => 17,
        'sitasi_scopus' => 87,
        'sitasi_scholar' => 1502,
    ])->and(Sinta::petakan(['gs_h_index' => '8', 'gs_citation' => '40', 'scopus_h_index' => '3', 'scopus_citation' => '12', 'sinta_score_overall' => '50']))->toBe([
        'skor_sinta' => 50.0,
        'h_index_scopus' => 3,
        'h_index_scholar' => 8,
        'sitasi_scopus' => 12,
        'sitasi_scholar' => 40,
    ]);
});

test('without the SINTA API account syncing is off and nothing is sent', function () {
    Http::fake();
    $user = dosenSinta();

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertOk()
        ->assertDontSee('Perbarui dari SINTA')->assertSee('menunggu akses API dari SINTA');
    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))->assertSessionHas('gagal');
    Http::assertNothingSent();
});

test('a lecturer syncs through the SINTA API and waits two weeks for the next sync', function () {
    aktifkanApiSinta();
    Http::fake([
        '*/consumer/login' => Http::response(['status' => true, 'data' => ['token' => 'tok-1']]),
        '*/v3/prod/kampus123/author/profile/id/6012345' => Http::response(jawabanProfilSinta()),
    ]);
    $user = dosenSinta();

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertSee('Perbarui dari SINTA');
    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))
        ->assertRedirect(route('profil-dosen.edit'))->assertSessionHas('status');

    $dosen = $user->dosen->fresh();
    expect((float) $dosen->skor_sinta)->toBe(1234.5)
        ->and($dosen->h_index_scopus)->toBe(5)
        ->and($dosen->h_index_scholar)->toBe(17)
        ->and($dosen->sitasi_scopus)->toBe(87)
        ->and($dosen->sitasi_scholar)->toBe(1502)
        ->and($dosen->sinkron_terakhir_at)->not->toBeNull();

    Http::assertSent(fn (ClientRequest $r) => $r->url() === 'http://apisinta.contoh.test/consumer/login'
        && $r['username'] === 'lppm' && $r['password'] === 'rahasia');
    Http::assertSent(fn (ClientRequest $r) => str_ends_with($r->url(), '/author/profile/id/6012345')
        && $r->method() === 'POST' && $r->header('Authorization')[0] === 'Bearer tok-1');

    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertDontSee('Perbarui dari SINTA')
        ->assertSee('Sinkron berikutnya tersedia mulai');
    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))->assertSessionHas('peringatan');
    Http::assertSentCount(2);

    $this->travel(Dosen::JEDA_SINKRON_SINTA)->days();
    $this->actingAs($user)->get(route('profil-dosen.edit'))->assertSee('Perbarui dari SINTA');
});

test('an expired token is renewed once', function () {
    aktifkanApiSinta();
    Http::fake([
        '*/consumer/login' => Http::sequence()->push(['token' => 'lama'])->push(['token' => 'baru']),
        '*/author/profile/*' => Http::sequence()->push([], 401)->push(jawabanProfilSinta()),
    ]);

    expect(app(Sinta::class)->ambilMetrik('6012345')['h_index_scholar'])->toBe(17);
    Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), '/author/profile/') && $r->header('Authorization')[0] === 'Bearer baru');
});

test('a refused or unreadable answer keeps the figures already stored', function (int $kode, array $isi) {
    aktifkanApiSinta();
    Http::fake([
        '*/consumer/login' => Http::response(['token' => 'tok']),
        '*/author/profile/*' => Http::response($isi, $kode),
    ]);
    $user = dosenSinta(['skor_sinta' => 100, 'h_index_scholar' => 4]);

    $this->actingAs($user)->post(route('profil-dosen.sinkron-sinta'))->assertSessionHas('gagal');

    $dosen = $user->dosen->fresh();
    expect((float) $dosen->skor_sinta)->toBe(100.0)
        ->and($dosen->h_index_scholar)->toBe(4)
        ->and($dosen->sinkron_terakhir_at)->toBeNull();
})->with([
    'IP not registered' => [403, []],
    'quota used up' => [429, []],
    'no known figures' => [200, ['data' => ['name' => 'Ani']]],
]);

test('a failed login is reported, not stored as a token', function () {
    aktifkanApiSinta();
    Http::fake(['*/consumer/login' => Http::response(['message' => 'invalid'], 401)]);

    app(Sinta::class)->ambilMetrik('6012345');
})->throws(RuntimeException::class, 'Login ke API SINTA gagal');

test('a lecturer without a SINTA ID is asked to fill it in and SINTA is not called', function () {
    aktifkanApiSinta();
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
