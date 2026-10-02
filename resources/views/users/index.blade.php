@php
    $roleOptions = [
        '' => 'All Roles',
        'admin' => 'Admin',
        'technician' => 'Technician',
        'cashier' => 'Cashier',
    ];
    $positionOptions = ['admin' => 'Admin', 'technician' => 'Technician', 'cashier' => 'Cashier'];
    $isEditReopen = $errors->any() && old('_method') === 'PUT';
@endphp
<x-app-layout title="User Management">
    <x-user-management-tabs active="users" :tab-counts="$tabCounts" :active-staff-count="$activeStaffCount" />

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18.5 11a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search by name or email"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            </div>
            <select name="role" onchange="this.form.submit()"
                class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-600 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-48">
                @foreach ($roleOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-100">Filter</button>
            @if ($filters['q'] || $filters['role'])
                <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-400 hover:text-slate-600">Reset</a>
            @endif
        </form>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-xs font-medium uppercase tracking-wide text-slate-400">
                        <th class="py-3 pr-3">User ID</th>
                        <th class="py-3 pr-3">Name</th>
                        <th class="py-3 pr-3">Email</th>
                        <th class="py-3 pr-3">Role</th>
                        <th class="py-3 pr-3">Status</th>
                        <th class="py-3 pr-3">Last Login</th>
                        <th class="py-3 pl-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/60">
                            <td class="py-3 pr-3 font-medium text-slate-700">{{ $user->userCode() }}</td>
                            <td class="py-3 pr-3 text-slate-600">{{ $user->name }}</td>
                            <td class="py-3 pr-3 text-slate-500">{{ $user->email }}</td>
                            <td class="py-3 pr-3"><x-badge :tone="$user->roleTone()">{{ $user->roleLabel() }}</x-badge></td>
                            <td class="py-3 pr-3">
                                @if ($user->status !== 'active')
                                    <x-badge tone="rose">Disabled</x-badge>
                                @elseif ($user->is_online)
                                    <x-badge tone="teal">Active</x-badge>
                                @else
                                    <x-badge tone="slate">Inactive</x-badge>
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                            <td class="py-3 pl-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" title="View details"
                                        onclick="openUserDetailsModal({{ Js::from([
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'username' => $user->username,
                                            'contact_number' => $user->contact_number,
                                            'position' => $user->roleLabel(),
                                            'status' => $user->status === 'active' ? 'Enabled' : 'Disabled',
                                            'role' => $user->role,
                                            'status_raw' => $user->status,
                                            'modules' => $user->modules ?? [],
                                        ]) }})"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </button>
                                    <button type="button" title="Edit"
                                        onclick="openUserEditModal({{ Js::from([
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'username' => $user->username,
                                            'contact_number' => $user->contact_number,
                                            'role' => $user->role,
                                            'status' => $user->status,
                                            'modules' => $user->modules ?? [],
                                        ]) }})"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-brand-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" /></svg>
                                    </button>
                                    @if (auth()->user()?->role === 'admin')
                                        <button type="button" title="Reset password"
                                            onclick="openUserResetPasswordModal({{ $user->id }}, {{ Js::from($user->name) }})"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-amber-50 hover:text-amber-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                                        </button>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                @if ($user->status === 'active')
                                                    <button type="submit" title="Disable account access" class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 1 0 5.636 5.636a9 9 0 0 0 12.728 12.728ZM18.364 18.364 5.636 5.636" /></svg>
                                                    </button>
                                                @else
                                                    <button type="submit" title="Enable account access" class="rounded-md p-1.5 text-slate-400 hover:bg-emerald-50 hover:text-emerald-600">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                                    </button>
                                                @endif
                                            </form>
                                        @endif
                                    @endif
                                    <button type="button" title="Delete"
                                        onclick="openUserDeleteModal({{ $user->id }}, {{ Js::from($user->name) }})"
                                        class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-sm text-slate-400">No users match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="mt-4 border-t border-slate-100 pt-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Add / Edit User modal --}}
    <div id="user-form-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('user-form-modal')">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between bg-brand-950 px-6 py-4">
                <h2 id="user-form-title" class="text-sm font-semibold uppercase tracking-wide text-white">Add User</h2>
                <button type="button" onclick="closeModal('user-form-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form id="user-form" method="POST" action="{{ route('users.store') }}" class="space-y-4 bg-brand-100 px-6 py-6">
                @csrf
                <input type="hidden" name="_method" id="user-form-method" value="{{ $isEditReopen ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_editing_id" value="{{ old('_editing_id') }}">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm text-slate-700">name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="role" class="block text-sm text-slate-700">position *</label>
                        <select id="role" name="role" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @foreach ($positionOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', 'cashier') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="email" class="block text-sm text-slate-700">email address *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contact_number" class="block text-sm text-slate-700">contact number</label>
                        <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>
                    <div>
                        <label for="username" class="block text-sm text-slate-700">username</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @error('username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="status" class="block text-sm text-slate-700">account access *</label>
                        <select id="status" name="status" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            <option value="active" @selected(old('status', 'active') === 'active')>Enabled</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Disabled</option>
                        </select>
                    </div>
                    <div>
                        <label for="password" id="user-password-label" class="block text-sm text-slate-700">password *</label>
                        <input type="password" id="password" name="password" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <p class="mt-1 text-xs text-slate-500">At least 11 characters, with uppercase, lowercase, a number, and 2 special characters (e.g. $ and ?).</p>
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm text-slate-700">confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>
                </div>

                @php $initialRole = old('role', 'cashier'); @endphp
                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Modules to Access</p>
                        <span class="text-xs font-medium text-brand-600">Set automatically by position</span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach (\App\Models\User::MODULES as $key => $label)
                            <label class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition select-none border-slate-200 bg-slate-50 text-slate-400 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-slate-800 has-[:checked]:font-semibold">
                                <input type="checkbox" value="{{ $key }}" tabindex="-1" aria-readonly="true" onclick="return false;"
                                    class="module-checkbox pointer-events-none h-4 w-4 shrink-0 rounded border-slate-300 accent-brand-600"
                                    @checked(in_array($key, \App\Models\User::modulesForRole($initialRole)))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" id="user-form-submit"
                    class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-brand-900">
                    Add User
                </button>
            </form>
        </div>
    </div>

    {{-- User Details modal --}}
    <div id="user-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('user-details-modal')">
        <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between bg-brand-950 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">User Details</h2>
                <button type="button" onclick="closeModal('user-details-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="space-y-3 bg-brand-100 px-6 py-6 text-sm">
                <p><span class="font-semibold uppercase text-slate-600">Name :</span> <span id="ud-name" class="text-slate-800"></span></p>
                <p><span class="font-semibold uppercase text-slate-600">Email :</span> <span id="ud-email" class="text-slate-800"></span></p>
                <p><span class="font-semibold uppercase text-slate-600">Contact Number :</span> <span id="ud-contact" class="text-slate-800"></span></p>
                <p><span class="font-semibold uppercase text-slate-600">Username :</span> <span id="ud-username" class="text-slate-800"></span></p>
                <p><span class="font-semibold uppercase text-slate-600">Position :</span> <span id="ud-position" class="text-slate-800"></span></p>
                <p><span class="font-semibold uppercase text-slate-600">Account Access :</span> <span id="ud-status" class="text-slate-800"></span></p>
                <p><span class="font-semibold uppercase text-slate-600">Password :</span> <span class="tracking-widest text-slate-800">**********</span></p>

                <button type="button" onclick="reopenAsEditFromDetails()"
                    class="mt-2 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-900">
                    Edit Details
                </button>
            </div>
        </div>
    </div>

    {{-- Delete User modal --}}
    <div id="user-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('user-delete-modal')">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-8.625 3.75h.008v.008h-.008v-.008Z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Delete user?</h3>
                    <p class="mt-1 text-sm text-slate-500">Are you sure you want to delete user <strong id="ud-delete-name" class="font-semibold text-slate-700"></strong>? This action cannot be undone.</p>
                </div>
            </div>
            <form id="user-delete-form" method="POST" class="mt-5 flex items-center justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeModal('user-delete-modal')" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Delete</button>
            </form>
        </div>
    </div>

    {{-- Reset Password modal --}}
    <div id="user-reset-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeModal('user-reset-password-modal')">
        <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-white shadow-2xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between bg-brand-950 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Reset Password</h2>
                <button type="button" onclick="closeModal('user-reset-password-modal')" class="text-white/60 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="user-reset-password-form" method="POST" class="space-y-4 bg-brand-100 px-6 py-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="reset_user_id" id="rp-user-id" value="{{ old('reset_user_id') }}">
                <input type="hidden" name="reset_user_name" id="rp-user-name" value="{{ old('reset_user_name') }}">

                <p class="text-sm text-slate-600">Set a new password for <strong id="rp-name" class="font-semibold text-slate-800"></strong>. They'll need to use it next time they log in.</p>

                <div>
                    <label for="rp-password" class="block text-sm text-slate-700">new password *</label>
                    <input type="password" id="rp-password" name="password" required
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <p class="mt-1 text-xs text-slate-500">At least 11 characters, with uppercase, lowercase, a number, and 2 special characters (e.g. $ and ?).</p>
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="rp-password-confirmation" class="block text-sm text-slate-700">confirm new password *</label>
                    <input type="password" id="rp-password-confirmation" name="password_confirmation" required
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-sm transition hover:bg-brand-900">
                    Reset Password
                </button>
            </form>
        </div>
    </div>

    <script>
        const USER_UPDATE_URL_TEMPLATE = "{{ route('users.update', ['user' => '__ID__']) }}";
        const USER_DESTROY_URL_TEMPLATE = "{{ route('users.destroy', ['user' => '__ID__']) }}";
        const USER_RESET_PASSWORD_URL_TEMPLATE = "{{ route('users.reset-password', ['user' => '__ID__']) }}";
        const USER_STORE_URL = "{{ route('users.store') }}";
        const ROLE_MODULES = @json(\App\Models\User::ROLE_MODULES);
        let lastViewedUser = null;

        // Reflect the position's module access in the (read-only) checkboxes.
        function syncModulesToRole() {
            const role = document.getElementById('role').value;
            const allowed = ROLE_MODULES[role] || ['dashboard'];
            document.querySelectorAll('.module-checkbox').forEach(cb => {
                cb.checked = allowed.includes(cb.value);
            });
        }

        function openModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('hidden');
            el.classList.add('flex');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.add('hidden');
            el.classList.remove('flex');
        }

        function resetUserForm() {
            const form = document.getElementById('user-form');
            form.reset();
            syncModulesToRole();
        }

        function openUserCreateModal() {
            resetUserForm();
            document.getElementById('user-form').action = USER_STORE_URL;
            document.getElementById('user-form-method').value = 'POST';
            document.getElementById('user-form-title').textContent = 'Add User';
            document.getElementById('user-form-submit').textContent = 'Add User';
            document.getElementById('user-password-label').textContent = 'password *';
            document.getElementById('password').required = true;
            openModal('user-form-modal');
        }

        function openUserEditModal(data) {
            resetUserForm();
            document.getElementById('user-form').action = USER_UPDATE_URL_TEMPLATE.replace('__ID__', data.id);
            document.getElementById('user-form-method').value = 'PUT';
            document.getElementById('user-form-title').textContent = 'Edit User Details';
            document.getElementById('user-form-submit').textContent = 'Confirm Changes';
            document.getElementById('user-password-label').textContent = 'password (leave blank to keep current)';
            document.getElementById('password').required = false;
            document.getElementById('name').value = data.name || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('username').value = data.username || '';
            document.getElementById('contact_number').value = data.contact_number || '';
            document.getElementById('role').value = data.role || 'cashier';
            document.getElementById('status').value = data.status || 'active';
            syncModulesToRole();
            openModal('user-form-modal');
        }

        function openUserDetailsModal(data) {
            lastViewedUser = data;
            document.getElementById('ud-name').textContent = data.name || '—';
            document.getElementById('ud-email').textContent = data.email || '—';
            document.getElementById('ud-contact').textContent = data.contact_number || '—';
            document.getElementById('ud-username').textContent = data.username || '—';
            document.getElementById('ud-position').textContent = data.position || '—';
            document.getElementById('ud-status').textContent = data.status || '—';
            openModal('user-details-modal');
        }

        function reopenAsEditFromDetails() {
            if (!lastViewedUser) return;
            closeModal('user-details-modal');
            openUserEditModal({
                id: lastViewedUser.id,
                name: lastViewedUser.name,
                email: lastViewedUser.email,
                username: lastViewedUser.username,
                contact_number: lastViewedUser.contact_number,
                role: lastViewedUser.role,
                status: lastViewedUser.status_raw,
                modules: lastViewedUser.modules,
            });
        }

        function openUserDeleteModal(id, name) {
            document.getElementById('user-delete-form').action = USER_DESTROY_URL_TEMPLATE.replace('__ID__', id);
            document.getElementById('ud-delete-name').textContent = name;
            openModal('user-delete-modal');
        }

        function openUserResetPasswordModal(id, name) {
            const form = document.getElementById('user-reset-password-form');
            form.reset();
            form.action = USER_RESET_PASSWORD_URL_TEMPLATE.replace('__ID__', id);
            document.getElementById('rp-user-id').value = id;
            document.getElementById('rp-user-name').value = name;
            document.getElementById('rp-name').textContent = name;
            openModal('user-reset-password-modal');
        }

        document.getElementById('role').addEventListener('change', syncModulesToRole);

        @if ($errors->any() && old('name') !== null)
            document.addEventListener('DOMContentLoaded', function () {
                @if ($isEditReopen)
                    document.getElementById('user-form').action = USER_UPDATE_URL_TEMPLATE.replace('__ID__', '{{ old('_editing_id') }}');
                    document.getElementById('user-form-title').textContent = 'Edit User Details';
                    document.getElementById('user-form-submit').textContent = 'Confirm Changes';
                    document.getElementById('user-password-label').textContent = 'password (leave blank to keep current)';
                    document.getElementById('password').required = false;
                @endif
                syncModulesToRole();
                openModal('user-form-modal');
            });
        @endif

        @if ($errors->has('password') && old('reset_user_id'))
            document.addEventListener('DOMContentLoaded', function () {
                openUserResetPasswordModal('{{ old('reset_user_id') }}', {{ Js::from(old('reset_user_name')) }});
            });
        @endif
    </script>
</x-app-layout>
