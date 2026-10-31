<?php

namespace App\Jobs;

use App\Models\SheetSyncLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoogleSheetsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTransactionToGoogleSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public $backoff = [10, 60, 300];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $userId,
        public ?string $transactionId = null,
        public string $action = 'create',
        public ?string $logId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleSheetsService $sheetsService): void
    {
        // 1. Resolve or create SheetSyncLog
        $log = null;
        if ($this->logId) {
            $log = SheetSyncLog::find($this->logId);
        }

        if (! $log) {
            $log = SheetSyncLog::create([
                'user_id' => $this->userId,
                'transaction_id' => $this->transactionId,
                'action' => $this->action,
                'status' => 'pending',
                'attempts' => 1,
            ]);
            $this->logId = $log->id;
        } else {
            $log->increment('attempts');
            $log->update(['status' => 'pending']);
        }

        // 2. Check if Google Sheets is enabled and properly configured
        if (! $sheetsService->isEnabled()) {
            $reason = 'Integrasi Google Sheets dinonaktifkan di file .env (GOOGLE_SHEETS_ENABLED=false).';

            if (empty($sheetsService->getSpreadsheetId())) {
                $reason = 'Spreadsheet ID belum dikonfigurasi di GOOGLE_SHEETS_SPREADSHEET_ID.';
            } elseif (! $sheetsService->credentialsExist()) {
                $reason = 'File kredensial Google Sheets (JSON) tidak ditemukan di server.';
            }

            $log->update([
                'status' => 'failed',
                'last_error' => $reason,
            ]);

            Log::info("Google Sheets sync skipped for User {$this->userId}: {$reason}");

            return;
        }

        try {
            // 3. Perform the requested synchronization action
            if ($this->action === 'delete') {
                if ($this->transactionId) {
                    $sheetsService->deleteTransaction($this->transactionId);
                }
            } elseif ($this->action === 'sync_all') {
                $user = User::findOrFail($this->userId);
                foreach ($user->transactions()->with(['account', 'category'])->cursor() as $tx) {
                    $sheetsService->upsertTransaction($tx);
                }
            } else { // 'create' or 'update'
                $transaction = Transaction::with(['account', 'category'])->find($this->transactionId);

                if ($transaction) {
                    $sheetsService->upsertTransaction($transaction);
                } else {
                    // Transaction was removed prior to sync job execution
                    if ($this->transactionId) {
                        $sheetsService->deleteTransaction($this->transactionId);
                    }
                }
            }

            // 4. Update monthly and category summaries in spreadsheet
            $sheetsService->updateSummaries($this->userId);

            // 5. Mark sync log as success
            $log->update([
                'status' => 'success',
                'synced_at' => now(),
                'last_error' => null,
            ]);

            Log::info("Google Sheets sync successful for log {$log->id} (Action: {$this->action}).");
        } catch (Throwable $e) {
            $cleanError = $sheetsService->sanitizeErrorMessage($e->getMessage());

            $log->update([
                'last_error' => $cleanError,
            ]);

            Log::warning("Google Sheets sync attempt {$this->attempts()} failed for log {$log->id}: {$cleanError}");

            if ($this->attempts() >= $this->tries) {
                $log->update(['status' => 'failed']);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        if ($this->logId) {
            $log = SheetSyncLog::find($this->logId);
            if ($log) {
                $errorMessage = $exception ? $exception->getMessage() : 'Sinkronisasi gagal setelah '.$this->tries.' percobaan.';
                $log->update([
                    'status' => 'failed',
                    'last_error' => mb_substr($errorMessage, 0, 500),
                ]);
            }
        }

        Log::error("Google Sheets sync job permanently failed for User {$this->userId}, Transaction: {$this->transactionId}");
    }
}
