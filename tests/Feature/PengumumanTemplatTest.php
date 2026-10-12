<?php

use App\Models\Berkas;
use App\Models\Dosen;
use App\Models\Pengumuman;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\TemplateDokumen;
use App\Models\User;
use App\Notifications\PengumumanTerbit;
use App\Services\SiaranPengumuman;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function penggunaDengan(string ...$peran): User
{
    $user = User::factory()->create();
    $user->assignRole(...$peran);

    return $user;
}

/**
 * @return array<string, mixed>
 */
function isianPengumuman(array $ubah = []): array
{
    return [
        'judul' => 'Penerimaan proposal penelitian 2027',
        'isi' => "Proposal diterima sampai 30 November.\nGunakan templat terbaru.",
        'kategori' => Pengumuman::HIBAH,
        'target_peran' => Pengumuman::SEMUA,
        'terbit_at' => now()->subMinute()->format('Y-m-d\TH:i'),
        ...$ubah,
    ];
}

test('an operator publishes an announcement and its readers are notified once', function () {
    Notification::fake();
    Storage::fake();
    $operator = penggunaDengan(Role::OPERATOR);
    $dosen = penggunaDengan(Role::DOSEN);
    $reviewer = penggunaDengan(Role::REVIEWER);

    $this->actingAs($operator)->post(route('kelola.pengumuman.store'), isianPengumuman([
        'lampiran' => UploadedFile::fake()->create('panduan.pdf', 100, 'application/pdf'),
    ]))->assertRedirect(route('kelola.pengumuman.index'));

    $pengumuman = Pengumuman::sole();
    expect($pengumuman->slug)->toBe('penerimaan-proposal-penelitian-2027')
        ->and($pengumuman->dibuat_oleh)->toBe($operator->id)
        ->and($pengumuman->notifikasi_terkirim_at)->not->toBeNull()
        ->and($pengumuman->lampiran->nama_asli)->toBe('panduan.pdf');

    Notification::assertSentTo([$dosen, $reviewer, $operator], PengumumanTerbit::class);

    // Saving again does not notify a second time.
    $this->actingAs($operator)->put(route('kelola.pengumuman.update', $pengumuman), isianPengumuman(['judul' => 'Judul baru']));
    Notification::assertSentToTimes($dosen, PengumumanTerbit::class, 1);
    expect($pengumuman->fresh()->slug)->toBe('penerimaan-proposal-penelitian-2027');
});

test('an announcement for one role reaches and shows to that role only', function () {
    Notification::fake();
    $admin = penggunaDengan(Role::ADMIN_LPPM);
    $reviewer = penggunaDengan(Role::REVIEWER);
    $dosen = penggunaDengan(Role::DOSEN);

    $this->actingAs($admin)->post(route('kelola.pengumuman.store'), isianPengumuman(['target_peran' => Role::REVIEWER]));
    $pengumuman = Pengumuman::sole();

    Notification::assertSentTo($reviewer, PengumumanTerbit::class);
    Notification::assertNotSentTo($dosen, PengumumanTerbit::class);

    $this->actingAs($reviewer)->get(route('pengumuman.show', $pengumuman))->assertOk()->assertSee('Gunakan templat terbaru.');
    $this->actingAs($dosen)->get(route('pengumuman.show', $pengumuman))->assertNotFound();
    $this->actingAs($dosen)->get(route('pengumuman.index'))->assertDontSee($pengumuman->judul);
});

test('a scheduled announcement is hidden and sent only when it goes live', function () {
    Notification::fake();
    $admin = penggunaDengan(Role::ADMIN_LPPM);
    $dosen = penggunaDengan(Role::DOSEN);

    $this->actingAs($admin)->post(route('kelola.pengumuman.store'), isianPengumuman([
        'terbit_at' => now()->addDay()->format('Y-m-d\TH:i'),
    ]))->assertSessionHas('status', 'Pengumuman disimpan dan akan terbit sesuai jadwal.');
    $pengumuman = Pengumuman::sole();

    Notification::assertNothingSent();
    $this->actingAs($dosen)->get(route('pengumuman.show', $pengumuman))->assertNotFound();
    $this->actingAs($admin)->get(route('pengumuman.show', $pengumuman))->assertOk()->assertSee('belum atau tidak lagi tayang');

    $this->travel(2)->days();
    expect(app(SiaranPengumuman::class)->kirimYangTerbit())->toBe(1)
        ->and(app(SiaranPengumuman::class)->kirimYangTerbit())->toBe(0);
    Notification::assertSentToTimes($dosen, PengumumanTerbit::class, 1);
    $this->actingAs($dosen)->get(route('dashboard'))->assertSee($pengumuman->judul);
});

test('only admins and operators manage announcements', function () {
    $this->actingAs(penggunaDengan(Role::DOSEN))->get(route('kelola.pengumuman.index'))->assertForbidden();
    $this->actingAs(penggunaDengan(Role::OPERATOR))->get(route('kelola.pengumuman.index'))->assertOk();
    $this->actingAs(penggunaDengan(Role::ADMIN_LPPM))->get(route('kelola.pengumuman.create'))->assertOk();
});

test('an expiry before the publish time is rejected', function () {
    $this->actingAs(penggunaDengan(Role::ADMIN_LPPM))
        ->post(route('kelola.pengumuman.store'), isianPengumuman(['berakhir_at' => now()->subDay()->format('Y-m-d\TH:i')]))
        ->assertSessionHasErrors('berakhir_at');
    expect(Pengumuman::count())->toBe(0);
});

test('a reader opens a notification, which marks it read', function () {
    $dosen = penggunaDengan(Role::DOSEN);
    $pengumuman = Pengumuman::factory()->create();
    app(SiaranPengumuman::class)->kirim($pengumuman);

    expect($dosen->unreadNotifications)->toHaveCount(1);
    $id = $dosen->notifications()->sole()->id;

    $this->actingAs($dosen)->get(route('notifikasi.index'))->assertOk()->assertSee($pengumuman->judul);
    $this->actingAs($dosen)->get(route('notifikasi.buka', $id))->assertRedirect(route('pengumuman.show', $pengumuman));
    expect($dosen->fresh()->unreadNotifications)->toHaveCount(0);

    $this->actingAs(penggunaDengan(Role::DOSEN))->get(route('notifikasi.buka', $id))->assertNotFound();
});

test('email is added only when turned on', function () {
    $user = penggunaDengan(Role::DOSEN);
    $notifikasi = new PengumumanTerbit(Pengumuman::factory()->create());

    expect($notifikasi->via($user))->toBe(['database']);
    config(['sisfotabmas.notifikasi_email' => true]);
    expect($notifikasi->via($user))->toBe(['database', 'mail']);
});

test('LPPM uploads a template and lecturers download it from their proposal', function () {
    Storage::fake();
    $admin = penggunaDengan(Role::ADMIN_LPPM);
    $dosen = Dosen::factory()->create();
    $periode = PeriodeHibah::factory()->create();
    $proposal = Proposal::factory()->for($periode, 'periode')->for($dosen, 'ketua')->create();

    $this->actingAs($admin)->post(route('admin.template.store'), [
        'jenis' => TemplateDokumen::SUBSTANSI, 'nama' => 'Templat proposal penelitian',
        'skema_id' => $periode->skema_id, 'tahun' => $periode->tahun_anggaran, 'is_active' => '1',
        'berkas' => UploadedFile::fake()->create('templat.docx', 50),
    ])->assertRedirect(route('admin.template.index'));

    $template = TemplateDokumen::sole();
    expect($template->berkasTerbaru->kategori)->toBe(Berkas::TEMPLAT);

    $this->actingAs($dosen->user)->get(route('proposal.show', $proposal))->assertSee('Templat proposal penelitian');
    $this->actingAs($dosen->user)->get(route('templat.unduh', $template))->assertDownload('templat.docx');

    // A new upload replaces the file people download; an inactive template is gone.
    $this->actingAs($admin)->put(route('admin.template.update', $template), [
        'jenis' => TemplateDokumen::SUBSTANSI, 'nama' => 'Templat proposal penelitian', 'is_active' => '0',
        'berkas' => UploadedFile::fake()->create('templat-v2.docx', 50),
    ]);
    expect($template->fresh()->berkasTerbaru->nama_asli)->toBe('templat-v2.docx');
    $this->actingAs($dosen->user)->get(route('templat.unduh', $template))->assertNotFound();
    $this->actingAs($dosen->user)->get(route('proposal.show', $proposal))->assertDontSee('Templat proposal penelitian');
});

test('templates apply to their scheme and year, or to all when left empty', function () {
    $periode = PeriodeHibah::factory()->create();
    $umum = TemplateDokumen::create(['jenis' => TemplateDokumen::SUBSTANSI, 'nama' => 'Umum', 'is_active' => true]);
    $cocok = TemplateDokumen::create(['jenis' => TemplateDokumen::SUBSTANSI, 'nama' => 'Cocok', 'skema_id' => $periode->skema_id, 'tahun' => $periode->tahun_anggaran, 'is_active' => true]);
    TemplateDokumen::create(['jenis' => TemplateDokumen::SUBSTANSI, 'nama' => 'Tahun lain', 'tahun' => $periode->tahun_anggaran - 1, 'is_active' => true]);

    expect(TemplateDokumen::berlakuUntuk($periode->skema_id, $periode->tahun_anggaran)->pluck('id')->sort()->values()->all())
        ->toBe(collect([$umum->id, $cocok->id])->sort()->values()->all());
});

test('only LPPM admins manage templates', function () {
    $this->actingAs(penggunaDengan(Role::OPERATOR))->get(route('admin.template.index'))->assertForbidden();
    $this->actingAs(penggunaDengan(Role::ADMIN_LPPM))->get(route('admin.template.index'))->assertOk();
});
