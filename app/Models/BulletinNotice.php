<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BulletinNotice extends Model
{
    protected $fillable = [
        'tab', 'title', 'message', 'reference', 'is_important',
        'priority', 'category', 'audience', 'is_pinned', 'requires_ack',
        'remove_on', 'archived_at', 'posted_by',
    ];

    protected $casts = [
        'is_important' => 'boolean',
        'is_pinned'    => 'boolean',
        'requires_ack' => 'boolean',
        'remove_on'    => 'date',
        'archived_at'  => 'datetime',
    ];

    public const PRIORITIES = [
        'urgent'    => 'Urgent',
        'important' => 'Important',
        'info'      => 'Info',
    ];

    public const CATEGORIES = [
        'announcement'  => 'Announcement',
        'work_process'  => 'Work process',
        'schedule'      => 'Schedule',
        'system_update' => 'System update',
        'policy'        => 'Policy',
    ];

    public const AUDIENCES = [
        'all'        => 'All staff',
        'technician' => 'Technicians',
        'cashier'    => 'Cashiers',
        'admin'      => 'Admins',
    ];

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reads()
    {
        return $this->hasMany(BulletinRead::class);
    }

    /** Not archived and not past its "show until" date. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at')
            ->where(fn ($q) => $q->whereNull('remove_on')->orWhereDate('remove_on', '>=', today()));
    }

    /** Admins see everything; other staff see "All staff" + their own role. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->whereIn('audience', ['all', $user->role]);
    }
}