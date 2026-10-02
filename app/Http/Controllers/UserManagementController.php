<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            if (in_array($role, User::ROLES, true)) {
                $query->where('role', $role);
            }
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $onlineUserIds = $this->onlineUserIds();
        $users->getCollection()->each(function (User $user) use ($onlineUserIds) {
            $user->is_online = $onlineUserIds->contains($user->id);
        });

        return view('users.index', [
            'users' => $users,
            'filters' => [
                'q' => $search ?? '',
                'role' => $role ?? '',
            ],
            'tabCounts' => $this->tabCounts(),
            'activeStaffCount' => $onlineUserIds->count(),
        ]);
    }

    /**
     * IDs of users with a session that's still within the configured session lifetime —
     * this is what "currently logged in" means, and it updates on every authenticated
     * request (not just login), so it also self-corrects when someone closes the browser
     * without clicking logout instead of relying solely on login/logout hooks.
     */
    private function onlineUserIds(): Collection
    {
        $threshold = now()->subMinutes((int) config('session.lifetime'))->timestamp;

        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $threshold)
            ->pluck('user_id')
            ->unique();
    }

    public function create()
    {
        return redirect()->route('users.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(User::ROLES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
            'modules' => User::modulesForRole($validated['role']),
        ]);

        ActivityLog::record([
            'module' => 'user_accounts',
            'action' => 'created',
            'reference' => $user->userCode(),
            'title' => "{$user->name} ({$user->roleLabel()})",
        ]);

        return redirect()->route('users.index')->with('status', 'User account created.');
    }

    public function edit(User $user)
    {
        return redirect()->route('users.index');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(User::ROLES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'confirmed', new StrongPassword()],
        ]);

        $before = $user->only(['role', 'status']);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'],
            'modules' => User::modulesForRole($validated['role']),
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $this->logUserChange($user, $before);

        return redirect()->route('users.index')->with('status', 'User account updated.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        ActivityLog::record([
            'module' => 'user_accounts',
            'action' => 'password_reset',
            'reference' => $user->userCode(),
            'title' => "Password reset for {$user->name}",
        ]);

        return redirect()->route('users.index')->with('status', "Password reset for {$user->name}.");
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'You cannot disable your own account.');
        }

        $before = $user->only(['role', 'status']);
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $this->logUserChange($user, $before);

        return redirect()->route('users.index')
            ->with('status', "{$user->name}'s account ".($user->status === 'active' ? 'enabled.' : 'disabled.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        $code = $user->userCode();
        $name = $user->name;
        $user->delete();

        ActivityLog::record([
            'module' => 'user_accounts',
            'action' => 'deleted',
            'reference' => $code,
            'title' => "{$name}'s account deleted",
        ]);

        return redirect()->route('users.index')->with('status', 'User account deleted.');
    }

    /**
     * The full record of changes made across the system — Product Inventory, Point of
     * Sale, Job Orders, and User accounts — filterable by staff, module, date, and search.
     */
    public function activityLog(Request $request)
    {
        return view('users.activity-log', [
            'logs' => $this->filteredLogs($request, excludeAction: null)->paginate(15)->withQueryString(),
            'filters' => $this->logFilters($request),
            'staffOptions' => User::orderBy('name')->get(['id', 'name']),
            'moduleOptions' => ActivityLog::MODULES,
            'tabCounts' => $this->tabCounts(),
            'activeStaffCount' => $this->onlineUserIds()->count(),
        ]);
    }

    /**
     * Just the password-reset trail — a focused view of the same log, since "who reset
     * whose password and when" is the one thing admins check on its own most often.
     */
    public function passwordResetLog(Request $request)
    {
        return view('users.password-reset-log', [
            'logs' => $this->filteredLogs($request, module: 'user_accounts', action: 'password_reset')->paginate(15)->withQueryString(),
            'filters' => $this->logFilters($request),
            'staffOptions' => User::orderBy('name')->get(['id', 'name']),
            'tabCounts' => $this->tabCounts(),
            'activeStaffCount' => $this->onlineUserIds()->count(),
        ]);
    }

    public function exportActivityLog(Request $request)
    {
        $isPasswordReset = $request->query('type') === 'password-reset';

        $logs = $isPasswordReset
            ? $this->filteredLogs($request, module: 'user_accounts', action: 'password_reset')->get()
            : $this->filteredLogs($request)->get();

        $filename = ($isPasswordReset ? 'password-reset-log' : 'activity-log').'-'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['date_time', 'staff', 'role', 'module', 'action', 'reference', 'title', 'detail', 'before', 'after']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->staff->name ?? 'System',
                    $log->staff?->roleLabel() ?? '',
                    $log->moduleLabel(),
                    $log->actionLabel(),
                    $log->reference,
                    $log->title,
                    $log->detail,
                    $log->before_value,
                    $log->after_value,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Shared query builder behind both log views and the CSV export, so the three
     * always agree on what "matches the current filters" means.
     */
    private function filteredLogs(Request $request, ?string $module = null, ?string $action = null, ?string $excludeAction = 'password_reset')
    {
        $query = ActivityLog::query()->with('staff')->latest('created_at');

        if ($module) {
            $query->where('module', $module);
        } elseif ($category = $request->query('module')) {
            if (array_key_exists($category, ActivityLog::MODULES)) {
                $query->where('module', $category);
            }
        }

        if ($action) {
            $query->where('action', $action);
        } elseif ($excludeAction) {
            // The general Activity Log doesn't duplicate what the Password Reset Log already shows.
            $query->where('action', '!=', $excludeAction);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhereHas('staff', fn ($s) => $s->where('name', 'like', "%{$search}%"));
            });
        }

        if ($staffId = $request->query('staff')) {
            $query->where('user_id', $staffId);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    private function logFilters(Request $request): array
    {
        return [
            'q' => $request->query('q', ''),
            'module' => $request->query('module', ''),
            'staff' => $request->query('staff', ''),
            'from' => $request->query('from', Carbon::now()->format('Y-m-d')),
            'to' => $request->query('to', Carbon::now()->format('Y-m-d')),
        ];
    }

    private function tabCounts(): array
    {
        return [
            'users' => User::count(),
            'password_resets' => ActivityLog::where('action', 'password_reset')->count(),
            'activity' => ActivityLog::where('action', '!=', 'password_reset')->count(),
        ];
    }

    /**
     * Log a user account's role/status change specifically, rather than one opaque
     * "updated" row — mirrors how product price/stock changes are logged separately.
     */
    private function logUserChange(User $user, array $before): void
    {
        $logged = false;
        $changes = [
            [
                'label' => 'Role',
                'before' => ucfirst($before['role']),
                'after' => $user->roleLabel(),
                'changed' => $before['role'] !== $user->role,
            ],
            [
                'label' => 'Status',
                'before' => ucfirst($before['status']),
                'after' => ucfirst($user->status),
                'changed' => $before['status'] !== $user->status,
            ],
        ];

        if ($before['role'] !== $user->role) {
            ActivityLog::record([
                'module' => 'user_accounts',
                'action' => 'updated',
                'reference' => $user->userCode(),
                'title' => "{$user->name}'s role changed",
                'before_value' => ucfirst($before['role']),
                'after_value' => $user->roleLabel(),
                'field_changes' => $changes,
            ]);
            $logged = true;
        }

        if ($before['status'] !== $user->status) {
            ActivityLog::record([
                'module' => 'user_accounts',
                'action' => 'status_changed',
                'reference' => $user->userCode(),
                'title' => "{$user->name}'s account ".($user->status === 'active' ? 'enabled' : 'disabled'),
                'before_value' => ucfirst($before['status']),
                'after_value' => ucfirst($user->status),
                'field_changes' => $changes,
            ]);
            $logged = true;
        }

        if (! $logged) {
            ActivityLog::record([
                'module' => 'user_accounts',
                'action' => 'updated',
                'reference' => $user->userCode(),
                'title' => "{$user->name}'s details updated",
                'field_changes' => $changes,
            ]);
        }
    }
}
