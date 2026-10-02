<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    public const WARRANTY_MONTHS = 1;

    public const PAYMENT_METHODS = ['GCash', 'Cash', 'Card'];

    public const SALE_TYPES = ['walk_in', 'job_order'];

    protected $fillable = [
        'code',
        'sale_type',
        'job_order_id',
        'payment_method',
        'total',
        'gross',
        'labor_cost',
        'amount_tendered',
        'change_due',
        'amount_paid',
        'balance_due',
        'is_paid',
        'sold_at',
        'warranty_expires_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'gross' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'is_paid' => 'boolean',
            'sold_at' => 'date',
            'warranty_expires_at' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public static function nextCode(): string
    {
        $last = static::query()->latest('id')->value('id') ?? 0;

        return sprintf('TXN-%03d', $last + 1);
    }

    /**
     * The next raw transaction number, shown in the cashier's "Current sale" panel
     * before the row exists (e.g. transaction_ID 2041).
     */
    public static function nextNumber(): int
    {
        return (int) (static::query()->max('id') ?? 0) + 1;
    }

    public function isWarrantyActive(): bool
    {
        return $this->warranty_expires_at->greaterThanOrEqualTo(today());
    }

    public function warrantyLabel(): string
    {
        if (! $this->isWarrantyActive()) {
            return 'Expired';
        }

        $daysLeft = today()->diffInDays($this->warranty_expires_at, false);

        return $daysLeft <= 0 ? 'Active · Last day' : "Active · {$daysLeft}d left";
    }

    public function warrantyTone(): string
    {
        return $this->isWarrantyActive() ? 'teal' : 'slate';
    }

    public function paymentTone(): string
    {
        return match ($this->payment_method) {
            'GCash' => 'brand',
            'Cash' => 'teal',
            'Card' => 'violet',
            default => 'slate',
        };
    }
}
