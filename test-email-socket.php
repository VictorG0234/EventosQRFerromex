<?php
/**
 * Script de prueba de correo - PHP con Socket directo
 * Conecta directamente al servidor SMTP sin librerías externas
 */

// Configuración SMTP para Office 365
$smtpHost = 'smtp.office365.com';
$smtpPort = 587; // Puerto para STARTTLS
$fromEmail = 'laviaquenosune@ferromex.mx';
$fromName = 'Eventos';
$toEmail = 'desarrollo@peltiermkt.com';
$subject = 'Prueba SMTP Socket TLS - ' . date('Y-m-d H:i:s');

// Autenticación requerida para Office 365
$smtpUsername = 'laviaquenosune@ferromex.mx';
$smtpPassword = '1Fxe#Gmxt';
$useAuth = true; // REQUERIDO para Office 365
$useTLS = true;  // REQUERIDO para Office 365

echo "═══════════════════════════════════════════════════════════\n";
echo "  PRUEBA DE CORREO - OFFICE 365 CON TLS\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Configuración:\n";
echo "  Host: {$smtpHost}:{$smtpPort}\n";
echo "  TLS: " . ($useTLS ? "HABILITADO (STARTTLS)" : "DESHABILITADO") . "\n";
echo "  Usuario: " . ($useAuth ? $smtpUsername : '(sin autenticación)') . "\n";
echo "  Desde: {$fromEmail}\n";
echo "  Para: {$toEmail}\n\n";

// Función para enviar comando y leer respuesta
function sendCommand($socket, $command, $expectedCode = 250, $hideCommand = false) {
    if ($command !== null) {
        $displayCommand = $hideCommand ? str_repeat('*', 20) : $command;
        echo "> {$displayCommand}\n";
        fwrite($socket, $command . "\r\n");
    }
    
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == ' ') break;
    }
    
    echo "< {$response}";
    
    $code = intval(substr($response, 0, 3));
    if ($expectedCode && $code != $expectedCode) {
        echo "✗ Error: Se esperaba código {$expectedCode}, recibido {$code}\n";
        return false;
    }
    
    return true;
}

// Función para obtener las extensiones soportadas por el servidor
function getServerExtensions($socket) {
    fwrite($socket, "EHLO localhost\r\n");
    
    $extensions = [];
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        // Parsear extensiones SMTP
        if (preg_match('/^250[ -](.+)$/i', trim($line), $matches)) {
            $extensions[] = strtoupper(trim($matches[1]));
        }
        if (substr($line, 3, 1) == ' ') break;
    }
    
    return [$extensions, $response];
}

echo "Conectando a {$smtpHost}:{$smtpPort}...\n";

// Conectar al servidor SMTP
$socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);

if (!$socket) {
    echo "✗ Error de conexión: {$errstr} ({$errno})\n";
    exit(1);
}

echo "✓ Conexión establecida\n\n";

// Leer banner del servidor
sendCommand($socket, null, 220);

// EHLO y obtener extensiones
echo "Obteniendo capacidades del servidor...\n";
list($extensions, $ehloResponse) = getServerExtensions($socket);
echo "> EHLO localhost\n";
echo "< {$ehloResponse}";

// Habilitar STARTTLS si está disponible y es requerido
if ($useTLS) {
    $supportsSTARTTLS = in_array('STARTTLS', $extensions);
    
    if ($supportsSTARTTLS) {
        echo "\n✓ Servidor soporta STARTTLS\n";
        echo "Iniciando negociación TLS...\n";
        
        if (!sendCommand($socket, "STARTTLS", 220)) {
            echo "✗ Error al iniciar STARTTLS\n";
            fclose($socket);
            exit(1);
        }
        
        // Habilitar encriptación TLS en el socket
        $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        if (!$crypto) {
            echo "✗ Error al establecer conexión TLS\n";
            fclose($socket);
            exit(1);
        }
        
        echo "✓ Conexión TLS establecida exitosamente\n\n";
        
        // Volver a hacer EHLO después de STARTTLS
        echo "Reenviando EHLO después de TLS...\n";
        list($extensions, $ehloResponse) = getServerExtensions($socket);
        echo "> EHLO localhost\n";
        echo "< {$ehloResponse}";
    } else {
        echo "\n✗ Servidor NO soporta STARTTLS pero es requerido\n";
        fclose($socket);
        exit(1);
    }
}

// Verificar si el servidor soporta AUTH
$supportsAuth = false;
$authMethods = [];
foreach ($extensions as $ext) {
    if (strpos($ext, 'AUTH') === 0) {
        $supportsAuth = true;
        $authMethods = array_slice(explode(' ', $ext), 1);
        break;
    }
}

if ($supportsAuth) {
    echo "✓ Servidor soporta AUTH: " . implode(', ', $authMethods) . "\n\n";
} else {
    echo "⚠ Servidor NO soporta autenticación SMTP\n\n";
}

// Intentar autenticación si está habilitada
if ($useAuth && $supportsAuth) {
    echo "Intentando autenticación...\n";
    
    // Intentar AUTH LOGIN
    if (in_array('LOGIN', $authMethods)) {
        echo "Usando AUTH LOGIN...\n";
        
        if (!sendCommand($socket, "AUTH LOGIN", 334)) {
            echo "⚠ AUTH LOGIN falló, continuando sin autenticación...\n\n";
        } else {
            // Enviar username en base64
            $usernameB64 = base64_encode($smtpUsername);
            if (!sendCommand($socket, $usernameB64, 334, true)) {
                echo "⚠ Error al enviar usuario, continuando sin autenticación...\n\n";
            } else {
                // Enviar password en base64
                $passwordB64 = base64_encode($smtpPassword);
                if (!sendCommand($socket, $passwordB64, 235, true)) {
                    echo "⚠ Error de autenticación, continuando sin autenticación...\n\n";
                } else {
                    echo "✓ Autenticación exitosa!\n\n";
                }
            }
        }
    } 
    // Intentar AUTH PLAIN
    elseif (in_array('PLAIN', $authMethods)) {
        echo "Usando AUTH PLAIN...\n";
        $authString = base64_encode("\0{$smtpUsername}\0{$smtpPassword}");
        if (!sendCommand($socket, "AUTH PLAIN {$authString}", 235, true)) {
            echo "⚠ AUTH PLAIN falló, continuando sin autenticación...\n\n";
        } else {
            echo "✓ Autenticación exitosa!\n\n";
        }
    }
} elseif ($useAuth && !$supportsAuth) {
    echo "⚠ Autenticación solicitada pero el servidor no la soporta\n";
    echo "  Continuando sin autenticación...\n\n";
}

// MAIL FROM
echo "Iniciando transacción de correo...\n";
if (!sendCommand($socket, "MAIL FROM:<{$fromEmail}>", 250)) {
    fclose($socket);
    exit(1);
}

// RCPT TO
if (!sendCommand($socket, "RCPT TO:<{$toEmail}>", 250)) {
    fclose($socket);
    exit(1);
}

// DATA
if (!sendCommand($socket, "DATA", 354)) {
    fclose($socket);
    exit(1);
}

// Construir el mensaje
$message = "From: {$fromName} <{$fromEmail}>\r\n";
$message .= "To: <{$toEmail}>\r\n";
$message .= "Subject: {$subject}\r\n";
$message .= "Date: " . date('r') . "\r\n";
$message .= "MIME-Version: 1.0\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "\r\n";
$message .= "Este es un correo de prueba enviado mediante conexión socket directa.\n\n";
$message .= "Detalles de la conexión:\n";
$message .= "- Servidor: {$smtpHost}:{$smtpPort}\n";
$message .= "- Desde: {$fromEmail}\n";
$message .= "- Fecha: " . date('Y-m-d H:i:s') . "\n";
$message .= "\r\n.\r\n";

echo "> [Enviando contenido del mensaje...]\n";
fwrite($socket, $message);

// Leer respuesta final
$response = fgets($socket, 515);
echo "< {$response}";

$code = intval(substr($response, 0, 3));
if ($code == 250) {
    echo "\n✓ ¡Correo enviado exitosamente!\n";
    echo "  Verifica la bandeja de entrada de {$toEmail}\n";
} else {
    echo "\n✗ Error al enviar el correo (código {$code})\n";
}

// QUIT
sendCommand($socket, "QUIT", 221);

fclose($socket);

echo "\n═══════════════════════════════════════════════════════════\n";
