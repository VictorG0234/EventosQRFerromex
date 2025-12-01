<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación - {{ $event->name }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            text-align: center;
            padding: 20px;
        }
        .invitation-image {
            max-width: 100%;
            height: auto;
            display: inline-block;
            margin: 0 auto;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <img src="{{ $invitationImageUrl }}" alt="Invitación {{ $event->name }}" class="invitation-image">
        
        {{-- <div class="footer">
            <p>Grupo México Transportes Ferromex</p>
            <p>{{ $event->name }}</p>
        </div> --}}
    </div>
</body>
</html>
