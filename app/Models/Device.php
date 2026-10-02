<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'device_brand',
        'device_type',
        'device_model',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    /**
     * Brand, type, and model as one display string — brand/type are blank on
     * job orders backfilled from the old free-text device field.
     */
    public function label(): string
    {
        return collect([$this->device_brand, $this->device_type, $this->device_model])
            ->filter(fn ($part) => filled($part))
            ->join(' ');
    }
}
