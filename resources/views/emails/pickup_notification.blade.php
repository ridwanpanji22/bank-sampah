<!DOCTYPE html>
<html>
<head>
    <title>Pickup Notification</title>
</head>
<body>
    <h1>Pickup Scheduled</h1>
    <p>Your trash pickuped by <b>{{ $schedule->driver }}</b> for order number <b>{{ $schedule->number_order }}</b> is <b>{{ $schedule->status }}</b>.</p>
</body>
</html>
