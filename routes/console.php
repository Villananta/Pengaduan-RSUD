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
