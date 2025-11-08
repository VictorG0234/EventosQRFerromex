<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class EmailService
{
    /**
     * Send welcome email to new guest
     */
    public function sendGuestWelcomeEmail(Guest $guest)
    {
        try {
            Mail::to($guest->email)->queue(new \App\Mail\GuestWelcomeMail($guest));
            
            Log::info('Welcome email queued for guest', [
                'guest_id' => $guest->id,
                'email' => $guest->email,
                'event_id' => $guest->event_id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send event reminder to all guests
     */
    public function sendEventReminder(Event $event, int $hoursBeforeEvent = 24)
    {
        try {
            $guests = $event->guests()->whereNotNull('email')->get();
            
            foreach ($guests as $guest) {
                Mail::to($guest->email)->queue(new \App\Mail\EventReminderMail($guest, $hoursBeforeEvent));
            }
            
            Log::info('Event reminder emails queued', [
                'event_id' => $event->id,
                'guest_count' => $guests->count(),
                'hours_before' => $hoursBeforeEvent
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue event reminder emails', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send attendance confirmation to guest
     */
    public function sendAttendanceConfirmation(Guest $guest)
    {
        try {
            Mail::to($guest->email)->queue(new \App\Mail\AttendanceConfirmationMail($guest));
            
            Log::info('Attendance confirmation email queued', [
                'guest_id' => $guest->id,
                'email' => $guest->email,
                'event_id' => $guest->event_id
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue attendance confirmation email', [
                'guest_id' => $guest->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send event summary to organizer
     */
    public function sendEventSummaryToOrganizer(Event $event)
    {
        try {
            $organizer = $event->user;
            
            Mail::to($organizer->email)->queue(new \App\Mail\EventSummaryMail($event));
            
            Log::info('Event summary email queued for organizer', [
                'event_id' => $event->id,
                'organizer_email' => $organizer->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue event summary email', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Send bulk custom message to event guests
     */
    public function sendBulkMessageToGuests(Event $event, string $subject, string $message, array $guestIds = [])
    {
        try {
            $query = $event->guests()->whereNotNull('email');
            
            if (!empty($guestIds)) {
                $query->whereIn('id', $guestIds);
            }
            
            $guests = $query->get();
            
            foreach ($guests as $guest) {
                Mail::to($guest->email)->queue(new \App\Mail\CustomMessageMail($guest, $subject, $message));
            }
            
            Log::info('Bulk custom messages queued', [
                'event_id' => $event->id,
                'guest_count' => $guests->count(),
                'subject' => $subject
            ]);
            
            return $guests->count();
        } catch (\Exception $e) {
            Log::error('Failed to queue bulk custom messages', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Send raffle winner notification
     */
    public function sendRaffleWinnerNotification(Guest $winner, $prize)
    {
        try {
            Mail::to($winner->email)->queue(new \App\Mail\RaffleWinnerMail($winner, $prize));
            
            Log::info('Raffle winner notification queued', [
                'winner_id' => $winner->id,
                'email' => $winner->email,
                'prize' => $prize->name ?? 'Unknown Prize'
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue raffle winner notification', [
                'winner_id' => $winner->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get email statistics for an event
     */
    public function getEmailStatistics(Event $event)
    {
        return [
            'total_guests' => $event->guests()->count(),
            'guests_with_email' => $event->guests()->whereNotNull('email')->count(),
            'guests_without_email' => $event->guests()->whereNull('email')->count(),
            'email_coverage_percentage' => $event->guests()->count() > 0 
                ? round(($event->guests()->whereNotNull('email')->count() / $event->guests()->count()) * 100, 2)
                : 0
        ];
    }

    /**
     * Validate email templates exist
     */
    public function validateEmailTemplates()
    {
        $templates = [
            'emails.guest-welcome',
            'emails.event-reminder', 
            'emails.attendance-confirmation',
            'emails.event-summary',
            'emails.custom-message',
            'emails.raffle-winner'
        ];

        $missing = [];
        
        foreach ($templates as $template) {
            if (!view()->exists($template)) {
                $missing[] = $template;
            }
        }

        return [
            'valid' => empty($missing),
            'missing_templates' => $missing
        ];
    }
}