<?php

use App\Services\AlurProposal;
use App\Services\SiaranPengumuman;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('proposal:tutup-lewat-batas', function (AlurProposal $alur) {
    $this->info($alur->tutupYangLewatBatas().' proposal ditutup karena batas perbaikan lewat.');
})->purpose('Tandai proposal yang dikembalikan dan lewat batas perbaikan sebagai tidak didanai');

Schedule::command('proposal:tutup-lewat-batas')->dailyAt('00:10');

Artisan::command('pengumuman:kirim-notifikasi', function (SiaranPengumuman $siaran) {
    $this->info($siaran->kirimYangTerbit().' pengumuman dikirim ke pembacanya.');
})->purpose('Kirim notifikasi untuk pengumuman terjadwal yang sudah terbit');

Schedule::command('pengumuman:kirim-notifikasi')->everyFiveMinutes();
