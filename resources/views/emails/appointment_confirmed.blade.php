<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mentorship Appointment Confirmed</title>
</head>
<body>
    <h2>Mentorship Appointment Confirmed</h2>
    <p>Hello {{ $appointment->student->name }},</p>
    <p>Your mentorship appointment is confirmed.</p>
    <ul>
        <li>Mentor: {{ $appointment->mentor->name }}</li>
        <li>Date: {{ $appointment->timeSlot->date }}</li>
        <li>Time: {{ $appointment->timeSlot->start_time }} - {{ $appointment->timeSlot->end_time }}</li>
    </ul>
    <p>Appointments are required; please arrive on time.</p>
</body>
</html>
