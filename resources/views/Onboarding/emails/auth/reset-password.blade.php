<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body>
    <p>Hi,</p>
    <p>We received a request to reset your password. Use the OTP below:</p>
    <h2>{{ $verificationCode }}</h2>
    <p>Or click the link below:</p>
    <p><a href="{{ $resetUrl }}">Reset Password</a></p>
    <p>If you didn't request this, please ignore this email.</p>
    <p>Regards,</p>
    <p>The Knowbuild Team</p>
</body>
</html>
