<?php

namespace App\Services;

use App\Models\JobOrder;
use App\Models\PartOut;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class BulletinAlerts
{
    public function for(User $user): Collection
    {
        $alerts = collect();

        $overdueJobs = JobOrder::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->whereNull('archived_at')
            ->count();

        if ($overdueJobs > 0) {
            $alerts->push([
                'module' => 'jobs',
                'priority' => 'urgent',
                'title' => $overdueJobs . ' overdue job ' . ($overdueJobs === 1 ? 'order' : 'orders'),
                'meta' => 'Past due and still active',
                'action' => 'Review',
                'url' => route('job-orders.index'),
            ]);
        }

        $availableProducts = Product::query()
            ->where('status', 'active')
            ->whereNull('archived_at');
        $outOfStock = (clone $availableProducts)
            ->where('stock', '<=', 0)
            ->count();
        $lowStock = (clone $availableProducts)
            ->where('stock', '>', 0)
            ->where('stock', '<=', Product::LOW_STOCK_THRESHOLD)
            ->count();

        if ($outOfStock > 0) {
            $alerts->push([
                'module' => 'stock',
                'priority' => 'urgent',
                'title' => $outOfStock . ' product' . ($outOfStock === 1 ? '' : 's') . ' out of stock',
                'meta' => 'Unavailable until inventory is replenished',
                'action' => 'View inventory',
                'url' => route('products.index'),
            ]);
        }

        if ($lowStock > 0) {
            $alerts->push([
                'module' => 'stock',
                'priority' => 'important',
                'title' => $lowStock . ' product' . ($lowStock === 1 ? '' : 's') . ' running low',
                'meta' => 'At or below the restock threshold',
                'action' => 'View inventory',
                'url' => route('products.index'),
            ]);
        }

        if ($user->role === 'admin') {
            $pendingParts = PartOut::query()->where('status', 'pending_approval')->count();
            if ($pendingParts > 0) {
                $alerts->push([
                    'module' => 'parts',
                    'priority' => 'important',
                    'title' => $pendingParts . ' parts request' . ($pendingParts === 1 ? '' : 's') . ' need approval',
                    'meta' => 'Awaiting approval',
                    'action' => 'Review',
                    'url' => route('products.index', ['tab' => 'parts-out']),
                ]);
            }

            $pendingBilling = PartOut::query()->where('status', 'pending_billing')->count();
            if ($pendingBilling > 0) {
                $alerts->push([
                    'module' => 'parts',
                    'priority' => 'important',
                    'title' => $pendingBilling . ' Parts Out record' . ($pendingBilling === 1 ? '' : 's') . ' awaiting billing',
                    'meta' => 'Approved and waiting to be billed',
                    'action' => 'Review',
                    'url' => route('products.index', ['tab' => 'parts-out']),
                ]);
            }
        }

        return $alerts;
    }
}