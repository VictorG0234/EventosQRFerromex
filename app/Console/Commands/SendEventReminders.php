<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Jobs\SendEmailJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send-reminders {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automatic event reminders to guests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('🔍 Checking for events needing reminders...');

        // Get events that are 24 hours away
        $events24h = $this->getEventsForReminder(24);
        
        // Get events that are 2 hours away
        $events2h = $this->getEventsForReminder(2);

        $totalReminders = 0;

        // Process 24-hour reminders
        if ($events24h->count() > 0) {
            $this->info("📅 Found {$events24h->count()} events needing 24-hour reminders");
            
            foreach ($events24h as $event) {
                $guestCount = $event->guests()->whereNotNull('email')->count();
                
                if ($guestCount > 0) {
                    $this->line("   • {$event->name} - {$guestCount} guests with email");
                    
                    if (!$isDryRun) {
                        SendEmailJob::dispatch('reminder', null, $event, ['hours_before_event' => 24]);
                        $totalReminders++;
                    }
                }
            }
        }

        // Process 2-hour reminders
        if ($events2h->count() > 0) {
            $this->info("⏰ Found {$events2h->count()} events needing 2-hour reminders");
            
            foreach ($events2h as $event) {
                $guestCount = $event->guests()->whereNotNull('email')->count();
                
                if ($guestCount > 0) {
                    $this->line("   • {$event->name} - {$guestCount} guests with email");
                    
                    if (!$isDryRun) {
                        SendEmailJob::dispatch('reminder', null, $event, ['hours_before_event' => 2]);
                        $totalReminders++;
                    }
                }
            }
        }

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No emails were actually sent');
            $this->info('Run without --dry-run to send the emails');
        } else {
            $this->info("✅ Queued {$totalReminders} reminder email jobs");
            Log::info('Automatic event reminders processed', ['total_jobs' => $totalReminders]);
        }

        return Command::SUCCESS;
    }

    /**
     * Get events that need reminders at specified hours before start
     */
    private function getEventsForReminder(int $hoursBeforeEvent)
    {
        $targetDateTime = Carbon::now()->addHours($hoursBeforeEvent);
        
        return Event::where('is_active', true)
            ->whereDate('date', $targetDateTime->toDateString())
            ->whereTime('time', '>=', $targetDateTime->subMinutes(30)->toTimeString())
            ->whereTime('time', '<=', $targetDateTime->addMinutes(30)->toTimeString())
            ->with(['guests'])
            ->get();
    }
}