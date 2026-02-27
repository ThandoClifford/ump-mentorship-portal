<?php

namespace App\Console\Commands;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:appointment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for confirmed appointments in the next 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $windowStart = now();
        $windowEnd = now()->addDay();
        $sentCount = 0;
        $failedCount = 0;

        $this->info('Scanning confirmed appointments from '.$windowStart->toDateTimeString().' to '.$windowEnd->toDateTimeString());

        Appointment::query()
            ->where('status', 'confirmed')
            ->whereNull('reminder_sent_at')
            ->with(['student:id,name,email', 'mentor:id,name,email', 'timeSlot:id,date,start_time,end_time,mentor_id'])
            ->orderBy('id')
            ->chunkById(100, function ($appointments) use ($windowStart, $windowEnd, &$sentCount, &$failedCount) {
                foreach ($appointments as $appointment) {
                    if (! $appointment->timeSlot || ! $appointment->student) {
                        continue;
                    }

                    $appointmentDateTime = Carbon::parse($appointment->timeSlot->date.' '.$appointment->timeSlot->start_time);

                    if (! $appointmentDateTime->betweenIncluded($windowStart, $windowEnd)) {
                        continue;
                    }

                    try {
                        Mail::to($appointment->student->email)->send(new AppointmentReminderMail($appointment));
                        $appointment->update(['reminder_sent_at' => now()]);
                        $sentCount++;
                        $this->info('Reminder sent for appointment #'.$appointment->id);
                    } catch (\Throwable $exception) {
                        $failedCount++;
                        $this->error('Failed reminder for appointment #'.$appointment->id.': '.$exception->getMessage());
                    }
                }
            });

        $this->info('Reminder run complete. Sent: '.$sentCount.', Failed: '.$failedCount);

        return self::SUCCESS;
    }
}
