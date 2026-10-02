<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Code · WJRC Computer Services</title>
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
                            <path d="M4 6.5C4 5.67 4.67 5 5.5 5h13c.83 0 1.5.67 1.5 1.5v11c0 .83-.67 1.5-1.5 1.5h-13A1.5 1.5 0 0 1 4 17.5v-11Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="m5 6.5 7 6 7-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h1 class="mt-4 text-xl font-semibold text-slate-800">Check your email</h1>
                    <p class="mt-1 text-sm text-slate-500">Enter the 6-digit verification code we sent you.</p>
                </div>

                @if (session('status'))
                    <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-600">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.otp.verify') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="otp" class="block text-sm font-medium text-slate-600">Insert OTP Code sent to {{ $email }}</label>
                        <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                            class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-center text-lg tracking-[0.5em] text-slate-800 placeholder:text-slate-400 placeholder:tracking-normal placeholder:text-sm focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            placeholder="Enter your code">
                    </div>

                    <button type="submit"
                        class="w-full rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500/40">
                        Proceed
                    </button>
                </form>

                <form method="POST" action="{{ route('password.otp.resend') }}" class="mt-3 text-center">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 hover:text-brand-700">Didn't get a code? Resend</button>
                </form>

                <div class="mt-2 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-brand-700">&larr; Back to Login</a>
                </div>

                <div class="mt-6 rounded-lg border border-brand-100 bg-brand-50 px-4 py-3 text-xs text-brand-700">
                    Codes expire after 2 minutes. You have a limited number of attempts before you'll need to request a new one.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
