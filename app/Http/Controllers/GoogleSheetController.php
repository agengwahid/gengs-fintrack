<?php

namespace App\Http\Controllers;

use App\Jobs\SyncTransactionToGoogleSheet;
use App\Models\SheetSyncLog;
use App\Services\GoogleSheetsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $spreadsheetId = $sheetsService->getSpreadsheetId();
        $credentialsExist = $sheetsService->credentialsExist();
        $isConfigured = ! empty($spreadsheetId) && $credentialsExist;
        $isEnabled = (bool) config('services.google_sheets.enabled', false);

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
                'is_ready' => $isEnabled && $isConfigured,
                'spreadsheet_id' => $spreadsheetId,
                'spreadsheet_url' => $spreadsheetId ? "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/edit" : null,
                'credentials_path' => config('services.google_sheets.credentials_path', 'storage/app/google/credentials.json'),
                'credentials_exist' => $credentialsExist,
            ],
            'stats' => $stats,
            'logs' => $logs,
        ]);
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
        $result = $sheetsService->testConnection();

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }
}
