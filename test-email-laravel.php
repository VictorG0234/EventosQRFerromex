<?php
/**
 * Script de prueba de correo - Laravel Artisan Command
 * 
 * INSTRUCCIONES:
 * 1. Guarda este archivo en: app/Console/Commands/TestEmailCommand.php
 * 2. Ejecuta: php artisan test:email
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestEmailCommand extends Command
{
    protected $signature = 'test:email {--to=desarrollo@peltiermkt.com}';
    protected $description = 'Envía un correo de prueba para verificar la configuración SMTP';

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  PRUEBA DE CORREO - LARAVEL');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Obtener configuración
        $config = [
            'mailer' => config('mail.mailers.smtp.transport'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        $this->info('Configuración actual:');
        $this->table(
            ['Parámetro', 'Valor'],
            [
                ['MAIL_MAILER', $config['mailer'] ?? 'smtp'],
                ['MAIL_HOST', $config['host']],
                ['MAIL_PORT', $config['port']],
                ['MAIL_ENCRYPTION', $config['encryption'] ?: '(ninguno)'],
                ['MAIL_USERNAME', $config['username'] ?: '(ninguno)'],
                ['MAIL_FROM_ADDRESS', $config['from_address']],
                ['MAIL_FROM_NAME', $config['from_name']],
            ]
        );

        $toEmail = $this->option('to');
        $this->newLine();
        $this->info("Enviando correo de prueba a: {$toEmail}");
        
        try {
            Mail::raw(
                "Este es un correo de prueba enviado desde Laravel.\n\n" .
                "Detalles de la configuración:\n" .
                "- Servidor: {$config['host']}:{$config['port']}\n" .
                "- Desde: {$config['from_address']}\n" .
                "- Fecha: " . now()->format('Y-m-d H:i:s') . "\n",
                function (Message $message) use ($toEmail, $config) {
                    $message->to($toEmail)
                            ->subject('Prueba de correo Laravel - ' . now()->format('Y-m-d H:i:s'));
                }
            );

            $this->newLine();
            $this->info('✓ ¡Correo enviado exitosamente!');
            $this->info("  Verifica la bandeja de entrada de {$toEmail}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Error al enviar el correo:');
            $this->error("  {$e->getMessage()}");
            $this->newLine();
            $this->warn('Sugerencias:');
            $this->warn('  - Verifica que el servidor SMTP sea accesible');
            $this->warn('  - Revisa los logs en storage/logs/laravel.log');
            $this->warn('  - Prueba la conexión con: telnet ' . $config['host'] . ' ' . $config['port']);
            
            return 1;
        } finally {
            $this->newLine();
            $this->info('═══════════════════════════════════════════════════════════');
        }
    }
}
