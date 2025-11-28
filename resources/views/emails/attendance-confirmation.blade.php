<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asistencia Confirmada</title>
    <style>
        @import url('https://fonts.cdnfonts.com/css/gotham');
        
        body {
            font-family: 'Gotham', 'Helvetica Neue', Arial, sans-serif;
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
            background: #D7282F;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header img {
            max-width: 200px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 30px;
        }
        .success-badge {
            background: #D7282F;
            color: white;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
        }
        .event-info {
            background: #f8f9fa;
            border-left: 4px solid #D7282F;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .attendance-info {
            background: #fde8e9;
            border: 2px solid #D7282F;
            padding: 25px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            background: #D7282F;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #b01f24;
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
            color: #D7282F;
            font-weight: 600;
        }
        .emoji {
            font-size: 24px;
            margin: 0 10px;
        }
        .next-steps {
            background: #cce5ff;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/emails/invitacion/LogoGMT.svg') }}" alt="Grupo México Transportes">
            <h1>✅ Asistencia Confirmada</h1>
            <p>¡Bienvenido/a al evento!</p>
        </div>
        
        <div class="content">
            <h2>¡Hola <span class="highlight">{{ $guest->name }}</span>! <span class="emoji">🎉</span></h2>
            
            <div class="success-badge">
                <span class="emoji">✅</span> Tu asistencia ha sido registrada exitosamente
            </div>
            
            <p>Nos complace confirmar que tu presencia en el evento ha sido registrada correctamente.</p>
            
            <div class="event-info">
                <h3><span class="emoji">🎉</span> {{ $event->name }}</h3>
                <p><strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</p>
                <p><strong>⏰ Hora:</strong> {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}</p>
                @if($event->location)
                <p><strong>📍 Ubicación:</strong> {{ $event->location }}</p>
                @endif
            </div>

            <div class="attendance-info">
                <h3><span class="emoji">🕐</span> Información de Asistencia</h3>
                @if($attendance)
                <p><strong>Hora de llegada:</strong> {{ \Carbon\Carbon::parse($attendance->created_at)->format('H:i') }}</p>
                <p><strong>Fecha de registro:</strong> {{ \Carbon\Carbon::parse($attendance->created_at)->format('d/m/Y') }}</p>
                @endif
                <p><strong>Estado:</strong> <span style="color: #D7282F; font-weight: bold;">CONFIRMADO ✓</span></p>
            </div>

            <div class="next-steps">
                <h3><span class="emoji">📋</span> Próximos Pasos</h3>
                <ul>
                    <li>🎯 Participa activamente en las actividades del evento</li>
                    <li>🤝 Conecta con otros asistentes</li>
                    <li>📸 Comparte tu experiencia en redes sociales</li>
                    @if($event->has_raffle)
                    <li>🎁 Estate atento/a a los sorteos que se realizarán</li>
                    @endif
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="#" class="btn">📋 Ver Agenda del Evento</a>
                <a href="#" class="btn">📧 Contactar Organizador</a>
            </div>

            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <strong>💡 Consejo:</strong> Guarda este email como confirmación de tu asistencia.
            </div>

            <p>¡Esperamos que disfrutes mucho del evento!</p>
        </div>

        <div class="footer">
            <p><strong>Grupo México Transportes Ferromex</strong></p>
            <p style="font-size: 12px; color: #999;">
                Este email confirma tu participación en el evento. Gracias por asistir.
            </p>
        </div>
    </div>
</body>
</html>