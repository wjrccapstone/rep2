@php
    $allNavItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'home'],
        ['label' => 'Job Orders', 'route' => 'job-orders.index', 'active' => request()->routeIs('job-orders.*'), 'icon' => 'clipboard'],
        ['label' => 'Product Inventory', 'route' => 'products.index', 'active' => request()->routeIs('products.*'), 'icon' => 'box'],
        ['label' => 'Forecasting', 'route' => 'forecasting.index', 'active' => request()->routeIs('forecasting.*'), 'icon' => 'chart'],
        ['label' => 'User Management', 'route' => 'users.index', 'active' => request()->routeIs('users.*'), 'icon' => 'users'],
        ['label' => 'Settings', 'route' => 'settings.index', 'active' => request()->routeIs('settings.*'), 'icon' => 'settings'],
    ];

    $role = auth()->user()?->role;
    // Hidden from cashiers (technicians have their own filter below; admins see everything).
    $adminOnlyRoutes = ['dashboard', 'job-orders.index', 'forecasting.index', 'users.index'];

    $navItems = $role === 'technician'
        ? array_values(array_filter($allNavItems, fn ($item) => in_array($item['route'], ['job-orders.index', 'products.index', 'settings.index'], true)))
        : array_values(array_filter($allNavItems, fn ($item) => $role === 'admin' || ! in_array($item['route'], $adminOnlyRoutes, true)));

    $icons = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 11.204 3.045a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />',
        'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2-13a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2m2-1h4a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1Z" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 9 7.5l4 4 8-8M15 3.5h4.5V8M3 20.25h18" />',
        'box' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25M21 7.5v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />',
        'cart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.38.137.752.43.992l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.127c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a7.65 7.65 0 0 1 0-.255c.007-.38-.138-.752-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.127.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" />',
    ];
@endphp

<aside id="app-sidebar" class="no-print fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-brand-950 via-brand-900 to-brand-800 transition-transform duration-200 lg:translate-x-0">
    <div class="flex items-center gap-3 px-5 py-6">
        <div class="shrink-0 rounded-xl bg-white px-2.5 py-2 shadow-sm">
            <img src="{{ asset('images/wjrclogo-trimmed.png') }}" alt="WJRC Computer Services" class="h-9 w-auto">
        </div>
        <div class="leading-tight">
            <p class="text-lg font-bold text-white">WJRC</p>
            <p class="text-xs text-brand-100/70">Computer Services</p>
        </div>
    </div>

    <a href="{{ route('profile.edit') }}" class="mx-4 mb-4 flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5 ring-1 ring-white/10 hover:bg-white/10">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-500 text-sm font-semibold text-white">
            @if (auth()->user()?->avatarUrl())
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="h-full w-full object-cover">
            @else
                {{ auth()->user()?->initials() ?? 'A' }}
            @endif
        </div>
        <div class="min-w-0 flex-1 leading-tight">
            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? 'Admin User' }}</p>
            <p class="truncate text-xs text-brand-100/60">{{ auth()->user()?->position ?? auth()->user()?->roleLabel() ?? 'Admin' }}</p>
        </div>
        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); openChangePasswordModal()" title="Change password"
            class="shrink-0 rounded-lg p-1.5 text-brand-100/60 hover:bg-white/10 hover:text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
        </button>
    </a>

    <nav class="flex-1 space-y-1 px-3">
        @foreach ($navItems as $item)
            <a href="{{ route($item['route']) }}"
                class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                    {{ $item['active'] ? 'bg-brand-500 text-white shadow-sm' : 'text-brand-100/70 hover:bg-white/5 hover:text-white' }}">
                <svg class="h-5 w-5 shrink-0 {{ $item['active'] ? 'text-white' : 'text-brand-100/50 group-hover:text-white' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    {!! $icons[$item['icon']] !!}
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 px-3 py-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-brand-100/70 transition hover:bg-white/5 hover:text-white">
                <svg class="h-5 w-5 text-brand-100/50 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    {!! $icons['logout'] !!}
                </svg>
                Log out
            </button>
        </form>
    </div>
</aside>
<div id="sidebar-backdrop" class="no-print fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden" onclick="document.getElementById('app-sidebar').classList.add('-translate-x-full')"></div>

{{-- Change Password modal (available on every page, for every role) --}}
<div id="change-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" onclick="if (event.target === this) closeChangePasswordModal()">
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-brand-800 shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-6 py-4">
            <h2 class="text-base font-semibold text-white">Change Password</h2>
            <button type="button" onclick="closeChangePasswordModal()" class="text-white/60 hover:text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('password.change') }}" class="space-y-4 px-6 py-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-brand-100">Current Password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                    class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                @error('current_password') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cp-password" class="block text-sm font-medium text-brand-100">New Password</label>
                <input type="password" id="cp-password" name="password" required autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
                <p class="mt-1 text-xs text-brand-50">At least 11 characters, with uppercase, lowercase, a number, and 2 special characters (e.g. $ and ?).</p>
                @error('password') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cp-password-confirmation" class="block text-sm font-medium text-brand-100">Confirm New Password</label>
                <input type="password" id="cp-password-confirmation" name="password_confirmation" required autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-lg border border-brand-600 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-brand-400">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeChangePasswordModal()" class="rounded-lg bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-400">Save Password</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openChangePasswordModal() {
        const el = document.getElementById('change-password-modal');
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function closeChangePasswordModal() {
        const el = document.getElementById('change-password-modal');
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    @if ($errors->hasAny(['current_password', 'password']))
        document.addEventListener('DOMContentLoaded', openChangePasswordModal);
    @endif
</script>
