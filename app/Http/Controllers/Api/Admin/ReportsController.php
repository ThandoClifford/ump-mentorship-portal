<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    use ApiResponse;

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $validated['from'] ?? now()->subDays(30)->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        $baseQuery = DB::table('appointments')
            ->join('time_slots', 'appointments.time_slot_id', '=', 'time_slots.id')
            ->whereBetween('time_slots.date', [$from, $to]);

        $totalAppointments = (clone $baseQuery)->count();
        $confirmedCount = (clone $baseQuery)->where('appointments.status', 'confirmed')->count();
        $cancelledCount = (clone $baseQuery)->where('appointments.status', 'cancelled')->count();
        $completedCount = (clone $baseQuery)->where('appointments.status', 'completed')->count();
        $pendingCount = (clone $baseQuery)->where('appointments.status', 'pending')->count();

        $topMentors = (clone $baseQuery)
            ->join('users as mentors', 'appointments.mentor_id', '=', 'mentors.id')
            ->groupBy('appointments.mentor_id', 'mentors.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get([
                'appointments.mentor_id',
                'mentors.name as mentor_name',
                DB::raw('COUNT(*) as total'),
            ]);

        $busiestDays = (clone $baseQuery)
            ->groupBy('time_slots.date')
            ->orderByDesc('total')
            ->orderBy('time_slots.date')
            ->limit(10)
            ->get([
                'time_slots.date',
                DB::raw('COUNT(*) as total'),
            ]);

        return $this->success('Reports summary retrieved', [
            'total_appointments' => $totalAppointments,
            'confirmed_count' => $confirmedCount,
            'cancelled_count' => $cancelledCount,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'top_mentors' => $topMentors,
            'busiest_days' => $busiestDays,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
