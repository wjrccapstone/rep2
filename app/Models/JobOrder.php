<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled', 'for_pickup'];

    public const PAYMENT_STATUSES = ['partial', 'paid'];

    protected $fillable = [
        'code',
        'customer_id',
        'technician_id',
        'service_id',
        'serial_no',
        'status',
        'payment_status',
        'planned_start_date',
        'due_date',
        'cost',
        'issue',
        'work_summary',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_start_date' => 'date',
            'due_date' => 'date',
            'cost' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function device(): HasOne
    {
        return $this->hasOne(Device::class);
    }

    /**
     * Point-of-sale settlements recorded against this job order. A job order can be
     * billed in several partial payments, so this is a hasMany.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Parts issued against this job order (any status — pending approval, approved,
     * billed, or rejected).
     */
    public function partOuts(): HasMany
    {
        return $this->hasMany(PartOut::class);
    }

    /**
     * Total collected against this job order across all of its settlements.
     */
    public function amountPaid(): float
    {
        return (float) $this->sales()->sum('amount_paid');
    }

    public function balanceDue(): float
    {
        return max(0, round((float) $this->cost - $this->amountPaid(), 2));
    }

    /**
     * Recompute payment_status from what has actually been collected so far.
     */
    public function syncPaymentStatus(): void
    {
        $paid = $this->amountPaid();

        $this->payment_status = round($paid, 2) >= round((float) $this->cost, 2) ? 'paid' : 'partial';

        $this->save();
    }

    public static function nextCode(): string
    {
        $last = static::query()->latest('id')->value('id') ?? 0;

        return sprintf('JON-%03d', $last + 1);
    }

    public function statusLabel(): string
    {
        return static::labelForStatus($this->status);
    }

    public static function labelForStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'for_pickup' => 'For Pick-up',
            default => ucfirst($status),
        };
    }

    public function paymentLabel(): string
    {
        return static::labelForPaymentStatus($this->payment_status);
    }

    public static function labelForPaymentStatus(string $status): string
    {
        return match ($status) {
            'partial' => 'Partial',
            'paid' => 'Paid',
            default => ucfirst($status),
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'pending' => 'amber',
            'in_progress' => 'brand',
            'completed' => 'teal',
            'cancelled' => 'rose',
            'for_pickup' => 'indigo',
            default => 'slate',
        };
    }

    public function paymentTone(): string
    {
        return match ($this->payment_status) {
            'partial' => 'amber',
            'paid' => 'teal',
            default => 'slate',
        };
    }
}
