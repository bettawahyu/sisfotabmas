<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The system administrator creates accounts and decides which roles each one holds.
 */
class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $cari = trim((string) $request->query('cari'));
        $peran = $request->query('peran');

        return view('sistem.pengguna.index', [
            'cari' => $cari,
            'peran' => $peran,
            'pengguna' => User::with('roles')
                ->when($cari !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$cari}%")->orWhere('email', 'like', "%{$cari}%")))
                ->when($peran, fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('kode', $peran)))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('sistem.pengguna.form', ['pengguna' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->markEmailAsVerified();
        // The admin chose this password, so the user replaces it on first login.
        $user->forceFill(['wajib_ganti_password' => true])->save();
        $user->roles()->sync(Role::whereIn('kode', $data['peran'])->pluck('id'));

        return redirect()->route('sistem.pengguna.index')->with('status', "Akun {$user->name} dibuat.");
    }

    public function edit(User $pengguna): View
    {
        return view('sistem.pengguna.form', ['pengguna' => $pengguna->load('roles')]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $data = $this->validasi($request, $pengguna);

        // Keep at least one way back in: nobody strips their own system admin role.
        if ($pengguna->is($request->user()) && ! in_array(Role::ADMIN_SISTEM, $data['peran'], true)) {
            throw ValidationException::withMessages(['peran' => 'Anda tidak bisa mencabut peran admin sistem dari akun sendiri.']);
        }

        $pengguna->fill(['name' => $data['name'], 'email' => $data['email']]);
        if (! empty($data['password'])) {
            $pengguna->password = $data['password'];
            $pengguna->wajib_ganti_password = ! $pengguna->is($request->user());
        }
        $pengguna->save();

        // sync() leaves the unit of a kept pimpinan_unit role untouched.
        $pengguna->roles()->sync(Role::whereIn('kode', $data['peran'])->pluck('id'));

        return redirect()->route('sistem.pengguna.index')->with('status', "Akun {$pengguna->name} diperbarui.");
    }

    /**
     * @return array{name: string, email: string, password: ?string, peran: list<string>}
     */
    private function validasi(Request $request, ?User $pengguna = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($pengguna)],
            'password' => [$pengguna ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'peran' => ['required', 'array', 'min:1'],
            'peran.*' => ['string', Rule::in(array_keys(Role::ALL))],
        ]) + ['password' => null];
    }
}
