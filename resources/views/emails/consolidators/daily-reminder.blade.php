<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Consolidation Reminder</title>
</head>
<body>
    <p>Hi {{ $consolidator->name ?? 'Consolidator' }},</p>

    @php
        $pendingCount = $pendingMembers instanceof \Illuminate\Support\Collection
            ? $pendingMembers->count()
            : collect($pendingMembers)->count();
    @endphp

    <p>
        This is your daily consolidation reminder.
        You currently have <strong>{{ $pendingCount }}</strong>
        consolidation member{{ $pendingCount === 1 ? '' : 's' }} that still need your attention.
    </p>

    @if($pendingCount > 0)
        <p>Here are the members who still need follow-up:</p>
        <ul>
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
                @endphp
                <li>
                    <strong>{{ $name }}</strong>
                    @if($status)
                        - Status: {{ $status }}
                    @endif
                    @if($nextAction)
                        - Next action: {{ $nextAction }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <p>Please log in to the cell monitoring system to review and update their consolidation status.</p>

    <p>Thank you for faithfully following up your consolidation members!</p>

    <p>Blessings,</p>
</body>
</html>


