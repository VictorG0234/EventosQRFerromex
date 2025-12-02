<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Guest;
use App\Mail\GuestInvitationMail;
use App\Services\QrCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:send {event_id : ID del evento}
                            {--force : Enviar incluso a invitados que ya recibieron email}
                            {--test : Modo de prueba, solo muestra información sin enviar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar invitaciones por correo a todos los invitados de un evento';

    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        parent::__construct();
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');
        $force = $this->option('force');
        $testMode = $this->option('test');

        // Buscar el evento
        $event = Event::find($eventId);
        
        if (!$event) {
            $this->error("❌ No se encontró el evento con ID: {$eventId}");
            return 1;
        }

        $this->info("📧 Enviando invitaciones para el evento: {$event->name}");
        $this->newLine();

        // Obtener invitados
        $query = Guest::where('event_id', $eventId);
        
        if (!$force) {
            $query->where('email_sent', false);
        }

        $guests = $query->get();

        if ($guests->isEmpty()) {
            $this->warn("⚠️  No hay invitados pendientes de recibir invitación.");
            $this->info("💡 Usa --force para reenviar a todos los invitados.");
            return 0;
        }

        $this->info("Total de invitados: " . $guests->count());
        
        if ($testMode) {
            $this->warn("🧪 MODO DE PRUEBA - No se enviarán correos");
        }

        $this->newLine();

        if (!$this->confirm('¿Deseas continuar?', true)) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $this->newLine();
        $progressBar = $this->output->createProgressBar($guests->count());
        $progressBar->start();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($guests as $guest) {
            try {
                // Verificar que el invitado tenga correo
                if (empty($guest->correo)) {
                    $errors[] = "Invitado {$guest->nombre_completo} (ID: {$guest->id}) no tiene correo registrado";
                    $failed++;
                    $progressBar->advance();
                    continue;
                }

                // Generar QR si no existe
                if (empty($guest->qr_code_path)) {
                    $this->qrCodeService->generateAndStoreQrCode($guest);
                    $guest->refresh();
                }

                // Enviar correo (solo si no es modo test)
                if (!$testMode) {
                    Mail::to($guest->correo)->send(new GuestInvitationMail($guest));
                    
                    // Marcar como enviado
                    $guest->update(['email_sent' => true]);
                }

                $sent++;
            } catch (\Exception $e) {
                $errors[] = "Error al enviar a {$guest->correo}: " . $e->getMessage();
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resultados
        if ($testMode) {
            $this->info("🧪 Modo de prueba completado");
            $this->info("✅ Se procesarían: {$sent} invitaciones");
        } else {
            $this->info("✅ Invitaciones enviadas exitosamente: {$sent}");
        }
        
        if ($failed > 0) {
            $this->error("❌ Fallos: {$failed}");
            $this->newLine();
            $this->error("Errores encontrados:");
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        return 0;
    }
}
