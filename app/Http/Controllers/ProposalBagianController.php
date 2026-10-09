<?php

namespace App\Http\Controllers;

use App\Enums\JenisKegiatan;
use App\Enums\JenisLuaran;
use App\Enums\KategoriRab;
use App\Enums\TipeAnggota;
use App\Models\Berkas;
use App\Models\Dosen;
use App\Models\Luaran;
use App\Models\Proposal;
use App\Models\ProposalAnggota;
use App\Models\ProposalMitra;
use App\Models\RabItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The parts of a proposal the lead fills in on its page: members, partners,
 * budget lines, promised outputs and the substance PDF.
 */
class ProposalBagianController extends Controller
{
    public function tambahAnggota(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $data = $request->validate([
            'tipe' => ['required', Rule::enum(TipeAnggota::class)],
            'nidn' => ['required_if:tipe,dosen', 'nullable', 'string'],
            'nama' => ['required_unless:tipe,dosen', 'nullable', 'string', 'max:255'],
            'nim' => ['required_if:tipe,mahasiswa', 'nullable', 'string', 'max:30'],
            'institusi' => ['nullable', 'string', 'max:255'],
            'peran' => ['nullable', 'string', 'max:255'],
            'uraian_tugas' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['tipe'] === TipeAnggota::Dosen->value) {
            $dosen = Dosen::where('nidn', $data['nidn'])->orWhere('nuptk', $data['nidn'])->first();
            if (! $dosen) {
                return back()->withErrors(['nidn' => 'Dosen dengan NIDN atau NUPTK itu belum terdaftar.'])->withInput();
            }
            if ($proposal->ketua_id === $dosen->id || $proposal->anggota()->where('dosen_id', $dosen->id)->exists()) {
                return back()->withErrors(['nidn' => 'Dosen itu sudah ada di tim.'])->withInput();
            }
            $data['dosen_id'] = $dosen->id;
            $data['nama'] = $dosen->namaLengkap();
            $data['token_undangan'] = Str::random(48);
        }

        $proposal->anggota()->create($data);

        return back()->with('status', 'Anggota ditambahkan.');
    }

    public function hapusAnggota(Proposal $proposal, ProposalAnggota $anggota): RedirectResponse
    {
        Gate::authorize('update', $proposal);
        abort_unless($anggota->proposal_id === $proposal->id, 404);

        $anggota->delete();

        return back()->with('status', 'Anggota dihapus.');
    }

    public function tambahMitra(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);
        abort_unless($proposal->periode->skema->jenis_kegiatan === JenisKegiatan::Pkm, 404);

        $proposal->mitra()->create($request->validate([
            'nama_mitra' => ['required', 'string', 'max:255'],
            'jenis_mitra' => ['required', 'string', 'max:40'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'kontak' => ['nullable', 'string', 'max:255'],
            'dana_pendamping' => ['nullable', 'integer', 'min:0'],
        ]));

        return back()->with('status', 'Mitra ditambahkan.');
    }

    public function hapusMitra(Proposal $proposal, ProposalMitra $mitra): RedirectResponse
    {
        Gate::authorize('update', $proposal);
        abort_unless($mitra->proposal_id === $proposal->id, 404);

        $mitra->delete();

        return back()->with('status', 'Mitra dihapus.');
    }

    public function tambahRab(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);
        $periode = $proposal->periode;

        $data = $request->validate([
            'kategori' => [
                'required',
                Rule::enum(KategoriRab::class)->when(! $periode->honor_diizinkan, fn ($rule) => $rule->except(KategoriRab::Honorarium)),
            ],
            'uraian' => ['required', 'string', 'max:255'],
            'volume' => ['required', 'numeric', 'gt:0'],
            'satuan' => ['required', 'string', 'max:30'],
            'harga_satuan' => ['required', 'integer', 'min:1'],
            'tahun_ke' => ['nullable', 'integer', 'between:1,'.$proposal->lama_tahun],
        ]);
        $data['tahun_ke'] ??= 1;
        $data['total'] = (int) round($data['volume'] * $data['harga_satuan']);

        $proposal->rab()->create($data);

        return back()->with('status', 'Item RAB ditambahkan.');
    }

    public function hapusRab(Proposal $proposal, RabItem $rab): RedirectResponse
    {
        Gate::authorize('update', $proposal);
        abort_unless($rab->proposal_id === $proposal->id, 404);

        $rab->delete();

        return back()->with('status', 'Item RAB dihapus.');
    }

    public function tambahLuaran(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $proposal->luaran()->create($request->validate([
            'kategori' => ['required', 'in:wajib,tambahan'],
            'jenis' => ['required', Rule::enum(JenisLuaran::class)],
            'target_keterangan' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('status', 'Target luaran ditambahkan.');
    }

    public function hapusLuaran(Proposal $proposal, Luaran $luaran): RedirectResponse
    {
        Gate::authorize('update', $proposal);
        abort_unless($luaran->proposal_id === $proposal->id, 404);

        $luaran->delete();

        return back()->with('status', 'Target luaran dihapus.');
    }

    public function unggahSubstansi(Request $request, Proposal $proposal): RedirectResponse
    {
        Gate::authorize('update', $proposal);

        $request->validate(['berkas' => ['required', 'file', 'mimes:pdf', 'max:10240']]);
        $file = $request->file('berkas');

        $proposal->berkas()->create([
            'kategori' => Berkas::SUBSTANSI,
            'nama_asli' => $file->getClientOriginalName(),
            'path' => $file->store("proposal/{$proposal->id}"),
            'mime' => $file->getMimeType(),
            'ukuran_byte' => $file->getSize(),
            'diunggah_oleh' => $request->user()->id,
        ]);

        return back()->with('status', 'Substansi proposal diunggah.');
    }

    public function unduhSubstansi(Proposal $proposal): StreamedResponse
    {
        Gate::authorize('view', $proposal);
        $berkas = $proposal->berkasSubstansi;
        abort_if($berkas === null, 404);

        return Storage::download($berkas->path, $berkas->nama_asli);
    }
}
