<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MentorAvailability;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\AuditService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SlotController extends Controller
{
    use ApiResponse;

    public function generateForMentor(Request $request, int $mentorId)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'slot_duration_minutes' => ['nullable', 'integer', Rule::in([15, 30, 45, 60])],
        ]);

        $mentor = User::where('id', $mentorId)
            ->where('role', UserRole::MENTOR->value)
            ->first();

        if (! $mentor) {
            return $this->failure('Mentor not found', null, 404);
        }

        $slotDuration = (int) ($validated['slot_duration_minutes'] ?? 60);
        $startDate = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->startOfDay();

        $result = DB::transaction(function () use ($mentor, $slotDuration, $startDate, $endDate) {
            $created = 0;
            $skipped = 0;

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dayOfWeek = strtolower($date->englishDayOfWeek);

                if (! in_array($dayOfWeek, ['tuesday', 'thursday'], true)) {
                    continue;
                }

                $availabilities = MentorAvailability::where('mentor_id', $mentor->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->get();

                foreach ($availabilities as $availability) {
                    $windowStart = Carbon::parse($date->toDateString().' '.$availability->start_time);
                    $windowEnd = Carbon::parse($date->toDateString().' '.$availability->end_time);

                    while ($windowStart->copy()->addMinutes($slotDuration)->lte($windowEnd)) {
                        $slotStart = $windowStart->copy();
                        $slotEnd = $windowStart->copy()->addMinutes($slotDuration);

                        $slot = TimeSlot::firstOrCreate(
                            [
                                'mentor_id' => $mentor->id,
                                'date' => $date->toDateString(),
                                'start_time' => $slotStart->format('H:i:s'),
                                'end_time' => $slotEnd->format('H:i:s'),
                            ],
                            [
                                'status' => 'available',
                            ]
                        );

                        if ($slot->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $skipped++;
                        }

                        $windowStart = $slotEnd;
                    }
                }
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
            ];
        });

        AuditService::log(
            (int) $request->user()->id,
            'slots.generated',
            'User',
            (int) $mentor->id,
            [
                'mentor_id' => (int) $mentor->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'created' => (int) $result['created'],
                'skipped' => (int) $result['skipped'],
            ]
        );

        return $this->success('Slots generated', $result);
    }
}
