<?php

namespace App\Console\Commands;

use App\Models\Guest;
use App\Mail\GuestInvitationMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestInvitationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-invitation {guestId} {--email=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de invitación de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $guestId = $this->argument('guestId');
        $customEmail = $this->option('email');
        
        $this->info('🔍 Buscando invitado...');
        
        $guest = Guest::with('event')->find($guestId);
        
        if (!$guest) {
            $this->error('❌ No se encontró el invitado con ID: ' . $guestId);
            $this->line('');
            $this->line('Usa: php artisan email:test-invitation {guestId} [--email=test@ejemplo.com]');
            return 1;
        }
        
        $this->info('✅ Invitado encontrado: ' . $guest->full_name);
        $this->info('📧 Email registrado: ' . ($guest->correo ?: 'Sin email'));
        $this->info('🎉 Evento: ' . $guest->event->name);
        $this->line('');
        
        $emailTo = $customEmail ?: $guest->correo;
        
        if (!$emailTo) {
            $this->error('❌ El invitado no tiene email registrado y no se proporcionó un email con --email');
            return 1;
        }
        
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            $this->error('❌ El email no es válido: ' . $emailTo);
            return 1;
        }
        
        $this->info('📨 Enviando email a: ' . $emailTo);
        
        try {
            Mail::to($emailTo)->send(new GuestInvitationMail($guest, $guest->event));
            
            $this->line('');
            $this->info('✅ ¡Email enviado exitosamente!');
            $this->line('');
            
            if (config('mail.default') === 'log') {
                $this->warn('⚠️  Estás usando MAIL_MAILER=log');
                $this->line('📄 Revisa el email en: storage/logs/laravel.log');
            } else {
                $this->line('📬 Revisa la bandeja de entrada de: ' . $emailTo);
            }
            
            $this->line('');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Invitado', $guest->full_name],
                    ['Email', $emailTo],
                    ['Evento', $guest->event->name],
                    ['Credenciales', $guest->compania . '-' . $guest->numero_empleado],
                    ['Tiene QR', $guest->qr_code_path ? '✅ Sí' : '❌ No'],
                ]
            );
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error al enviar el email: ' . $e->getMessage());
            $this->line('');
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
