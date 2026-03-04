@extends('layouts.app')

@section('title', 'UMPCFERI Mentorship Portal')

@section('content')
    <section class="ump-card overflow-hidden p-0">
        <div class="bg-[var(--ump-primary-navy)] px-6 py-12 text-white sm:px-10">
            @if (file_exists(public_path('images/ump-logo.png')))
                <img
                    src="{{ asset('images/ump-logo.png') }}"
                    alt="University of Mpumalanga"
                    class="mb-5 h-16 w-auto rounded bg-white/95 p-2"
                >
            @endif
            <p class="text-sm font-semibold uppercase tracking-wide text-[var(--ump-accent-gold)]">UMPCFERI</p>
            <h1 class="mt-2 max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">Welcome to the UMPCFERI Mentorship Portal</h1>
            <p class="mt-3 max-w-2xl text-sm text-white/85 sm:text-base">
                Connect students and mentors, manage availability, and oversee engagement outcomes from a single professional platform.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('student.index') }}" class="ump-btn ump-btn-primary">Student Portal</a>
                <a href="{{ route('mentor.index') }}" class="ump-btn ump-btn-secondary">Mentor Portal</a>
                <a href="{{ route('admin.index') }}" class="ump-btn border border-white/20 bg-transparent text-white hover:bg-white/10">Admin Portal</a>
            </div>
        </div>

        <div class="grid gap-4 bg-white p-6 sm:grid-cols-2 lg:grid-cols-3 sm:p-8">
            <div class="rounded-lg border border-[var(--ump-border)] p-4 transition hover:border-[var(--ump-primary-navy)] hover:bg-[var(--ump-page-gray)] hover:shadow-sm">
                <h2 class="text-base font-semibold text-[var(--ump-primary-navy)]">Student</h2>
                <p class="mt-2 text-sm ump-muted">Browse available slots and manage upcoming mentorship appointments.</p>
            </div>
            <div class="rounded-lg border border-[var(--ump-border)] p-4 transition hover:border-[var(--ump-primary-navy)] hover:bg-[var(--ump-page-gray)] hover:shadow-sm">
                <h2 class="text-base font-semibold text-[var(--ump-primary-navy)]">Mentor</h2>
                <p class="mt-2 text-sm ump-muted">Review schedules, capture notes, and support student progression.</p>
            </div>
            <div class="rounded-lg border border-[var(--ump-border)] p-4 transition hover:border-[var(--ump-primary-navy)] hover:bg-[var(--ump-page-gray)] hover:shadow-sm">
                <h2 class="text-base font-semibold text-[var(--ump-primary-navy)]">Admin</h2>
                <p class="mt-2 text-sm ump-muted">Manage mentors, availability, reports, operations, and system alerts.</p>
            </div>
        </div>
    </section>

    <div class="grid gap-4">
        <x-ui.card title="Announcements" subtitle="Latest admin notices.">
            <div class="space-y-3">
                @forelse (($announcements ?? []) as $announcement)
                    <div class="rounded-md border border-[var(--ump-border)] p-3">
                        <div class="mb-1 flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-[var(--ump-primary-navy)]">{{ $announcement['title'] }}</p>
                            <span class="text-xs ump-muted">{{ $announcement['type'] }}</span>
                        </div>
                        <p class="text-sm text-[var(--ump-text-dark)]">{{ $announcement['message'] }}</p>
                        <p class="mt-1 text-xs ump-muted">{{ $announcement['date'] }}</p>
                    </div>
                @empty
                    <p class="text-sm ump-muted">No announcements available.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>

    <x-ui.card title="Upcoming Centre Events" subtitle="Events that will be held at UMPCFERI.">
        <div class="overflow-x-auto">
            <table class="ump-table min-w-[760px]">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($centreEvents ?? []) as $event)
                        <tr>
                            <td>{{ $event['title'] }}</td>
                            <td>{{ $event['date'] }}</td>
                            <td>{{ $event['time'] }}</td>
                            <td>{{ $event['venue'] }}</td>
                            <td>{{ $event['category'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="ump-muted">No upcoming centre events yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card title="Support" subtitle="Need assistance with the portal?">
        <div class="space-y-2 text-sm">
            <p><span class="font-semibold text-[var(--ump-primary-navy)]">Email:</span> umpcferi-support@ump.ac.za</p>
            <p><span class="font-semibold text-[var(--ump-primary-navy)]">Office Hours:</span> Mon-Fri, 08:00-16:30</p>
            <a href="mailto:umpcferi-support@ump.ac.za" class="ump-btn ump-btn-secondary mt-2 inline-flex">Need help?</a>
        </div>
    </x-ui.card>
@endsection
