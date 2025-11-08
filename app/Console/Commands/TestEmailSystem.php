<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Guest;
use App\Services\EmailService;
use Illuminate\Console\Command;

class TestEmailSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:test {event_id? : ID of the event to test} {--template= : Specific template to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the email system with sample data';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService)
    {
        $this->info('🧪 Testing Email System...');

        // Get event
        $eventId = $this->argument('event_id');
        if ($eventId) {
            $event = Event::with(['guests', 'user'])->find($eventId);
            if (!$event) {
                $this->error("Event with ID {$eventId} not found");
                return Command::FAILURE;
            }
        } else {
            $event = Event::with(['guests', 'user'])->first();
            if (!$event) {
                $this->error("No events found in the database");
                return Command::FAILURE;
            }
        }

        $this->info("📋 Using event: {$event->name}");

        // Test specific template
        $template = $this->option('template');
        if ($template) {
            return $this->testSpecificTemplate($emailService, $event, $template);
        }

        // Test all functionality
        $this->testEmailStatistics($emailService, $event);
        $this->testTemplateValidation($emailService);
        
        if ($event->guests()->whereNotNull('email')->count() > 0) {
            $this->testEmailFunctions($emailService, $event);
        } else {
            $this->warn('⚠️ No guests with email found. Skipping email sending tests.');
        }

        $this->info('✅ Email system test completed!');
        return Command::SUCCESS;
    }

    private function testEmailStatistics(EmailService $emailService, Event $event)
    {
        $this->info('📊 Testing Email Statistics...');
        
        $stats = $emailService->getEmailStatistics($event);
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Guests', $stats['total_guests']],
                ['Guests with Email', $stats['guests_with_email']],
                ['Guests without Email', $stats['guests_without_email']],
                ['Email Coverage', $stats['email_coverage_percentage'] . '%']
            ]
        );
    }

    private function testTemplateValidation(EmailService $emailService)
    {
        $this->info('🎨 Testing Template Validation...');
        
        $validation = $emailService->validateEmailTemplates();
        
        if ($validation['valid']) {
            $this->info('✅ All email templates are valid');
        } else {
            $this->error('❌ Some templates are missing:');
            foreach ($validation['missing_templates'] as $template) {
                $this->line("   - {$template}");
            }
        }
    }

    private function testEmailFunctions(EmailService $emailService, Event $event)
    {
        $this->info('📧 Testing Email Functions...');
        
        $guestWithEmail = $event->guests()->whereNotNull('email')->first();
        
        if (!$guestWithEmail) {
            $this->warn('No guest with email found');
            return;
        }

        $this->info("Using guest: {$guestWithEmail->full_name} ({$guestWithEmail->email})");

        // Test welcome email
        if ($this->confirm('Send test welcome email?')) {
            $result = $emailService->sendGuestWelcomeEmail($guestWithEmail);
            $this->info($result ? '✅ Welcome email queued' : '❌ Welcome email failed');
        }

        // Test event reminder
        if ($this->confirm('Send test event reminder?')) {
            $result = $emailService->sendEventReminder($event, 24);
            $this->info($result ? '✅ Event reminder queued' : '❌ Event reminder failed');
        }

        // Test custom message
        if ($this->confirm('Send test custom message?')) {
            $result = $emailService->sendBulkMessageToGuests(
                $event,
                'Test Message from CLI',
                'This is a test message sent from the command line to verify the email system is working correctly.',
                [$guestWithEmail->id]
            );
            $this->info($result > 0 ? "✅ Custom message queued to {$result} guests" : '❌ Custom message failed');
        }

        // Test event summary
        if ($this->confirm('Send test event summary?')) {
            $result = $emailService->sendEventSummaryToOrganizer($event);
            $this->info($result ? '✅ Event summary queued' : '❌ Event summary failed');
        }
    }

    private function testSpecificTemplate(EmailService $emailService, Event $event, string $template)
    {
        $this->info("🎨 Testing specific template: {$template}");

        $templates = [
            'welcome' => 'sendGuestWelcomeEmail',
            'reminder' => 'sendEventReminder',
            'summary' => 'sendEventSummaryToOrganizer',
            'custom' => 'sendBulkMessageToGuests',
        ];

        if (!isset($templates[$template])) {
            $this->error("Unknown template: {$template}");
            $this->info('Available templates: ' . implode(', ', array_keys($templates)));
            return Command::FAILURE;
        }

        $guest = $event->guests()->whereNotNull('email')->first();
        if (!$guest && in_array($template, ['welcome', 'reminder', 'custom'])) {
            $this->error('No guest with email found for this template test');
            return Command::FAILURE;
        }

        switch ($template) {
            case 'welcome':
                $result = $emailService->sendGuestWelcomeEmail($guest);
                break;
            case 'reminder':
                $result = $emailService->sendEventReminder($event, 24);
                break;
            case 'summary':
                $result = $emailService->sendEventSummaryToOrganizer($event);
                break;
            case 'custom':
                $result = $emailService->sendBulkMessageToGuests(
                    $event,
                    'CLI Test Message',
                    'This is a test message from the CLI command',
                    [$guest->id]
                );
                $result = $result > 0;
                break;
        }

        $this->info($result ? "✅ {$template} template test successful" : "❌ {$template} template test failed");
        return Command::SUCCESS;
    }
}