<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mentorship Appointment Cancelled</title>
</head>
<body>
    <h2>Mentorship Appointment Cancelled</h2>
    <p>Hello {{ $appointment->student->name }},</p>
    <p>Your mentorship appointment has been cancelled.</p>
    <ul>
        <li>Mentor: {{ $appointment->mentor->name }}</li>
        <li>Date: {{ $appointment->timeSlot->date }}</li>
        <li>Time: {{ $appointment->timeSlot->start_time }} - {{ $appointment->timeSlot->end_time }}</li>
    </ul>
    @if(!empty($appointment->cancelled_reason))
        <p>Reason: {{ $appointment->cancelled_reason }}</p>
    @endif
</body>
</html>
