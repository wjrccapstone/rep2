<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public const MODULES = [
        'product_inventory' => 'Product Inventory',
        'point_of_sale' => 'Product Transaction',
        'parts_out' => 'Parts Out',
        'job_orders' => 'Job Orders',
        'user_accounts' => 'User accounts',
    ];

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'reference',
        'reference_route',
        'reference_id',
        'title',
        'detail',
        'before_value',
        'after_value',
        'field_changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'field_changes' => 'array',
        ];
    }

    /**
     * A permanent, human-facing id for this entry — shown in the detail modal so an
     * admin has something concrete to reference (e.g. in a dispute) beyond the row itself.
     */
    public function entryNo(): string
    {
        return 'LOG-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Write one log row. Captures the acting user and their IP automatically,
     * so every call site only needs to say *what* happened.
     */
    public static function record(array $attributes): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'ip_address' => request()?->ip(),
            ...$attributes,
        ]);
    }

    public function moduleLabel(): string
    {
        return self::MODULES[$this->module] ?? ucfirst(str_replace('_', ' ', $this->module));
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'added' => 'Added',
            'price_changed' => 'Price changed',
            'stock_adjusted' => 'Stock adjusted',
            'archived' => 'Archived',
            'restored' => 'Restored',
            'deleted' => 'Deleted',
            'imported' => 'Imported',
            'created' => 'Created',
            'updated' => 'Updated',
            'status_changed' => 'Status changed',
            'password_reset' => 'Password reset',
            'sale_recorded' => 'Sale recorded',
            'issued' => 'Parts issued',
            'billed' => 'Marked billed',
            'requested' => 'Requested',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    public function actionTone(): string
    {
        return match ($this->action) {
            'added', 'created', 'restored', 'sale_recorded', 'billed', 'approved' => 'teal',
            'price_changed', 'stock_adjusted', 'status_changed', 'updated', 'imported', 'issued', 'requested' => 'amber',
            'archived', 'deleted', 'rejected' => 'rose',
            'password_reset' => 'violet',
            default => 'slate',
        };
    }

    public function referenceUrl(): ?string
    {
        if (! $this->reference_route || ! $this->reference_id) {
            return null;
        }

        try {
            return route($this->reference_route, $this->reference_id);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The full payload the detail modal (openActivityLogModal) renders — built here so
     * every list that opens it (Activity Log, Password Reset Log) stays in sync.
     */
    public function modalPayload(): array
    {
        $staff = $this->staff;

        return [
            'title' => $this->title,
            'subheader' => collect([$this->reference, $this->actionLabel(), $this->created_at->format('F j, Y \a\t g:i A')])
                ->filter()
                ->implode(' · '),
            'entryNo' => $this->entryNo(),
            'staffDisplay' => $staff ? "{$staff->name} ({$staff->roleLabel()})" : 'Deleted user',
            'module' => $this->moduleLabel(),
            'reason' => $this->detail,
            'changes' => $this->field_changes ?? [],
        ];
    }
}
