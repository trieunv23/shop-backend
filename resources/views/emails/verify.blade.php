<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
</head>
<body>
    <h1>Verify Your Email Address</h1>
    <p>Dear {{ $user->name }},</p>
    <p>Please use the following code to verify your email address:</p>
    <h2>{{ $verificationCode }}</h2>
    <p>Thank you!</p>
</body>
</html>
