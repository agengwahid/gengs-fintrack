<?php

use App\Jobs\SyncTransactionToGoogleSheet;
use App\Models\GoogleSheetSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fintrack:sync-sheets', function () {
    $this->info('Memulai sinkronisasi seluruh data ke Google Sheets...');

    $settings = GoogleSheetSetting::where('is_enabled', true)
        ->whereNotNull('spreadsheet_id')
        ->whereNotNull('service_account_credentials')
        ->get();

    $count = 0;
    foreach ($settings as $setting) {
        SyncTransactionToGoogleSheet::dispatch($setting->user_id, null, 'sync_all');
        $count++;
    }

    $this->info("Berhasil mengirim {$count} antrean sinkronisasi ke Google Sheets.");
})->purpose('Sinkronkan seluruh data transaksi aktif ke Google Sheets');

// Safety net: Jadwal sinkronisasi harian otomatis pukul 23:59 WIB
Schedule::command('fintrack:sync-sheets')->dailyAt('23:59');
