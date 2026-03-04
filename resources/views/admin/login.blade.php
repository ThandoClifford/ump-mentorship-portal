<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | UMP Mentorship Portal</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--ump-page-gray)] font-sans text-[var(--ump-text-dark)]">
    <header class="border-b border-[var(--ump-border)] bg-[var(--ump-primary-navy)]">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-3">
            <p class="text-sm font-semibold text-[var(--ump-white)]">UMP UMPCFERI Mentorship Portal</p>
            <a href="{{ route('dashboard') }}" class="ump-btn ump-btn-primary ump-focusable px-3 py-2 text-sm">
                Back to Home
            </a>
        </div>
    </header>

    <div class="mx-auto flex min-h-screen w-full max-w-7xl items-center justify-center px-4 py-10">
        <section class="ump-card w-full max-w-md p-6 sm:p-8">
            <div class="mb-5 text-center">
                <div class="mb-3 flex justify-center">
                    @if (file_exists(public_path('images/ump-logo.png')))
                        <img
                            src="{{ asset('images/ump-logo.png') }}"
                            alt="University of Mpumalanga"
                            class="h-14 w-auto"
                        >
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-full border border-[var(--ump-border)] bg-white text-xs font-semibold text-[var(--ump-primary-navy)]">UMP</div>
                    @endif
                </div>
                <p class="text-xl font-semibold text-[var(--ump-primary-navy)]">UMPCFERI Admin Access</p>
                <p class="mt-1 text-sm ump-muted">Sign in to the University of Mpumalanga UMPCFERI Mentorship Portal.</p>
            </div>

            @if (session('status'))
                <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="grid gap-4">
                @csrf
                <div>
                    <label for="email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label for="password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Password</label>
                    <input id="password" name="password" type="password" class="ump-focusable w-full rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm" required>
                </div>
                <x-ui.button type="submit" class="w-full">Sign In</x-ui.button>
            </form>
        </section>
    </div>
</body>
</html>
