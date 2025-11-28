<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación Concierto Sinfónico</title>
    <style>
        @import url('https://fonts.cdnfonts.com/css/gotham');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Gotham', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #33529F;
            background-color: #f4f4f4;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .header-section {
            width: 100%;
        }
        
        .header-section img {
            width: 100%;
            display: block;
        }
        
        .message-section {
            background-color: #B2D6EC;
            padding: 40px 30px;
            text-align: center;
        }
        
        .message-section p {
            font-size: 18px;
            line-height: 1.8;
            color: #33529F;
            margin: 0;
            text-align: justify;
        }
        
        .qr-section {
            background-color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        
        .qr-section img {
            max-width: 250px;
            width: 100%;
            height: auto;
            /* margin: 0 auto 20px; */
        }
        
        .event-details {
            font-size: 15px;
            line-height: 1.8;
            color: #33529F;
            text-align: center;
            margin-top: 20px;
        }
        
        .event-details p {
            margin: 5px 0;
        }

        p.Fecha {
            font-size: 30px;
        }

        p.Hora {
            font-size: 24px;
        }

        p.Evento {
            font-size: 20px;
        }
        
        .access-header {
            background-color: #33529F;
            padding: 15px 30px;
            text-align: center;
        }
        
        .access-header h2 {
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }
        
        .instructions-section {
            background-color: #ffffff;
            padding: 40px 30px;
            position: relative;
            display: table;
            width: 100%;
        }
        
        .violin-column {
            position: relative;
            width: 200px;
            display: table-cell;
            vertical-align: top;
        }
        
        .violin-image {
            position: absolute;
            top: -140px;
            left: -30px;
            width: 580px;
            height: auto;
        }
        
        .text-column {
            display: table-cell;
            vertical-align: top;
            padding-left: 20px;
        }
        
        .text-column p {
            font-size: 14px;
            line-height: 1.8;
            color: #33529F;
            margin-bottom: 15px;
        }
        
        .text-column p:last-child {
            margin-bottom: 0;
        }
        
        .footer-section {
            background-color: #6B7280;
            padding: 25px 30px;
            text-align: center;
        }
        
        .footer-section p {
            color: #ffffff;
            font-size: 14px;
            margin: 0;
        }
        
        /* Responsive Design */
        @media only screen and (max-width: 600px) {
            .message-section {
                padding: 30px 20px;
            }
            
            .message-section p {
                font-size: 14px;
            }
            
            .qr-section {
                padding: 30px 20px;
            }
            
            .qr-section img {
                max-width: 200px;
            }
            
            .event-details {
                font-size: 13px;
            }
            
            .access-header {
                padding: 12px 20px;
            }
            
            .access-header h2 {
                font-size: 16px;
            }
            
            .instructions-section {
                display: block;
                padding: 30px 20px;
            }
            
            .violin-column {
                display: none;
            }
            
            .text-column {
                display: block;
                padding-left: 0;
            }
            
            .text-column p {
                font-size: 13px;
            }
            
            .footer-section {
                padding: 20px 15px;
            }
            
            .footer-section p {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header-section">
            <img src="{{ asset('images/emails/invitacion/HeaderAzul.svg') }}" alt="Header Azul">
            <img src="{{ asset('images/emails/invitacion/Header2.svg') }}" alt="Header 2">
        </div>
        
        <!-- Message Section -->
        <div class="message-section">
            <p>
                Acompáñanos al concierto sinfónico, con música de <strong>The Beatles</strong>, realizado en colaboración con las divisiones de Minería, Transportes e Infraestructura este 2 de diciembre de 2025 en el Centro Cultural Roberto Cantoral, con acceso a partir de las 16:00 h.
            </p>
        </div>
        
        <!-- QR Code Section -->
        <div class="qr-section">
            <img src="{{ $qrCodeUrl }}" alt="Código QR">
            
            <div class="event-details">
                <p class="Fecha"><strong>Diciembre 2, 2025</strong></p>
                <p class="Hora">16:00 hrs</p>
                <p class="Evento">El Cantoral</p>
                <p>Puente Xoco s/n-Puerta A, Xoco, Benito Juárez, 03330 Ciudad de México, CDMX</p>
            </div>
        </div>
        
        <!-- Access Instructions Header -->
        <div class="access-header">
            <h2>Indicaciones de Acceso</h2>
        </div>
        
        <!-- Instructions Section with Violin -->
        <div class="instructions-section">
            <div class="violin-column">
                <img src="{{ asset('images/emails/invitacion/violin.png') }}" alt="Violín" class="violin-image">
            </div>
            <div class="text-column">
                <p>
                    Recuerda que el traslado es responsabilidad individual. Te recomendamos llegar con anticipación para agilizar tu acceso. Para estacionamiento, te sugerimos utilizar el Centro Comercial Mitikah, ya que el recinto podría no contar con espacios disponibles.
                </p>
                <p>
                    Por favor, guarda tu código QR en tu celular o imprímelo y preséntalo en el módulo de registro al llegar.
                </p>
                <p>
                    Este código es indispensable para ingresar.
                </p>
            </div>
        </div>
        
        <!-- Footer Section -->
        <div class="footer-section">
            <p>Grupo México Transportes Ferromex</p>
        </div>
    </div>
</body>
</html>
