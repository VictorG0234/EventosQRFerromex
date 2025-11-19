<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitación - {{ $event->name }}</title>
    <style>
        /* ==================================
           VARIABLES DE PERSONALIZACIÓN
           Modifica estos valores para cambiar
           los colores y estilos de la marca
           ================================== */
        :root {
            /* Colores principales de la marca */
            --primary-color: #1e40af;        /* Azul principal */
            --secondary-color: #3b82f6;      /* Azul secundario */
            --accent-color: #60a5fa;         /* Azul de acento */
            --gradient-start: #1e40af;       /* Inicio del gradiente */
            --gradient-end: #3b82f6;         /* Fin del gradiente */
            
            /* Colores de texto */
            --text-primary: #1f2937;         /* Texto principal */
            --text-secondary: #6b7280;       /* Texto secundario */
            --text-light: #9ca3af;           /* Texto claro */
            
            /* Colores de fondo */
            --bg-primary: #ffffff;           /* Fondo principal */
            --bg-secondary: #f9fafb;         /* Fondo secundario */
            --bg-accent: #eff6ff;            /* Fondo de acento */
            
            /* Tipografía */
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --font-size-base: 16px;
            
            /* Espaciado */
            --spacing-sm: 10px;
            --spacing-md: 20px;
            --spacing-lg: 30px;
            --spacing-xl: 40px;
        }
        
        /* ==================================
           ESTILOS GENERALES
           ================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            font-size: var(--font-size-base);
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-secondary);
            padding: var(--spacing-md);
        }
        
        /* ==================================
           CONTENEDOR PRINCIPAL
           ================================== */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--bg-primary);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        /* ==================================
           HEADER - Personalizable con logo
           ================================== */
        .email-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
        }
        
        /* Si el cliente tiene logo, descomentar y ajustar */
        /*
        .company-logo {
            max-width: 200px;
            height: auto;
            margin-bottom: var(--spacing-md);
        }
        */
        
        .email-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .email-header .subtitle {
            font-size: 18px;
            font-weight: 400;
            opacity: 0.95;
        }
        
        /* ==================================
           CONTENIDO PRINCIPAL
           ================================== */
        .email-content {
            padding: var(--spacing-lg);
        }
        
        .greeting {
            font-size: 22px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            text-align: center;
        }
        
        .intro-text {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }
        
        /* ==================================
           INFORMACIÓN DEL EVENTO
           ================================== */
        .event-details {
            background: var(--bg-accent);
            border-left: 4px solid var(--primary-color);
            padding: var(--spacing-md);
            margin: var(--spacing-lg) 0;
            border-radius: 8px;
        }
        
        .event-details h3 {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
        }
        
        .event-details h3::before {
            content: "📅";
            margin-right: var(--spacing-sm);
            font-size: 24px;
        }
        
        .event-detail-item {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .event-detail-item:last-child {
            border-bottom: none;
        }
        
        .event-detail-item .label {
            font-weight: 600;
            color: var(--text-primary);
            min-width: 100px;
        }
        
        .event-detail-item .value {
            color: var(--text-secondary);
            flex: 1;
        }
        
        /* ==================================
           SECCIÓN DEL CÓDIGO QR
           ================================== */
        .qr-section {
            background: white;
            border: 3px dashed var(--accent-color);
            border-radius: 12px;
            padding: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
            text-align: center;
        }
        
        .qr-section h3 {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }
        
        .qr-section .qr-instruction {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: var(--spacing-md);
            line-height: 1.6;
        }
        
        .qr-code-container {
            background: white;
            padding: var(--spacing-md);
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .qr-code {
            max-width: 250px;
            height: auto;
            display: block;
        }
        
        .qr-note {
            font-size: 13px;
            color: var(--text-light);
            margin-top: var(--spacing-md);
            font-style: italic;
        }
        
        /* ==================================
           INFORMACIÓN DEL INVITADO
           ================================== */
        .guest-info {
            background: var(--bg-secondary);
            padding: var(--spacing-md);
            margin: var(--spacing-lg) 0;
            border-radius: 8px;
            text-align: center;
        }
        
        .guest-info h4 {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }
        
        .guest-credential {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            background: white;
            padding: 12px 20px;
            border-radius: 6px;
            display: inline-block;
            margin-top: var(--spacing-sm);
            border: 2px solid var(--accent-color);
        }
        
        /* ==================================
           INSTRUCCIONES IMPORTANTES
           ================================== */
        .important-info {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: var(--spacing-md);
            margin: var(--spacing-lg) 0;
            border-radius: 8px;
        }
        
        .important-info h4 {
            color: #92400e;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
        }
        
        .important-info h4::before {
            content: "⚠️";
            margin-right: var(--spacing-sm);
            font-size: 20px;
        }
        
        .important-info ul {
            margin-left: 20px;
            color: #78350f;
        }
        
        .important-info li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        /* ==================================
           BOTONES DE ACCIÓN
           ================================== */
        .action-buttons {
            text-align: center;
            margin: var(--spacing-lg) 0;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 14px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            margin: 8px;
            transition: background-color 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            background: var(--gradient-end);
        }
        
        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-secondary:hover {
            background: var(--bg-accent);
        }
        
        /* ==================================
           FOOTER
           ================================== */
        .email-footer {
            background: var(--bg-secondary);
            padding: var(--spacing-lg);
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .email-footer .company-name {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 18px;
            margin-bottom: var(--spacing-sm);
        }
        
        .email-footer .contact-info {
            font-size: 14px;
            color: var(--text-secondary);
            margin: 8px 0;
        }
        
        .email-footer .disclaimer {
            font-size: 12px;
            color: var(--text-light);
            margin-top: var(--spacing-md);
            line-height: 1.5;
        }
        
        /* ==================================
           RESPONSIVE DESIGN
           ================================== */
        @media only screen and (max-width: 600px) {
            body {
                padding: 0;
            }
            
            .email-container {
                border-radius: 0;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
            
            .email-header .subtitle {
                font-size: 16px;
            }
            
            .email-content {
                padding: var(--spacing-md);
            }
            
            .qr-code {
                max-width: 200px;
            }
            
            .btn {
                display: block;
                margin: 10px 0;
            }
        }
        
        /* ==================================
           MODO OSCURO (opcional)
           ================================== */
        @media (prefers-color-scheme: dark) {
            /* Descomentar si se desea soporte para modo oscuro */
            /*
            body {
                background-color: #1f2937;
            }
            
            .email-container {
                background: #374151;
                color: #f9fafb;
            }
            */
        }
    </style>
</head>
<body>
    <div class="email-container">
        
        <!-- HEADER -->
        <div class="email-header">
            <!-- Si el cliente tiene logo de empresa, descomentar y agregar la URL -->
            <!--
            <img src="{{ asset('images/company-logo.png') }}" alt="Logo de la Empresa" class="company-logo">
            -->
            
            <h1>¡Estás Invitado!</h1>
            <div class="subtitle">{{ $event->name }}</div>
        </div>
        
        <!-- CONTENIDO PRINCIPAL -->
        <div class="email-content">
            
            <!-- Saludo personalizado -->
            <div class="greeting">
                ¡Hola, {{ $guest->full_name }}!
            </div>
            
            <p class="intro-text">
                Nos complace confirmar tu registro para nuestro evento especial. 
                Este correo contiene toda la información que necesitas para asistir, 
                incluyendo tu <strong>código QR personal</strong> para el acceso al evento.
            </p>
            
            <!-- DETALLES DEL EVENTO -->
            <div class="event-details">
                <h3>Detalles del Evento</h3>
                
                <div class="event-detail-item">
                    <span class="label">📌 Evento:</span>
                    <span class="value">{{ $event->name }}</span>
                </div>
                
                @if($event->description)
                <div class="event-detail-item">
                    <span class="label">📝 Descripción:</span>
                    <span class="value">{{ $event->description }}</span>
                </div>
                @endif
                
                <div class="event-detail-item">
                    <span class="label">📅 Fecha:</span>
                    <span class="value">{{ $event->event_date->format('d/m/Y') }}</span>
                </div>
                
                @if($event->start_time)
                <div class="event-detail-item">
                    <span class="label">🕐 Hora:</span>
                    <span class="value">{{ $event->start_time->format('H:i') }}</span>
                </div>
                @endif
                
                <div class="event-detail-item">
                    <span class="label">📍 Ubicación:</span>
                    <span class="value">{{ $event->location }}</span>
                </div>
            </div>
            
            <!-- CÓDIGO QR -->
            <div class="qr-section">
                <h3>🎫 Tu Código QR de Acceso</h3>
                
                <p class="qr-instruction">
                    Este es tu código QR personal e intransferible. Preséntalo en el evento 
                    para registrar tu asistencia. Puedes mostrarlo desde tu celular o imprimirlo.
                </p>
                
                @if($qrCodeUrl)
                <div class="qr-code-container">
                    <img src="{{ $qrCodeUrl }}" alt="Código QR de {{ $guest->full_name }}" class="qr-code">
                </div>
                
                <p class="qr-note">
                    💡 También encontrarás el código QR adjunto a este correo
                </p>
                @else
                <p style="color: #ef4444;">
                    ⚠️ Tu código QR está siendo generado y será enviado en breve.
                </p>
                @endif
            </div>
            
            <!-- INFORMACIÓN DEL INVITADO -->
            <div class="guest-info">
                <h4>Tus Credenciales de Acceso</h4>
                <p style="margin: 10px 0; color: var(--text-secondary); font-size: 14px;">
                    Guarda esta información para futuras consultas
                </p>
                <div class="guest-credential">
                    {{ $guest->compania }}-{{ $guest->numero_empleado }}
                </div>
            </div>
            
            <!-- INSTRUCCIONES IMPORTANTES -->
            <div class="important-info">
                <h4>Instrucciones Importantes</h4>
                <ul>
                    <li><strong>Llega temprano:</strong> Te recomendamos llegar al menos 15 minutos antes del inicio del evento.</li>
                    <li><strong>Presenta tu QR:</strong> Ten listo tu código QR en tu dispositivo móvil o impreso para agilizar el registro.</li>
                    <li><strong>Código personal:</strong> Este código QR es exclusivo para ti. No lo compartas.</li>
                    @if($guest->categoria_rifa)
                    <li><strong>Participación en rifa:</strong> ¡Estás participando en la categoría {{ $guest->categoria_rifa }}!</li>
                    @endif
                </ul>
            </div>
            
            <!-- BOTONES DE ACCIÓN -->
            <div class="action-buttons">
                <a href="{{ route('public.event.guest.details', [$event->public_token, $guest->id]) }}" class="btn">
                    Ver Mi Invitación
                </a>
                
                @if($qrCodeUrl)
                <a href="{{ $qrCodeUrl }}" download="codigo-qr-{{ $guest->numero_empleado }}.png" class="btn btn-secondary">
                    Descargar QR
                </a>
                @endif
            </div>
            
            <!-- MENSAJE ADICIONAL -->
            <p style="text-align: center; margin-top: var(--spacing-lg); color: var(--text-secondary); font-size: 14px;">
                ¿Tienes preguntas? No dudes en contactarnos. 
                <strong>¡Esperamos verte en el evento!</strong>
            </p>
            
        </div>
        
        <!-- FOOTER -->
        <div class="email-footer">
            <div class="company-name">
                <!-- Personalizar con el nombre de la empresa -->
                Ferromex - Sistema de Eventos
            </div>
            
            <div class="contact-info">
                📧 Correo: eventos@ferromex.com.mx
            </div>
            
            <div class="contact-info">
                📱 Teléfono: +52 (55) 1234-5678
            </div>
            
            <div class="disclaimer">
                Este correo electrónico es generado automáticamente. 
                Por favor, no responder a este mensaje. 
                Si necesitas asistencia, contacta directamente con el equipo de eventos.
            </div>
            
            <div style="margin-top: var(--spacing-md); color: var(--text-light); font-size: 12px;">
                © {{ date('Y') }} Ferromex. Todos los derechos reservados.
            </div>
        </div>
        
    </div>
</body>
</html>
