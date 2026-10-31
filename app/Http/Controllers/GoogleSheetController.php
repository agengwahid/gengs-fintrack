<?php

namespace App\Http\Controllers;

use App\Jobs\SyncTransactionToGoogleSheet;
use App\Models\SheetSyncLog;
use App\Services\GoogleSheetsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GoogleSheetController extends Controller
{
    /**
     * Display Google Sheets synchronization center & logs.
     */
    public function index(Request $request, GoogleSheetsService $sheetsService): Response
    {
        $user = $request->user();
        $setting = $user->googleSheetSetting;

        $spreadsheetId = $sheetsService->getSpreadsheetId($user);
        $credentialsExist = $sheetsService->credentialsExist($user);
        $isReady = $sheetsService->isEnabled($user);
        $isEnabled = $setting ? (bool) $setting->is_enabled : (bool) config('services.google_sheets.enabled', false);

        $stats = [
            'total' => $user->sheetSyncLogs()->count(),
            'success' => $user->sheetSyncLogs()->success()->count(),
            'pending' => $user->sheetSyncLogs()->pending()->count(),
            'failed' => $user->sheetSyncLogs()->failed()->count(),
        ];

        $logs = $user->sheetSyncLogs()
            ->with(['transaction.category', 'transaction.account'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sheets/Index', [
            'config' => [
                'enabled' => $isEnabled,
                'is_ready' => $isReady,
                'spreadsheet_id' => $spreadsheetId,
                'spreadsheet_url' => $spreadsheetId ? "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit" : null,
                'credentials_path' => config('services.google_sheets.credentials_path', 'storage/app/google/credentials.json'),
                'credentials_exist' => $credentialsExist,
                'client_email' => $setting?->client_email,
                'project_id' => $setting?->project_id,
                'has_custom_credentials' => (bool) $setting?->hasCredentials(),
                'last_tested_at' => $setting?->last_tested_at,
                'last_test_status' => $setting?->last_test_status,
                'last_test_message' => $setting?->last_test_message,
            ],
            'stats' => $stats,
            'logs' => $logs,
        ]);
    }

    /**
     * Save or update Google Sheets settings dynamically from the web UI.
     */
    public function updateSettings(Request $request, GoogleSheetsService $sheetsService): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'spreadsheet_id' => ['nullable', 'string', 'max:500'],
            'credentials_file' => ['nullable', 'file', 'max:2048'],
            'credentials_json' => ['nullable', 'string'],
        ]);

        $credentialsContent = null;
        if ($request->hasFile('credentials_file')) {
            $credentialsContent = file_get_contents($request->file('credentials_file')->getRealPath());
        } elseif ($request->filled('credentials_json')) {
            $credentialsContent = $request->input('credentials_json');
        }

        $decodedCredentials = null;
        if ($credentialsContent) {
            $decoded = json_decode($credentialsContent, true);

            if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                $errorField = $request->hasFile('credentials_file') ? 'credentials_file' : 'credentials_json';
                throw ValidationException::withMessages([
                    $errorField => 'Kredensial JSON tidak valid. Pastikan file adalah Service Account JSON resmi dari Google Cloud yang memiliki "client_email" dan "private_key".',
                ]);
            }

            $decodedCredentials = $decoded;
        }

        $cleanedSpreadsheetId = GoogleSheetsService::extractSpreadsheetId($request->input('spreadsheet_id'));

        $setting = $user->googleSheetSetting()->firstOrNew();
        $setting->is_enabled = (bool) $validated['is_enabled'];
        $setting->spreadsheet_id = $cleanedSpreadsheetId;

        if ($decodedCredentials) {
            $setting->service_account_credentials = $decodedCredentials;
            $setting->client_email = $decodedCredentials['client_email'] ?? null;
            $setting->project_id = $decodedCredentials['project_id'] ?? null;
        }

        $setting->save();

        return back()->with('success', 'Pengaturan Google Sheets berhasil disimpan!');
    }

    /**
     * Delete custom service account credentials.
     */
    public function deleteCredentials(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->googleSheetSetting) {
            $user->googleSheetSetting->update([
                'service_account_credentials' => null,
                'client_email' => null,
                'project_id' => null,
            ]);
        }

        return back()->with('success', 'Kredensial Service Account berhasil dihapus.');
    }

    /**
     * Retry a failed synchronization job.
     */
    public function retry(Request $request, SheetSyncLog $log): RedirectResponse
    {
        abort_if($log->user_id !== $request->user()->id, 403, 'Akses ditolak.');

        $log->update([
            'status' => 'pending',
            'last_error' => null,
        ]);

        SyncTransactionToGoogleSheet::dispatch(
            $request->user()->id,
            $log->transaction_id,
            $log->action,
            $log->id
        );

        return back()->with('success', 'Permintaan sinkronisasi ulang berhasil dimasukkan ke antrean!');
    }

    /**
     * Trigger synchronization for all user transactions.
     */
    public function syncAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        SyncTransactionToGoogleSheet::dispatch(
            $user->id,
            null,
            'sync_all'
        );

        return back()->with('success', 'Sinkronisasi seluruh transaksi berhasil dimasukkan ke antrean latar belakang!');
    }

    /**
     * Test connection to the Google Spreadsheet.
     */
    public function testConnection(Request $request, GoogleSheetsService $sheetsService): RedirectResponse
    {
        $result = $sheetsService->testConnection($request->user());

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
