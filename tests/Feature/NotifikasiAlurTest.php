<?php

use App\Models\Dosen;
use App\Models\Proposal;
use App\Models\Role;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Facades\Notification;

test('sending a proposal tells the faculty heads it awaits endorsement', function () {
    Notification::fake();
    $kaprodi = pengguna(Role::PIMPINAN_UNIT);
    $proposal = Proposal::factory()->lengkap()->create();

    alur()->kirim($proposal->fresh(), $proposal->ketua->user);

    Notification::assertSentTo($kaprodi, Pemberitahuan::class, fn ($n) => $n->judul === 'Proposal menunggu pengesahan'
        && $n->url === route('pengesahan.index'));
});

test('endorsement tells the lead and LPPM', function () {
    Notification::fake();
    $admin = pengguna(Role::ADMIN_LPPM);
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);

    alur()->sahkan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT));

    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Proposal disahkan'
        && $n->url === route('proposal.show', $proposal));
    Notification::assertSentTo($admin, Pemberitahuan::class, fn ($n) => $n->judul === 'Proposal baru untuk seleksi administrasi');
});

test('the lead hears the screening outcome with the reviewer note', function () {
    Notification::fake();
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    alur()->sahkan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT));

    alur()->kembalikan($proposal->fresh(), pengguna(Role::ADMIN_LPPM), 'Lengkapi surat mitra.', now()->addWeek());

    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Proposal perlu diperbaiki'
        && str_contains($n->pesan, 'Lengkapi surat mitra.'));
});

test('an invited lecturer is notified and the lead hears the answer', function () {
    Notification::fake();
    $proposal = Proposal::factory()->create();
    $anggota = Dosen::factory()->create();

    $this->actingAs($proposal->ketua->user)->post(route('proposal.anggota.store', $proposal), [
        'tipe' => 'dosen', 'nidn' => $anggota->nidn,
    ])->assertRedirect();

    $token = $proposal->anggota()->sole()->token_undangan;
    Notification::assertSentTo($anggota->user, Pemberitahuan::class, fn ($n) => $n->url === route('undangan.show', $token));

    $this->actingAs($anggota->user)->post(route('undangan.jawab', $token), ['jawaban' => 'terima'])->assertRedirect();

    Notification::assertSentTo($proposal->ketua->user, Pemberitahuan::class, fn ($n) => $n->judul === 'Undangan anggota diterima');
});

test('a flow notification shows its message on the notification page', function () {
    $proposal = Proposal::factory()->lengkap()->create();
    alur()->kirim($proposal->fresh(), $proposal->ketua->user);
    alur()->tolakPengesahan($proposal->fresh(), pengguna(Role::PIMPINAN_UNIT), 'Judul terlalu umum.');

    $this->actingAs($proposal->ketua->user)->get(route('notifikasi.index'))
        ->assertOk()
        ->assertSee('Pengesahan proposal ditolak')
        ->assertSee('Judul terlalu umum.');
});

test('flow notifications are emailed only when email is switched on', function () {
    $user = pengguna(Role::DOSEN);
    $n = new Pemberitahuan('Judul', 'Pesan');

    config(['sisfotabmas.notifikasi_email' => false]);
    expect($n->via($user))->toBe(['database']);

    config(['sisfotabmas.notifikasi_email' => true]);
    expect($n->via($user))->toBe(['database', 'mail']);
});
