<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartOut extends Model
{
    use HasFactory;

    public const STATUSES = ['pending_approval', 'pending_billing', 'billed', 'rejected'];

    protected $fillable = [
        'code',
        'job_order_id',
        'issued_by',
        'total_cost',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'total_cost' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PartOutItem::class);
    }

    public static function nextCode(): string
    {
        $last = static::query()->latest('id')->value('id') ?? 0;

        return sprintf('PO-%03d', $last + 1);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_approval' => 'Pending Approval',
            'billed' => 'Billed',
            'rejected' => 'Rejected',
            default => 'Pending Billing',
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'pending_approval' => 'amber',
            'billed' => 'teal',
            'rejected' => 'rose',
            default => 'indigo',
        };
    }
}
