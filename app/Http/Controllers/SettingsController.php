<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', ['settings' => Setting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'two_factor_enabled' => ['nullable', 'boolean'],
            'session_timeout_minutes' => ['required', 'integer', Rule::in([10, 20, 30, 60])],
            // Password Expiry, Automated Backups, and Data Retention are admin-only fields in
            // the view — "sometimes" so a technician/cashier submission (where they're absent)
            // neither fails validation nor null-overwrites the admin-configured value below.
            'password_expiry_days' => ['sometimes', 'required', 'integer', Rule::in([30, 60, 90, 180])],
            'email_notifications' => ['nullable', 'boolean'],
            'job_status_updates' => ['nullable', 'boolean'],
            'payment_reminders' => ['nullable', 'boolean'],
            'backup_frequency' => ['sometimes', 'required', Rule::in(['daily', 'weekly', 'monthly'])],
            'data_retention_period' => ['sometimes', 'required', Rule::in(['3_months', '6_months', '1_year', '2_years', 'indefinite'])],
            'six_year_sales_target' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['two_factor_enabled'] = $request->boolean('two_factor_enabled');
        $validated['email_notifications'] = $request->boolean('email_notifications');
        $validated['job_status_updates'] = $request->boolean('job_status_updates');
        $validated['payment_reminders'] = $request->boolean('payment_reminders');

        // The sales-target field isn't shown to non-admins, so it's absent from their form
        // submissions — don't let that silently null out an admin-configured target.
        if ($request->user()?->role !== 'admin') {
            unset($validated['six_year_sales_target']);
        }

        Setting::current()->update($validated);

        return redirect()->route('settings.index')->with('status', 'Settings saved.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $defaults = [
            'two_factor_enabled' => true,
            'session_timeout_minutes' => 20,
            'email_notifications' => true,
            'job_status_updates' => true,
            'payment_reminders' => true,
        ];

        // Password Expiry, Automated Backups, and Data Retention are admin-only fields in
        // the view — don't let a technician/cashier reset touch settings they can't see.
        if ($request->user()?->role === 'admin') {
            $defaults['password_expiry_days'] = 30;
            $defaults['backup_frequency'] = 'daily';
            $defaults['data_retention_period'] = '6_months';
        }

        Setting::current()->update($defaults);

        return redirect()->route('settings.index')->with('status', 'Settings reset to default.');
    }

    public function export()
    {
        $rows = JobOrder::with(['customer', 'service', 'device'])->orderBy('id')->get();

        $csv = "Job Order ID,Customer,Service,Device,Status,Payment Status,Planned Start,Due Date,Cost\n";
        foreach ($rows as $row) {
            $csv .= implode(',', [
                $row->code,
                '"'.str_replace('"', '""', $row->customer->name ?? '').'"',
                '"'.str_replace('"', '""', $row->service->name).'"',
                '"'.str_replace('"', '""', $row->device?->label() ?? '').'"',
                $row->statusLabel(),
                $row->paymentLabel(),
                $row->planned_start_date?->format('Y-m-d'),
                $row->due_date?->format('Y-m-d'),
                $row->cost,
            ])."\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="wjrc-job-orders-'.now()->format('Y-m-d').'.csv"',
        ]);
    }
}
