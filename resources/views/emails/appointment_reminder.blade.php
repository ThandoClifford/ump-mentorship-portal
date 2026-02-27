<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mentorship Appointment Reminder</title>
</head>
<body>
    <h2>Mentorship Appointment Reminder (Tomorrow)</h2>
    <p>Hello {{ $appointment->student->name }},</p>
    <p>This is a reminder for your mentorship appointment tomorrow.</p>
    <ul>
        <li>Mentor: {{ $appointment->mentor->name }}</li>
        <li>Date: {{ $appointment->timeSlot->date }}</li>
        <li>Time: {{ $appointment->timeSlot->start_time }} - {{ $appointment->timeSlot->end_time }}</li>
    </ul>
    <p>See you tomorrow. If you cannot attend, please cancel in advance.</p>
</body>
</html>
