<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Replace the user's profile photo.
     */
    public function updateFoto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $user = $request->user();
        $lama = $user->foto_profil;

        $user->foto_profil = $request->file('foto')->store('foto-profil');
        $user->save();

        if ($lama !== null) {
            Storage::delete($lama);
        }

        return Redirect::back()->with('status', 'foto-updated');
    }

    /**
     * Remove the user's profile photo, falling back to their initials.
     */
    public function destroyFoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->foto_profil !== null) {
            Storage::delete($user->foto_profil);
            $user->foto_profil = null;
            $user->save();
        }

        return Redirect::back()->with('status', 'foto-deleted');
    }

    /**
     * Serve a user's profile photo to signed-in users.
     */
    public function foto(User $user): StreamedResponse
    {
        abort_if($user->foto_profil === null || ! Storage::exists($user->foto_profil), 404);

        return Storage::response($user->foto_profil, headers: ['Cache-Control' => 'private, max-age=604800']);
    }
}
