<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleSheetSetting extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'google_sheet_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'is_enabled',
        'spreadsheet_id',
        'service_account_credentials',
        'client_email',
        'project_id',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'service_account_credentials' => 'encrypted:array',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'service_account_credentials',
    ];

    /**
     * Get the user who owns this setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has uploaded credentials.
     */
    public function hasCredentials(): bool
    {
        return ! empty($this->service_account_credentials);
    }

    /**
     * Get credentials as associative array.
     *
     * @return array<string, mixed>|null
     */
    public function getCredentialsArray(): ?array
    {
        if (empty($this->service_account_credentials)) {
            return null;
        }

        if (is_array($this->service_account_credentials)) {
            return $this->service_account_credentials;
        }

        $decoded = json_decode($this->service_account_credentials, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Check if user has configured spreadsheet ID.
     */
    public function hasValidSpreadsheet(): bool
    {
        return ! empty($this->spreadsheet_id);
    }

    /**
     * Check if integration is completely ready for automated syncing.
     */
    public function isReady(): bool
    {
        return $this->is_enabled && $this->hasCredentials() && $this->hasValidSpreadsheet();
    }
}
