<?php
/**
 * Script simple de prueba SMTP para Office 365
 * Versión simplificada para debugging rápido
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "═══════════════════════════════════════════════════════════\n";
echo "  PRUEBA RÁPIDA SMTP - Office 365\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$config = [
    'host' => 'smtp.office365.com',
    'port' => 587,
    'username' => 'laviaquenosune@ferromex.mx',
    'password' => '1Fxe#Gmxt',
    'from' => 'laviaquenosune@ferromex.mx',
    'to' => 'desarrollo@peltiermkt.com',
];

echo "Configuración:\n";
foreach ($config as $key => $value) {
    $display = ($key === 'password') ? str_repeat('*', 10) : $value;
    echo "  " . ucfirst($key) . ": {$display}\n";
}
echo "\n";

// Paso 1: Conectar
echo "[1/6] Conectando a {$config['host']}:{$config['port']}... ";
$socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 10);

if (!$socket) {
    die("❌ FALLÓ\n      Error: {$errstr} ({$errno})\n");
}
echo "✅\n";

// Paso 2: Leer banner
echo "[2/6] Leyendo banner del servidor... ";
$banner = fgets($socket, 512);
if (substr($banner, 0, 3) != '220') {
    die("❌ FALLÓ\n      Respuesta: {$banner}");
}
echo "✅\n";

// Paso 3: EHLO
echo "[3/6] Enviando EHLO... ";
fwrite($socket, "EHLO localhost\r\n");
$response = '';
while ($line = fgets($socket, 512)) {
    $response .= $line;
    if (substr($line, 3, 1) == ' ') break;
}
if (substr($response, 0, 3) != '250') {
    die("❌ FALLÓ\n      Respuesta: {$response}");
}
echo "✅\n";

// Paso 4: STARTTLS
echo "[4/6] Iniciando STARTTLS... ";
fwrite($socket, "STARTTLS\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '220') {
    die("❌ FALLÓ\n      Respuesta: {$response}");
}

$crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
if (!$crypto) {
    die("❌ FALLÓ\n      No se pudo establecer TLS\n");
}
echo "✅\n";

// Paso 5: EHLO después de TLS
echo "[5/6] Reenviando EHLO después de TLS... ";
fwrite($socket, "EHLO localhost\r\n");
$response = '';
while ($line = fgets($socket, 512)) {
    $response .= $line;
    if (substr($line, 3, 1) == ' ') break;
}
if (substr($response, 0, 3) != '250') {
    die("❌ FALLÓ\n      Respuesta: {$response}");
}
echo "✅\n";

// Paso 6: Autenticación
echo "[6/6] Autenticando... ";
fwrite($socket, "AUTH LOGIN\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '334') {
    die("❌ FALLÓ\n      Respuesta: {$response}");
}

// Enviar username
fwrite($socket, base64_encode($config['username']) . "\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '334') {
    die("❌ FALLÓ (username)\n      Respuesta: {$response}");
}

// Enviar password
fwrite($socket, base64_encode($config['password']) . "\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '235') {
    die("❌ FALLÓ (password)\n      Respuesta: {$response}");
}
echo "✅\n\n";

echo "🎉 ¡Todas las verificaciones pasaron!\n";
echo "   La configuración es correcta.\n\n";

// Enviar correo
echo "Enviando correo de prueba... ";

fwrite($socket, "MAIL FROM:<{$config['from']}>\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '250') {
    echo "❌ MAIL FROM falló: {$response}\n";
    fclose($socket);
    exit(1);
}

fwrite($socket, "RCPT TO:<{$config['to']}>\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '250') {
    echo "❌ RCPT TO falló: {$response}\n";
    fclose($socket);
    exit(1);
}

fwrite($socket, "DATA\r\n");
$response = fgets($socket, 512);
if (substr($response, 0, 3) != '354') {
    echo "❌ DATA falló: {$response}\n";
    fclose($socket);
    exit(1);
}

$message = "From: Eventos <{$config['from']}>\r\n";
$message .= "To: <{$config['to']}>\r\n";
$message .= "Subject: Prueba Office 365 - " . date('Y-m-d H:i:s') . "\r\n";
$message .= "Date: " . date('r') . "\r\n";
$message .= "\r\n";
$message .= "Este es un correo de prueba desde Office 365 con TLS.\n";
$message .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
$message .= "\r\n.\r\n";

fwrite($socket, $message);
$response = fgets($socket, 512);

if (substr($response, 0, 3) == '250') {
    echo "✅\n\n";
    echo "✉️  ¡Correo enviado exitosamente!\n";
    echo "    Verifica: {$config['to']}\n";
} else {
    echo "❌ Falló: {$response}\n";
}

fwrite($socket, "QUIT\r\n");
fclose($socket);

echo "\n═══════════════════════════════════════════════════════════\n";
