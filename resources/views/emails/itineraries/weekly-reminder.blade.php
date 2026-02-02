<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Plan Your Week</title>
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
        $calendarMonthPath = resource_path('views/emails/assets/calendar_month.png');
        $targetPath = resource_path('views/emails/assets/target.png');
        $calendarMonthSrc = (isset($message) && is_file($calendarMonthPath)) ? $message->embed($calendarMonthPath) : null;
        $targetSrc = (isset($message) && is_file($targetPath)) ? $message->embed($targetPath) : null;
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #edf2f7; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);">
                    <tr>
                        <td style="background-color: #10b981; background-image: linear-gradient(180deg, #10b981 0%, #06b6d4 100%); color: #ffffff; padding: 34px 36px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <div style="width: 54px; height: 54px; border-radius: 999px; background-color: rgba(255, 255, 255, 0.18); display: block; margin: 0 auto; text-align: center;">
                                            <table role="presentation" width="54" height="54" cellspacing="0" cellpadding="0" style="width: 54px; height: 54px;">
                                                <tr>
                                                    <td align="center" valign="middle">
                                                        @if($calendarMonthSrc)
                                                            <img src="{{ $calendarMonthSrc }}" width="26" height="26" alt="Calendar" style="display: block; border: 0; line-height: 0;">
                                                        @else
                                                            <div style="font-size: 18px; font-weight: 700; line-height: 54px; text-align: center; mso-line-height-rule: exactly;">📅</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div style="margin-top: 14px; display: inline-block; background-color: rgba(255, 255, 255, 0.2); border-radius: 999px; padding: 6px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">
                                            <span style="display: inline-block; width: 22px; height: 22px; border-radius: 999px; background-color: #ffffff; color: #0f766e; line-height: 22px; text-align: center; font-size: 10px; font-weight: 700; margin-right: 6px;">
                                                {{ $initials ?: 'TY' }}
                                            </span>
                                            Team YES!
                                        </div>

                                        <div style="margin-top: 10px; font-size: 11px; opacity: 0.8;">to me v</div>
                                        <div style="font-size: 26px; font-weight: 700; margin-top: 10px;">
                                            Plan Your Week
                                        </div>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 22px 36px 4px;">
                            <div style="font-size: 18px; font-weight: 700; color: #0f172a;">
                                Hi {{ $userName }},
                            </div>
                            <div style="margin-top: 10px; font-size: 13px; color: #475569; line-height: 1.6;">
                                It is a new week. Take a moment to fill in your itinerary so you can stay on track.
                            </div>
                            <div style="margin-top: 10px; font-size: 13px; color: #475569;">
                                We are excited to see you grow this week.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 14px 36px 10px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ecfdf5; border-radius: 16px; border: 1px solid #86efac;">
                                <tr>
                                    <td style="padding: 18px 18px 10px; text-align: center;">
                                        <div style="width: 42px; height: 42px; border-radius: 999px; background-color: #059669; color: #ffffff; font-weight: 700; line-height: 42px; text-align: center; display: inline-block;">
                                            @if($targetSrc)
                                                <img src="{{ $targetSrc }}" width="20" height="20" alt="Target" style="display: inline-block; vertical-align: middle; border: 0; line-height: 0;">
                                            @else
                                                🎯
                                            @endif
                                        </div>
                                        <div style="margin-top: 12px; font-size: 16px; font-weight: 700; color: #0f172a;">
                                            Weekly Goals & Planning
                                        </div>
                                        <div style="margin-top: 4px; font-size: 12px; color: #475569;">
                                            Set yourself up for success this week
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 8px 16px 16px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 10px;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td width="38" valign="middle">
                                                                <div style="width: 30px; height: 30px; border-radius: 10px; background-color: #d1fae5; text-align: center; line-height: 30px;">📝</div>
                                                            </td>
                                                            <td valign="middle">
                                                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Fill in Your Schedule</div>
                                                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Add your commitments, meetings, and activities for the week.</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 10px;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td width="38" valign="middle">
                                                                <div style="width: 30px; height: 30px; border-radius: 10px; background-color: #d1fae5; text-align: center; line-height: 30px;">🎯</div>
                                                            </td>
                                                            <td valign="middle">
                                                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Set Your Priorities</div>
                                                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Identify what matters most and plan accordingly.</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0;">
                                            <tr>
                                                <td style="padding: 14px 16px;">
                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                            <td width="38" valign="middle">
                                                                <div style="width: 30px; height: 30px; border-radius: 10px; background-color: #d1fae5; text-align: center; line-height: 30px;">⏰</div>
                                                            </td>
                                                            <td valign="middle">
                                                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Stay Consistent</div>
                                                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Build habits that lead to spiritual growth and productivity.</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 8px 36px 4px;">
                            <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 28px; background-color: #059669; background-image: linear-gradient(180deg, #10b981 0%, #047857 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 999px;">
                                Plan My Week Now
                            </a>
                            <div style="margin-top: 8px; font-size: 11px; color: #64748b;">Takes less than 5 minutes</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 12px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-radius: 14px; border: 1px solid #86efac; background-color: #ffffff;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">Why Weekly Planning Matters</div>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px; color: #1e293b;">
                                            <tr>
                                                <td valign="top" width="16" style="color: #10b981;">&#10003;</td>
                                                <td style="padding-bottom: 6px;">Stay focused on your spiritual growth journey.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="16" style="color: #10b981;">&#10003;</td>
                                                <td style="padding-bottom: 6px;">Balance ministry commitments with personal time.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="16" style="color: #10b981;">&#10003;</td>
                                                <td style="padding-bottom: 6px;">Track progress and celebrate wins.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="16" style="color: #10b981;">&#10003;</td>
                                                <td>Be intentional about discipleship and service.</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 10px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #059669; border-radius: 16px;">
                                <tr>
                                    <td style="padding: 18px 18px; text-align: center; color: #ffffff;">
                                        <div style="font-size: 20px;">🌱</div>
                                        <div style="font-size: 16px; font-weight: 700; margin-top: 8px;">Growth Happens with Planning!</div>
                                        <div style="font-size: 12px; margin-top: 6px; color: #d1fae5;">A well-planned week leads to a fruitful week</div>
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
                                        "Commit to the LORD whatever you do, and he will establish your plans."
                                        <div style="margin-top: 8px; font-style: normal; font-weight: 700; color: #86efac;">
                                            - Proverbs 16:3
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 8px 36px 18px;">
                            <div style="font-size: 12px; color: #475569;">Let's make this week count!</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #0f172a; color: #e2e8f0; text-align: center; font-size: 11px; padding: 12px 24px;">
                            Weekly planning reminder from Team YES!<br>
                            This helps you stay organized and focused on your goals.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
