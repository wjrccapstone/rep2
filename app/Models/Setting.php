<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'two_factor_enabled',
        'session_timeout_minutes',
        'password_expiry_days',
        'email_notifications',
        'job_status_updates',
        'payment_reminders',
        'backup_frequency',
        'data_retention_period',
        'six_year_sales_target',
    ];

    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'email_notifications' => 'boolean',
            'job_status_updates' => 'boolean',
            'payment_reminders' => 'boolean',
            'six_year_sales_target' => 'float',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
