<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recordatorio de Evento</title>
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
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
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
        .countdown {
            background: linear-gradient(135deg, #ff7e5f, #feb47b);
            color: white;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .event-info {
            background: #f8f9fa;
            border-left: 4px solid #ff7e5f;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .qr-section {
            text-align: center;
            background: #fff;
            padding: 25px;
            margin: 20px 0;
            border: 2px dashed #ff7e5f;
            border-radius: 10px;
        }
        .btn {
            display: inline-block;
            background: #ff7e5f;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #ff6b47;
        }
        .checklist {
            background: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
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
            color: #ff7e5f;
            font-weight: 600;
        }
        .emoji {
            font-size: 24px;
            margin: 0 10px;
        }
        .urgent {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Recordatorio de Evento</h1>
            @if($hoursBeforeEvent <= 2)
            <p>¡Tu evento comienza muy pronto!</p>
            @else
            <p>Tu evento está próximo a comenzar</p>
            @endif
        </div>
        
        <div class="content">
            <h2>Hola <span class="highlight">{{ $guest->name }}</span> <span class="emoji">👋</span></h2>
            
            @if($hoursBeforeEvent <= 2)
            <div class="urgent">
                <strong>🚨 ¡ATENCIÓN!</strong> Tu evento comienza en menos de {{ $hoursBeforeEvent }} horas.
            </div>
            @endif

            <div class="countdown">
                @if($hoursBeforeEvent == 1)
                <span class="emoji">⏰</span> Tu evento comienza en 1 hora
                @elseif($hoursBeforeEvent < 1)
                <span class="emoji">🚀</span> ¡Tu evento está por comenzar!
                @else
                <span class="emoji">⏰</span> Tu evento comienza en {{ $hoursBeforeEvent }} horas
                @endif
            </div>
            
            <div class="event-info">
                <h3><span class="emoji">🎉</span> {{ $event->name }}</h3>
                <p><strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</p>
                <p><strong>⏰ Hora:</strong> {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}</p>
                @if($event->location)
                <p><strong>📍 Ubicación:</strong> {{ $event->location }}</p>
                @endif
            </div>

            <div class="checklist">
                <h3><span class="emoji">✅</span> Lista de Verificación</h3>
                <ul>
                    <li>📱 Ten tu código QR listo en el teléfono</li>
                    <li>🕐 Llega 15 minutos antes</li>
                    @if($event->location)
                    <li>🗺️ Verifica la ubicación: {{ $event->location }}</li>
                    @endif
                    <li>📞 Ten a mano el contacto del organizador</li>
                </ul>
            </div>

            <div class="qr-section">
                <h3><span class="emoji">📱</span> Tu Código QR</h3>
                <p>No olvides presentar este código en el evento:</p>
                @if($qrCodeUrl)
                    <img src="{{ $qrCodeUrl }}" alt="Código QR para {{ $guest->name }}" style="max-width: 200px;">
                @else
                    <p style="color: #e74c3c;">El código QR se generará próximamente</p>
                @endif
                <p><strong>ID:</strong> {{ $guest->id }}</p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                @if($qrCodeUrl)
                <a href="{{ $qrCodeUrl }}" class="btn" download>📱 Descargar QR</a>
                @endif
                <a href="#" class="btn">📍 Ver Ubicación</a>
            </div>

            @if($hoursBeforeEvent <= 6)
            <div class="urgent">
                <h4>🚨 Recordatorios de Último Momento:</h4>
                <ul>
                    <li>Verifica que tu teléfono esté cargado</li>
                    <li>Descarga el código QR por si no hay internet</li>
                    <li>Confirma la ubicación del evento</li>
                </ul>
            </div>
            @endif

            <p><strong>¿Necesitas ayuda?</strong> Contacta al organizador si tienes alguna pregunta.</p>
        </div>

        <div class="footer">
            <p>Este recordatorio fue enviado por <strong>QR Eventos</strong></p>
            <p>Organizador: {{ $event->user->name ?? 'Sistema de Eventos' }}</p>
            <p style="font-size: 12px; color: #999;">
                Si no puedes asistir, por favor avisa al organizador lo antes posible.
            </p>
        </div>
    </div>
</body>
</html>