<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .qrcode-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>{{ $user->name }} QR Code</h1>
    <div class="qrcode-container">
        {!! $qrCode !!}
    </div>
</body>
</html>
