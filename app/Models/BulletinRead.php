<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulletinRead extends Model
{
    protected $fillable = [
        'bulletin_notice_id',
        'user_id',
        'read_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(BulletinNotice::class, 'bulletin_notice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}