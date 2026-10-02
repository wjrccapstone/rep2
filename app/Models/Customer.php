<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_name',
        'contact_no',
        'email',
        'address',
    ];

    public function jobOrders(): HasMany
    {
        return $this->hasMany(JobOrder::class);
    }
}
