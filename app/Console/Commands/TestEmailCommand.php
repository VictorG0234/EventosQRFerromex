<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {recipient? : Email address to send test to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando prueba de configuración de email...');
        $this->newLine();

        // Obtener email destinatario
        $recipient = $this->argument('recipient') ?? $this->ask('¿A qué email deseas enviar la prueba?');

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ Email inválido: ' . $recipient);
            return 1;
        }

        // Mostrar configuración actual
        $this->info('📧 Configuración SMTP actual:');
        $this->table(
            ['Configuración', 'Valor'],
            [
                ['MAIL_MAILER', config('mail.default')],
                ['MAIL_HOST', config('mail.mailers.smtp.host')],
                ['MAIL_PORT', config('mail.mailers.smtp.port')],
                ['MAIL_USERNAME', config('mail.mailers.smtp.username')],
                ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption')],
                ['MAIL_FROM_ADDRESS', config('mail.from.address')],
                ['MAIL_FROM_NAME', config('mail.from.name')],
            ]
        );
        $this->newLine();

        if (!$this->confirm('¿Deseas continuar con el envío?', true)) {
            $this->warn('⚠️  Prueba cancelada');
            return 0;
        }

        // Intentar enviar email
        try {
            $this->info('📤 Enviando email de prueba a: ' . $recipient);
            
            $bar = $this->output->createProgressBar(3);
            $bar->start();

            // Preparar email
            $bar->advance();
            
            Mail::raw(
                "¡Hola!\n\n" .
                "Este es un email de prueba del sistema de Eventos Ferromex.\n\n" .
                "Si recibiste este mensaje, significa que tu configuración SMTP está funcionando correctamente. ✅\n\n" .
                "Detalles de la prueba:\n" .
                "- Fecha: " . now()->format('d/m/Y H:i:s') . "\n" .
                "- Servidor SMTP: " . config('mail.mailers.smtp.host') . "\n" .
                "- Puerto: " . config('mail.mailers.smtp.port') . "\n" .
                "- Encriptación: " . config('mail.mailers.smtp.encryption') . "\n\n" .
                "Saludos,\n" .
                "Sistema de Eventos Ferromex",
                function ($message) use ($recipient) {
                    $message->to($recipient)
                            ->subject('🧪 Email de Prueba - Ferromex Eventos');
                }
            );

            $bar->advance();
            sleep(1);
            $bar->finish();
            
            $this->newLine(2);
            $this->info('✅ Email enviado exitosamente!');
            $this->newLine();
            $this->line('📬 Revisa la bandeja de entrada de: ' . $recipient);
            $this->line('📁 Si no lo ves, revisa la carpeta de SPAM/Correo no deseado');
            $this->newLine();

            // Información adicional para Google Workspace
            if (str_contains(config('mail.mailers.smtp.host'), 'google') || 
                str_contains(config('mail.mailers.smtp.host'), 'gmail')) {
                $this->warn('📝 Nota para Google Workspace/Gmail:');
                $this->line('   • Asegúrate de usar una "Contraseña de aplicación" (App Password)');
                $this->line('   • Habilita IMAP en la configuración de Gmail');
                $this->line('   • Verifica que la verificación en 2 pasos esté activa');
                $this->line('   • Link: https://myaccount.google.com/apppasswords');
                $this->newLine();
            }

            return 0;

        } catch (Exception $e) {
            $this->newLine(2);
            $this->error('❌ Error al enviar el email:');
            $this->error($e->getMessage());
            $this->newLine();

            // Diagnóstico de errores comunes
            $this->warn('🔍 Posibles causas del error:');
            $this->line('   1. Credenciales incorrectas (usuario/contraseña)');
            $this->line('   2. Servidor SMTP bloqueado o incorrecto');
            $this->line('   3. Puerto bloqueado por firewall');
            $this->line('   4. Necesitas usar App Password en lugar de contraseña normal');
            $this->line('   5. IMAP/SMTP no habilitado en la cuenta');
            $this->newLine();

            $this->info('💡 Sugerencias:');
            $this->line('   • Verifica tu archivo .env');
            $this->line('   • Ejecuta: php artisan config:clear');
            $this->line('   • Revisa los logs: storage/logs/laravel.log');
            $this->newLine();

            return 1;
        }
    }
}
