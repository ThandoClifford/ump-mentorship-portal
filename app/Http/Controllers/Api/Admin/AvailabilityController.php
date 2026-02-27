<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MentorAvailability;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvailabilityController extends Controller
{
    use ApiResponse;

    public function indexByMentor(int $mentorId)
    {
        $mentor = $this->findMentor($mentorId);

        if (! $mentor) {
            return $this->failure('Mentor not found', null, 404);
        }

        $availability = MentorAvailability::where('mentor_id', $mentor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->success('Availability retrieved', $availability);
    }

    public function storeForMentor(Request $request, int $mentorId)
    {
        $mentor = $this->findMentor($mentorId);

        if (! $mentor) {
            return $this->failure('Mentor not found', null, 404);
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'string', Rule::in(['tuesday', 'thursday'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $availability = MentorAvailability::firstOrCreate([
            'mentor_id' => $mentor->id,
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ], [
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! $availability->wasRecentlyCreated && array_key_exists('is_active', $validated)) {
            $availability->update(['is_active' => $validated['is_active']]);
        }

        return $this->success(
            $availability->wasRecentlyCreated ? 'Availability created' : 'Availability already exists',
            $availability->fresh(),
            $availability->wasRecentlyCreated ? 201 : 200
        );
    }

    public function update(Request $request, int $id)
    {
        $availability = MentorAvailability::find($id);

        if (! $availability) {
            return $this->failure('Availability not found', null, 404);
        }

        $validated = $request->validate([
            'day_of_week' => ['sometimes', 'required', 'string', Rule::in(['tuesday', 'thursday'])],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $startTime = $validated['start_time'] ?? $availability->start_time;
        $endTime = $validated['end_time'] ?? $availability->end_time;

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->failure('The end_time must be after start_time.', null, 422);
        }

        $availability->update($validated);

        return $this->success('Availability updated', $availability->fresh());
    }

    public function destroy(int $id)
    {
        $availability = MentorAvailability::find($id);

        if (! $availability) {
            return $this->failure('Availability not found', null, 404);
        }

        $availability->delete();

        return $this->success('Availability deleted');
    }

    private function findMentor(int $mentorId): ?User
    {
        return User::where('id', $mentorId)
            ->where('role', UserRole::MENTOR->value)
            ->first();
    }
}
