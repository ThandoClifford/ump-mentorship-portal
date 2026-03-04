@extends('layouts.app')

@section('title', 'Mentor Portal | UMP Mentorship Portal')

@section('content')
    <x-ui.page-header title="Mentor Calendar" subtitle="Manage your mentorship sessions month by month.">
        <a
            href="{{ route('mentor.index', ['month' => $previousMonthValue]) }}"
            class="ump-focusable inline-flex items-center justify-center rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm font-medium text-[var(--ump-text-dark)] transition hover:bg-[var(--ump-page-gray)]"
        >
            Previous
        </a>
        <span class="rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm font-semibold text-[var(--ump-primary-navy)]">
            {{ $currentMonthLabel }}
        </span>
        <a
            href="{{ route('mentor.index', ['month' => $nextMonthValue]) }}"
            class="ump-focusable inline-flex items-center justify-center rounded-md border border-[var(--ump-border)] bg-white px-3 py-2 text-sm font-medium text-[var(--ump-text-dark)] transition hover:bg-[var(--ump-page-gray)]"
        >
            Next
        </a>
    </x-ui.page-header>

    <div class="space-y-4">
        <x-ui.card title="My Availability Status" subtitle="Control your own availability windows.">
            <div class="space-y-2 text-sm">
                @forelse (($mentorAvailabilities ?? collect()) as $availability)
                    <div class="rounded-md border border-[var(--ump-border)] p-3 sm:flex sm:items-center sm:justify-between sm:gap-3">
                        <div>
                            <p class="font-semibold text-[var(--ump-primary-navy)]">
                                {{ ucfirst($availability->day_of_week) }} · {{ \Carbon\Carbon::parse($availability->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($availability->end_time)->format('H:i') }}
                            </p>
                            <p class="mt-1 text-xs uppercase tracking-wide {{ $availability->is_active ? 'text-green-700' : 'text-slate-500' }}">
                                {{ $availability->is_active ? 'Available' : 'Unavailable' }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('mentor.availability.status', $availability->id) }}" class="mt-2 sm:mt-0">
                            @csrf
                            <input type="hidden" name="is_active" value="{{ $availability->is_active ? '0' : '1' }}">
                            <x-ui.button type="submit" variant="secondary" class="!px-3 !py-1.5 !text-xs">
                                {{ $availability->is_active ? 'Set Unavailable' : 'Set Available' }}
                            </x-ui.button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm ump-muted">No availability windows assigned yet. Contact admin to add time windows.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Upcoming Sessions" subtitle="Your next scheduled mentorship meetings.">
            <div class="space-y-2 text-sm">
                @forelse ($upcomingSessions as $session)
                    <div class="rounded-md border border-[var(--ump-border)] p-3">
                        <p class="font-semibold text-[var(--ump-primary-navy)]">
                            {{ optional($session->timeSlot)->date ? \Carbon\Carbon::parse($session->timeSlot->date)->format('D, d M Y') : '-' }}
                        </p>
                        <p class="mt-1">
                            {{ optional($session->timeSlot)->start_time ? \Carbon\Carbon::parse($session->timeSlot->start_time)->format('H:i') : '-' }}
                            -
                            {{ optional($session->timeSlot)->end_time ? \Carbon\Carbon::parse($session->timeSlot->end_time)->format('H:i') : '-' }}
                        </p>
                        <p class="mt-1">{{ optional($session->student)->name ?? 'Student' }}</p>
                        <p class="mt-1 text-xs ump-muted">Contact: {{ $session->student_contact_details ?: (optional($session->student)->email ?: 'Not provided') }}</p>
                        <p class="mt-1 text-xs ump-muted">Subject: {{ $session->appointment_subject ?: 'Not provided' }}</p>
                        <p class="mt-1 text-xs uppercase tracking-wide {{ $session->status === 'confirmed' ? 'text-green-700' : ($session->status === 'pending' ? 'text-amber-700' : 'text-sky-700') }}">
                            {{ ucfirst($session->status) }}
                        </p>
                        @if ($session->status === 'pending')
                            <div class="mt-2 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('mentor.appointments.confirm', $session->id) }}">
                                    @csrf
                                    <x-ui.button type="submit" class="!px-3 !py-1.5 !text-xs">Confirm</x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('mentor.appointments.decline', $session->id) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="destructive" class="!px-3 !py-1.5 !text-xs">Decline</x-ui.button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm ump-muted">No upcoming sessions found.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Session Calendar" subtitle="{{ $mentor->name }} · {{ $totalSessionsThisMonth }} sessions in {{ $currentMonthLabel }}.">
            <div class="mb-3 grid gap-2 sm:grid-cols-3">
                <div class="rounded-md border border-[var(--ump-border)] bg-[var(--ump-page-gray)] px-2.5 py-2 text-sm">
                    <p class="ump-muted">Confirmed</p>
                    <p class="text-base font-semibold text-[var(--ump-primary-navy)]">{{ $statusSummary['confirmed'] }}</p>
                </div>
                <div class="rounded-md border border-[var(--ump-border)] bg-[var(--ump-page-gray)] px-2.5 py-2 text-sm">
                    <p class="ump-muted">Pending</p>
                    <p class="text-base font-semibold text-[var(--ump-primary-navy)]">{{ $statusSummary['pending'] }}</p>
                </div>
                <div class="rounded-md border border-[var(--ump-border)] bg-[var(--ump-page-gray)] px-2.5 py-2 text-sm">
                    <p class="ump-muted">Rescheduled</p>
                    <p class="text-base font-semibold text-[var(--ump-primary-navy)]">{{ $statusSummary['rescheduled'] }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <div class="grid min-w-[700px] grid-cols-7 gap-1.5 text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">
                    <div class="px-2 py-1">Mon</div>
                    <div class="px-2 py-1">Tue</div>
                    <div class="px-2 py-1">Wed</div>
                    <div class="px-2 py-1">Thu</div>
                    <div class="px-2 py-1">Fri</div>
                    <div class="px-2 py-1">Sat</div>
                    <div class="px-2 py-1">Sun</div>
                </div>

                <div class="mt-1 grid min-w-[700px] grid-cols-7 gap-1.5">
                    @foreach ($calendarWeeks as $week)
                        @foreach ($week as $day)
                            <div class="rounded-md border p-1.5 text-xs {{ $day['inMonth'] ? 'border-[var(--ump-border)] bg-white' : 'border-[var(--ump-border)] bg-[var(--ump-page-gray)]/60 text-slate-500' }} {{ $day['isToday'] ? '!border-[var(--ump-accent-gold)] ring-1 ring-[var(--ump-accent-gold)]/40' : '' }}">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="font-semibold">{{ $day['date']->format('j') }}</span>
                                    @if ($day['sessions']->count() > 0)
                                        <span class="rounded-full bg-[var(--ump-primary-navy)] px-2 py-0.5 text-[10px] font-semibold text-white">
                                            {{ $day['sessions']->count() }}
                                        </span>
                                    @endif
                                </div>

                                <div class="space-y-1">
                                    @foreach ($day['sessions']->take(2) as $session)
                                        <div class="rounded border border-[var(--ump-border)] bg-[var(--ump-page-gray)] px-1.5 py-1">
                                            <p class="font-medium text-[var(--ump-primary-navy)]">
                                                {{ \Carbon\Carbon::parse($session->timeSlot->start_time)->format('H:i') }}
                                                -
                                                {{ \Carbon\Carbon::parse($session->timeSlot->end_time)->format('H:i') }}
                                            </p>
                                            <p class="truncate">{{ optional($session->student)->name ?? 'Student' }}</p>
                                        </div>
                                    @endforeach

                                    @if ($day['sessions']->count() > 2)
                                        <p class="ump-muted">+{{ $day['sessions']->count() - 2 }} more</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </x-ui.card>
    </div>
@endsection
