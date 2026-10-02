<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    public const AVAILABILITIES = [
        'in_stock' => 'In Stock',
        'out_of_stock' => 'Out of Stock',
        'low_stock' => 'Low Stock',
        'on_order' => 'On Order',
    ];

    public const STATUSES = ['active', 'inactive'];

    /**
     * Stock at or below this level (but above zero) is treated as "Low Stock".
     */
    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'code',
        'name',
        'image_path',
        'category_id',
        'price',
        'stock',
        'on_order',
        'availability',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'on_order' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Availability is always derived from stock (and the "on order" flag),
        // never set by hand. Keep it in sync on every insert/update, including
        // stock changes made through increment()/decrement() during a sale.
        static::saving(function (Product $product): void {
            $product->availability = static::availabilityFor(
                (int) $product->stock,
                (bool) $product->on_order,
            );
        });
    }

    /**
     * Resolve the availability label key from a stock count.
     */
    public static function availabilityFor(int $stock, bool $onOrder = false): string
    {
        if ($stock <= 0) {
            return $onOrder ? 'on_order' : 'out_of_stock';
        }

        if ($stock <= self::LOW_STOCK_THRESHOLD) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public static function nextCode(): string
    {
        $last = static::query()->withoutGlobalScopes()->latest('id')->value('id') ?? 0;

        return sprintf('PI-%03d', $last + 1);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? route('media.show', ['path' => $this->image_path]) : null;
    }

    public function availabilityLabel(): string
    {
        return self::AVAILABILITIES[$this->availability] ?? ucfirst($this->availability);
    }

    public function availabilityTone(): string
    {
        return match ($this->availability) {
            'in_stock' => 'teal',
            'out_of_stock' => 'rose',
            'low_stock' => 'amber',
            'on_order' => 'indigo',
            default => 'slate',
        };
    }

    public function statusTone(): string
    {
        return $this->status === 'active' ? 'teal' : 'rose';
    }
}
