<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Knowbuild</title>
</head>
<body>
    <h2>Hi {{ $name }},</h2>
    <p>Thanks for starting your journey with Knowbuild!</p>
    <p>
        Before we start, please complete your setup by clicking the button below:
    </p>
    <p>
        <a href="{{ $setupPasswordUrl }}" style="padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">
            Setup Your Password
        </a>
    </p>
    <p>
        You can check your browser configurations by referring to this <a href="#">help guide</a>. 
        Reach out to <a href="#">Knowbuild Help Guide</a> for support.
    </p>
    <p>Regards,</p>
    <p>The Knowbuild Team</p>
</body>
</html>
