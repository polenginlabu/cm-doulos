<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Itinerary for Today</title>
</head>
<body>
    <p>Hi {{ $user->name ?? 'there' }},</p>
    <p>Here is your itinerary for {{ $date->format('l, M d') }}:</p>
    <ul>
        @foreach($activities as $activity)
            <li>{{ $activity }}</li>
        @endforeach
    </ul>
    <p>Have a blessed day.</p>
</body>
</html>
