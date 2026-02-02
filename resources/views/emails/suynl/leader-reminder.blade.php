<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SUYNL Students - Progress Update</title>
</head>
<body style="margin: 0; padding: 0; background-color: #edf2f7; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    @php
        $leaderName = $leader->name ?? 'Leader';
        $actionUrl = $actionUrl ?? url('/');
        $studentCount = is_countable($students ?? null) ? count($students) : 0;
        $iconPath = resource_path('views/emails/assets/school.png');
        $bookIconPath = resource_path('views/emails/assets/book.png');
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #edf2f7; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);">
                    <tr>
                        <td style="background-color: #7c3aed; background-image: linear-gradient(180deg, #7c3aed 0%, #4f46e5 100%); color: #ffffff; padding: 36px 36px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        @php
                                            $iconSrc = (isset($message) && is_file($iconPath))
                                                ? $message->embed($iconPath)
                                                : null;
                                        @endphp
                                        <div style="width: 54px; height: 54px; border-radius: 999px; background-color: rgba(255, 255, 255, 0.18); display: block; margin: 0 auto; text-align: center;">
                                            <table role="presentation" width="54" height="54" cellspacing="0" cellpadding="0" style="width: 54px; height: 54px;">
                                                <tr>
                                                    <td align="center" valign="middle">
                                                        @if($iconSrc)
                                                            <img src="{{ $iconSrc }}" width="28" height="28" alt="School" style="display: block; border: 0; line-height: 0;">
                                                        @else
                                                            <div style="font-size: 16px; font-weight: 700; line-height: 54px; text-align: center; mso-line-height-rule: exactly;">SU</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div style="letter-spacing: 2px; font-size: 11px; font-weight: 700; margin-top: 14px;">
                                            STUDENT PROGRESS UPDATE
                                        </div>
                                        <div style="font-size: 24px; font-weight: 700; margin-top: 12px;">
                                            Hi {{ $leaderName }},
                                        </div>
                                        <div style="font-size: 14px; margin-top: 6px; opacity: 0.9;">
                                            Here's your SUYNL students' progress report
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 22px 36px 6px; font-size: 13px; color: #475569; line-height: 1.6;">
                            This is a summary of your SUYNL students and their next lessons. This will help you plan your upcoming sessions and follow-ups.
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 36px 12px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f7f4ff; border-radius: 14px; border: 1px solid #e9ddff;">
                                <tr>
                                    <td style="padding: 18px 18px 12px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="42" valign="middle">
                                                    @php
                                                        $bookIconSrc = (isset($message) && is_file($bookIconPath))
                                                            ? $message->embed($bookIconPath)
                                                            : null;
                                                    @endphp
                                                    <div style="width: 32px; height: 32px; border-radius: 10px; background-color: #7c3aed; color: #ffffff; font-weight: 700; font-size: 14px; text-align: center; display: block;">
                                                        <table role="presentation" width="32" height="32" cellspacing="0" cellpadding="0" style="width: 32px; height: 32px;">
                                                            <tr>
                                                                <td align="center" valign="middle">
                                                                    @if($bookIconSrc)
                                                                        <img src="{{ $bookIconSrc }}" width="18" height="18" alt="Book" style="display: block; border: 0; line-height: 0;">
                                                                    @else
                                                                        <div style="line-height: 32px; font-size: 14px; font-weight: 700; text-align: center; mso-line-height-rule: exactly;">B</div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </td>
                                                <td valign="middle">
                                                    <div style="font-size: 14px; font-weight: 700; color: #0f172a;">Student Overview</div>
                                                </td>
                                                <td valign="middle" align="right" style="font-size: 12px; color: #6b7280;">
                                                    {{ $studentCount }} student{{ $studentCount === 1 ? '' : 's' }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 12px 16px;">
                                        @if(!empty($students))
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                                                <tr style="background-color: #6d28d9; color: #ffffff;">
                                                    <th align="left" style="padding: 10px 12px; font-size: 11px; font-weight: 700;">Student</th>
                                                    <th align="left" style="padding: 10px 12px; font-size: 11px; font-weight: 700;">Lessons Attended</th>
                                                    <th align="left" style="padding: 10px 12px; font-size: 11px; font-weight: 700;">Next Lesson #</th>
                                                    <th align="left" style="padding: 10px 12px; font-size: 11px; font-weight: 700;">Next Lesson Title</th>
                                                </tr>
                                                @foreach($students as $row)
                                                    @php
                                                        $student = $row['student'] ?? null;
                                                        $studentName = $student->name ?? 'Unknown Student';
                                                        $lessonsAttended = $row['lessons_attended'] ?? 0;
                                                        $totalLessons = $row['total_lessons'] ?? 10;
                                                        $nextLessonNumber = $row['next_lesson_number'] ?? 'N/A';
                                                        $nextLessonTitle = $row['next_lesson_title'] ?? 'N/A';
                                                        $initials = collect(explode(' ', preg_replace('/\s+/', ' ', trim($studentName))))
                                                            ->filter()
                                                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                                            ->take(2)
                                                            ->implode('');
                                                    @endphp
                                                    <tr>
                                                        <td style="padding: 10px 12px; border-top: 1px solid #eef2ff;">
                                                            <table role="presentation" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td width="36" valign="middle">
                                                                        <div style="width: 30px; height: 30px; border-radius: 999px; background-color: #7c3aed; color: #ffffff; font-weight: 700; font-size: 11px; line-height: 30px; text-align: center; display: block; mso-line-height-rule: exactly;">
                                                                            {{ $initials ?: 'SU' }}
                                                                        </div>
                                                                    </td>
                                                                    <td valign="middle" style="padding-left: 8px; font-size: 12px; color: #0f172a; font-weight: 600;">
                                                                        {{ $studentName }}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td style="padding: 10px 12px; border-top: 1px solid #eef2ff; font-size: 12px; color: #0f172a;">
                                                            <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; background-color: #f3e8ff; color: #6d28d9; font-weight: 700; font-size: 11px;">
                                                                {{ $lessonsAttended }} / {{ $totalLessons }}
                                                            </span>
                                                        </td>
                                                        <td style="padding: 10px 12px; border-top: 1px solid #eef2ff; font-size: 12px; color: #0f172a; font-weight: 600;">
                                                            {{ $nextLessonNumber }}
                                                        </td>
                                                        <td style="padding: 10px 12px; border-top: 1px solid #eef2ff; font-size: 12px; color: #0f172a;">
                                                            {{ $nextLessonTitle }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        @else
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; border: 1px dashed #e5e7eb;">
                                                <tr>
                                                    <td style="padding: 16px; color: #64748b; font-size: 12px;">
                                                        You currently have no SUYNL students with pending lessons.
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 36px 10px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 14px; border: 1px solid #e9ddff;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                                            Action Steps
                                        </div>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px; color: #1e293b;">
                                            <tr>
                                                <td valign="top" width="16" style="color: #7c3aed;">&bull;</td>
                                                <td style="padding-bottom: 6px;">Please log in to the cell monitoring system to review and update their SUYNL progress.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="16" style="color: #7c3aed;">&bull;</td>
                                                <td>Thank you for faithfully following up your consolidation students!</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 14px 36px 8px;">
                            <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 26px; background-color: #7c3aed; background-image: linear-gradient(180deg, #8b5cf6 0%, #6d28d9 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 999px;">
                                Review & Update Progress
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 36px 18px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f172a; border-radius: 14px;">
                                <tr>
                                    <td style="padding: 18px 20px; color: #e2e8f0; text-align: center; font-style: italic; font-size: 12px; line-height: 1.6;">
                                        "And the things you have heard me say in the presence of many witnesses entrust to reliable people who will also be qualified to teach others."
                                        <div style="margin-top: 8px; font-style: normal; font-weight: 700; color: #c4b5fd;">
                                            - 2 Timothy 2:2
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 36px 24px; text-align: center; font-size: 12px; color: #475569;">
                            Blessings,<br>
                            <span style="font-weight: 700; color: #0f172a;">Your Ministry Team</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #0f172a; color: #e2e8f0; text-align: center; font-size: 11px; padding: 12px 24px;">
                            This is an automated progress report from your student tracking system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
