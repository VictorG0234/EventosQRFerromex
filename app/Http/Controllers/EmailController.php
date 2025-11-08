<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmailController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Show email management interface
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        $statistics = $this->emailService->getEmailStatistics($event);
        
        return Inertia::render('Events/Emails/Index', [
            'event' => $event,
            'statistics' => $statistics,
            'guests' => $event->guests()->with(['attendance'])->get()
        ]);
    }

    /**
     * Send welcome email to specific guest
     */
    public function sendWelcomeEmail(Request $request, Event $event, Guest $guest)
    {
        $this->authorize('update', $event);

        if ($guest->event_id !== $event->id) {
            return response()->json(['error' => 'Guest not found in this event'], 404);
        }

        if (!$guest->email) {
            return response()->json(['error' => 'Guest has no email address'], 400);
        }

        $result = $this->emailService->sendGuestWelcomeEmail($guest);

        if ($result) {
            return response()->json(['message' => 'Welcome email sent successfully']);
        }

        return response()->json(['error' => 'Failed to send welcome email'], 500);
    }

    /**
     * Send welcome emails to all guests
     */
    public function sendBulkWelcomeEmails(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $guests = $event->guests()->whereNotNull('email')->get();
        $sent = 0;
        $failed = 0;

        foreach ($guests as $guest) {
            if ($this->emailService->sendGuestWelcomeEmail($guest)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return response()->json([
            'message' => "Welcome emails processed: {$sent} sent, {$failed} failed",
            'sent' => $sent,
            'failed' => $failed
        ]);
    }

    /**
     * Send event reminder
     */
    public function sendEventReminder(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'hours_before_event' => 'required|integer|min:1|max:168' // max 7 days
        ]);

        $hoursBeforeEvent = $request->input('hours_before_event', 24);
        
        $result = $this->emailService->sendEventReminder($event, $hoursBeforeEvent);

        if ($result) {
            return response()->json(['message' => 'Event reminder sent successfully']);
        }

        return response()->json(['error' => 'Failed to send event reminder'], 500);
    }

    /**
     * Send custom message to guests
     */
    public function sendCustomMessage(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'guest_ids' => 'nullable|array',
            'guest_ids.*' => 'exists:guests,id'
        ]);

        $subject = $request->input('subject');
        $message = $request->input('message');
        $guestIds = $request->input('guest_ids', []);

        $sentCount = $this->emailService->sendBulkMessageToGuests(
            $event, 
            $subject, 
            $message, 
            $guestIds
        );

        return response()->json([
            'message' => "Custom message sent to {$sentCount} guests",
            'sent_count' => $sentCount
        ]);
    }

    /**
     * Send event summary to organizer
     */
    public function sendEventSummary(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $result = $this->emailService->sendEventSummaryToOrganizer($event);

        if ($result) {
            return response()->json(['message' => 'Event summary sent successfully']);
        }

        return response()->json(['error' => 'Failed to send event summary'], 500);
    }

    /**
     * Send attendance confirmation to guest
     */
    public function sendAttendanceConfirmation(Request $request, Event $event, Guest $guest)
    {
        $this->authorize('view', $event);

        if ($guest->event_id !== $event->id) {
            return response()->json(['error' => 'Guest not found in this event'], 404);
        }

        if (!$guest->email) {
            return response()->json(['error' => 'Guest has no email address'], 400);
        }

        if (!$guest->attendance) {
            return response()->json(['error' => 'Guest has not attended the event'], 400);
        }

        $result = $this->emailService->sendAttendanceConfirmation($guest);

        if ($result) {
            return response()->json(['message' => 'Attendance confirmation sent successfully']);
        }

        return response()->json(['error' => 'Failed to send attendance confirmation'], 500);
    }

    /**
     * Get email statistics
     */
    public function getEmailStatistics(Event $event)
    {
        $this->authorize('view', $event);

        $statistics = $this->emailService->getEmailStatistics($event);

        return response()->json($statistics);
    }

    /**
     * Validate email templates
     */
    public function validateEmailTemplates()
    {
        $validation = $this->emailService->validateEmailTemplates();

        return response()->json($validation);
    }

    /**
     * Preview email template
     */
    public function previewEmailTemplate(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $request->validate([
            'template' => 'required|in:welcome,reminder,attendance-confirmation,event-summary,custom-message,raffle-winner',
            'guest_id' => 'nullable|exists:guests,id'
        ]);

        $template = $request->input('template');
        $guestId = $request->input('guest_id');

        // Get a sample guest if none provided
        if (!$guestId) {
            $guest = $event->guests()->first();
            if (!$guest) {
                return response()->json(['error' => 'No guests found for this event'], 404);
            }
        } else {
            $guest = Guest::findOrFail($guestId);
            if ($guest->event_id !== $event->id) {
                return response()->json(['error' => 'Guest not found in this event'], 404);
            }
        }

        // Prepare template data based on type
        $viewData = $this->getTemplatePreviewData($template, $event, $guest);

        try {
            $html = view("emails.{$template}", $viewData)->render();
            
            return response()->json([
                'html' => $html,
                'template' => $template,
                'guest' => $guest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to render template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template preview data
     */
    private function getTemplatePreviewData($template, $event, $guest)
    {
        $baseData = [
            'event' => $event,
            'guest' => $guest,
            'organizer' => $event->user,
            'qrCodeUrl' => $guest->qr_code_url
        ];

        switch ($template) {
            case 'guest-welcome':
                return $baseData;

            case 'event-reminder':
                return array_merge($baseData, [
                    'hoursBeforeEvent' => 24
                ]);

            case 'attendance-confirmation':
                return array_merge($baseData, [
                    'attendance' => $guest->attendance ?: new \stdClass()
                ]);

            case 'event-summary':
                return array_merge($baseData, [
                    'statistics' => $this->emailService->getEmailStatistics($event)
                ]);

            case 'custom-message':
                return array_merge($baseData, [
                    'customMessage' => 'Este es un mensaje personalizado de ejemplo para mostrar cómo se verá tu comunicación.'
                ]);

            case 'raffle-winner':
                return array_merge($baseData, [
                    'prize' => (object) [
                        'name' => 'Premio de Ejemplo',
                        'description' => 'Este es un premio de ejemplo para mostrar la plantilla',
                        'value' => 50000
                    ]
                ]);

            default:
                return $baseData;
        }
    }
}