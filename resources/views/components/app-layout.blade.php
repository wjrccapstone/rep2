@props(['title' => 'Dashboard', 'dark' => false])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · WJRC Computer Services</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wjrclogo-trimmed.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-700">
    <div class="flex min-h-screen">
        <x-sidebar />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-64">
            <header class="no-print flex items-center justify-between border-b border-slate-200 bg-white/80 px-4 py-3 backdrop-blur lg:hidden">
                <button id="sidebar-toggle" type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="text-sm font-semibold text-brand-900">WJRC</span>
                <span class="w-9"></span>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 {{ $dark ? 'bg-gradient-to-br from-brand-950 via-brand-900 to-brand-600' : '' }}">
                @if (session('password_status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('password_status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            document.getElementById('app-sidebar')?.classList.toggle('-translate-x-full');
        });
    </script>

    @include('bulletin._modal')

</body>
</body>
</html>
