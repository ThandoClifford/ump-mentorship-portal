<?php

namespace App\Http\Controllers\Api\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\SessionNote;
use App\Services\AuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MentorAppointmentsController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = Appointment::query()
            ->where('appointments.mentor_id', $request->user()->id)
            ->join('time_slots', 'appointments.time_slot_id', '=', 'time_slots.id')
            ->with([
                'student:id,name,email',
                'timeSlot:id,mentor_id,date,start_time,end_time,status',
            ])
            ->orderBy('time_slots.date')
            ->orderBy('time_slots.start_time')
            ->select('appointments.*');

        if (! empty($validated['date'])) {
            $query->whereDate('time_slots.date', $validated['date']);
        } elseif (! empty($validated['from']) && ! empty($validated['to'])) {
            $query->whereBetween('time_slots.date', [$validated['from'], $validated['to']]);
        }

        $appointments = $query->get();

        return $this->success('Mentor appointments retrieved', $appointments);
    }

    public function complete(Request $request, int $id)
    {
        $appointment = Appointment::where('id', $id)
            ->where('mentor_id', $request->user()->id)
            ->first();

        if (! $appointment) {
            return $this->failure('Appointment not found', null, 404);
        }

        if ($appointment->status !== 'confirmed') {
            return $this->failure('Only confirmed appointments can be completed', null, 422);
        }

        $appointment->update(['status' => 'completed']);

        AuditService::log(
            (int) $request->user()->id,
            'appointment.completed',
            'Appointment',
            (int) $appointment->id
        );

        return $this->success('Appointment marked as completed', $appointment->fresh());
    }

    public function upsertNotes(Request $request, int $id)
    {
        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $appointment = Appointment::where('id', $id)
            ->where('mentor_id', $request->user()->id)
            ->first();

        if (! $appointment) {
            return $this->failure('Appointment not found', null, 404);
        }

        $note = DB::transaction(function () use ($request, $appointment, $validated) {
            return SessionNote::updateOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'mentor_id' => $request->user()->id,
                    'notes' => $validated['notes'],
                ]
            );
        });

        AuditService::log(
            (int) $request->user()->id,
            'appointment.notes_updated',
            'Appointment',
            (int) $appointment->id,
            [
                'session_note_id' => (int) $note->id,
            ]
        );

        return $this->success('Session note saved', $note);
    }
}
