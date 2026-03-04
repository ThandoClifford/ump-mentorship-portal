<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | UMP Mentorship Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--ump-page-gray)] font-sans text-[var(--ump-text-dark)]">
    <header class="border-b border-[var(--ump-border)] bg-[var(--ump-primary-navy)]">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-3">
            <p class="text-sm font-semibold text-[var(--ump-white)]">UMP UMPCFERI Mentorship Portal</p>
            <a href="{{ route('login') }}" class="ump-btn ump-btn-primary ump-focusable px-3 py-2 text-sm">Back to Sign In</a>
        </div>
    </header>

    <div class="mx-auto flex min-h-screen w-full max-w-7xl items-center justify-center px-4 py-10">
        <section class="ump-card w-full max-w-md p-6 sm:p-8">
            <div class="mb-5 text-center">
                <p class="text-xl font-semibold text-[var(--ump-primary-navy)]">Create Account</p>
                <p class="mt-1 text-sm ump-muted">Register as a student or mentor.</p>
                <p class="mt-1 text-xs ump-muted">Mentor accounts require admin verification before mentor portal access.</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.submit') }}" class="grid gap-4">
                @csrf
                <div>
                    <label for="name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label for="email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label for="role" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Role</label>
                    <select id="role" name="role" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                        <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>Student</option>
                        <option value="mentor" {{ old('role') === 'mentor' ? 'selected' : '' }}>Mentor</option>
                    </select>
                </div>
                <div id="faculty_field_wrapper" class="{{ old('role') === 'mentor' ? '' : 'hidden' }}">
                    <label for="faculty" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Faculty</label>
                    <input id="faculty" name="faculty" type="text" value="{{ old('faculty') }}" placeholder="e.g. Faculty of Agriculture and Natural Sciences" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" {{ old('role') === 'mentor' ? 'required' : '' }}>
                    <p class="mt-1 text-xs ump-muted">Required for mentor signup.</p>
                </div>
                <div>
                    <label for="password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Password</label>
                    <input id="password" name="password" type="password" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                </div>
                <x-ui.button type="submit" class="w-full">Register</x-ui.button>
            </form>
        </section>
    </div>

    <script>
        (function () {
            const roleSelect = document.getElementById('role');
            const facultyWrapper = document.getElementById('faculty_field_wrapper');
            const facultyInput = document.getElementById('faculty');

            if (!roleSelect || !facultyWrapper || !facultyInput) {
                return;
            }

            const syncFacultyVisibility = () => {
                const isMentor = roleSelect.value === 'mentor';
                facultyWrapper.classList.toggle('hidden', !isMentor);
                facultyInput.required = isMentor;

                if (!isMentor) {
                    facultyInput.value = '';
                }
            };

            roleSelect.addEventListener('change', syncFacultyVisibility);
            syncFacultyVisibility();
        })();
    </script>
</body>
</html>
