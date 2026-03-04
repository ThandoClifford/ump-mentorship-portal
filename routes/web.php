<?php

use App\Models\Appointment;
use App\Models\Announcement;
use App\Models\CentreEvent;
use App\Models\MentorAvailability;
use App\Models\TimeSlot;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

$defaultMentorAvailabilityWindows = [
    ['day_of_week' => 'tuesday', 'start_time' => '09:00', 'end_time' => '15:00'],
    ['day_of_week' => 'thursday', 'start_time' => '09:00', 'end_time' => '15:00'],
];

$syncMentorDefaultAvailabilities = function (int $mentorId) use ($defaultMentorAvailabilityWindows): void {
    $windowsByKey = collect($defaultMentorAvailabilityWindows)
        ->mapWithKeys(fn (array $window) => [
            strtolower($window['day_of_week']).'|'.$window['start_time'].'|'.$window['end_time'] => $window,
        ]);

    $existing = MentorAvailability::query()
        ->where('mentor_id', $mentorId)
        ->get(['id', 'day_of_week', 'start_time', 'end_time']);

    foreach ($existing as $availability) {
        $key = strtolower((string) $availability->day_of_week).'|'.substr((string) $availability->start_time, 0, 5).'|'.substr((string) $availability->end_time, 0, 5);

        if (! $windowsByKey->has($key)) {
            $availability->delete();
        }
    }

    foreach ($defaultMentorAvailabilityWindows as $window) {
        $record = MentorAvailability::query()
            ->where('mentor_id', $mentorId)
            ->where('day_of_week', $window['day_of_week'])
            ->where('start_time', $window['start_time'])
            ->where('end_time', $window['end_time'])
            ->first();

        if (! $record) {
            MentorAvailability::query()->create([
                'mentor_id' => $mentorId,
                'day_of_week' => $window['day_of_week'],
                'start_time' => $window['start_time'],
                'end_time' => $window['end_time'],
                'is_active' => true,
            ]);
        }
    }
};

$resolvePortalDestination = function (User $user, Request $request) {
    $role = $user->role instanceof UserRole
        ? $user->role->value
        : (string) $user->role;

    if ($role === 'student') {
        return redirect()->route('student.index');
    }

    if ($role === 'mentor') {
        $mentorVerificationEnabled = Schema::hasColumn('users', 'mentor_verified_at');

        if ($mentorVerificationEnabled && $user->mentor_verified_at === null) {
            $request->session()->forget(['portal_user_id', 'portal_selected_role']);

            return redirect()->route('login')->withErrors([
                'auth' => 'Your mentor profile is pending admin verification. Please wait for approval.',
            ]);
        }

        return redirect()->route('mentor.index');
    }

    $request->session()->forget(['portal_user_id', 'portal_selected_role']);

    return redirect()->route('login')->withErrors([
        'auth' => 'Only student and mentor accounts can continue here.',
    ]);
};

Route::get('/', function () {
    $today = now()->toDateString();
    $startOfWeek = now()->startOfWeek()->toDateString();
    $endOfWeek = now()->endOfWeek()->toDateString();

    $todayUpcomingSessions = Appointment::query()
        ->whereIn('status', ['confirmed', 'pending', 'rescheduled'])
        ->whereHas('timeSlot', function ($query) use ($today) {
            $query->whereDate('date', $today);
        })
        ->count();

    $availableSlotsThisWeek = TimeSlot::query()
        ->where('status', 'available')
        ->whereBetween('date', [$startOfWeek, $endOfWeek])
        ->count();

    $activeMentors = User::query()
        ->where('role', 'mentor')
        ->whereIn('id', function ($query) {
            $query->select('mentor_id')
                ->from('mentor_availabilities')
                ->where('is_active', true)
                ->distinct();
        })
        ->count();

    $totalUsers = User::query()->count();

    $defaultAnnouncements = [
        [
            'title' => 'Mentorship Week Registration Open',
            'message' => 'Students can now reserve mentorship week sessions through the Student Portal.',
            'date' => '2026-03-02',
            'type' => 'Campaign',
        ],
        [
            'title' => 'System Maintenance Notice',
            'message' => 'Portal maintenance is scheduled for Saturday 22:00-23:00. Booking remains available before and after maintenance.',
            'date' => '2026-03-01',
            'type' => 'Maintenance',
        ],
        [
            'title' => 'Monthly Progress Reporting Deadline',
            'message' => 'Mentors are reminded to submit session progress notes by Friday 17:00.',
            'date' => '2026-02-28',
            'type' => 'Deadline',
        ],
    ];

    $announcements = $defaultAnnouncements;
    if (Schema::hasTable('announcements')) {
        $dbAnnouncements = Announcement::query()
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['title', 'type', 'message', 'published_on'])
            ->map(function (Announcement $announcement): array {
                return [
                    'title' => $announcement->title,
                    'type' => $announcement->type,
                    'message' => $announcement->message,
                    'date' => optional($announcement->published_on)->format('Y-m-d') ?? '-',
                ];
            })
            ->all();

        if (! empty($dbAnnouncements)) {
            $announcements = $dbAnnouncements;
        }
    }

    $centreEvents = [
        [
            'title' => 'Mentorship Orientation Session',
            'date' => '2026-03-05',
            'time' => '10:00',
            'venue' => 'UMPCFERI Main Hall',
            'category' => 'Orientation',
        ],
        [
            'title' => 'CV & Career Readiness Workshop',
            'date' => '2026-03-07',
            'time' => '13:30',
            'venue' => 'Innovation Lab 2',
            'category' => 'Workshop',
        ],
        [
            'title' => 'Industry Mentor Networking Hour',
            'date' => '2026-03-12',
            'time' => '15:00',
            'venue' => 'UMPCFERI Collaboration Space',
            'category' => 'Networking',
        ],
    ];

    if (Schema::hasTable('centre_events')) {
        $dbCentreEvents = CentreEvent::query()
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->limit(20)
            ->get(['title', 'category', 'event_date', 'event_time', 'venue'])
            ->map(function (CentreEvent $event): array {
                return [
                    'title' => $event->title,
                    'date' => optional($event->event_date)->format('Y-m-d') ?? '-',
                    'time' => $event->event_time ? substr((string) $event->event_time, 0, 5) : '-',
                    'venue' => $event->venue,
                    'category' => $event->category,
                ];
            })
            ->all();

        if (! empty($dbCentreEvents)) {
            $centreEvents = $dbCentreEvents;
        }
    }

    return view('welcome', [
        'currentNav' => 'dashboard',
        'todayUpcomingSessions' => $todayUpcomingSessions,
        'availableSlotsThisWeek' => $availableSlotsThisWeek,
        'activeMentors' => $activeMentors,
        'totalUsers' => $totalUsers,
        'announcements' => $announcements,
        'centreEvents' => $centreEvents,
    ]);
});

Route::get('/dashboard', function () {
    return redirect('/');
})->name('dashboard');

Route::get('/login', function (Request $request) use ($resolvePortalDestination) {
    $adminUserId = (int) $request->session()->get('admin_user_id');
    if ($adminUserId) {
        return redirect()->route('admin.index');
    }

    $portalUserId = (int) $request->session()->get('portal_user_id');
    if ($portalUserId) {
        $user = User::query()->find($portalUserId);
        if ($user) {
            return $resolvePortalDestination($user, $request);
        }

        $request->session()->forget(['portal_user_id', 'portal_selected_role']);
    }

    return view('auth.login', [
        'currentNav' => 'dashboard',
    ]);
})->name('login');

Route::get('/logout', function (Request $request) {
    $request->session()->forget(['portal_user_id', 'portal_selected_role', 'admin_user_id']);
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login')->with('status', 'Signed out successfully.');
})->name('logout.get');

Route::post('/login', function (Request $request) use ($resolvePortalDestination) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::query()->where('email', $validated['email'])->first();

    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->onlyInput('email');
    }

    $role = $user->role instanceof UserRole
        ? $user->role->value
        : (string) $user->role;

    $request->session()->regenerate();

    if (in_array($role, ['admin', 'super_admin'], true)) {
        $request->session()->put('admin_user_id', (int) $user->id);
        $request->session()->forget(['portal_user_id', 'portal_selected_role']);

        return redirect()->route('admin.index');
    }

    $request->session()->put('portal_user_id', (int) $user->id);
    $request->session()->forget('portal_selected_role');

    return $resolvePortalDestination($user, $request);
})->name('login.attempt');

Route::get('/register', function () {
    return view('auth.register', [
        'currentNav' => 'dashboard',
    ]);
})->name('register');

Route::post('/register', function (Request $request) use ($resolvePortalDestination, $syncMentorDefaultAvailabilities) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:student,mentor'],
        'faculty' => ['nullable', 'string', 'max:255', 'required_if:role,mentor'],
    ]);

    $mentorVerificationEnabled = Schema::hasColumn('users', 'mentor_verified_at');

    $payload = [
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => $validated['role'],
        'faculty' => $validated['faculty'] ?? null,
    ];

    if ($mentorVerificationEnabled) {
        $payload['mentor_verified_at'] = $validated['role'] === 'mentor' ? null : now();
    }

    $user = User::query()->create($payload);

    $request->session()->regenerate();
    $request->session()->put('portal_user_id', (int) $user->id);
    $request->session()->forget('portal_selected_role');

    $role = $user->role instanceof UserRole
        ? $user->role->value
        : (string) $user->role;

    if ($role === 'mentor') {
        $syncMentorDefaultAvailabilities((int) $user->id);
    }

    return $resolvePortalDestination($user, $request)->with('status', 'Account created successfully.');
})->name('register.submit');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password', [
        'currentNav' => 'dashboard',
    ]);
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
    ]);

    $status = Password::sendResetLink($validated);

    if ($status !== Password::RESET_LINK_SENT) {
        return back()->withErrors([
            'email' => __($status),
        ])->onlyInput('email');
    }

    return back()->with('status', __($status));
})->name('password.email');

Route::get('/reset-password/{token}', function (string $token, Request $request) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => (string) $request->query('email', ''),
        'currentNav' => 'dashboard',
    ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $validated = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $status = Password::reset($validated, function (User $user, string $password): void {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();
    });

    if ($status !== Password::PASSWORD_RESET) {
        return back()->withErrors([
            'email' => __($status),
        ])->withInput($request->only('email'));
    }

    return redirect()->route('login')->with('status', 'Password reset successful. Please sign in.');
})->name('password.update');

Route::get('/auth/{provider}/redirect', function (string $provider) {
    if (! in_array($provider, ['google', 'github'], true)) {
        abort(404);
    }

    $clientId = config("services.{$provider}.client_id");
    $clientSecret = config("services.{$provider}.client_secret");
    $redirectUri = config("services.{$provider}.redirect");

    if (! $clientId || ! $clientSecret || ! $redirectUri) {
        return redirect()->route('login')->withErrors([
            'auth' => ucfirst($provider).' sign-in is not configured yet.',
        ]);
    }

    return Socialite::driver($provider)->redirect();
})->name('oauth.redirect');

Route::get('/auth/{provider}/callback', function (string $provider, Request $request) use ($resolvePortalDestination) {
    if (! in_array($provider, ['google', 'github'], true)) {
        abort(404);
    }

    try {
        $oauthUser = Socialite::driver($provider)->user();
    } catch (\Throwable $exception) {
        return redirect()->route('login')->withErrors([
            'auth' => ucfirst($provider).' login failed. Please try again.',
        ]);
    }

    $email = $oauthUser->getEmail();
    if (! $email) {
        return redirect()->route('login')->withErrors([
            'auth' => 'Unable to retrieve email from '.ucfirst($provider).' account.',
        ]);
    }

    $user = User::query()->firstOrCreate(
        ['email' => $email],
        [
            'name' => $oauthUser->getName() ?: Str::before($email, '@'),
            'password' => Hash::make(Str::random(32)),
            'role' => 'student',
        ]
    );

    $role = $user->role instanceof UserRole
        ? $user->role->value
        : (string) $user->role;

    $request->session()->regenerate();

    if (in_array($role, ['admin', 'super_admin'], true)) {
        $request->session()->put('admin_user_id', (int) $user->id);
        $request->session()->forget(['portal_user_id', 'portal_selected_role']);

        return redirect()->route('admin.index');
    }

    $request->session()->put('portal_user_id', (int) $user->id);
    $request->session()->forget('portal_selected_role');

    return $resolvePortalDestination($user, $request);
})->name('oauth.callback');

Route::middleware('portal.session')->group(function () use ($resolvePortalDestination) {
    Route::get('/choose-role', function (Request $request) use ($resolvePortalDestination) {
        $user = $request->attributes->get('portal_user');

        return $resolvePortalDestination($user, $request);
    })->name('role.select');

    Route::post('/choose-role', function (Request $request) use ($resolvePortalDestination) {
        $user = $request->attributes->get('portal_user');

        return $resolvePortalDestination($user, $request);
    })->name('role.select.submit');

    Route::post('/logout', function (Request $request) {
        $request->session()->forget(['portal_user_id', 'portal_selected_role']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Signed out successfully.');
    })->name('logout');
});

Route::get('/student', function (Request $request) {
    $selectedMentorId = request()->integer('mentor_id') ?: null;
    $portalUser = $request->attributes->get('portal_user');
    $selectedStudentId = (int) $portalUser->id;

    $mentors = User::query()
        ->where('role', 'mentor')
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'faculty']);

    if ($selectedMentorId && ! $mentors->contains('id', $selectedMentorId)) {
        $selectedMentorId = null;
    }

    if (! $selectedMentorId) {
        $selectedMentorId = $mentors->first()?->id;
    }

    $mentorIds = $mentors->pluck('id');

    $availabilityQuery = MentorAvailability::query()
        ->whereIn('mentor_id', $mentorIds)
        ->where('is_active', true)
        ->orderBy('day_of_week')
        ->orderBy('start_time');

    if ($selectedMentorId) {
        $availabilityQuery->where('mentor_id', $selectedMentorId);
    }

    $availabilitiesByMentor = $availabilityQuery->get([
        'id',
        'mentor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ])->groupBy('mentor_id');

    $currentDayOfWeek = strtolower(now()->englishDayOfWeek);
    $currentTime = now()->format('H:i:s');

    $mentorLiveStatus = $mentors
        ->mapWithKeys(function (User $mentor) use ($availabilitiesByMentor, $currentDayOfWeek, $currentTime): array {
            $isAvailableNow = $availabilitiesByMentor
                ->get($mentor->id, collect())
                ->contains(function ($availability) use ($currentDayOfWeek, $currentTime): bool {
                    return (bool) $availability->is_active
                        && strtolower((string) $availability->day_of_week) === $currentDayOfWeek
                        && (string) $availability->start_time <= $currentTime
                        && (string) $availability->end_time >= $currentTime;
                });

            return [$mentor->id => $isAvailableNow];
        });

    $slotWindowStart = now()->toDateString();
    $slotWindowEnd = now()->copy()->addDays(5)->toDateString();

    $slotDatesInWindow = collect(range(0, 5))
        ->map(fn (int $offset) => now()->copy()->addDays($offset))
        ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), ['tuesday', 'thursday'], true))
        ->values();

    foreach ($mentorIds as $mentorId) {
        foreach ($slotDatesInWindow as $slotDate) {
            TimeSlot::query()->firstOrCreate(
                [
                    'mentor_id' => (int) $mentorId,
                    'date' => $slotDate->toDateString(),
                    'start_time' => '09:00:00',
                    'end_time' => '15:00:00',
                ],
                [
                    'status' => 'available',
                ]
            );

            TimeSlot::query()
                ->where('mentor_id', (int) $mentorId)
                ->whereDate('date', $slotDate->toDateString())
                ->where('status', 'available')
                ->where(function ($query) {
                    $query->where('start_time', '!=', '09:00:00')
                        ->orWhere('end_time', '!=', '15:00:00');
                })
                ->delete();
        }
    }

    $slotQuery = TimeSlot::query()
        ->whereIn('mentor_id', $mentorIds)
        ->where('status', 'available')
        ->whereBetween('date', [$slotWindowStart, $slotWindowEnd])
        ->where('start_time', '09:00:00')
        ->where('end_time', '15:00:00')
        ->orderBy('date')
        ->orderBy('start_time');

    if ($selectedMentorId) {
        $slotQuery->where('mentor_id', $selectedMentorId);
    }

    $slotsByMentor = $slotQuery->get([
        'id',
        'mentor_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ])->groupBy('mentor_id');

    $appointments = collect();

    if ($selectedStudentId) {
        $appointments = Appointment::query()
            ->where('student_id', $selectedStudentId)
            ->with(['mentor:id,name,email', 'timeSlot:id,date,start_time,end_time,status'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    return view('student.index', [
        'currentNav' => 'student',
        'portal' => 'student',
        'mentors' => $mentors,
        'availabilitiesByMentor' => $availabilitiesByMentor,
        'mentorLiveStatus' => $mentorLiveStatus,
        'slotsByMentor' => $slotsByMentor,
        'appointments' => $appointments,
        'selectedMentorId' => $selectedMentorId,
        'selectedStudentId' => $selectedStudentId,
    ]);
})->middleware(['portal.session', 'portal.role:student'])->name('student.index');

Route::post('/student/book-slot', function (Request $request) {
    $validated = $request->validate([
        'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
        'appointment_subject' => ['required', 'string', 'max:255'],
    ]);

    $student = $request->attributes->get('portal_user');

    try {
        DB::transaction(function () use ($validated, $student) {
            $hasActiveAppointment = Appointment::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['confirmed', 'pending', 'rescheduled'])
                ->whereHas('timeSlot', function ($query) {
                    $query->whereDate('date', '>=', now()->toDateString());
                })
                ->exists();

            if ($hasActiveAppointment) {
                throw new \RuntimeException('Only one appointment is allowed at a time.');
            }

            $slot = TimeSlot::query()
                ->where('id', (int) $validated['time_slot_id'])
                ->lockForUpdate()
                ->first();

            if (! $slot || $slot->status !== 'available') {
                throw new \RuntimeException('Selected slot is no longer available.');
            }

            $dayOfWeek = strtolower(Carbon::parse($slot->date)->englishDayOfWeek);
            if (! in_array($dayOfWeek, ['tuesday', 'thursday'], true)) {
                throw new \RuntimeException('Appointments are only allowed on Tuesday or Thursday slots.');
            }

            $hasExistingSameDay = Appointment::query()
                ->join('time_slots', 'appointments.time_slot_id', '=', 'time_slots.id')
                ->where('appointments.student_id', $student->id)
                ->whereIn('appointments.status', ['confirmed', 'pending'])
                ->whereDate('time_slots.date', $slot->date)
                ->exists();

            if ($hasExistingSameDay) {
                throw new \RuntimeException('This student already has an appointment on the selected date.');
            }

            $slotAlreadyHasAppointment = Appointment::query()
                ->where('time_slot_id', $slot->id)
                ->exists();

            if ($slotAlreadyHasAppointment) {
                throw new \RuntimeException('This slot has already been used and cannot be booked again.');
            }

            Appointment::query()->create([
                'student_id' => $student->id,
                'mentor_id' => $slot->mentor_id,
                'time_slot_id' => $slot->id,
                'status' => 'pending',
                'student_contact_details' => $student->email,
                'appointment_subject' => $validated['appointment_subject'],
            ]);

            $slot->update(['status' => 'booked']);
        });
    } catch (\RuntimeException $exception) {
        return redirect()
            ->route('student.index', $request->only(['mentor_id']))
            ->withErrors(['booking' => $exception->getMessage()]);
    }

    return redirect()
        ->route('student.index', $request->only(['mentor_id']))
        ->with('status', 'Appointment request submitted. Awaiting mentor confirmation.');
})->middleware(['portal.session', 'portal.role:student'])->name('student.book-slot');

Route::get('/mentor', function (Request $request) {
    $mentor = $request->attributes->get('portal_user');

    $mentorAvailabilities = MentorAvailability::query()
        ->where('mentor_id', $mentor->id)
        ->orderBy('day_of_week')
        ->orderBy('start_time')
        ->get(['id', 'mentor_id', 'day_of_week', 'start_time', 'end_time', 'is_active']);

    $monthInput = (string) $request->query('month', now()->format('Y-m'));
    if (! preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
        $monthInput = now()->format('Y-m');
    }

    try {
        $currentMonth = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
    } catch (\Throwable $exception) {
        $currentMonth = now()->startOfMonth();
    }

    $monthStart = $currentMonth->copy()->startOfMonth();
    $monthEnd = $currentMonth->copy()->endOfMonth();

    $monthlyAppointments = Appointment::query()
        ->with([
            'student:id,name,email',
            'timeSlot:id,date,start_time,end_time',
        ])
        ->where('mentor_id', $mentor->id)
        ->whereIn('status', ['confirmed', 'pending', 'rescheduled'])
        ->whereHas('timeSlot', function ($query) use ($monthStart, $monthEnd) {
            $query->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()]);
        })
        ->get()
        ->filter(fn (Appointment $appointment) => $appointment->timeSlot !== null)
        ->sortBy(fn (Appointment $appointment) => ($appointment->timeSlot->date ?? '').' '.($appointment->timeSlot->start_time ?? ''))
        ->values();

    $appointmentsByDate = $monthlyAppointments->groupBy(function (Appointment $appointment): string {
        return (string) $appointment->timeSlot->date;
    });

    $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
    $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
    $cursor = $gridStart->copy();
    $calendarWeeks = [];

    while ($cursor->lte($gridEnd)) {
        $week = [];
        for ($day = 0; $day < 7; $day++) {
            $date = $cursor->copy();
            $dateKey = $date->toDateString();

            $week[] = [
                'date' => $date,
                'inMonth' => $date->month === $currentMonth->month,
                'isToday' => $date->isToday(),
                'sessions' => $appointmentsByDate->get($dateKey, collect()),
            ];

            $cursor->addDay();
        }

        $calendarWeeks[] = $week;
    }

    $upcomingSessions = Appointment::query()
        ->with([
            'student:id,name,email',
            'timeSlot:id,date,start_time,end_time',
        ])
        ->where('mentor_id', $mentor->id)
        ->whereIn('status', ['confirmed', 'pending', 'rescheduled'])
        ->whereHas('timeSlot', function ($query) {
            $query->whereDate('date', '>=', now()->toDateString());
        })
        ->get()
        ->filter(fn (Appointment $appointment) => $appointment->timeSlot !== null)
        ->sortBy(fn (Appointment $appointment) => ($appointment->timeSlot->date ?? '').' '.($appointment->timeSlot->start_time ?? ''))
        ->take(8)
        ->values();

    $statusSummary = [
        'confirmed' => $monthlyAppointments->where('status', 'confirmed')->count(),
        'pending' => $monthlyAppointments->where('status', 'pending')->count(),
        'rescheduled' => $monthlyAppointments->where('status', 'rescheduled')->count(),
    ];

    $totalSessionsThisMonth = $monthlyAppointments->count();

    return view('mentor.index', [
        'currentNav' => 'mentor',
        'portal' => 'mentor',
        'sidebarTitle' => 'Mentor Pages',
        'sidebarItems' => [
            ['label' => 'My Schedule', 'route' => route('mentor.index'), 'active' => true],
            ['label' => 'Notes', 'route' => route('mentor.index'), 'active' => false],
        ],
        'mentor' => $mentor,
        'mentorAvailabilities' => $mentorAvailabilities,
        'currentMonthLabel' => $currentMonth->format('F Y'),
        'currentMonthValue' => $currentMonth->format('Y-m'),
        'previousMonthValue' => $currentMonth->copy()->subMonthNoOverflow()->format('Y-m'),
        'nextMonthValue' => $currentMonth->copy()->addMonthNoOverflow()->format('Y-m'),
        'calendarWeeks' => $calendarWeeks,
        'upcomingSessions' => $upcomingSessions,
        'statusSummary' => $statusSummary,
        'totalSessionsThisMonth' => $totalSessionsThisMonth,
    ]);
})->middleware(['portal.session', 'portal.role:mentor'])->name('mentor.index');

Route::post('/mentor/availability/{id}/status', function (Request $request, int $id) {
    $validated = $request->validate([
        'is_active' => ['required', 'boolean'],
    ]);

    $mentor = $request->attributes->get('portal_user');

    $availability = MentorAvailability::query()
        ->where('id', $id)
        ->where('mentor_id', $mentor->id)
        ->first();

    if (! $availability) {
        return redirect()->route('mentor.index')->withErrors([
            'availability' => 'Availability entry not found.',
        ]);
    }

    $availability->update([
        'is_active' => (bool) $validated['is_active'],
    ]);

    return redirect()->route('mentor.index')->with('status', 'Availability status updated.');
})->middleware(['portal.session', 'portal.role:mentor'])->name('mentor.availability.status');

Route::post('/mentor/appointments/{id}/confirm', function (Request $request, int $id) {
    $mentor = $request->attributes->get('portal_user');

    $appointment = Appointment::query()
        ->where('id', $id)
        ->where('mentor_id', $mentor->id)
        ->first();

    if (! $appointment) {
        return redirect()->route('mentor.index')->withErrors([
            'appointment' => 'Appointment not found.',
        ]);
    }

    $appointment->update([
        'status' => 'confirmed',
    ]);

    return redirect()->route('mentor.index')->with('status', 'Appointment confirmed.');
})->middleware(['portal.session', 'portal.role:mentor'])->name('mentor.appointments.confirm');

Route::post('/mentor/appointments/{id}/decline', function (Request $request, int $id) {
    $mentor = $request->attributes->get('portal_user');

    $appointment = Appointment::query()
        ->where('id', $id)
        ->where('mentor_id', $mentor->id)
        ->first();

    if (! $appointment) {
        return redirect()->route('mentor.index')->withErrors([
            'appointment' => 'Appointment not found.',
        ]);
    }

    $appointment->update([
        'status' => 'cancelled',
        'cancelled_reason' => 'Declined by mentor.',
    ]);

    $timeSlot = TimeSlot::query()->find($appointment->time_slot_id);
    if ($timeSlot && $timeSlot->status !== 'available') {
        $timeSlot->update(['status' => 'available']);
    }

    return redirect()->route('mentor.index')->with('status', 'Appointment declined.');
})->middleware(['portal.session', 'portal.role:mentor'])->name('mentor.appointments.decline');

Route::get('/admin/login', function () {
    return view('admin.login', [
        'currentNav' => 'dashboard',
    ]);
})->name('admin.login');

Route::post('/admin/login', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::query()->where('email', $validated['email'])->first();

    $role = $user?->role;
    if ($role instanceof \App\Enums\UserRole) {
        $role = $role->value;
    }

    $isAllowedRole = in_array((string) $role, ['admin', 'super_admin'], true);

    if (! $user || ! $isAllowedRole || ! Hash::check($validated['password'], $user->password)) {
        return back()
            ->withErrors(['email' => 'Invalid admin credentials.'])
            ->onlyInput('email');
    }

    $request->session()->regenerate();
    $request->session()->put('admin_user_id', (int) $user->id);

    return redirect()->route('admin.index');
})->name('admin.login.attempt');

Route::middleware('admin.session')->group(function () use ($syncMentorDefaultAvailabilities) {
    Route::post('/admin/logout', function (Request $request) {
        $request->session()->forget('admin_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'Signed out successfully.');
    })->name('admin.logout');

    Route::get('/admin', function (Request $request) use ($syncMentorDefaultAvailabilities) {
        $adminUser = $request->attributes->get('admin_user');
        $mentorVerificationEnabled = Schema::hasColumn('users', 'mentor_verified_at');

        $mentorIds = User::query()
            ->where('role', 'mentor')
            ->pluck('id');

        foreach ($mentorIds as $mentorId) {
            $syncMentorDefaultAvailabilities((int) $mentorId);
        }

        $mentors = User::query()
            ->where('role', 'mentor')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'faculty', 'created_at']);

        $pendingMentorVerifications = collect();
        if ($mentorVerificationEnabled) {
            $pendingMentorVerifications = User::query()
                ->where('role', 'mentor')
                ->whereNull('mentor_verified_at')
                ->orderBy('created_at')
                ->get(['id', 'name', 'email', 'created_at']);
        }

        $availabilitiesByMentor = MentorAvailability::query()
            ->whereIn('mentor_id', $mentors->pluck('id'))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['id', 'mentor_id', 'day_of_week', 'start_time', 'end_time', 'is_active'])
            ->groupBy('mentor_id');

        $currentDayOfWeek = strtolower(now()->englishDayOfWeek);
        $currentTime = now()->format('H:i:s');

        $mentorLiveStatus = $mentors
            ->mapWithKeys(function (User $mentor) use ($availabilitiesByMentor, $currentDayOfWeek, $currentTime): array {
                $isAvailableNow = $availabilitiesByMentor
                    ->get($mentor->id, collect())
                    ->contains(function ($availability) use ($currentDayOfWeek, $currentTime): bool {
                        return (bool) $availability->is_active
                            && strtolower((string) $availability->day_of_week) === $currentDayOfWeek
                            && (string) $availability->start_time <= $currentTime
                            && (string) $availability->end_time >= $currentTime;
                    });

                return [$mentor->id => $isAvailableNow];
            });

        $upcomingAppointments = Appointment::query()
            ->with([
                'student:id,name',
                'mentor:id,name',
                'timeSlot:id,date,start_time,end_time',
            ])
            ->whereIn('status', ['confirmed', 'pending', 'rescheduled'])
            ->whereHas('timeSlot', function ($query) {
                $query->whereDate('date', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $announcements = collect();
        if (Schema::hasTable('announcements')) {
            $announcements = Announcement::query()
                ->orderByDesc('published_on')
                ->orderByDesc('id')
                ->get(['id', 'title', 'type', 'message', 'published_on']);
        }

        $centreEvents = collect();
        if (Schema::hasTable('centre_events')) {
            $centreEvents = CentreEvent::query()
                ->orderBy('event_date')
                ->orderBy('event_time')
                ->get(['id', 'title', 'category', 'event_date', 'event_time', 'venue']);
        }

        return view('admin.index', [
            'currentNav' => 'admin',
            'portal' => 'admin',
            'sidebarTitle' => 'Admin Modules',
            'sidebarItems' => [
                ['label' => 'Mentors', 'route' => route('admin.index'), 'active' => true],
                ['label' => 'Availability', 'route' => route('admin.index'), 'active' => false],
                ['label' => 'Slot Generation', 'route' => route('admin.index'), 'active' => false],
                ['label' => 'Reports', 'route' => route('admin.index'), 'active' => false],
                ['label' => 'Ops', 'route' => route('admin.index'), 'active' => false],
                ['label' => 'Alerts', 'route' => route('admin.index'), 'active' => false],
            ],
            'adminUser' => $adminUser,
            'mentors' => $mentors,
            'mentorVerificationEnabled' => $mentorVerificationEnabled,
            'pendingMentorVerifications' => $pendingMentorVerifications,
            'availabilitiesByMentor' => $availabilitiesByMentor,
            'mentorLiveStatus' => $mentorLiveStatus,
            'upcomingAppointments' => $upcomingAppointments,
            'announcements' => $announcements,
            'centreEvents' => $centreEvents,
        ]);
    })->name('admin.index');

    Route::post('/admin/centre-events', function (Request $request) {
        if (! Schema::hasTable('centre_events')) {
            return redirect()->route('admin.index')->withErrors([
                'centre_events' => 'Centre events table not found. Please run migrations first.',
            ]);
        }

        $validated = $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'event_category' => ['required', 'string', 'max:100'],
            'event_date' => ['required', 'date'],
            'event_time' => ['required', 'date_format:H:i'],
            'event_venue' => ['required', 'string', 'max:255'],
        ]);

        CentreEvent::query()->create([
            'title' => $validated['event_title'],
            'category' => $validated['event_category'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'],
            'venue' => $validated['event_venue'],
        ]);

        return redirect()->route('admin.index')->with('status', 'Centre event added successfully.');
    })->name('admin.centre-events.store');

    Route::post('/admin/centre-events/{id}/delete', function (int $id) {
        if (! Schema::hasTable('centre_events')) {
            return redirect()->route('admin.index')->withErrors([
                'centre_events' => 'Centre events table not found. Please run migrations first.',
            ]);
        }

        $centreEvent = CentreEvent::query()->find($id);
        if (! $centreEvent) {
            return redirect()->route('admin.index')->withErrors([
                'centre_events' => 'Centre event not found.',
            ]);
        }

        $centreEvent->delete();

        return redirect()->route('admin.index')->with('status', 'Centre event deleted.');
    })->name('admin.centre-events.delete');

    Route::post('/admin/mentors/{mentor}/verify', function (int $mentor) {
        if (! Schema::hasColumn('users', 'mentor_verified_at')) {
            return redirect()->route('admin.index')->withErrors([
                'mentor_verification' => 'Mentor verification is not available yet. Please run database migrations.',
            ]);
        }

        $mentorUser = User::query()
            ->where('id', $mentor)
            ->where('role', 'mentor')
            ->first();

        if (! $mentorUser) {
            return redirect()->route('admin.index')->withErrors([
                'mentor_verification' => 'Mentor account not found.',
            ]);
        }

        if ($mentorUser->mentor_verified_at !== null) {
            return redirect()->route('admin.index')->with('status', 'Mentor is already verified.');
        }

        $mentorUser->update([
            'mentor_verified_at' => now(),
        ]);

        return redirect()->route('admin.index')->with('status', 'Mentor verified successfully.');
    })->name('admin.mentors.verify');

    Route::post('/admin/announcements', function (Request $request) {
        if (! Schema::hasTable('announcements')) {
            return redirect()->route('admin.index')->withErrors([
                'announcements' => 'Announcements table not found. Please run migrations first.',
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
            'published_on' => ['required', 'date'],
        ]);

        Announcement::query()->create($validated);

        return redirect()->route('admin.index')->with('status', 'Announcement added successfully.');
    })->name('admin.announcements.store');

    Route::post('/admin/announcements/{id}/delete', function (int $id) {
        if (! Schema::hasTable('announcements')) {
            return redirect()->route('admin.index')->withErrors([
                'announcements' => 'Announcements table not found. Please run migrations first.',
            ]);
        }

        $announcement = Announcement::query()->find($id);
        if (! $announcement) {
            return redirect()->route('admin.index')->withErrors([
                'announcements' => 'Announcement not found.',
            ]);
        }

        $announcement->delete();

        return redirect()->route('admin.index')->with('status', 'Announcement deleted.');
    })->name('admin.announcements.delete');

    Route::post('/admin/mentors', function (Request $request) use ($syncMentorDefaultAvailabilities) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'faculty' => ['required', 'string', 'max:255'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'mentor',
            'faculty' => $validated['faculty'],
        ];

        if (Schema::hasColumn('users', 'mentor_verified_at')) {
            $payload['mentor_verified_at'] = now();
        }

        $mentor = User::query()->create($payload);

        $syncMentorDefaultAvailabilities((int) $mentor->id);

        return redirect()->route('admin.index')->with('status', 'Mentor created successfully.');
    })->name('admin.mentors.store');

    Route::post('/admin/availability', function (Request $request) use ($syncMentorDefaultAvailabilities) {
        $validated = $request->validate([
            'mentor_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['nullable', 'string'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $mentor = User::query()
            ->where('id', (int) $validated['mentor_id'])
            ->where('role', 'mentor')
            ->first();

        if (! $mentor) {
            return redirect()->route('admin.index')->withErrors(['mentor_id' => 'Selected user is not a mentor.']);
        }

        $syncMentorDefaultAvailabilities((int) $validated['mentor_id']);

        return redirect()->route('admin.index')->with('status', 'Default mentor availability applied: Tuesday/Thursday · 09:00 - 15:00.');
    })->name('admin.availability.store');

    Route::post('/admin/availability/{id}/update', function (Request $request, int $id) {
        $validated = $request->validate([
            'day_of_week' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $availability = MentorAvailability::query()->find($id);

        if (! $availability) {
            return redirect()->route('admin.index')->withErrors(['availability' => 'Availability entry not found.']);
        }

        $availability->update([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return redirect()->route('admin.index')->with('status', 'Availability updated.');
    })->name('admin.availability.update');

    Route::post('/admin/availability/{id}/delete', function (int $id) {
        $availability = MentorAvailability::query()->find($id);

        if (! $availability) {
            return redirect()->route('admin.index')->withErrors(['availability' => 'Availability entry not found.']);
        }

        $availability->delete();

        return redirect()->route('admin.index')->with('status', 'Availability deleted.');
    })->name('admin.availability.delete');
});
