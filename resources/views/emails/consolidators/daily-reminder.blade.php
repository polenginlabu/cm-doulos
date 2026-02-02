<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Consolidation Reminder</title>
</head>
<body style="margin: 0; padding: 0; background-color: #edf2f7; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    @php
        $pendingCount = $pendingMembers instanceof \Illuminate\Support\Collection
            ? $pendingMembers->count()
            : collect($pendingMembers)->count();
        $actionUrl = $actionUrl ?? url('/');
        $userName = $consolidator->name ?? 'Consolidator';

        $statusStyles = [
            'in_progress' => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#fde68a', 'label' => 'In Progress'],
            'contacted' => ['bg' => '#dcfce7', 'text' => '#166534', 'border' => '#bbf7d0', 'label' => 'Contacted'],
            'pending' => ['bg' => '#e2e8f0', 'text' => '#334155', 'border' => '#cbd5f5', 'label' => 'Pending'],
        ];
        $iconPath = resource_path('views/emails/assets/group.png');

    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #edf2f7; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);">
                    <tr>
                        <td style="background-color: #1d4ed8; background-image: linear-gradient(180deg, #1d4ed8 0%, #0ea5e9 100%); color: #ffffff; padding: 36px 36px 28px;">
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
                                                            <img src="{{ $iconSrc }}" width="28" height="28" alt="Users" style="display: block; border: 0; line-height: 0;">
                                                        @else
                                                            <div style="font-size: 20px; text-align: center; mso-line-height-rule: exactly;">&#128101;</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div style="letter-spacing: 2px; font-size: 11px; font-weight: 700; margin-top: 14px;">
                                            DAILY CONSOLIDATION REMINDER
                                        </div>
                                        <div style="font-size: 24px; font-weight: 700; margin-top: 12px;">
                                            Hi {{ $userName }},
                                        </div>
                                        <div style="font-size: 14px; margin-top: 6px; opacity: 0.9;">
                                            Members who need your attention today
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f0f7ff; border-radius: 12px; border-left: 4px solid #2563eb;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="36" valign="middle">
                                                    <div style="width: 28px; height: 28px; border-radius: 999px; background-color: #1d4ed8; color: #ffffff; font-weight: 700; font-size: 14px; line-height: 28px; text-align: center; display: block; mso-line-height-rule: exactly;">
                                                        i
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                                                        You currently have <span style="color: #1d4ed8;">{{ $pendingCount }}</span>
                                                        consolidation member{{ $pendingCount === 1 ? '' : 's' }} that still need your attention
                                                    </div>
                                                    <div style="font-size: 12px; color: #475569; margin-top: 6px;">
                                                        Here are the members who still need follow-up:
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 36px 8px;">
                            <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">Members Status</div>
                            @if($pendingCount > 0)
                                @foreach($pendingMembers as $member)
                                    @php
                                        // Support both full models and simple arrays
                                        $user = is_array($member) ? ($member['user'] ?? null) : $member->user ?? null;
                                        $name = $user?->name ?? ($member['name'] ?? 'Unknown Member');
                                        $status = is_array($member)
                                            ? ($member['status_label'] ?? $member['status'] ?? null)
                                            : ($member->status_label ?? $member->status ?? null);
                                        $nextAction = is_array($member)
                                            ? ($member['next_action'] ?? null)
                                            : ($member->next_action ?? null);

                                        $displayStatus = $status ? trim($status) : 'Pending';
                                        $statusKey = strtolower(str_replace(' ', '_', $displayStatus));
                                        $style = $statusStyles[$statusKey] ?? $statusStyles['pending'];

                                        $initials = collect(explode(' ', preg_replace('/\s+/', ' ', trim($name))))
                                            ->filter()
                                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                                            ->take(2)
                                            ->implode('');
                                    @endphp
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 12px;">
                                        <tr>
                                            <td style="padding: 16px 18px;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td width="52" valign="middle">
                                                            <div style="width: 42px; height: 42px; border-radius: 999px; background-color: #2563eb; color: #ffffff; font-weight: 700; font-size: 14px; line-height: 42px; text-align: center; display: block; mso-line-height-rule: exactly;">
                                                                {{ $initials ?: 'CM' }}
                                                            </div>
                                                        </td>
                                                        <td valign="middle">
                                                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                                                                {{ $name }}
                                                            </div>
                                                            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                                                {{ $nextAction ? 'Next action: ' . $nextAction : 'Follow up needed' }}
                                                            </div>
                                                        </td>
                                                        <td valign="middle" align="right">
                                                            <span style="display: inline-block; padding: 4px 10px; border-radius: 999px; background-color: {{ $style['bg'] }}; color: {{ $style['text'] }}; font-size: 11px; font-weight: 700; border: 1px solid {{ $style['border'] }};">
                                                                {{ $style['label'] ?? $displayStatus }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            @else
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px dashed #cbd5f5; border-radius: 14px;">
                                    <tr>
                                        <td style="padding: 18px; color: #64748b; font-size: 13px;">
                                            You are all caught up today. Great job staying on top of your consolidation members!
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 36px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #ecf6ff; border-radius: 14px; border: 1px solid #bfdbfe;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
                                            What You Need to Do
                                        </div>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 12px; color: #1e293b;">
                                            <tr>
                                                <td valign="top" width="18">&gt;</td>
                                                <td style="padding-bottom: 6px;">Please log in to the cell monitoring system to review and update their consolidation status.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="18">&gt;</td>
                                                <td style="padding-bottom: 6px;">Reach out to each member to schedule their next consolidation session.</td>
                                            </tr>
                                            <tr>
                                                <td valign="top" width="18">&gt;</td>
                                                <td>Update their progress after each interaction.</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 18px 36px 10px;">
                            <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 26px; background-color: #0ea5e9; background-image: linear-gradient(180deg, #2563eb 0%, #0284c7 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 999px;">
                                Update Member Status
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 36px 10px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #e0f2fe; border-radius: 14px; border: 1px solid #bae6fd;">
                                <tr>
                                    <td style="padding: 16px 18px; text-align: center;">
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                                            Thank you for faithfully following up your consolidation members!
                                        </div>
                                        <div style="font-size: 12px; color: #475569; margin-top: 6px;">
                                            Your dedication is making disciples and transforming lives.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 36px 18px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f172a; border-radius: 14px;">
                                <tr>
                                    <td style="padding: 18px 20px; color: #e2e8f0; text-align: center; font-style: italic; font-size: 12px; line-height: 1.5;">
                                        "Therefore encourage one another and build each other up, just as in fact you are doing."
                                        <div style="margin-top: 8px; font-style: normal; font-weight: 700; color: #7dd3fc;">
                                            - 1 Thessalonians 5:11
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 36px 24px; text-align: center; font-size: 12px; color: #475569;">
                            Blessings,<br>
                            {{-- <span style="font-weight: 700; color: #0f172a;">Your Ministry Team</span> --}}
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #0f172a; color: #e2e8f0; text-align: center; font-size: 11px; padding: 12px 24px;">
                            This is your daily consolidation reminder to help you stay on track with discipleship.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
