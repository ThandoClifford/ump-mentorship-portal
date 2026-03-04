@extends('layouts.app')

@section('title', 'Student Portal | UMP Mentorship Portal')

@section('content')
    <x-ui.page-header title="Student Portal" subtitle="View mentors, check availability, and book a mentorship slot." />

    @if (session('status'))
        <x-ui.card class="border-green-200 bg-green-50">
            <p class="text-sm font-medium text-green-800">{{ session('status') }}</p>
        </x-ui.card>
    @endif

    @if ($errors->any())
        <x-ui.card class="border-red-200 bg-red-50">
            <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <div class="grid gap-4 lg:grid-cols-[280px,1fr]">
        <x-ui.card title="Mentors" subtitle="Select a mentor to view availability and slots.">
            <div class="space-y-2">
                @forelse ($mentors as $mentor)
                    @php
                        $isMentorAvailable = (bool) (($mentorLiveStatus ?? collect())->get($mentor->id, false));
                    @endphp
                    <a
                        href="{{ route('student.index', ['mentor_id' => $mentor->id]) }}"
                        class="ump-focusable block rounded-md border px-3 py-2 text-sm transition {{ $selectedMentorId === $mentor->id ? 'border-[var(--ump-accent-gold)] bg-[var(--ump-accent-gold)]/20 text-[var(--ump-text-dark)] font-semibold' : 'border-[var(--ump-border)] text-[var(--ump-text-dark)] hover:bg-[var(--ump-page-gray)]' }}"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p>{{ $mentor->name }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $isMentorAvailable ? 'bg-green-700 text-white' : 'border border-green-700 bg-white text-green-700' }}">
                                {{ $isMentorAvailable ? 'Available' : 'Unavailable' }}
                            </span>
                        </div>
                        <p class="text-xs ump-muted">Faculty: {{ $mentor->faculty ?: 'Not specified' }}</p>
                        <p class="text-xs ump-muted">{{ $mentor->email }}</p>
                    </a>
                @empty
                    <p class="text-sm ump-muted">No mentors found.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card id="browse-slots" title="Availability and Slots" subtitle="Selected mentor availability and available bookable slots.">
            @php
                $selectedMentor = $mentors->firstWhere('id', $selectedMentorId);
                $mentorAvailabilities = $selectedMentor ? $availabilitiesByMentor->get($selectedMentor->id, collect()) : collect();
                $mentorSlots = $selectedMentor ? $slotsByMentor->get($selectedMentor->id, collect()) : collect();
            @endphp

            @if (! $selectedMentor)
                <p class="text-sm ump-muted">Select a mentor from the list to view availability.</p>
            @else
                <div class="mb-3 rounded-md border border-[var(--ump-border)] bg-white px-3 py-3">
                    <div class="flex items-center gap-3">
                        @if (file_exists(public_path('images/ump-logo.png')))
                            <img
                                src="{{ asset('images/ump-logo.png') }}"
                                alt="University of Mpumalanga"
                                class="h-10 w-auto"
                            >
                        @else
                            <div class="flex h-9 w-9 items-center justify-center rounded-full border border-[var(--ump-border)] bg-[var(--ump-page-gray)] text-xs font-semibold text-[var(--ump-primary-navy)]">UMP</div>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-[var(--ump-primary-navy)]">UMPCFERI Mentor Session</p>
                            <p class="text-xs ump-muted">University of Mpumalanga UMPCFERI Mentorship Portal</p>
                        </div>
                    </div>
                </div>

                <div class="mb-3 rounded-md border border-[var(--ump-border)] bg-[var(--ump-page-gray)] px-3 py-2">
                    <p class="text-sm font-semibold text-[var(--ump-primary-navy)]">{{ $selectedMentor->name }}</p>
                    <p class="text-xs ump-muted">Faculty: {{ $selectedMentor->faculty ?: 'Not specified' }}</p>
                    <p class="text-xs ump-muted">{{ $selectedMentor->email }}</p>
                </div>

                <div class="mb-4">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Availability</p>
                    @if ($mentorAvailabilities->isEmpty())
                        <p class="text-sm ump-muted">No availability windows configured yet.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($mentorAvailabilities as $availability)
                                <span class="rounded-md bg-[var(--ump-page-gray)] px-2.5 py-1 text-xs text-[var(--ump-text-dark)]">
                                    {{ ucfirst($availability->day_of_week) }} · {{ \Carbon\Carbon::parse($availability->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($availability->end_time)->format('H:i') }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--ump-primary-navy)]">Available Slots</p>
                    @if ($mentorSlots->isEmpty())
                        <p class="text-sm ump-muted">No open slots available right now.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="ump-table min-w-[620px]">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Appointment Subject</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mentorSlots->take(8) as $slot)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($slot->date)->format('Y-m-d') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($slot->date)->format('l') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}</td>
                                            <td>
                                                <input
                                                    type="text"
                                                    name="appointment_subject"
                                                    value="{{ old('appointment_subject') }}"
                                                    placeholder="Brief subject"
                                                    class="ump-focusable w-full min-w-[180px] rounded-md border border-[var(--ump-border)] bg-white px-2 py-1.5 text-xs"
                                                    form="book-slot-{{ $slot->id }}"
                                                    maxlength="255"
                                                    required
                                                >
                                            </td>
                                            <td>
                                                <form id="book-slot-{{ $slot->id }}" method="POST" action="{{ route('student.book-slot') }}" class="inline-flex gap-2">
                                                    @csrf
                                                    <input type="hidden" name="time_slot_id" value="{{ $slot->id }}">
                                                    <input type="hidden" name="mentor_id" value="{{ $selectedMentorId }}">
                                                    <x-ui.button type="submit" class="!px-3 !py-1.5 !text-xs" :disabled="! $selectedStudentId">
                                                        Make Appointment
                                                    </x-ui.button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (! $selectedStudentId)
                            <p class="mt-2 text-xs text-amber-700">No student account is currently available for booking.</p>
                        @endif
                    @endif
                </div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card id="my-appointments" title="My Appointments" subtitle="Booked sessions for the selected student.">
        @if (! $selectedStudentId)
            <p class="text-sm ump-muted">No student account found to display appointments.</p>
        @else
            <div class="overflow-x-auto">
                <table class="ump-table min-w-[640px]">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Mentor</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appointments as $appointment)
                            <tr>
                                <td>{{ optional($appointment->timeSlot)->date ? \Carbon\Carbon::parse($appointment->timeSlot->date)->format('Y-m-d') : '-' }}</td>
                                <td>{{ optional($appointment->mentor)->name ?? '-' }}</td>
                                <td>
                                    @if ($appointment->timeSlot)
                                        {{ \Carbon\Carbon::parse($appointment->timeSlot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($appointment->timeSlot->end_time)->format('H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ ucfirst($appointment->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="ump-muted">No appointments found for this student.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
@endsection
