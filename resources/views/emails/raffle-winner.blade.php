<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>¡Has Ganado un Premio!</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: celebrate 0.6s ease-in-out;
        }
        @keyframes celebrate {
            0% { transform: scale(0.8) rotate(-5deg); opacity: 0; }
            50% { transform: scale(1.05) rotate(2deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: "🎉🎊✨🎁🏆";
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            font-size: 30px;
            opacity: 0.3;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .content {
            padding: 40px 30px;
        }
        .winner-announcement {
            background: linear-gradient(135deg, #ff9ff3 0%, #f368e0 100%);
            color: white;
            padding: 30px;
            margin: 20px 0;
            text-align: center;
            border-radius: 15px;
            font-size: 20px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .prize-card {
            background: #fff5f5;
            border: 3px solid #ff6b6b;
            padding: 30px;
            margin: 25px 0;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.2);
        }
        .prize-icon {
            font-size: 80px;
            margin: 20px 0;
        }
        .event-info {
            background: #f8f9fa;
            border-left: 4px solid #ff6b6b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            margin: 10px 5px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
            text-transform: uppercase;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.6);
        }
        .instructions {
            background: #e8f8f5;
            border-left: 4px solid #1dd1a1;
            padding: 20px;
            margin: 25px 0;
            border-radius: 5px;
        }
        .footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .highlight {
            color: #ff6b6b;
            font-weight: 700;
            font-size: 1.2em;
        }
        .emoji {
            font-size: 32px;
            margin: 0 10px;
        }
        .confetti {
            position: absolute;
            font-size: 25px;
            opacity: 0.7;
            animation: fall 4s linear infinite;
        }
        @keyframes fall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(600px) rotate(360deg); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="confetti" style="left: 10%; animation-delay: 0s;">🎊</div>
    <div class="confetti" style="left: 20%; animation-delay: 1s;">🎉</div>
    <div class="confetti" style="left: 30%; animation-delay: 0.5s;">✨</div>
    <div class="confetti" style="left: 40%; animation-delay: 2s;">🎁</div>
    <div class="confetti" style="left: 50%; animation-delay: 1.5s;">🏆</div>
    <div class="confetti" style="left: 60%; animation-delay: 0.3s;">🎊</div>
    <div class="confetti" style="left: 70%; animation-delay: 2.5s;">🎉</div>
    <div class="confetti" style="left: 80%; animation-delay: 0.8s;">✨</div>
    <div class="confetti" style="left: 90%; animation-delay: 1.8s;">🎁</div>

    <div class="container">
        <div class="header">
            <h1>🎉 ¡FELICIDADES! 🎉</h1>
            <p style="font-size: 20px; margin: 10px 0;">¡Eres uno de los ganadores!</p>
        </div>
        
        <div class="content">
            <div class="winner-announcement">
                <span class="emoji">🏆</span>
                ¡<span class="highlight">{{ $guest->name }}</span>, has ganado un premio!
                <span class="emoji">🏆</span>
            </div>
            
            <p style="font-size: 18px; text-align: center; margin: 30px 0;">
                ¡Increíble! Has sido seleccionado/a como ganador/a en el sorteo de nuestro evento.
            </p>
            
            <div class="prize-card">
                <div class="prize-icon">🎁</div>
                <h2 style="color: #ff6b6b; margin: 20px 0;">Tu Premio</h2>
                <h3 style="font-size: 24px; color: #333; margin: 15px 0;">
                    {{ $prize->name ?? 'Premio Especial' }}
                </h3>
                @if($prize && $prize->description)
                <p style="font-size: 16px; color: #666; margin: 15px 0;">
                    {{ $prize->description }}
                </p>
                @endif
                @if($prize && $prize->value)
                <p style="font-size: 18px; font-weight: bold; color: #1dd1a1;">
                    Valor: ${{ number_format($prize->value, 0, ',', '.') }}
                </p>
                @endif
            </div>
            
            <div class="event-info">
                <h3><span class="emoji">🎪</span> Evento: {{ $event->name }}</h3>
                <p><strong>📅 Fecha del sorteo:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                <p><strong>🎯 Organizador:</strong> {{ $organizer->name }}</p>
            </div>

            <div class="instructions">
                <h3><span class="emoji">📋</span> Cómo reclamar tu premio</h3>
                <ol>
                    <li><strong>📧 Contacta al organizador</strong> usando los datos de contacto proporcionados</li>
                    <li><strong>🆔 Presenta tu identificación</strong> y menciona tu ID de invitado: <strong>#{{ $guest->id }}</strong></li>
                    <li><strong>📅 Coordina la entrega</strong> del premio según las instrucciones del organizador</li>
                    <li><strong>📸 Comparte tu alegría</strong> en redes sociales mencionando el evento</li>
                </ol>
            </div>

            <div style="text-align: center; margin: 40px 0;">
                @if($organizer->email)
                <a href="mailto:{{ $organizer->email }}" class="btn">📧 Contactar Organizador</a>
                @endif
                <a href="#" class="btn">📋 Ver Detalles</a>
            </div>

            <div style="background: linear-gradient(135deg, #ff7675, #fd79a8); color: white; padding: 25px; margin: 25px 0; border-radius: 15px; text-align: center;">
                <h3 style="margin: 0 0 15px 0;"><span class="emoji">⏰</span> ¡Importante!</h3>
                <p style="margin: 0; font-size: 16px;">
                    Tienes <strong>30 días</strong> para reclamar tu premio. Contacta al organizador lo antes posible.
                </p>
            </div>

            <div style="background: #ffeaa7; padding: 20px; margin: 20px 0; border-radius: 10px; text-align: center; color: #2d3436;">
                <strong>🎊 ¡Gracias por participar!</strong><br>
                Tu presencia hizo posible este momento especial. ¡Disfruta tu premio!
            </div>
        </div>

        <div class="footer">
            <p>🎉 <strong>Notificación de premio enviada por QR Eventos</strong> 🎉</p>
            <p>Sistema de sorteos y gestión de eventos</p>
            <p style="font-size: 12px; color: #999; margin-top: 15px;">
                Esta notificación confirma tu estatus como ganador/a. Conserva este email como comprobante hasta recibir tu premio.
            </p>
        </div>
    </div>
</body>
</html>