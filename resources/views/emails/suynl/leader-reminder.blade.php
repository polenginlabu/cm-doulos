<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SUYNL Students – Next Lessons Reminder</title>
</head>
<body>
    <p>Hi {{ $leader->name ?? 'Leader' }},</p>

    <p>
        Here is a summary of your SUYNL students and their <strong>next lessons</strong>.
        This can help you plan your upcoming sessions and follow-ups.
    </p>

    @if(!empty($students))
        <table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr>
                    <th align="left">Student</th>
                    <th align="left">Lessons Attended</th>
                    <th align="left">Next Lesson #</th>
                    <th align="left">Next Lesson Title</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $row)
                    @php
                        $student = $row['student'];
                    @endphp
                    <tr>
                        <td>{{ $student->name ?? 'Unknown Student' }}</td>
                        <td>{{ $row['lessons_attended'] }} / 10</td>
                        <td>{{ $row['next_lesson_number'] }}</td>
                        <td>{{ $row['next_lesson_title'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>You currently have no SUYNL students with pending lessons.</p>
    @endif

    <p>Please log in to the cell monitoring system to review and update their SUYNL progress.</p>

    <p>Thank you for leading and discipling your SUYNL students!</p>

    <p>Blessings,</p>
</body>
</html>


