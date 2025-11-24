<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitación - Evento Ferromex</title>
    <style>
        body {
            font-family: 'Gotham', Arial, sans-serif;
            line-height: 1.6;
            color: #33529F;
            background-color: #E5E7EB;
            margin: 0;
            padding: 0;
        }
        .bold {
            font-weight: 900;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            overflow: hidden;
        }
        .header-images {
            width: 100%;
            display: block;
        }
        .header-images img {
            width: 100%;
            display: block;
            margin: 0;
            padding: 0;
        }
        .mensaje-section {
            background-color: #B2D6EC;
            padding: 30px 40px;
            text-align: justify;
        }
        .mensaje-section p {
            margin: 0 0 15px 0;
            font-size: 15px;
            line-height: 1.8;
            color: #33529F;
        }
        .mensaje-section p:last-child {
            margin-bottom: 0;
        }
        .qr-section {
            text-align: center;
            background: #ffffff;
            padding: 40px 30px;
        }
        .qr-code {
            margin: 0 auto;
            display: inline-block;
        }
        .qr-code img {
            max-width: 250px;
            height: auto;
        }
        .datos-evento {
            width: 100%;
            display: block;
        }
        .datos-evento img {
            width: 90%;
            display: block;
            margin: 0 auto;
            padding-bottom: 10%;
        }
        .indicaciones-header {
            background-color: #D7282F;
            padding: 15px 40px;
            text-align: center;
        }
        .indicaciones-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
        }
        .indicaciones-content {
            background: #ffffff;
            padding: 30px 40px;
        }
        .indicaciones-content ul {
            margin: 0;
            padding-left: 20px;
            list-style-type: disc;
        }
        .indicaciones-content li {
            margin: 12px 0;
            font-size: 15px;
            line-height: 1.6;
            color: #33529F;
        }
        .footer {
            background-color: #6B7280;
            padding: 30px 40px;
            text-align: center;
        }
        .footer p {
            margin: 0 0 10px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #ffffff;
        }
        .footer p:last-child {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }
            .mensaje-section {
                padding: 20px 15px;
            }
            .mensaje-section p {
                font-size: 14px;
            }
            .qr-section {
                padding: 30px 15px;
            }
            .qr-code img {
                max-width: 200px;
            }
            .datos-evento img {
                width: 95%;
            }
            .indicaciones-header {
                padding: 12px 15px;
            }
            .indicaciones-header h2 {
                font-size: 16px;
            }
            .indicaciones-content {
                padding: 20px 15px;
            }
            .indicaciones-content li {
                font-size: 14px;
            }
            .footer {
                padding: 20px 15px;
            }
            .footer p {
                font-size: 13px;
            }
        }
    </style>
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header con dos imágenes SVG -->
        <div class="header-images">
            <img src="{{ asset('images/emails/invitacion/Header1.svg') }}" alt="Header 1">
            <img src="{{ asset('images/emails/invitacion/Header2.svg') }}" alt="Header 2">
        </div>
        
        <!-- Mensaje principal con fondo azul claro -->
        <div class="mensaje-section">
            <p><strong>Estimado(a) colaborador(a):</strong></p>
            
            <p>Este año ha sido especialmente significativo para todos nosotros.</p>
            
            <p>Cada reto, cada logro y cada proyecto alcanzado ha sido posible gracias al compromiso y esfuerzo de quienes forman parte de Grupo México Transportes.</p>
            
            <p>Por eso, queremos cerrar este ciclo celebrando juntos, reconociendo el trabajo que nos une y el camino que hemos construido como equipo.</p>
            
            <p class="bold">Para tu acceso al evento, te compartimos tu código QR personal. Este código es único e intransferible; te pedimos conservarlo y presentarlo el día del evento.</p>
        </div>
        
        <!-- Sección del código QR -->
        <div class="qr-section">
            <div class="qr-code">
                <img src="{{ $qrCodeUrl }}" alt="Código QR de {{ $guest->full_name }}">
            </div>
        </div>
        
        <!-- Imagen con datos del evento -->
        <div class="datos-evento">
            <img src="{{ asset('images/emails/invitacion/DatosEvento.svg') }}" alt="Datos del Evento">
        </div>
        
        <!-- Indicaciones de Acceso -->
        <div class="indicaciones-header">
            <h2>Indicaciones de Acceso</h2>
        </div>
        
        <div class="indicaciones-content">
            <ul>
                <li>Guarda tu código QR en tu celular o imprímelo.</li>
                <li>Preséntalo en el módulo de registro al llegar.</li>
                <li>Es indispensable para ingresar.</li>
                <li>Te recomendamos llegar con anticipación para agilizar tu acceso.</li>
            </ul>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Agradecemos tu dedicación durante este año y esperamos compartir contigo esta celebración especial.</p>
            <p>Grupo México Transportes Ferromex</p>
        </div>
    </div>
</body>
</html>