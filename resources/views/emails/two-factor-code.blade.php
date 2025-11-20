<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }
        .code-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #ffffff;
            font-family: 'Courier New', monospace;
        }
        .code-label {
            color: #ffffff;
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .info {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info p {
            margin: 5px 0;
            font-size: 14px;
            color: #555555;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 5px 0;
            font-size: 14px;
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666666;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Código de Verificación</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                <p>Hola <strong>{{ $user->name }}</strong>,</p>
            </div>
            
            <p>Has solicitado acceso al sistema de Eventos Ferromex. Para continuar, ingresa el siguiente código de verificación:</p>
            
            <div class="code-container">
                <div class="code-label">TU CÓDIGO DE VERIFICACIÓN</div>
                <div class="code">{{ $code }}</div>
            </div>
            
            <div class="info">
                <p><strong>⏱️ Tiempo de expiración:</strong> {{ $expiresIn }} minutos</p>
                <p><strong>📱 Uso único:</strong> Este código solo puede usarse una vez</p>
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Importante:</strong></p>
                <p>• Si no solicitaste este código, ignora este mensaje</p>
                <p>• Nunca compartas este código con nadie</p>
                <p>• Nuestro equipo nunca te pedirá este código por teléfono o email</p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Sistema de Eventos Ferromex</strong></p>
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p style="margin-top: 10px; color: #999999; font-size: 11px;">
                © {{ date('Y') }} Ferromex. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
