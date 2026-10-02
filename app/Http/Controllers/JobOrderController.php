<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\JobOrder;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobOrderController extends Controller
{
    public function index(Request $request)
    {
        $isTechnician = $request->user()->role === 'technician';
        $technicianId = $isTechnician ? Technician::where('user_id', $request->user()->id)->value('id') : null;

        $scope = fn ($query) => $isTechnician ? $query->where('technician_id', $technicianId) : $query;

        $tab = $request->query('tab') === 'archive' ? 'archive' : 'active';

        $query = $scope(
            JobOrder::with(['customer', 'technician.user', 'service', 'device'])
                ->withSum('sales as paid_total', 'amount_paid')
                ->when(
                    $tab === 'archive',
                    fn ($q) => $q->whereNotNull('archived_at'),
                    fn ($q) => $q->whereNull('archived_at'),
                )
        );

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('service', fn ($s) => $s->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('device', function ($d) use ($search) {
                        $d->where('device_brand', 'like', "%{$search}%")
                            ->orWhere('device_type', 'like', "%{$search}%")
                            ->orWhere('device_model', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, JobOrder::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        $jobOrders = $query->orderByDesc('planned_start_date')->orderByDesc('id')->paginate(15)->withQueryString();

        // These KPI tiles reflect the active pipeline — an archived job order (always
        // Completed) shouldn't keep inflating the Completed count forever.
        $activeScope = fn () => $scope(JobOrder::query())->whereNull('archived_at');
        $summary = [
            'total' => $activeScope()->count(),
            'pending' => $activeScope()->where('status', 'pending')->count(),
            'in_progress' => $activeScope()->where('status', 'in_progress')->count(),
            'completed' => $activeScope()->where('status', 'completed')->count(),
        ];

        // The bulk-archive button (admin-only) and its confirm dialog need the exact
        // set of rows that currently qualify — completed, paid, not yet archived.
        $archivableJobOrders = JobOrder::with('customer')
            ->whereNull('archived_at')
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->get()
            ->map(fn (JobOrder $jobOrder) => [
                'code' => $jobOrder->code,
                'customer' => $jobOrder->customer->name ?? '—',
            ]);

        return view('job-orders.index', [
            'jobOrders' => $jobOrders,
            'summary' => $summary,
            'tab' => $tab,
            'archivedCount' => JobOrder::whereNotNull('archived_at')->count(),
            'archivableJobOrders' => $archivableJobOrders,
            'filters' => [
                'q' => $search ?? '',
                'status' => $status ?? '',
            ],
            'customers' => $isTechnician ? collect() : Customer::orderBy('name')->get(),
            'technicians' => $isTechnician ? collect() : User::whereIn('role', ['technician', 'admin'])->orderBy('name')->get(),
            'nextCode' => $isTechnician ? null : JobOrder::nextCode(),
        ]);
    }

    public function create()
    {
        return redirect()->route('job-orders.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request, isCreate: true);

        $customerId = $this->resolveCustomer($request);
        $serviceId = Service::firstOrCreate(['name' => trim($validated['service'])])->id;
        $technicianId = $this->resolveTechnicianId($validated['technician_id'] ?? null);

        $jobOrder = JobOrder::create([
            ...Arr::except($validated, ['service', 'technician_id', 'device_brand', 'device_type', 'device_model']),
            // A new job order always starts pending — the create form doesn't ask, and
            // the server ignores whatever it's sent for this rather than trust the client.
            'status' => 'pending',
            'code' => JobOrder::nextCode(),
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'technician_id' => $technicianId,
            'planned_start_date' => now(),
        ]);

        $jobOrder->device()->create([
            'device_brand' => $validated['device_brand'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'device_model' => $validated['device_model'],
        ]);

        ActivityLog::record([
            'module' => 'job_orders',
            'action' => 'created',
            'reference' => $jobOrder->code,
            'reference_route' => 'job-orders.service-report',
            'reference_id' => $jobOrder->id,
            'title' => "{$jobOrder->service->name} for ".($jobOrder->customer->business_name ?: $jobOrder->customer->name),
        ]);

        return redirect()->route('job-orders.index')->with('status', "Job order {$jobOrder->code} created.");
    }

    public function edit(JobOrder $jobOrder)
    {
        return redirect()->route('job-orders.index');
    }

    public function update(Request $request, JobOrder $jobOrder): RedirectResponse
    {
        $validated = $this->validated($request);
        $before = $jobOrder->only(['status', 'payment_status']);

        $customerId = $this->resolveCustomer($request);
        $serviceId = Service::firstOrCreate(['name' => trim($validated['service'])])->id;
        $technicianId = $this->resolveTechnicianId($validated['technician_id'] ?? null);

        $jobOrder->update([
            ...Arr::except($validated, ['service', 'technician_id', 'device_brand', 'device_type', 'device_model']),
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'technician_id' => $technicianId,
        ]);

        $jobOrder->device()->updateOrCreate([], [
            'device_brand' => $validated['device_brand'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'device_model' => $validated['device_model'],
        ]);

        $this->syncArchivedOnStatusChange($jobOrder, $before['status']);
        $this->logJobOrderChange($jobOrder, $before);

        return redirect()->route('job-orders.index')->with('status', "Job order {$jobOrder->code} updated.");
    }

    public function destroy(JobOrder $jobOrder): RedirectResponse
    {
        $code = $jobOrder->code;
        $service = $jobOrder->service->name;
        $jobOrder->delete();

        ActivityLog::record([
            'module' => 'job_orders',
            'action' => 'deleted',
            'reference' => $code,
            'title' => "{$service} job order deleted",
        ]);

        return redirect()->route('job-orders.index')->with('status', "Job order {$code} deleted.");
    }

    /**
     * Archive every completed, fully-paid job order in one action — mirrors
     * ProductController::destroyAllArchived()'s empty-archive handling, but moves
     * rows to the Archive tab instead of deleting them.
     */
    public function archiveCompleted(): RedirectResponse
    {
        $jobOrders = JobOrder::whereNull('archived_at')
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->get();

        if ($jobOrders->isEmpty()) {
            return redirect()->route('job-orders.index')->with('status', 'No completed & paid job orders to archive at the moment.');
        }

        $count = $jobOrders->count();
        $codes = $jobOrders->pluck('code')->implode(', ');

        JobOrder::whereIn('id', $jobOrders->pluck('id'))->update(['archived_at' => now()]);

        ActivityLog::record([
            'module' => 'job_orders',
            'action' => 'archived',
            'reference' => 'ARCHIVE_COMPLETED',
            'title' => "{$count} completed ".Str::plural('job order', $count).' archived',
            'detail' => $codes,
        ]);

        return redirect()->route('job-orders.index', ['tab' => 'archive'])
            ->with('status', "{$count} ".Str::plural('job order', $count).' archived.');
    }

    public function restore(JobOrder $jobOrder): RedirectResponse
    {
        $jobOrder->update(['archived_at' => null]);

        ActivityLog::record([
            'module' => 'job_orders',
            'action' => 'restored',
            'reference' => $jobOrder->code,
            'title' => "{$jobOrder->code} restored to the active list",
        ]);

        return redirect()->route('job-orders.index')->with('status', "Job order {$jobOrder->code} restored.");
    }

    public function exportCsv(Request $request)
    {
        $tab = $request->query('tab') === 'archive' ? 'archive' : 'active';

        $jobOrders = JobOrder::with(['customer', 'service', 'device'])
            ->withSum('sales as paid_total', 'amount_paid')
            ->when(
                $tab === 'archive',
                fn ($q) => $q->whereNotNull('archived_at'),
                fn ($q) => $q->whereNull('archived_at'),
            )
            ->orderByDesc('planned_start_date')
            ->get();

        $filename = ($tab === 'archive' ? 'job-orders-archive' : 'job-orders').'-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($jobOrders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['code', 'customer', 'service', 'device', 'status', 'payment_status', 'cost', 'planned_start_date', 'due_date', 'archived_at']);
            foreach ($jobOrders as $jobOrder) {
                fputcsv($handle, [
                    $jobOrder->code,
                    $jobOrder->customer->name ?? '',
                    $jobOrder->service->name,
                    $jobOrder->device?->label() ?? '',
                    $jobOrder->status,
                    $jobOrder->payment_status,
                    $jobOrder->cost,
                    $jobOrder->planned_start_date?->format('Y-m-d'),
                    $jobOrder->due_date?->format('Y-m-d'),
                    $jobOrder->archived_at?->format('Y-m-d'),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function serviceReport(JobOrder $jobOrder)
    {
        $jobOrder->load(['customer', 'technician.user', 'service', 'device']);

        return view('job-orders.service-report', ['jobOrder' => $jobOrder]);
    }

    public function updateProgress(Request $request, JobOrder $jobOrder): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'technician' && $jobOrder->technician?->user_id !== $user->id) {
            abort(403);
        }

        $before = $jobOrder->only(['status', 'payment_status']);

        $validated = $request->validate([
            'status' => ['required', Rule::in(JobOrder::STATUSES)],
            // The total cost isn't known until a technician has diagnosed (or finished) the
            // work, so it's set here rather than on the create/edit form.
            'cost' => ['nullable', 'numeric', 'min:0'],
            'work_summary' => ['nullable', 'string'],
        ]);

        $jobOrder->update($validated);

        $this->syncArchivedOnStatusChange($jobOrder, $before['status']);
        $this->logJobOrderChange($jobOrder, $before);

        return redirect()->route('job-orders.index')->with('status', "Job order {$jobOrder->code} updated.");
    }

    /**
     * Cancelled job orders never sit in the active list — the moment a job order
     * becomes Cancelled it's archived immediately (no manual "Archive Completed"
     * click needed, unlike Completed+Paid). Editing it back off Cancelled restores
     * it just as automatically, so Archive never holds a stale non-cancelled row.
     */
    private function syncArchivedOnStatusChange(JobOrder $jobOrder, string $statusBefore): void
    {
        if ($statusBefore === $jobOrder->status) {
            return;
        }

        if ($jobOrder->status === 'cancelled') {
            $jobOrder->update(['archived_at' => now()]);
        } elseif ($statusBefore === 'cancelled') {
            $jobOrder->update(['archived_at' => null]);
        }
    }

    /**
     * Log a job order's status change specifically (a useful before/after fact), rather
     * than a generic "updated" row that hides what actually happened.
     */
    private function logJobOrderChange(JobOrder $jobOrder, array $before): void
    {
        $changes = [
            [
                'label' => 'Status',
                'before' => JobOrder::labelForStatus($before['status']),
                'after' => $jobOrder->statusLabel(),
                'changed' => $before['status'] !== $jobOrder->status,
            ],
            [
                'label' => 'Payment Status',
                'before' => JobOrder::labelForPaymentStatus($before['payment_status']),
                'after' => $jobOrder->paymentLabel(),
                'changed' => $before['payment_status'] !== $jobOrder->payment_status,
            ],
        ];

        if ($before['status'] !== $jobOrder->status) {
            ActivityLog::record([
                'module' => 'job_orders',
                'action' => 'status_changed',
                'reference' => $jobOrder->code,
                'reference_route' => 'job-orders.service-report',
                'reference_id' => $jobOrder->id,
                'title' => $jobOrder->service->name,
                'before_value' => JobOrder::labelForStatus($before['status']),
                'after_value' => $jobOrder->statusLabel(),
                'field_changes' => $changes,
            ]);

            return;
        }

        ActivityLog::record([
            'module' => 'job_orders',
            'action' => 'updated',
            'reference' => $jobOrder->code,
            'reference_route' => 'job-orders.service-report',
            'reference_id' => $jobOrder->id,
            'title' => "{$jobOrder->service->name} details updated",
            'field_changes' => $changes,
        ]);
    }

    private function validated(Request $request, bool $isCreate = false): array
    {
        // A brand-new job order can't already be "Paid" for work that hasn't started —
        // matches the create form, which hides that option — so it's excluded here too,
        // rather than trusting the client not to submit it directly.
        $allowedPaymentStatuses = $isCreate
            ? array_diff(JobOrder::PAYMENT_STATUSES, ['paid'])
            : JobOrder::PAYMENT_STATUSES;

        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            // Business name only applies to business customers — left blank for individuals.
            'business_name' => ['nullable', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'service' => ['required', 'string', 'max:255'],
            'device_brand' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', 'string', 'max:255'],
            'device_model' => ['required', 'string', 'max:255'],
            'serial_no' => ['required', 'string', 'max:255'],
            // On create the status is forced to "pending" in store(), so the form doesn't
            // send it and it isn't required here; on edit it's a real, required choice.
            'status' => [$isCreate ? 'sometimes' : 'required', Rule::in(JobOrder::STATUSES)],
            'payment_status' => ['required', Rule::in($allowedPaymentStatuses)],
            'due_date' => ['required', 'date'],
            'issue' => ['nullable', 'string'],
            'work_summary' => ['nullable', 'string'],
        ]);
    }

    /**
     * Find (by name) or create the customer, keeping their contact details in sync with
     * what was entered on this job order — a blank field never wipes an existing value.
     */
    private function resolveCustomer(Request $request): int
    {
        $name = trim((string) $request->input('customer_name'));

        $details = array_filter([
            'business_name' => trim((string) $request->input('business_name')),
            'contact_no' => trim((string) $request->input('contact_no')),
            'address' => trim((string) $request->input('address')),
        ], fn ($value) => $value !== '');

        $customer = Customer::firstOrNew(['name' => $name]);
        $customer->fill($details)->save();

        return $customer->id;
    }

    /**
     * The "Assign Technician" field posts a users.id (it lists technicians and
     * admins alike — see index()'s $technicians query). Resolve it to that user's
     * technician profile, creating one on first assignment.
     */
    private function resolveTechnicianId(?string $userId): ?int
    {
        if (! $userId) {
            return null;
        }

        return Technician::firstOrCreate(['user_id' => $userId])->id;
    }
}
