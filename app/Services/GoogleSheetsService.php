<?php

namespace App\Services;

use App\Models\Transaction;
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
    protected ?GoogleClient $client = null;

    protected ?GoogleSheets $service = null;

    /**
     * Check if Google Sheets integration is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return config('services.google_sheets.enabled', false)
            && ! empty($this->getSpreadsheetId())
            && $this->credentialsExist();
    }

    /**
     * Get configured Google Spreadsheet ID.
     */
    public function getSpreadsheetId(): ?string
    {
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
     * Check if credentials file exists on disk.
     */
    public function credentialsExist(): bool
    {
        return file_exists($this->getCredentialsPath());
    }

    /**
     * Get or initialize Google Client.
     */
    public function getClient(): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $credentialsPath = $this->getCredentialsPath();

        if (! file_exists($credentialsPath)) {
            throw new Exception("File kredensial Google Sheets tidak ditemukan di: {$credentialsPath}");
        }

        $client = new GoogleClient;
        $client->setApplicationName(config('app.name', 'FinTrack'));
        $client->setScopes([GoogleSheets::SPREADSHEETS]);
        $client->setAuthConfig($credentialsPath);
        $client->setAccessType('offline');

        $this->client = $client;

        return $this->client;
    }

    /**
     * Get or initialize Google Sheets API Service.
     */
    public function getService(): GoogleSheets
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $this->service = new GoogleSheets($this->getClient());

        return $this->service;
    }

    /**
     * Test connection to Google Spreadsheet and verify permissions.
     */
    public function testConnection(): array
    {
        try {
            $spreadsheetId = $this->getSpreadsheetId();

            if (empty($spreadsheetId)) {
                return [
                    'success' => false,
                    'message' => 'GOOGLE_SHEETS_SPREADSHEET_ID belum dikonfigurasi di file .env.',
                ];
            }

            if (! $this->credentialsExist()) {
                return [
                    'success' => false,
                    'message' => 'File kredensial service account (JSON) tidak ditemukan di: '.$this->getCredentialsPath(),
                ];
            }

            $service = $this->getService();
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $title = $spreadsheet->getProperties()->getTitle();

            // Ensure our 3 worksheets exist
            $this->ensureWorksheetsExist();

            return [
                'success' => true,
                'title' => $title,
                'message' => "Koneksi berhasil! Terhubung ke spreadsheet '{$title}'.",
            ];
        } catch (Throwable $e) {
            Log::error('Google Sheets test connection error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => $this->sanitizeErrorMessage($e->getMessage()),
            ];
        }
    }

    /**
     * Ensure the 3 required worksheets exist with correct headers:
     * 1. Transactions
     * 2. Monthly Summary
     * 3. Category Summary
     */
    public function ensureWorksheetsExist(): void
    {
        $spreadsheetId = $this->getSpreadsheetId();
        $service = $this->getService();

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
        $this->ensureWorksheetsExist();

        $spreadsheetId = $this->getSpreadsheetId();
        $service = $this->getService();

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
    public function deleteTransaction(string $transactionId): void
    {
        $this->ensureWorksheetsExist();

        $spreadsheetId = $this->getSpreadsheetId();
        $service = $this->getService();

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
        $this->ensureWorksheetsExist();

        $spreadsheetId = $this->getSpreadsheetId();
        $service = $this->getService();

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
