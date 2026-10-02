<x-app-layout title="Settings">
    <x-page-header title="Settings" subtitle="Configure system preferences and security settings." />

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-sm font-semibold text-slate-700">Security Settings</h2>
                <p class="mt-0.5 text-xs text-slate-400">Add an extra layer of security to your account.</p>

                <div class="mt-5 space-y-5">
                    <x-toggle name="two_factor_enabled" :checked="$settings->two_factor_enabled" label="Two-Factor Authentication" description="Add an extra layer of security" />

                    <div>
                        <label for="session_timeout_minutes" class="block text-sm font-medium text-slate-700">Session Timeout</label>
                        <p class="text-xs text-slate-400">Auto logout after inactivity</p>
                        <select id="session_timeout_minutes" name="session_timeout_minutes"
                            class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @foreach ([10 => '10 minutes', 20 => '20 minutes', 30 => '30 minutes', 60 => '60 minutes'] as $value => $label)
                                <option value="{{ $value }}" @selected($settings->session_timeout_minutes === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (auth()->user()?->role === 'admin')
                        <div>
                            <label for="password_expiry_days" class="block text-sm font-medium text-slate-700">Password Expiry</label>
                            <p class="text-xs text-slate-400">Force password change</p>
                            <select id="password_expiry_days" name="password_expiry_days"
                                class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                @foreach ([30 => '30 days', 60 => '60 days', 90 => '90 days', 180 => '180 days'] as $value => $label)
                                    <option value="{{ $value }}" @selected($settings->password_expiry_days === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Notification Settings</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Choose what you want to be notified about.</p>

                    <div class="mt-5 space-y-5">
                        <x-toggle name="email_notifications" :checked="$settings->email_notifications" label="Email Notifications" description="Receive updates via email" />
                        <x-toggle name="job_status_updates" :checked="$settings->job_status_updates" label="Job Status Updates" description="Notify on job status changes" />
                        <x-toggle name="payment_reminders" :checked="$settings->payment_reminders" label="Payment Reminders" description="Alert for pending payments" />
                    </div>
                </div>

                @if (auth()->user()?->role === 'admin')
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">Data Management</h2>
                        <p class="mt-0.5 text-xs text-slate-400">Control backups and data retention.</p>

                        <div class="mt-5 space-y-5">
                            <div>
                                <label for="backup_frequency" class="block text-sm font-medium text-slate-700">Automated Backups</label>
                                <select id="backup_frequency" name="backup_frequency"
                                    class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                    @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                                        <option value="{{ $value }}" @selected($settings->backup_frequency === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="data_retention_period" class="block text-sm font-medium text-slate-700">Data Retention Period</label>
                                <select id="data_retention_period" name="data_retention_period"
                                    class="mt-2 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                    @foreach (['3_months' => '3 months', '6_months' => '6 months', '1_year' => '1 year', '2_years' => '2 years', 'indefinite' => 'Indefinite'] as $value => $label)
                                        <option value="{{ $value }}" @selected($settings->data_retention_period === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <a href="{{ route('settings.export') }}" class="block w-full rounded-lg border border-slate-200 px-4 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50">
                                Export All Data
                            </a>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h2 class="text-sm font-semibold text-slate-700">Sales Forecasting</h2>
                        <p class="mt-0.5 text-xs text-slate-400">Used to track progress toward your long-term revenue goal.</p>

                        <div class="mt-5">
                            <label for="six_year_sales_target" class="block text-sm font-medium text-slate-700">6-Year Sales Revenue Target</label>
                            <p class="text-xs text-slate-400">Total projected revenue goal over the next 6 years</p>
                            <div class="mt-2 flex items-center rounded-lg border border-slate-200 bg-slate-50 focus-within:border-brand-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-500/30">
                                <span class="pl-3.5 text-sm text-slate-400">PHP</span>
                                <input type="number" id="six_year_sales_target" name="six_year_sales_target" min="0" step="0.01"
                                    value="{{ old('six_year_sales_target', $settings->six_year_sales_target) }}"
                                    placeholder="Not set"
                                    class="w-full border-0 bg-transparent px-2 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-0">
                            </div>
                            @error('six_year_sales_target') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="submit" formaction="{{ route('settings.reset') }}"
                class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Reset to Default
            </button>
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                Save Changes
            </button>
        </div>
    </form>
</x-app-layout>
