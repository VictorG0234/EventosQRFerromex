<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenido al Evento</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 30px;
        }
        .event-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .qr-section {
            text-align: center;
            background: #fff;
            padding: 25px;
            margin: 20px 0;
            border: 2px dashed #667eea;
            border-radius: 10px;
        }
        .qr-code {
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .highlight {
            color: #667eea;
            font-weight: 600;
        }
        .emoji {
            font-size: 24px;
            margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido/a!</h1>
            <p>Estás registrado/a para este evento</p>
        </div>
        
        <div class="content">
            <h2>Hola <span class="highlight">{{ $guest->name }}</span> <span class="emoji">👋</span></h2>
            
            <p>¡Nos complace confirmar tu registro para el evento!</p>
            
            <div class="event-info">
                <h3><span class="emoji">🎉</span> {{ $event->name }}</h3>
                <p><strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</p>
                <p><strong>⏰ Hora:</strong> {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}</p>
                @if($event->location)
                <p><strong>📍 Ubicación:</strong> {{ $event->location }}</p>
                @endif
                @if($event->description)
                <p><strong>📝 Descripción:</strong> {{ $event->description }}</p>
                @endif
            </div>

            <div class="qr-section">
                <h3><span class="emoji">📱</span> Tu Código QR Personal</h3>
                <p>Presenta este código QR en el evento para confirmar tu asistencia:</p>
                <div class="qr-code">
                    @if($qrCodeUrl)
                        <img src="{{ $qrCodeUrl }}" alt="Código QR para {{ $guest->name }}" style="max-width: 200px;">
                    @else
                        <p style="color: #e74c3c;">El código QR se generará próximamente</p>
                    @endif
                </div>
                <p><strong>ID de Invitado:</strong> {{ $guest->id }}</p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="#" class="btn">Ver Detalles del Evento</a>
                @if($qrCodeUrl)
                <a href="{{ $qrCodeUrl }}" class="btn" download>Descargar QR</a>
                @endif
            </div>

            <p><strong>Importante:</strong></p>
            <ul>
                <li>Guarda este código QR en tu teléfono</li>
                <li>Llega unos minutos antes del evento</li>
                <li>Si tienes problemas, contacta al organizador</li>
            </ul>
        </div>

        <div class="footer">
            <p>Este email fue enviado por <strong>QR Eventos</strong></p>
            <p>Organizador: {{ $event->user->name ?? 'Sistema de Eventos' }}</p>
            <p style="font-size: 12px; color: #999;">
                Si no puedes asistir, por favor informa al organizador con anticipación.
            </p>
        </div>
    </div>
</body>
</html>