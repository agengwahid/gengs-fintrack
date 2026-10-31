<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get user accounts.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get user categories.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get user transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get user budgets.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get user Google Sheets synchronization logs.
     */
    public function sheetSyncLogs(): HasMany
    {
        return $this->hasMany(SheetSyncLog::class);
    }

    /**
     * Seed starter accounts and categories for this user.
     */
    public function seedDefaultFinances(): void
    {
        if ($this->categories()->count() === 0) {
            $defaultCategories = [
                // Expenses
                ['name' => 'Makanan & Minuman', 'type' => 'expense', 'color' => '#ef4444', 'icon' => 'Utensils', 'description' => 'Makan sehari-hari, kafe, jajan'],
                ['name' => 'Transportasi', 'type' => 'expense', 'color' => '#f97316', 'icon' => 'Car', 'description' => 'Bensin, ojek online, parkir, tol'],
                ['name' => 'Belanja Harian', 'type' => 'expense', 'color' => '#f59e0b', 'icon' => 'ShoppingCart', 'description' => 'Kebutuhan pokok, supermarket'],
                ['name' => 'Tagihan & Utilitas', 'type' => 'expense', 'color' => '#eab308', 'icon' => 'Zap', 'description' => 'Listrik, air, internet, pulsa'],
                ['name' => 'Hiburan & Gaya Hidup', 'type' => 'expense', 'color' => '#8b5cf6', 'icon' => 'Gamepad2', 'description' => 'Bioskop, langganan streaming, hobi'],
                ['name' => 'Kesehatan', 'type' => 'expense', 'color' => '#ec4899', 'icon' => 'HeartPulse', 'description' => 'Obat, dokter, asuransi kesehatan'],
                ['name' => 'Pendidikan', 'type' => 'expense', 'color' => '#3b82f6', 'icon' => 'GraduationCap', 'description' => 'Buku, kursus, seminar'],
                ['name' => 'Tempat Tinggal', 'type' => 'expense', 'color' => '#6366f1', 'icon' => 'Home', 'description' => 'Sewa kos/rumah, perbaikan'],
                ['name' => 'Lain-lain (Pengeluaran)', 'type' => 'expense', 'color' => '#64748b', 'icon' => 'MoreHorizontal', 'description' => 'Pengeluaran tak terduga lainnya'],

                // Income
                ['name' => 'Gaji Pokok', 'type' => 'income', 'color' => '#10b981', 'icon' => 'Briefcase', 'description' => 'Pendapatan gaji bulanan'],
                ['name' => 'Bonus & THR', 'type' => 'income', 'color' => '#06b6d4', 'icon' => 'Gift', 'description' => 'Bonus prestasi, THR, komisi'],
                ['name' => 'Freelance / Sampingan', 'type' => 'income', 'color' => '#3b82f6', 'icon' => 'Laptop', 'description' => 'Proyek lepas, honorarium'],
                ['name' => 'Investasi / Dividen', 'type' => 'income', 'color' => '#14b8a6', 'icon' => 'TrendingUp', 'description' => 'Hasil reksadana, saham, bunga'],
                ['name' => 'Lain-lain (Pemasukan)', 'type' => 'income', 'color' => '#84cc16', 'icon' => 'Coins', 'description' => 'Hadiah, cashback, dll'],
            ];

            foreach ($defaultCategories as $cat) {
                $this->categories()->create(array_merge($cat, ['is_default' => true]));
            }
        }

        if ($this->accounts()->count() === 0) {
            $defaultAccounts = [
                [
                    'name' => 'Dompet Tunai',
                    'type' => 'cash',
                    'icon' => 'Wallet',
                    'color' => '#10b981',
                    'initial_balance' => 250000,
                    'current_balance' => 250000,
                    'description' => 'Uang tunai di dompet',
                ],
                [
                    'name' => 'Bank BCA',
                    'type' => 'bank',
                    'icon' => 'Building2',
                    'color' => '#2563eb',
                    'account_number' => '1234567890',
                    'initial_balance' => 5000000,
                    'current_balance' => 5000000,
                    'description' => 'Rekening utama untuk tabungan & transaksi',
                ],
                [
                    'name' => 'GoPay',
                    'type' => 'ewallet',
                    'icon' => 'Smartphone',
                    'color' => '#06b6d4',
                    'account_number' => '08123456789',
                    'initial_balance' => 150000,
                    'current_balance' => 150000,
                    'description' => 'E-wallet transaksi harian',
                ],
            ];

            foreach ($defaultAccounts as $acc) {
                $this->accounts()->create($acc);
            }
        }
    }
}
