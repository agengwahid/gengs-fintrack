<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\ClearValuesRequest;
use Google\Service\Sheets\DeleteDimensionRequest;
use Google\Service\Sheets\DimensionRange;
use Google\Service\Sheets\Request as SheetRequest;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSheetsService
{
    protected ?User $user = null;

    protected ?GoogleClient $client = null;

    protected ?GoogleSheets $service = null;

    /**
     * Set context user for user-specific spreadsheet configuration.
     */
    public function forUser(?User $user): self
    {
        $this->user = $user;
        $this->client = null;
        $this->service = null;

        return $this;
    }

    /**
     * Check if Google Sheets integration is enabled and configured.
     */
    public function isEnabled(?User $user = null): bool
    {
        $u = $user ?? $this->user;

        if ($u && $u->googleSheetSetting) {
            return $u->googleSheetSetting->isReady();
        }

        return config('services.google_sheets.enabled', false)
            && ! empty($this->getSpreadsheetId($u))
            && $this->credentialsExist($u);
    }

    /**
     * Get configured Google Spreadsheet ID.
     */
    public function getSpreadsheetId(?User $user = null): ?string
    {
        $u = $user ?? $this->user;

        if ($u && $u->googleSheetSetting && ! empty($u->googleSheetSetting->spreadsheet_id)) {
            return $u->googleSheetSetting->spreadsheet_id;
        }

        return config('services.google_sheets.spreadsheet_id');
    }

    /**
     * Get configured credentials file path.
     */
    public function getCredentialsPath(): string
    {
        $path = config('services.google_sheets.credentials_path', 'storage/app/google/credentials.json');

        if (! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return base_path($path);
        }

        return $path;
    }

    /**
     * Check if credentials exist (either in user database setting or on disk).
     */
    public function credentialsExist(?User $user = null): bool
    {
        $u = $user ?? $this->user;

        if ($u && $u->googleSheetSetting && $u->googleSheetSetting->hasCredentials()) {
            return true;
        }

        return file_exists($this->getCredentialsPath());
    }

    /**
     * Get or initialize Google Client.
     */
    public function getClient(?User $user = null): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $u = $user ?? $this->user;
        $client = new GoogleClient;
        $client->setApplicationName(config('app.name', 'FinTrack'));
        $client->setScopes([GoogleSheets::SPREADSHEETS]);
        $client->setAccessType('offline');

        // Check user database credentials first
        if ($u && $u->googleSheetSetting && $u->googleSheetSetting->hasCredentials()) {
            $credentialsArray = $u->googleSheetSetting->getCredentialsArray();
            if (! is_array($credentialsArray)) {
                throw new Exception('Format kredensial Google Service Account pada pengaturan akun tidak valid.');
            }
            $client->setAuthConfig($credentialsArray);
            $this->client = $client;

            return $this->client;
        }

        // Fallback to disk credentials file
        $credentialsPath = $this->getCredentialsPath();

        if (! file_exists($credentialsPath)) {
            throw new Exception('File kredensial Google Sheets belum diunggah atau tidak ditemukan.');
        }

        $client->setAuthConfig($credentialsPath);
        $this->client = $client;

        return $this->client;
    }

    /**
     * Get or initialize Google Sheets API Service.
     */
    public function getService(?User $user = null): GoogleSheets
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $this->service = new GoogleSheets($this->getClient($user));

        return $this->service;
    }

    /**
     * Test connection to Google Spreadsheet and verify permissions.
     */
    public function testConnection(?User $user = null): array
    {
        $u = $user ?? $this->user;

        try {
            $spreadsheetId = $this->getSpreadsheetId($u);

            if (empty($spreadsheetId)) {
                $errorMsg = 'ID Google Spreadsheet belum diisi.';
                $this->recordTestResult($u, false, $errorMsg);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }

            if (! $this->credentialsExist($u)) {
                $errorMsg = 'Kredensial Service Account belum diunggah.';
                $this->recordTestResult($u, false, $errorMsg);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }

            $service = $this->getService($u);
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $title = $spreadsheet->getProperties()->getTitle();

            // Ensure our 3 worksheets exist
            $this->ensureWorksheetsExist($u);

            $successMsg = "Koneksi berhasil! Terhubung ke spreadsheet '{$title}'.";
            $this->recordTestResult($u, true, $successMsg);

            return [
                'success' => true,
                'title' => $title,
                'message' => $successMsg,
            ];
        } catch (Throwable $e) {
            $sanitized = $this->sanitizeErrorMessage($e->getMessage());
            Log::error('Google Sheets test connection error: '.$e->getMessage());

            $this->recordTestResult($u, false, $sanitized);

            return [
                'success' => false,
                'message' => $sanitized,
            ];
        }
    }

    /**
     * Record last test result in user settings if available.
     */
    protected function recordTestResult(?User $user, bool $success, string $message): void
    {
        if ($user && $user->googleSheetSetting) {
            $user->googleSheetSetting->update([
                'last_tested_at' => now(),
                'last_test_status' => $success ? 'success' : 'failed',
                'last_test_message' => mb_substr($message, 0, 500),
            ]);
        }
    }

    /**
     * Extract spreadsheet ID from either a full URL or plain ID string.
     */
    public static function extractSpreadsheetId(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        $input = trim($input);

        // Check if full Google Sheets URL
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $input, $matches)) {
            return $matches[1];
        }

        return $input;
    }

    /**
     * Ensure the 3 required worksheets exist with correct headers:
     * 1. Transactions
     * 2. Monthly Summary
     * 3. Category Summary
     */
    public function ensureWorksheetsExist(?User $user = null): void
    {
        $u = $user ?? $this->user;
        $spreadsheetId = $this->getSpreadsheetId($u);
        $service = $this->getService($u);

        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();

        $existingSheetTitles = [];
        foreach ($sheets as $sheet) {
            $existingSheetTitles[] = $sheet->getProperties()->getTitle();
        }

        $requiredSheets = [
            'Transactions' => [
                'headers' => [
                    'Transaction ID',
                    'Transaction Date',
                    'Type',
                    'Account',
                    'Category',
                    'Amount',
                    'Description',
                    'Notes',
                    'Created At',
                    'Updated At',
                ],
            ],
            'Monthly Summary' => [
                'headers' => [
                    'Period (YYYY-MM)',
                    'Total Income (IDR)',
                    'Total Expense (IDR)',
                    'Net Cashflow (IDR)',
                ],
            ],
            'Category Summary' => [
                'headers' => [
                    'Category',
                    'Type',
                    'Total Amount (IDR)',
                    'Transaction Count',
                ],
            ],
        ];

        $requests = [];
        foreach ($requiredSheets as $sheetTitle => $config) {
            if (! in_array($sheetTitle, $existingSheetTitles, true)) {
                $requests[] = new SheetRequest([
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheetTitle,
                        ],
                    ],
                ]);
            }
        }

        if (! empty($requests)) {
            $batchUpdateRequest = new BatchUpdateSpreadsheetRequest([
                'requests' => $requests,
            ]);
            $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
        }

        // Check & write headers if empty
        foreach ($requiredSheets as $sheetTitle => $config) {
            $range = "{$sheetTitle}!A1:Z1";
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values = $response->getValues();

            if (empty($values) || empty($values[0])) {
                $headerBody = new ValueRange([
                    'values' => [$config['headers']],
                ]);
                $service->spreadsheets_values->update(
                    $spreadsheetId,
                    "{$sheetTitle}!A1:".$this->getColumnLetter(count($config['headers'])).'1',
                    $headerBody,
                    ['valueInputOption' => 'USER_ENTERED']
                );
            }
        }
    }

    /**
     * Upsert a transaction in 'Transactions' sheet using Transaction ID as identity.
     */
    public function upsertTransaction(Transaction $transaction): void
    {
        $u = $transaction->user ?? ($this->user ?? User::find($transaction->user_id));
        $this->forUser($u);

        $this->ensureWorksheetsExist($u);

        $spreadsheetId = $this->getSpreadsheetId($u);
        $service = $this->getService($u);

        // 1. Fetch column A of Transactions sheet to check if ID exists
        $range = 'Transactions!A:A';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues() ?? [];

        $existingRowIndex = null;
        foreach ($rows as $index => $row) {
            if (! empty($row[0]) && trim((string) $row[0]) === (string) $transaction->id) {
                $existingRowIndex = $index + 1; // 1-based index in Google Sheets
                break;
            }
        }

        $dateFormatted = $transaction->date instanceof \DateTimeInterface
            ? $transaction->date->format('Y-m-d')
            : (string) $transaction->date;

        $rowData = [
            (string) $transaction->id,
            $dateFormatted,
            $transaction->type,
            $transaction->account?->name ?? '-',
            $transaction->category?->name ?? ($transaction->type === 'transfer' ? 'Transfer Antar Rekening' : '-'),
            (float) $transaction->amount,
            $transaction->notes ?? '-',
            $transaction->reference_number ?? '-',
            $transaction->created_at ? $transaction->created_at->toDateTimeString() : '-',
            $transaction->updated_at ? $transaction->updated_at->toDateTimeString() : '-',
        ];

        $body = new ValueRange([
            'values' => [$rowData],
        ]);

        if ($existingRowIndex !== null) {
            // Update existing row
            $updateRange = "Transactions!A{$existingRowIndex}:J{$existingRowIndex}";
            $service->spreadsheets_values->update($spreadsheetId, $updateRange, $body, [
                'valueInputOption' => 'USER_ENTERED',
            ]);
        } else {
            // Append new row
            $appendRange = 'Transactions!A:J';
            $service->spreadsheets_values->append($spreadsheetId, $appendRange, $body, [
                'valueInputOption' => 'USER_ENTERED',
                'insertDataOption' => 'INSERT_ROWS',
            ]);
        }
    }

    /**
     * Delete transaction row from 'Transactions' sheet based on Transaction ID.
     */
    public function deleteTransaction(string $transactionId, ?User $user = null): void
    {
        $u = $user ?? $this->user;
        $this->forUser($u);

        $this->ensureWorksheetsExist($u);

        $spreadsheetId = $this->getSpreadsheetId($u);
        $service = $this->getService($u);

        // Find sheetId for 'Transactions'
        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        $transactionsSheetId = null;
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === 'Transactions') {
                $transactionsSheetId = $sheet->getProperties()->getSheetId();
                break;
            }
        }

        // Find row index where column A matches $transactionId
        $range = 'Transactions!A:A';
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $rows = $response->getValues() ?? [];

        $targetRowIndex = null;
        foreach ($rows as $index => $row) {
            if (! empty($row[0]) && trim((string) $row[0]) === trim($transactionId)) {
                $targetRowIndex = $index; // 0-based index for deleteDimension
                break;
            }
        }

        if ($targetRowIndex !== null) {
            if ($transactionsSheetId !== null) {
                // Completely delete the row dimension
                $batchRequest = new BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        new SheetRequest([
                            'deleteDimension' => new DeleteDimensionRequest([
                                'range' => new DimensionRange([
                                    'sheetId' => $transactionsSheetId,
                                    'dimension' => 'ROWS',
                                    'startIndex' => $targetRowIndex,
                                    'endIndex' => $targetRowIndex + 1,
                                ]),
                            ]),
                        ]),
                    ],
                ]);
                $service->spreadsheets->batchUpdate($spreadsheetId, $batchRequest);
            } else {
                // Fallback: clear the row
                $rowNumber = $targetRowIndex + 1;
                $service->spreadsheets_values->clear(
                    $spreadsheetId,
                    "Transactions!A{$rowNumber}:J{$rowNumber}",
                    new ClearValuesRequest
                );
            }
        }
    }

    /**
     * Update Monthly Summary and Category Summary worksheets in an idempotent way.
     */
    public function updateSummaries(?string $userId = null): void
    {
        $u = $userId ? User::find($userId) : $this->user;
        $this->forUser($u);

        $this->ensureWorksheetsExist($u);

        $spreadsheetId = $this->getSpreadsheetId($u);
        $service = $this->getService($u);

        // Query transactions
        $query = Transaction::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $transactions = $query->with('category')->get();

        // 1. Calculate Monthly Summary
        $monthlyMap = [];
        foreach ($transactions as $t) {
            if (! in_array($t->type, ['income', 'expense'], true)) {
                continue;
            }
            $month = Carbon::parse($t->date)->format('Y-m');
            if (! isset($monthlyMap[$month])) {
                $monthlyMap[$month] = [
                    'income' => 0.0,
                    'expense' => 0.0,
                ];
            }
            if ($t->type === 'income') {
                $monthlyMap[$month]['income'] += (float) $t->amount;
            } elseif ($t->type === 'expense') {
                $monthlyMap[$month]['expense'] += (float) $t->amount;
            }
        }

        ksort($monthlyMap);

        $monthlyRows = [];
        foreach ($monthlyMap as $month => $data) {
            $net = $data['income'] - $data['expense'];
            $monthlyRows[] = [
                $month,
                $data['income'],
                $data['expense'],
                $net,
            ];
        }

        // Clear existing rows in 'Monthly Summary' (A2:D)
        $service->spreadsheets_values->clear(
            $spreadsheetId,
            'Monthly Summary!A2:D',
            new ClearValuesRequest
        );

        if (! empty($monthlyRows)) {
            $monthlyBody = new ValueRange([
                'values' => $monthlyRows,
            ]);
            $service->spreadsheets_values->update(
                $spreadsheetId,
                'Monthly Summary!A2:D'.(count($monthlyRows) + 1),
                $monthlyBody,
                ['valueInputOption' => 'USER_ENTERED']
            );
        }

        // 2. Calculate Category Summary
        $categoryMap = [];
        foreach ($transactions as $t) {
            if (! in_array($t->type, ['income', 'expense'], true)) {
                continue;
            }
            $catName = $t->category ? $t->category->name : 'Tanpa Kategori';
            $key = $catName.'||'.$t->type;

            if (! isset($categoryMap[$key])) {
                $categoryMap[$key] = [
                    'name' => $catName,
                    'type' => $t->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    'total' => 0.0,
                    'count' => 0,
                ];
            }
            $categoryMap[$key]['total'] += (float) $t->amount;
            $categoryMap[$key]['count']++;
        }

        uasort($categoryMap, fn ($a, $b) => $b['total'] <=> $a['total']);

        $categoryRows = [];
        foreach ($categoryMap as $data) {
            $categoryRows[] = [
                $data['name'],
                $data['type'],
                $data['total'],
                $data['count'],
            ];
        }

        // Clear existing rows in 'Category Summary' (A2:D)
        $service->spreadsheets_values->clear(
            $spreadsheetId,
            'Category Summary!A2:D',
            new ClearValuesRequest
        );

        if (! empty($categoryRows)) {
            $categoryBody = new ValueRange([
                'values' => $categoryRows,
            ]);
            $service->spreadsheets_values->update(
                $spreadsheetId,
                'Category Summary!A2:D'.(count($categoryRows) + 1),
                $categoryBody,
                ['valueInputOption' => 'USER_ENTERED']
            );
        }
    }

    /**
     * Convert column index number to letter (1 => A, 2 => B, etc).
     */
    protected function getColumnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $mod = ($colIndex - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $colIndex = (int) (($colIndex - $mod) / 26);
        }

        return $letter;
    }

    /**
     * Sanitize error message to avoid leaking any private keys or tokens.
     */
    public function sanitizeErrorMessage(string $message): string
    {
        // Strip out private keys, client secret, or long hex/base64 patterns if present
        $sanitized = preg_replace('/-----BEGIN [A-Z ]+-----[^-]+-----END [A-Z ]+-----/s', '[REDACTED_KEY]', $message);
        $sanitized = preg_replace('/"private_key":\s*"[^"]+"/i', '"private_key": "[REDACTED]"', $sanitized);
        $sanitized = preg_replace('/bearer\s+[a-zA-Z0-9\._\-]+/i', 'Bearer [REDACTED_TOKEN]', $sanitized);

        return mb_substr($sanitized, 0, 500);
    }
}
