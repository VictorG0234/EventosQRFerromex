<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mensaje del Organizador</title>
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
            background: linear-gradient(135deg, #e17055 0%, #fdcb6e 100%);
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
        .message-from {
            background: #f1f3f4;
            border-left: 4px solid #e17055;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        .message-content {
            background: #fff;
            border: 2px solid #e17055;
            padding: 25px;
            margin: 25px 0;
            border-radius: 10px;
            font-size: 16px;
            line-height: 1.7;
        }
        .event-info {
            background: #f8f9fa;
            border-left: 4px solid #e17055;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            background: #e17055;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #d63031;
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
            color: #e17055;
            font-weight: 600;
        }
        .emoji {
            font-size: 24px;
            margin: 0 10px;
        }
        .organizer-signature {
            background: #fef7f0;
            border-left: 4px solid #fdcb6e;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Mensaje del Organizador</h1>
            <p>Comunicación importante para ti</p>
        </div>
        
        <div class="content">
            <h2>Hola <span class="highlight">{{ $guest->name }}</span> <span class="emoji">👋</span></h2>
            
            <div class="message-from">
                <strong>📬 De:</strong> {{ $organizer->name }} (Organizador del evento)
                <br>
                <strong>📅 Enviado:</strong> {{ now()->format('d/m/Y H:i') }}
            </div>
            
            <div class="event-info">
                <h3><span class="emoji">🎉</span> Evento: {{ $event->name }}</h3>
                <p><strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</p>
                <p><strong>⏰ Hora:</strong> {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}</p>
                @if($event->location)
                <p><strong>📍 Ubicación:</strong> {{ $event->location }}</p>
                @endif
            </div>

            <div class="message-content">
                {!! nl2br(e($customMessage)) !!}
            </div>

            <div class="organizer-signature">
                <h4><span class="emoji">✍️</span> Enviado por:</h4>
                <p><strong>{{ $organizer->name }}</strong></p>
                <p>Organizador del evento: {{ $event->name }}</p>
                @if($organizer->email)
                <p>📧 {{ $organizer->email }}</p>
                @endif
            </div>

            <div style="text-align: center; margin: 30px 0;">
                @if($organizer->email)
                <a href="mailto:{{ $organizer->email }}" class="btn">📧 Responder</a>
                @endif
                <a href="#" class="btn">📋 Ver Evento</a>
            </div>

            <div style="background: #e1f5fe; border-left: 4px solid #0288d1; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <strong>💡 Recordatorio:</strong> Este mensaje es específicamente para los invitados del evento "{{ $event->name }}". Si tienes preguntas adicionales, no dudes en contactar al organizador.
            </div>

            <div style="background: #f3e5f5; border-left: 4px solid #9c27b0; padding: 15px; margin: 20px 0; border-radius: 5px; font-size: 14px;">
                <strong>📱 Tu código QR sigue activo</strong><br>
                Recuerda que puedes usar tu código QR personal para confirmar tu asistencia en el evento.
                <br>
                <strong>ID de invitado:</strong> #{{ $guest->id }}
            </div>
        </div>

        <div class="footer">
            <p>Mensaje enviado a través de <strong>QR Eventos</strong></p>
            <p>Sistema de comunicación para eventos</p>
            <p style="font-size: 12px; color: #999;">
                Este mensaje fue enviado por el organizador del evento. Si no deseas recibir más comunicaciones de este evento, contacta al organizador directamente.
            </p>
        </div>
    </div>
</body>
</html>