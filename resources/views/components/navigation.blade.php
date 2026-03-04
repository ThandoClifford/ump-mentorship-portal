@props(['current' => 'dashboard', 'portal' => null, 'adminUser' => null])

@php
    $showMainNav = in_array($portal, ['student', 'mentor', 'admin'], true);
    $portalUser = request()->attributes->get('portal_user');
@endphp

<header class="w-full bg-[var(--ump-white)] shadow-sm">
    <div class="ump-top-header">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 lg:px-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    @if (file_exists(public_path('images/ump-logo.png')))
                        <img
                            src="{{ asset('images/ump-logo.png') }}"
                            alt="University of Mpumalanga"
                            class="h-14 w-auto md:h-16"
                        >
                    @endif
                    <div>
                        <p class="text-lg font-semibold text-[var(--ump-primary-navy)]">University of Mpumalanga</p>
                        <p class="text-sm ump-muted">Creating Opportunities · UMP Mentorship Portal</p>
                    </div>
                </div>

            </div>

        </div>
    </div>

    @if ($showMainNav)
        <nav class="ump-main-nav">
            <div class="mx-auto max-w-7xl px-4 lg:px-8">
                <div class="flex flex-wrap items-center gap-1 py-2 text-sm">
                    @if ($portal === 'admin')
                        <details class="relative">
                            <summary class="ump-focusable flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-md border border-white/30 bg-white text-base font-semibold text-[var(--ump-primary-navy)]">
                                ☰
                            </summary>
                            <div class="absolute left-0 z-30 mt-2 w-56 rounded-lg border border-[var(--ump-border)] bg-white p-3 text-[var(--ump-text-dark)] shadow-lg">
                                <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Admin Modules</p>
                                <div class="space-y-1 text-sm">
                                    <a href="{{ route('admin.index') }}" class="ump-focusable block rounded-md px-2 py-2 hover:bg-[var(--ump-surface)]">Mentors</a>
                                    <a href="{{ route('admin.index') }}" class="ump-focusable block rounded-md px-2 py-2 hover:bg-[var(--ump-surface)]">Availability</a>
                                    <a href="{{ route('admin.index') }}" class="ump-focusable block rounded-md px-2 py-2 hover:bg-[var(--ump-surface)]">Slot Generation</a>
                                    <a href="{{ route('admin.index') }}" class="ump-focusable block rounded-md px-2 py-2 hover:bg-[var(--ump-surface)]">Reports</a>
                                    <a href="{{ route('admin.index') }}" class="ump-focusable block rounded-md px-2 py-2 hover:bg-[var(--ump-surface)]">Ops</a>
                                    <a href="{{ route('admin.index') }}" class="ump-focusable block rounded-md px-2 py-2 hover:bg-[var(--ump-surface)]">Alerts</a>
                                </div>
                            </div>
                        </details>
                    @endif

                    @if ($portal === 'student')
                        <a href="{{ route('student.index') }}#browse-slots" class="ump-focusable ump-nav-link rounded-md px-3 py-2 ump-nav-link-active">Browse Slots</a>
                        <span class="px-1 text-white/60">|</span>
                        <a href="{{ route('student.index') }}#my-appointments" class="ump-focusable ump-nav-link rounded-md px-3 py-2">My Appointments</a>
                    @elseif ($portal === 'mentor')
                        <a href="{{ route('mentor.index') }}" class="ump-focusable ump-nav-link rounded-md px-3 py-2 ump-nav-link-active">Mentor Portal</a>
                    @endif

                    @if (in_array($portal, ['admin', 'student', 'mentor'], true))
                        @php
                            $profileUser = $portal === 'admin' ? $adminUser : $portalUser;
                            $profileRole = $portal === 'admin'
                                ? (isset($adminUser)
                                    ? ($adminUser->role instanceof \App\Enums\UserRole ? $adminUser->role->value : (string) $adminUser->role)
                                    : 'admin')
                                : ($portal ?: 'user');
                            $logoutRoute = $portal === 'admin' ? route('admin.logout') : route('logout');
                        @endphp
                        <div class="ml-auto">
                            <details class="relative">
                                <summary class="ump-focusable flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full border border-white/30 bg-white text-xs font-semibold text-[var(--ump-primary-navy)]">
                                    {{ isset($profileUser) ? strtoupper(substr($profileUser->name, 0, 1)) : strtoupper(substr((string) $profileRole, 0, 1)) }}
                                </summary>
                                <div class="absolute right-0 z-30 mt-2 w-64 rounded-lg border border-[var(--ump-border)] bg-white p-4 text-[var(--ump-text-dark)] shadow-lg">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">{{ strtoupper((string) $profileRole) }} Profile</p>
                                    <div class="mt-2 space-y-1 text-sm">
                                        <p><span class="font-semibold text-[var(--ump-primary-navy)]">Name:</span> {{ $profileUser->name ?? 'Portal User' }}</p>
                                        <p><span class="font-semibold text-[var(--ump-primary-navy)]">Email:</span> {{ $profileUser->email ?? '-' }}</p>
                                        <p><span class="font-semibold text-[var(--ump-primary-navy)]">Role:</span> {{ strtoupper((string) $profileRole) }}</p>
                                    </div>

                                    <form method="POST" action="{{ $logoutRoute }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="ump-focusable inline-flex w-full items-center justify-center rounded-md bg-[var(--ump-surface)] px-3 py-2 text-sm font-medium text-[var(--ump-text-dark)] transition hover:bg-slate-200">Logout</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</header>
