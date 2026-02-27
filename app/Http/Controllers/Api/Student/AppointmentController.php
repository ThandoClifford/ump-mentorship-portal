<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TimeSlot;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
        ]);

        $studentId = (int) $request->user()->id;

        try {
            $appointment = DB::transaction(function () use ($validated, $studentId) {
                $slot = TimeSlot::where('id', $validated['time_slot_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $slot || $slot->status !== 'available') {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Selected slot is no longer available',
                        'data' => null,
                    ], 422));
                }

                $dayOfWeek = strtolower(Carbon::parse($slot->date)->englishDayOfWeek);
                if (! in_array($dayOfWeek, ['tuesday', 'thursday'], true)) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Appointments are only allowed on Tuesday or Thursday slots',
                        'data' => null,
                    ], 422));
                }

                $hasExistingSameDay = Appointment::query()
                    ->join('time_slots', 'appointments.time_slot_id', '=', 'time_slots.id')
                    ->where('appointments.student_id', $studentId)
                    ->whereIn('appointments.status', ['confirmed', 'pending'])
                    ->whereDate('time_slots.date', $slot->date)
                    ->exists();

                if ($hasExistingSameDay) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'You already have an appointment on this date',
                        'data' => null,
                    ], 422));
                }

                $appointment = Appointment::create([
                    'student_id' => $studentId,
                    'mentor_id' => $slot->mentor_id,
                    'time_slot_id' => $slot->id,
                    'status' => 'confirmed',
                ]);

                $slot->update(['status' => 'booked']);

                return $appointment;
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            throw $exception;
        }

        $appointment->load([
            'timeSlot:id,mentor_id,date,start_time,end_time,status',
            'mentor:id,name,email',
        ]);

        return $this->success('Appointment booked successfully', $appointment, 201);
    }

    public function index(Request $request)
    {
        $appointments = Appointment::query()
            ->where('appointments.student_id', $request->user()->id)
            ->join('time_slots', 'appointments.time_slot_id', '=', 'time_slots.id')
            ->with([
                'timeSlot:id,mentor_id,date,start_time,end_time,status',
                'mentor:id,name,email',
            ])
            ->orderByDesc('time_slots.date')
            ->orderByDesc('time_slots.start_time')
            ->select('appointments.*')
            ->get();

        return $this->success('Appointments retrieved', $appointments);
    }

    public function cancel(Request $request, int $id)
    {
        $validated = $request->validate([
            'cancelled_reason' => ['nullable', 'string'],
        ]);

        $studentId = (int) $request->user()->id;

        $updatedAppointment = DB::transaction(function () use ($id, $studentId, $validated) {
            $appointment = Appointment::where('id', $id)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first();

            if (! $appointment) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Appointment not found',
                    'data' => null,
                ], 404));
            }

            if ($appointment->status === 'completed') {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Completed appointments cannot be cancelled',
                    'data' => null,
                ], 422));
            }

            $appointment->update([
                'status' => 'cancelled',
                'cancelled_reason' => $validated['cancelled_reason'] ?? $appointment->cancelled_reason,
            ]);

            $slot = TimeSlot::where('id', $appointment->time_slot_id)
                ->lockForUpdate()
                ->first();

            if ($slot && $slot->status === 'booked') {
                $slot->update(['status' => 'available']);
            }

            return $appointment->fresh([
                'timeSlot:id,mentor_id,date,start_time,end_time,status',
                'mentor:id,name,email',
            ]);
        });

        return $this->success('Appointment cancelled', $updatedAppointment);
    }
}
