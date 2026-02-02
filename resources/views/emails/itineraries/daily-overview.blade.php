<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Itinerary</title>
</head>
<body style="margin: 0; padding: 0; background-color: #edf2f7; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    @php
        $userName = $user->name ?? 'there';
        $actionUrl = $actionUrl ?? url('/');
        $initials = collect(explode(' ', preg_replace('/\s+/', ' ', trim($userName))))
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $sunnyPath = resource_path('views/emails/assets/sunny.png');
        $calendarPath = resource_path('views/emails/assets/calendar.png');
        $sunnySrc = (isset($message) && is_file($sunnyPath)) ? $message->embed($sunnyPath) : null;
        $calendarSrc = (isset($message) && is_file($calendarPath)) ? $message->embed($calendarPath) : null;
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #edf2f7; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);">
                    <tr>
                        <td style="background-color: #0ea5e9; background-image: linear-gradient(180deg, #06b6d4 0%, #2563eb 100%); color: #ffffff; padding: 32px 36px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <div style="width: 54px; height: 54px; border-radius: 999px; background-color: rgba(255, 255, 255, 0.18); display: block; margin: 0 auto; text-align: center;">
                                            <table role="presentation" width="54" height="54" cellspacing="0" cellpadding="0" style="width: 54px; height: 54px;">
                                                <tr>
                                                    <td align="center" valign="middle">
                                                        @if($sunnySrc)
                                                            <img src="{{ $sunnySrc }}" width="28" height="28" alt="Sun" style="display: block; border: 0; line-height: 0;">
                                                        @else
                                                            <div style="line-height: 54px; font-size: 18px; font-weight: 700; text-align: center; mso-line-height-rule: exactly;">SUN</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div style="margin-top: 14px; display: inline-block; background-color: rgba(255, 255, 255, 0.2); border-radius: 999px; padding: 6px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                            <span style="display: inline-block; width: 22px; height: 22px; border-radius: 999px; background-color: #ffffff; color: #2563eb; line-height: 22px; text-align: center; font-size: 10px; font-weight: 700; margin-right: 6px;">
                                                {{ $initials ?: 'TY' }}
                                            </span>
                                            Team YES!
                                        </div>

                                        <div style="font-size: 24px; font-weight: 700; margin-top: 14px;">
                                            Hi {{ $userName }},
                                        </div>
                                        <div style="font-size: 14px; margin-top: 6px; opacity: 0.9;">
                                            Here is your itinerary for {{ $date->format('l, M j') }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 22px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <div style="width: 42px; height: 42px; border-radius: 999px; background-color: #e0f2fe; display: inline-block; text-align: center;">
                                            <table role="presentation" width="42" height="42" cellspacing="0" cellpadding="0" style="width: 42px; height: 42px;">
                                                <tr>
                                                    <td align="center" valign="middle">
                                                        @if($calendarSrc)
                                                            <img src="{{ $calendarSrc }}" width="20" height="20" alt="Calendar" style="display: block; border: 0; line-height: 0;">
                                                        @else
                                                            <div style="line-height: 42px; font-size: 11px; font-weight: 700; text-align: center; mso-line-height-rule: exactly;">CAL</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div style="margin-top: 10px; font-size: 13px; color: #475569;">
                                            Here's what's planned for your day!
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 10px 36px 6px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ecfafe; border-radius: 16px; border: 1px solid #bae6fd;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <div style="text-align: center; font-size: 14px; font-weight: 700; color: #0f172a;">
                                            Today's Schedule
                                        </div>
                                        <div style="margin-top: 12px;">
                                            @forelse($activities as $activity)
                                                @php
                                                    $activityLabel = is_string($activity) ? $activity : ($activity->name ?? 'Scheduled activity');
                                                    $activitySubtitle = is_string($activity) ? 'Scheduled activity' : ($activity->description ?? 'Scheduled activity');
                                                    $activityInitial = strtoupper(substr($activityLabel, 0, 1));
                                                @endphp
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 10px; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0;">
                                                    <tr>
                                                        <td style="padding: 12px 14px;">
                                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                                <tr>
                                                                    <td width="48" valign="middle">
                                                                        <div style="width: 38px; height: 38px; border-radius: 12px; background-color: #0ea5e9; color: #ffffff; font-weight: 700; font-size: 14px; line-height: 38px; text-align: center; display: block; mso-line-height-rule: exactly;">
                                                                            {{ $activityInitial }}
                                                                        </div>
                                                                    </td>
                                                                    <td valign="middle">
                                                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a;">
                                                                            {{ $activityLabel }}
                                                                        </div>
                                                                        <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                                                            {{ $activitySubtitle }}
                                                                        </div>
                                                                    </td>
                                                                    <td valign="middle" align="right">
                                                                        <div style="width: 26px; height: 26px; border-radius: 8px; border: 1px solid #e2e8f0; color: #94a3b8; line-height: 26px; text-align: center; font-size: 12px; mso-line-height-rule: exactly;">
                                                                            &#10003;
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            @empty
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 12px; border: 1px dashed #cbd5f5;">
                                                    <tr>
                                                        <td style="padding: 14px; font-size: 12px; color: #64748b; text-align: center;">
                                                            No activities scheduled yet. Add one in your itinerary!
                                                        </td>
                                                    </tr>
                                                </table>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 10px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-radius: 14px; border: 1px solid #bae6fd; background-color: #ffffff;">
                                <tr>
                                    <td style="padding: 16px 18px;">
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">
                                            Preparation Tips
                                        </div>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px; color: #1e293b;">
                                            <tr>
                                                <td valign="top" width="16" style="color: #0ea5e9;">&bull;</td>
                                                <td style="padding-bottom: 6px;">Arrive 10 minutes early to prepare your heart and mind.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="16" style="color: #0ea5e9;">&bull;</td>
                                                <td style="padding-bottom: 6px;">Bring your Bible, notebook, and any materials needed.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="16" style="color: #0ea5e9;">&bull;</td>
                                                <td>Remember to check in with your OIKOS family members.</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 10px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0ea5e9; background-image: linear-gradient(180deg, #06b6d4 0%, #2563eb 100%); border-radius: 16px;">
                                <tr>
                                    <td style="padding: 20px 18px; text-align: center; color: #ffffff;">
                                        <div style="font-size: 18px; font-weight: 700;">Have a blessed day!</div>
                                        <div style="font-size: 12px; margin-top: 6px; color: #e0f2fe;">Make today count for His kingdom</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 8px 36px 12px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f172a; border-radius: 16px;">
                                <tr>
                                    <td style="padding: 18px 20px; color: #e2e8f0; text-align: center; font-style: italic; font-size: 12px; line-height: 1.6;">
                                        "This is the day that the LORD has made; let us rejoice and be glad in it."
                                        <div style="margin-top: 8px; font-style: normal; font-weight: 700; color: #7dd3fc;">
                                            - Psalm 118:24
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 10px 36px 18px;">
                            <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 26px; background-color: #0ea5e9; background-image: linear-gradient(180deg, #06b6d4 0%, #2563eb 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 999px;">
                                View Full Calendar
                            </a>
                            <div style="margin-top: 12px; font-size: 12px; color: #475569;">
                                Go and make a difference today!
                            </div>
                            <div style="margin-top: 6px; font-size: 12px; font-weight: 700; color: #0f172a;">
                                Team YES!
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #0f172a; color: #e2e8f0; text-align: center; font-size: 11px; padding: 12px 24px;">
                            Your daily itinerary is automatically generated based on your commitments.<br>
                            Need to update your schedule? Contact your team leader.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
