<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Referral Notification</title>
</head>
<body>
    <h2>New Referral Notification</h2>
    <p>{{ $message }}</p>
    
    <h3>Referral Details:</h3>
    <ul>
        <li><strong>Referral ID:</strong> #{{ $referral->id }}</li>
        <li><strong>Urgency:</strong> {{ ucfirst($referral->urgency) }}</li>
        <li><strong>Status:</strong> {{ ucfirst($referral->status) }}</li>
        <li><strong>Department:</strong> {{ $referral->department ?? 'Not assigned' }}</li>
    </ul>
    
    <p>Please log in to the system to view full details and take action.</p>
</body>
</html>

