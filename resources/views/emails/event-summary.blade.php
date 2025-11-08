<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resumen del Evento</title>
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
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 25px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #6c5ce7;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #6c5ce7;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        .event-info {
            background: #f8f9fa;
            border-left: 4px solid #6c5ce7;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #6c5ce7, #a29bfe);
            color: white;
            text-align: center;
            padding: 8px 0;
            font-weight: bold;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #6c5ce7;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #5f3dc4;
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
            color: #6c5ce7;
            font-weight: 600;
        }
        .emoji {
            font-size: 24px;
            margin: 0 10px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .insights {
            background: #e8f4ff;
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
            <h1>📊 Resumen del Evento</h1>
            <p>Análisis completo de tu evento</p>
        </div>
        
        <div class="content">
            <h2>Hola <span class="highlight">{{ $organizer->name }}</span> <span class="emoji">👋</span></h2>
            
            <p>Aquí tienes un resumen completo de cómo se desarrolló tu evento.</p>
            
            <div class="event-info">
                <h3><span class="emoji">🎉</span> {{ $event->name }}</h3>
                <p><strong>📅 Fecha:</strong> {{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</p>
                <p><strong>⏰ Hora:</strong> {{ \Carbon\Carbon::parse($event->time)->format('H:i') }}</p>
                @if($event->location)
                <p><strong>📍 Ubicación:</strong> {{ $event->location }}</p>
                @endif
                <p><strong>📈 Estado:</strong> 
                    @if($event->is_active)
                        <span class="success">ACTIVO</span>
                    @else
                        <span class="warning">FINALIZADO</span>
                    @endif
                </p>
            </div>

            <h3><span class="emoji">📈</span> Estadísticas de Asistencia</h3>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['total_guests'] }}</div>
                    <div class="stat-label">Total Invitados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['attended_guests'] }}</div>
                    <div class="stat-label">Asistieron</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['pending_guests'] }}</div>
                    <div class="stat-label">No Asistieron</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $statistics['attendance_rate'] }}%</div>
                    <div class="stat-label">Tasa de Asistencia</div>
                </div>
            </div>

            <h4>Progreso de Asistencia</h4>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $statistics['attendance_rate'] }}%">
                    {{ $statistics['attendance_rate'] }}% de asistencia
                </div>
            </div>

            <div class="insights">
                <h3><span class="emoji">💡</span> Análisis e Insights</h3>
                
                @if($statistics['attendance_rate'] >= 80)
                <p><span class="success">🎯 ¡Excelente!</span> Tu evento tuvo una asistencia muy alta ({{ $statistics['attendance_rate'] }}%). Esto indica un gran interés y una buena planificación.</p>
                @elseif($statistics['attendance_rate'] >= 60)
                <p><span class="success">👍 ¡Bien!</span> Tu evento tuvo una buena asistencia ({{ $statistics['attendance_rate'] }}%). Está por encima del promedio típico.</p>
                @elseif($statistics['attendance_rate'] >= 40)
                <p><span class="warning">⚠️ Regular</span> La asistencia fue moderada ({{ $statistics['attendance_rate'] }}%). Considera enviar más recordatorios para futuros eventos.</p>
                @else
                <p><span style="color: #dc3545; font-weight: bold;">📉 Oportunidad</span> La asistencia fue baja ({{ $statistics['attendance_rate'] }}%). Revisa la estrategia de comunicación y horarios para próximos eventos.</p>
                @endif

                <p><strong>Recomendaciones:</strong></p>
                <ul>
                    @if($statistics['attendance_rate'] < 70)
                    <li>Envía recordatorios 24h y 2h antes del evento</li>
                    <li>Considera ajustar horarios según disponibilidad del público</li>
                    @endif
                    <li>Recopila feedback de los asistentes</li>
                    <li>Analiza las horas de mayor llegada para futuros eventos</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="#" class="btn">📊 Ver Análisis Completo</a>
                <a href="#" class="btn">📋 Exportar Datos</a>
                <a href="#" class="btn">📧 Enviar Encuesta</a>
            </div>

            <div style="background: #e8f5e8; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <strong>🎉 ¡Felicitaciones!</strong> Has completado exitosamente la gestión de tu evento. Los datos están disponibles para análisis futuros.
            </div>

            <p><strong>Próximos pasos:</strong></p>
            <ul>
                <li>Revisa los comentarios de los asistentes</li>
                <li>Planifica tu próximo evento con estas métricas</li>
                <li>Mantén el contacto con tu audiencia</li>
            </ul>
        </div>

        <div class="footer">
            <p>Resumen generado por <strong>QR Eventos</strong></p>
            <p>Sistema de gestión profesional de eventos</p>
            <p style="font-size: 12px; color: #999;">
                Todos los datos están disponibles en tu dashboard para análisis más detallado.
            </p>
        </div>
    </div>
</body>
</html>