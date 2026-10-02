<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends Controller
{
    private const OTP_TTL_MINUTES = 2;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    public function showEmailForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = $data['email'];
        $existing = DB::table('password_reset_otps')->where('email', $email)->first();

        if ($existing && $existing->created_at && now()->diffInSeconds($existing->created_at, true) < self::RESEND_COOLDOWN_SECONDS) {
            throw ValidationException::withMessages([
                'email' => 'Please wait a moment before requesting another code.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);

            DB::table('password_reset_otps')->updateOrInsert(
                ['email' => $email],
                [
                    'otp' => Hash::make($otp),
                    'attempts' => 0,
                    'verified' => false,
                    'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($email)->send(new PasswordResetOtpMail($otp, self::OTP_TTL_MINUTES));
                error_log('OTP_MAIL_DEBUG: sent OK to '.$email);
            } catch (TransportExceptionInterface $e) {
                Log::warning('Failed to send password reset OTP email.', ['email' => $email, 'error' => $e->getMessage()]);
                error_log('OTP_MAIL_DEBUG: FAILED for '.$email.' - '.get_class($e).' - '.$e->getMessage());
            }
        }

        $request->session()->put('password_reset.email', $email);

        return redirect()->route('password.otp')
            ->with('status', "If {$email} is registered with us, a verification code has been sent to it.");
    }

    public function showOtpForm(Request $request): RedirectResponse|View
    {
        $email = $request->session()->get('password_reset.email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-otp', ['email' => $this->maskEmail($email)]);
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset.email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return $this->sendOtp($request->merge(['email' => $email]));
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $email = $request->session()->get('password_reset.email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $record = DB::table('password_reset_otps')->where('email', $email)->first();

        if (! $record || now()->greaterThan($record->expires_at)) {
            throw ValidationException::withMessages([
                'otp' => 'This code is invalid or has expired. Please request a new one.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_otps')->where('email', $email)->delete();

            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($request->string('otp'), $record->otp)) {
            DB::table('password_reset_otps')->where('email', $email)->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => 'The code you entered is incorrect.',
            ]);
        }

        DB::table('password_reset_otps')->where('email', $email)->update(['verified' => true]);
        $request->session()->put('password_reset.verified', true);

        return redirect()->route('password.reset');
    }

    public function showResetForm(Request $request): RedirectResponse|View
    {
        if (! $request->session()->get('password_reset.email') || ! $request->session()->get('password_reset.verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset.email');

        if (! $email || ! $request->session()->get('password_reset.verified')) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', new StrongPassword()],
        ]);

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->forceFill(['password' => Hash::make($data['password'])])->save();
        }

        DB::table('password_reset_otps')->where('email', $email)->delete();
        $request->session()->forget(['password_reset.email', 'password_reset.verified']);

        return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.');
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email);

        $visible = Str::substr($name, 0, min(2, Str::length($name)));

        return $visible.str_repeat('*', max(Str::length($name) - Str::length($visible), 1)).'@'.$domain;
    }
}
