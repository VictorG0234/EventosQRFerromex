<?php

namespace App\Jobs;

use App\Models\Guest;
use App\Models\Event;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailType;
    protected $guest;
    protected $event;
    protected $additionalData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $emailType, ?Guest $guest = null, ?Event $event = null, array $additionalData = [])
    {
        $this->emailType = $emailType;
        $this->guest = $guest;
        $this->event = $event;
        $this->additionalData = $additionalData;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        try {
            switch ($this->emailType) {
                case 'welcome':
                    if ($this->guest) {
                        $emailService->sendGuestWelcomeEmail($this->guest);
                        Log::info('Welcome email job completed', ['guest_id' => $this->guest->id]);
                    }
                    break;

                case 'reminder':
                    if ($this->event) {
                        $hoursBeforeEvent = $this->additionalData['hours_before_event'] ?? 24;
                        $emailService->sendEventReminder($this->event, $hoursBeforeEvent);
                        Log::info('Reminder email job completed', ['event_id' => $this->event->id]);
                    }
                    break;

                case 'attendance_confirmation':
                    if ($this->guest) {
                        $emailService->sendAttendanceConfirmation($this->guest);
                        Log::info('Attendance confirmation email job completed', ['guest_id' => $this->guest->id]);
                    }
                    break;

                case 'event_summary':
                    if ($this->event) {
                        $emailService->sendEventSummaryToOrganizer($this->event);
                        Log::info('Event summary email job completed', ['event_id' => $this->event->id]);
                    }
                    break;

                case 'custom_message':
                    if ($this->event) {
                        $subject = $this->additionalData['subject'] ?? 'Mensaje personalizado';
                        $message = $this->additionalData['message'] ?? '';
                        $guestIds = $this->additionalData['guest_ids'] ?? [];
                        
                        $emailService->sendBulkMessageToGuests($this->event, $subject, $message, $guestIds);
                        Log::info('Custom message email job completed', ['event_id' => $this->event->id]);
                    }
                    break;

                case 'raffle_winner':
                    if ($this->guest) {
                        $prize = $this->additionalData['prize'] ?? null;
                        $emailService->sendRaffleWinnerNotification($this->guest, $prize);
                        Log::info('Raffle winner email job completed', ['guest_id' => $this->guest->id]);
                    }
                    break;

                default:
                    Log::warning('Unknown email type in SendEmailJob', ['email_type' => $this->emailType]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('SendEmailJob failed', [
                'email_type' => $this->emailType,
                'guest_id' => $this->guest?->id,
                'event_id' => $this->event?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception so the job fails and can be retried
            throw $e;
        }
    }

    /**
     * Determine the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 1min, 2min
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendEmailJob permanently failed', [
            'email_type' => $this->emailType,
            'guest_id' => $this->guest?->id,
            'event_id' => $this->event?->id,
            'error' => $exception->getMessage()
        ]);
    }
}