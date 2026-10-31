<?php

use App\Jobs\SyncTransactionToGoogleSheet;
use App\Models\Account;
use App\Models\Category;
use App\Models\SheetSyncLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Queue;

test('unauthenticated users are redirected to login when visiting sheets center', function () {
    $response = $this->get(route('sheets.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view sheets integration page with logs and config', function () {
    $user = User::factory()->create();

    // Create sync logs for this user
    SheetSyncLog::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('sheets.index'));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $props = $page->toArray()['props'];
        expect($props)->toHaveKeys(['config', 'stats', 'logs']);
        expect($props['stats']['total'])->toBe(3);
    });
});

test('creating transaction dispatches sync job to queue', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'expense',
        'amount' => 125000,
        'date' => '2026-10-31',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'notes' => 'Pembelian buku',
    ]);

    $response->assertRedirect();

    Queue::assertPushed(SyncTransactionToGoogleSheet::class, function ($job) use ($user) {
        return $job->userId === $user->id && $job->action === 'create';
    });
});

test('updating transaction dispatches sync update job to queue', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 100000,
    ]);

    $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
        'type' => 'expense',
        'amount' => 150000,
        'date' => '2026-10-31',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'notes' => 'Revisi harga buku',
    ]);

    $response->assertRedirect();

    Queue::assertPushed(SyncTransactionToGoogleSheet::class, function ($job) use ($user, $transaction) {
        return $job->userId === $user->id && $job->transactionId === $transaction->id && $job->action === 'update';
    });
});

test('deleting transaction dispatches sync delete job to queue', function () {
    Queue::fake();

    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
    ]);

    $txId = $transaction->id;

    $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

    $response->assertRedirect();

    Queue::assertPushed(SyncTransactionToGoogleSheet::class, function ($job) use ($user, $txId) {
        return $job->userId === $user->id && $job->transactionId === $txId && $job->action === 'delete';
    });
});

test('sync job records failed log if integration is disabled without throwing unhandled error', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
    ]);

    // Google Sheets is disabled by default in testing
    $job = new SyncTransactionToGoogleSheet($user->id, $transaction->id, 'create');
    $service = app(GoogleSheetsService::class);

    $job->handle($service);

    $this->assertDatabaseHas('sheet_sync_logs', [
        'user_id' => $user->id,
        'transaction_id' => $transaction->id,
        'action' => 'create',
        'status' => 'failed',
    ]);
});

test('sync job successfully executes with mocked sheets service', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
    ]);

    // Mock GoogleSheetsService
    $mockService = Mockery::mock(GoogleSheetsService::class);
    $mockService->shouldReceive('isEnabled')->andReturn(true);
    $mockService->shouldReceive('upsertTransaction')->once()->with(Mockery::type(Transaction::class));
    $mockService->shouldReceive('updateSummaries')->once()->with($user->id);

    $job = new SyncTransactionToGoogleSheet($user->id, $transaction->id, 'create');
    $job->handle($mockService);

    $this->assertDatabaseHas('sheet_sync_logs', [
        'user_id' => $user->id,
        'transaction_id' => $transaction->id,
        'action' => 'create',
        'status' => 'success',
    ]);
});

test('user can retry failed sync log and job is dispatched', function () {
    Queue::fake();

    $user = User::factory()->create();
    $log = SheetSyncLog::factory()->failed()->create([
        'user_id' => $user->id,
        'action' => 'create',
    ]);

    $response = $this->actingAs($user)->post(route('sheets.retry', $log));

    $response->assertRedirect();
    expect($log->fresh()->status)->toBe('pending');

    Queue::assertPushed(SyncTransactionToGoogleSheet::class, function ($job) use ($user, $log) {
        return $job->userId === $user->id && $job->logId === $log->id;
    });
});

test('user cannot retry sync log belonging to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherLog = SheetSyncLog::factory()->failed()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->post(route('sheets.retry', $otherLog));

    $response->assertForbidden();
});

test('user can trigger sync all transactions', function () {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('sheets.sync-all'));

    $response->assertRedirect();

    Queue::assertPushed(SyncTransactionToGoogleSheet::class, function ($job) use ($user) {
        return $job->userId === $user->id && $job->action === 'sync_all';
    });
});

test('test connection endpoint returns feedback from service', function () {
    $user = User::factory()->create();

    $mockService = Mockery::mock(GoogleSheetsService::class);
    $mockService->shouldReceive('testConnection')->andReturn([
        'success' => true,
        'message' => 'Koneksi berhasil! Terhubung ke spreadsheet.',
    ]);
    $this->app->instance(GoogleSheetsService::class, $mockService);

    $response = $this->actingAs($user)->post(route('sheets.test-connection'));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Koneksi berhasil! Terhubung ke spreadsheet.');
});

test('sheets service sanitizes sensitive keys and bearer tokens from error messages', function () {
    $service = new GoogleSheetsService;

    $errorWithKey = 'Error communicating: -----BEGIN PRIVATE KEY-----MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC6-----END PRIVATE KEY----- failed';
    $sanitizedKey = $service->sanitizeErrorMessage($errorWithKey);
    expect($sanitizedKey)->not->toContain('MIIEvgIBADANBgkq');
    expect($sanitizedKey)->toContain('[REDACTED_KEY]');

    $errorWithToken = 'Request failed with Bearer ya29.a0AfH6SMD_very_secret_oauth_token_123';
    $sanitizedToken = $service->sanitizeErrorMessage($errorWithToken);
    expect($sanitizedToken)->not->toContain('very_secret_oauth_token');
    expect($sanitizedToken)->toContain('Bearer [REDACTED_TOKEN]');
});
