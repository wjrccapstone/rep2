<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · WJRC Computer Services</title>
    <link rel="icon" type="image/png" href="{{ asset('images/wjrclogo-trimmed.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="rounded-2xl bg-white p-8 shadow-xl">
                <div class="flex flex-col items-center text-center">
                    <img src="{{ asset('images/wjrclogo-trimmed.png') }}" alt="WJRC Computer Services" class="h-16 w-auto">
                    <h1 class="mt-4 text-xl font-bold text-brand-900">WJRC Computer Services</h1>
                    <p class="mt-1 text-sm text-slate-400">Job Order System</p>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-600">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-600">Email</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            </span>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                placeholder="Enter Email">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-600">Password</label>
                        <div class="relative mt-1.5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25h-10.5a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            </span>
                            <input id="password" name="password" type="password" required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                placeholder="Enter password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-slate-500">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/40">
                            Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="font-medium italic text-brand-600 hover:text-brand-700">Forgot password?</a>
                    </div>

                    <button type="submit"
                        class="w-full rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500/40">
                        Sign In
                    </button>
                </form>

                <div class="mt-6 rounded-lg bg-brand-50 px-4 py-3 text-xs text-brand-700">
                    Demo access: <span class="font-medium">admin@wjrc.com</span> / <span class="font-medium">password</span>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-brand-100/60">&copy; {{ date('Y') }} WJRC Computer Services. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
