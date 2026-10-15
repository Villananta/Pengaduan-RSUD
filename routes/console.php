<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Data kesehatan tidak boleh disimpan selamanya, tetapi tidak ada cron
// bawaan yang menjalankan pembersihan. Tanpa baris ini masa retensi hanya
// dijalankan kalau ada orang yang mengingatnya. --force dipakai karena
// konfirmasi tidak pernah terjawab di latar belakang, dan akibatnya
// penghapusan selalu dibatalkan diam-diam.
Schedule::command('pengaduan:bersihkan --force')->dailyAt('02:00');

// Cek SLA setiap hari pukul 07:00 pagi (sebelum jam kerja) dan kirim notifikasi
// ke PIC unit yang tiketnya mendekati atau lewat batas investigasi.
Schedule::command('pengaduan:cek-sla --kirim-notifikasi')->dailyAt('07:00');
