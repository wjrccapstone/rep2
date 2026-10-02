<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password · WJRC Computer Services</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="relative rounded-2xl bg-white p-8 shadow-xl">
                <a href="{{ route('login') }}" aria-label="Back to login"
                    class="absolute right-5 top-5 flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </a>

                <div class="flex flex-col items-center text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-900">
                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6 text-white" aria-hidden="true">
                            <path d="M12 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M6 19c0-2.76 2.69-5 6-5s6 2.24 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <h1 class="mt-4 text-xl font-semibold text-slate-800">Set a New Password</h1>
                    <p class="mt-1 text-sm text-slate-500">Your code has been verified. Choose a new password for your account.</p>
                </div>

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-600">New Password</label>
                        <input id="password" name="password" type="password" required autofocus
                            class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            placeholder="••••••••">
                        <p class="mt-1.5 text-xs text-slate-400">At least 11 characters, with uppercase, lowercase, a number, and 2 special characters (e.g. $ and ?).</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-600">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            placeholder="••••••••">
                    </div>

                    <button type="submit"
                        class="w-full rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500/40">
                        Reset Password
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-brand-700">&larr; Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
