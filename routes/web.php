<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\RaffleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('events.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Rutas de eventos
    Route::resource('events', EventController::class);
    Route::patch('events/{event}/toggle-active', [EventController::class, 'toggleActive'])->name('events.toggle-active');
    Route::get('events/{event}/statistics', [EventController::class, 'statistics'])->name('events.statistics');
    
    // Rutas especiales para invitados (DEBEN ir ANTES del resource)
    Route::get('events/{event}/guests/import', [GuestController::class, 'importForm'])->name('events.guests.import');
    Route::post('events/{event}/guests/import', [GuestController::class, 'import'])->name('events.guests.import.process');
    Route::post('events/{event}/guests/preview', [GuestController::class, 'preview'])->name('events.guests.preview');
    Route::get('events/{event}/guests/{guest}/download-qr', [GuestController::class, 'downloadQr'])->name('events.guests.download-qr');
    
    // Rutas de invitados (nested resource)
    Route::resource('events.guests', GuestController::class)->except(['index', 'create', 'store']);
    Route::get('events/{event}/guests', [GuestController::class, 'index'])->name('events.guests.index');
    Route::get('events/{event}/guests/create', [GuestController::class, 'create'])->name('events.guests.create');
    Route::post('events/{event}/guests', [GuestController::class, 'store'])->name('events.guests.store');
    
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
    Route::post('events/{event}/emails/welcome/{guest}', [EmailController::class, 'sendWelcomeEmail'])->name('events.emails.welcome');
    Route::post('events/{event}/emails/bulk-welcome', [EmailController::class, 'sendBulkWelcomeEmails'])->name('events.emails.bulk-welcome');
    Route::post('events/{event}/emails/reminder', [EmailController::class, 'sendEventReminder'])->name('events.emails.reminder');
    Route::post('events/{event}/emails/custom-message', [EmailController::class, 'sendCustomMessage'])->name('events.emails.custom-message');
    Route::post('events/{event}/emails/summary', [EmailController::class, 'sendEventSummary'])->name('events.emails.summary');
    Route::post('events/{event}/emails/attendance-confirmation/{guest}', [EmailController::class, 'sendAttendanceConfirmation'])->name('events.emails.attendance-confirmation');
    Route::get('events/{event}/emails/statistics', [EmailController::class, 'getEmailStatistics'])->name('events.emails.statistics');
    Route::get('events/{event}/emails/preview', [EmailController::class, 'previewEmailTemplate'])->name('events.emails.preview');
    Route::get('admin/emails/validate-templates', [EmailController::class, 'validateEmailTemplates'])->name('emails.validate-templates');
    
    // Rutas de premios
    Route::resource('events.prizes', PrizeController::class)->except(['index']);
    Route::get('events/{event}/prizes', [PrizeController::class, 'index'])->name('events.prizes.index');
    Route::patch('events/{event}/prizes/{prize}/toggle-active', [PrizeController::class, 'toggleActive'])->name('events.prizes.toggle-active');
    
    // Rutas de rifas
    Route::get('events/{event}/raffle', [RaffleController::class, 'index'])->name('events.raffle.index');
    Route::get('events/{event}/raffle/prizes/{prize}', [RaffleController::class, 'show'])->name('events.raffle.show');
    Route::post('events/{event}/raffle/prizes/{prize}/entries', [RaffleController::class, 'createEntries'])->name('events.raffle.create-entries');
    Route::post('events/{event}/raffle/prizes/{prize}/draw', [RaffleController::class, 'draw'])->name('events.raffle.draw');
    Route::post('events/{event}/raffle/prizes/{prize}/cancel', [RaffleController::class, 'cancel'])->name('events.raffle.cancel');
    Route::get('events/{event}/raffle/prizes/{prize}/entries', [RaffleController::class, 'entries'])->name('events.raffle.entries');
    Route::patch('events/{event}/raffle/prizes/{prize}/entries/{entry}/reset', [RaffleController::class, 'resetEntry'])->name('events.raffle.reset-entry');
    Route::delete('events/{event}/raffle/prizes/{prize}/entries/{entry}', [RaffleController::class, 'deleteEntry'])->name('events.raffle.delete-entry');
    Route::post('events/{event}/raffle/prizes/{prize}/select-winner', [RaffleController::class, 'selectWinner'])->name('events.raffle.select-winner');
    Route::get('events/{event}/raffle/live-data', [RaffleController::class, 'liveData'])->name('events.raffle.live-data');
});

require __DIR__.'/auth.php';
