<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's in-app notifications.
 */
class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifikasi.index', ['notifikasi' => $request->user()->notifications()->paginate(20)]);
    }

    /**
     * Mark one notification read and open what it points to.
     */
    public function buka(Request $request, string $id): RedirectResponse
    {
        $notifikasi = $request->user()->notifications()->findOrFail($id);
        $notifikasi->markAsRead();

        return redirect($notifikasi->data['url'] ?? route('notifikasi.index'));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
