<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
    <p>Hello {{ $user->name }},</p>
    <p>Thank you for registering. Please click the link below to verify your email address:</p>
    <p><a href="{{ $verificationUrl }}">Verify Email</a></p>
    <p>If you did not create an account, no further action is required.</p>
</body>
</html>
