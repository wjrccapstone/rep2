<x-app-layout title="My Profile">
    <x-page-header title="My Profile" subtitle="Manage your personal and work details." />

    @if (session('profile_status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('profile_status') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Photo + identity card --}}
            <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-slate-100 lg:col-span-1">
                <div class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-brand-500 text-3xl font-semibold text-white ring-4 ring-brand-50"
                    id="avatar-preview-wrap">
                    @if ($user->avatarUrl())
                        <img id="avatar-preview" src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                    @else
                        <span id="avatar-preview-initial">{{ $user->initials() }}</span>
                        <img id="avatar-preview" src="" alt="{{ $user->name }}" class="hidden h-full w-full object-cover">
                    @endif
                </div>

                <div class="mt-4 flex flex-col items-center gap-2">
                    <label for="avatar" class="cursor-pointer rounded-lg border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Change Photo
                    </label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                    @error('avatar') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                    @if ($user->avatarUrl())
                        <label class="flex items-center gap-1.5 text-xs text-slate-400 hover:text-rose-600">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Remove photo
                        </label>
                    @endif
                </div>

                <div class="mt-5 border-t border-slate-100 pt-5">
                    <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $user->userCode() }}</p>
                    <x-badge :tone="$user->roleTone()">{{ $user->roleLabel() }}</x-badge>
                </div>
            </div>

            {{-- Editable details --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Personal Details</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Your name and contact information.</p>

                    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700">Full Name</label>
                            <input type="text" id="name" name="name" required value="{{ old('name', $user->name) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="username" class="block text-sm font-medium text-slate-700">Username</label>
                            <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @error('username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                            <input type="email" id="email" name="email" required value="{{ old('email', $user->email) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact_number" class="block text-sm font-medium text-slate-700">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}"
                                class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @error('contact_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700">Work Details</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Shown alongside your name across the system.</p>

                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="position" class="block text-sm font-medium text-slate-700">Job Title / Position</label>
                            <input type="text" id="position" name="position" value="{{ old('position', $user->position) }}"
                                placeholder="e.g. Senior Technician"
                                class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            @error('position') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bio" class="block text-sm font-medium text-slate-700">Short Bio</label>
                            <textarea id="bio" name="bio" rows="4" maxlength="1000" placeholder="A brief professional summary."
                                class="mt-1.5 block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-800 focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30">{{ old('bio', $user->bio) }}</textarea>
                            @error('bio') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="button" onclick="openChangePasswordModal()" class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                        Change Password
                    </button>
                    <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById('avatar-preview');
                const initial = document.getElementById('avatar-preview-initial');
                img.src = e.target.result;
                img.classList.remove('hidden');
                if (initial) initial.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    </script>
</x-app-layout>
