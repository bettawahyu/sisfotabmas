<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    /**
     * The page a user whose password was set by someone else must pass before anything else.
     */
    public function wajib(Request $request): View|RedirectResponse
    {
        if (! $request->user()->wajib_ganti_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.ganti-password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();
        $wajib = $user->wajib_ganti_password;
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'wajib_ganti_password' => false,
        ])->save();

        return $wajib
            ? redirect()->route('dashboard')->with('status', 'Kata sandi diganti.')
            : back()->with('status', 'password-updated');
    }
}
