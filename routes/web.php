<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\RaffleController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\AuditLogController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rutas públicas (sin autenticación)
Route::get('/event/{token}', [PublicEventController::class, 'showRegistrationForm'])->name('public.event.register');
Route::post('/event/{token}/validate', [PublicEventController::class, 'validateGuest'])->name('public.event.validate');
Route::get('/event/{token}/guest/{guest}', [PublicEventController::class, 'showGuestDetails'])->name('public.event.guest.details');
Route::get('/aviso-de-privacidad', [PublicEventController::class, 'showPrivacyNotice'])->name('public.privacy.notice');

Route::get('/', function () {
    return redirect()->route('register');
});

Route::get('/dashboard', function () {
    return redirect()->route('events.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // 🧪 RUTA DE PRUEBA - Generar invitación con QR (eliminar después)
    Route::get('/test-invitation-image', function () {
        // Obtener un invitado al azar que tenga QR
        $guest = \App\Models\Guest::whereNotNull('qr_code_path')->inRandomOrder()->first();
        
        if (!$guest) {
            return response()->json(['error' => 'No hay invitados con QR disponibles'], 404);
        }
        
        try {
            $service = new \App\Services\InvitationImageService();
            return $service->downloadInvitationWithQR($guest);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->name('test.invitation.image');
    
    // Rutas de eventos
    Route::resource('events', EventController::class);
    Route::patch('events/{event}/toggle-active', [EventController::class, 'toggleActive'])->name('events.toggle-active');
    Route::get('events/{event}/statistics', [EventController::class, 'statistics'])->name('events.statistics');
    Route::get('events/{event}/statistics-report', [EventController::class, 'statisticsReport'])->name('events.statistics.report');
    Route::get('events/{event}/statistics-pdf', [EventController::class, 'generatePDF'])->name('events.statistics.pdf');
    
    // Rutas de invitados - Las rutas específicas DEBEN ir ANTES de las rutas con parámetros dinámicos
    Route::get('events/{event}/guests', [GuestController::class, 'index'])->name('events.guests.index');
    Route::get('events/{event}/guests/create', [GuestController::class, 'create'])->name('events.guests.create');
    Route::post('events/{event}/guests', [GuestController::class, 'store'])->name('events.guests.store');
    Route::get('events/{event}/guests/import', [GuestController::class, 'importForm'])->name('events.guests.import');
    Route::post('events/{event}/guests/import', [GuestController::class, 'import'])->name('events.guests.import.process');
    Route::post('events/{event}/guests/preview', [GuestController::class, 'preview'])->name('events.guests.preview');
    Route::get('events/{event}/guests/{guest}', [GuestController::class, 'show'])->name('events.guests.show');
    Route::get('events/{event}/guests/{guest}/edit', [GuestController::class, 'edit'])->name('events.guests.edit');
    Route::patch('events/{event}/guests/{guest}', [GuestController::class, 'update'])->name('events.guests.update');
    Route::delete('events/{event}/guests/{guest}', [GuestController::class, 'destroy'])->name('events.guests.destroy');
    Route::get('events/{event}/guests/{guest}/download-qr', [GuestController::class, 'downloadQr'])->name('events.guests.download-qr');
    
    // Rutas de asistencia
    Route::get('events/{event}/attendance', [AttendanceController::class, 'index'])->name('events.attendance.index');
    Route::get('events/{event}/attendance/scanner', [AttendanceController::class, 'scanner'])->name('events.attendance.scanner');
    Route::post('events/{event}/attendance/scan', [AttendanceController::class, 'scan'])->name('events.attendance.scan');
    Route::post('events/{event}/attendance/manual', [AttendanceController::class, 'manualRegister'])->name('events.attendance.manual');
    Route::delete('events/{event}/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('events.attendance.destroy');
    Route::get('events/{event}/attendance/live-stats', [AttendanceController::class, 'liveStats'])->name('events.attendance.live-stats');
    Route::get('events/{event}/attendance/export', [AttendanceController::class, 'export'])->name('events.attendance.export');
    
    // Rutas de email
    Route::get('events/{event}/emails', [EmailController::class, 'index'])->name('events.emails.index');
    Route::get('events/{event}/emails/preview-invitation/{guest}', [EmailController::class, 'previewInvitation'])->name('events.emails.preview-invitation');
    Route::post('events/{event}/emails/welcome/{guest}', [EmailController::class, 'sendWelcomeEmail'])->name('events.emails.welcome');
    Route::post('events/{event}/emails/bulk-welcome', [EmailController::class, 'sendBulkWelcomeEmails'])->name('events.emails.bulk-welcome');
    Route::post('events/{event}/emails/reminder', [EmailController::class, 'sendEventReminder'])->name('events.emails.reminder');
    Route::post('events/{event}/emails/custom-message', [EmailController::class, 'sendCustomMessage'])->name('events.emails.custom-message');
    Route::post('events/{event}/emails/summary', [EmailController::class, 'sendEventSummary'])->name('events.emails.summary');
    Route::post('events/{event}/emails/attendance-confirmation/{guest}', [EmailController::class, 'sendAttendanceConfirmation'])->name('events.emails.attendance-confirmation');
    Route::get('events/{event}/emails/statistics', [EmailController::class, 'getEmailStatistics'])->name('events.emails.statistics');
    Route::get('events/{event}/emails/preview', [EmailController::class, 'previewEmailTemplate'])->name('events.emails.preview');
    Route::get('admin/emails/validate-templates', [EmailController::class, 'validateEmailTemplates'])->name('emails.validate-templates');
    
    // Ruta temporal para previsualizar plantilla de concierto
    Route::get('/preview-concert-email', function () {
        // Obtener un invitado de ejemplo con QR
        $guest = \App\Models\Guest::with('event')->whereNotNull('qr_code_path')->first();
        
        if (!$guest) {
            return 'No hay invitados con código QR disponibles para previsualizar.';
        }
        
        $qrCodeUrl = $guest->qr_code_url;
        
        return view('emails.concert-invitation', [
            'qrCodeUrl' => $qrCodeUrl,
            'guest' => $guest
        ]);
    })->name('preview.concert.email');
    
    // Rutas de premios - Las rutas específicas DEBEN ir ANTES de las rutas con parámetros dinámicos
    Route::get('events/{event}/prizes', [PrizeController::class, 'index'])->name('events.prizes.index');
    Route::get('events/{event}/prizes/import', [PrizeController::class, 'importForm'])->name('events.prizes.import');
    Route::post('events/{event}/prizes/import', [PrizeController::class, 'import'])->name('events.prizes.import.process');
    Route::post('events/{event}/prizes/preview', [PrizeController::class, 'preview'])->name('events.prizes.preview');
    Route::get('events/{event}/prizes/create', [PrizeController::class, 'create'])->name('events.prizes.create');
    Route::post('events/{event}/prizes', [PrizeController::class, 'store'])->name('events.prizes.store');
    Route::get('events/{event}/prizes/{prize}', [PrizeController::class, 'show'])->name('events.prizes.show');
    Route::get('events/{event}/prizes/{prize}/edit', [PrizeController::class, 'edit'])->name('events.prizes.edit');
    Route::patch('events/{event}/prizes/{prize}', [PrizeController::class, 'update'])->name('events.prizes.update');
    Route::delete('events/{event}/prizes/{prize}', [PrizeController::class, 'destroy'])->name('events.prizes.destroy');
    Route::patch('events/{event}/prizes/{prize}/toggle-active', [PrizeController::class, 'toggleActive'])->name('events.prizes.toggle-active');
    Route::get('templates/prizes', [PrizeController::class, 'downloadTemplate'])->name('templates.prizes');
    
    // Rutas de rifas
    Route::get('events/{event}/raffle', [RaffleController::class, 'index'])->name('events.raffle.index');
    Route::get('events/{event}/draw', [RaffleController::class, 'drawCards'])->name('events.draw.cards');
    Route::get('events/{event}/draw-general', [RaffleController::class, 'drawGeneral'])->name('events.draw.general');
    Route::post('events/{event}/draw-general/execute', [RaffleController::class, 'executeGeneralDraw'])->name('events.draw.general.execute');
    Route::post('events/{event}/draw-general/reselect', [RaffleController::class, 'reselectGeneralWinner'])->name('events.draw.general.reselect');
    Route::get('events/{event}/raffle/prizes/{prize}', [RaffleController::class, 'show'])->name('events.raffle.show');
    Route::post('events/{event}/raffle/prizes/{prize}/entries', [RaffleController::class, 'createEntries'])->name('events.raffle.create-entries');
    Route::post('events/{event}/raffle/prizes/{prize}/draw', [RaffleController::class, 'draw'])->name('events.raffle.draw');
    Route::post('events/{event}/raffle/prizes/{prize}/draw-single', [RaffleController::class, 'drawSingle'])->name('events.raffle.draw-single');
    Route::post('events/{event}/raffle/prizes/{prize}/confirm-winner', [RaffleController::class, 'confirmWinner'])->name('events.raffle.confirm-winner');
    Route::post('events/{event}/raffle/prizes/{prize}/cancel', [RaffleController::class, 'cancel'])->name('events.raffle.cancel');
    Route::get('events/{event}/raffle/prizes/{prize}/entries', [RaffleController::class, 'entries'])->name('events.raffle.entries');
    Route::patch('events/{event}/raffle/prizes/{prize}/entries/{entry}/reset', [RaffleController::class, 'resetEntry'])->name('events.raffle.reset-entry');
    Route::delete('events/{event}/raffle/prizes/{prize}/entries/{entry}', [RaffleController::class, 'deleteEntry'])->name('events.raffle.delete-entry');
    Route::post('events/{event}/raffle/prizes/{prize}/select-winner', [RaffleController::class, 'selectWinner'])->name('events.raffle.select-winner');
    Route::get('events/{event}/raffle/live-data', [RaffleController::class, 'liveData'])->name('events.raffle.live-data');
    
    // Rutas de auditoría
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('audit-logs/export/csv', [AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('events/{event}/raffle/attendees', [RaffleController::class, 'getAttendees'])->name('events.raffle.attendees');
});

require __DIR__.'/auth.php';
