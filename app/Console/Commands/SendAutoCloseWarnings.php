<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use App\Notifications\WorkSessionAutoCloseWarningNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAutoCloseWarnings extends Command
{
    protected $signature = 'work-sessions:send-warnings {--warning-minutes=30 : Minutes before auto-close to send warning}';

    protected $description = 'Send warnings to users about upcoming auto-close of their work sessions';

    public function handle(): int
    {
        $warningMinutes = (int) $this->option('warning-minutes');
        $autoCloseHours = 5; // Should match the auto-close hours

        if ($warningMinutes <= 0) {
            $this->error('Warning minutes must be a positive integer.');
            return Command::FAILURE;
        }

        $this->info("Checking for work sessions that will auto-close in {$warningMinutes} minutes...");

        // Calculate the time window for warnings
        // Sessions that started between (5 hours - warning time - 5 min) and (5 hours - warning time + 5 min) ago
        $targetTime = now()->subHours($autoCloseHours)->addMinutes($warningMinutes);
        $windowStart = $targetTime->copy()->subMinutes(5);
        $windowEnd = $targetTime->copy()->addMinutes(5);

        $entries = TimeEntry::query()
            ->whereNull('stop_time')
            ->whereBetween('start_time', [$windowStart, $windowEnd])
            ->with('user')
            ->get();

        if ($entries->isEmpty()) {
            $this->info('No sessions found that need warnings.');
            return Command::SUCCESS;
        }

        $sentCount = 0;

        foreach ($entries as $entry) {
            try {
                $user = $entry->user;

                if (!$user || !$user->fcm_token) {
                    continue;
                }

                // Send warning notification
                $user->notify(new WorkSessionAutoCloseWarningNotification($entry, $warningMinutes));

                $sentCount++;
                $this->info("Sent warning to user {$user->name} (ID: {$user->id})");

                Log::info('Auto-close warning sent', [
                    'time_entry_id' => $entry->id,
                    'user_id' => $user->id,
                    'minutes_remaining' => $warningMinutes,
                ]);

            } catch (\Exception $e) {
                $this->error("Failed to send warning for session {$entry->id}: {$e->getMessage()}");
                Log::error('Failed to send auto-close warning', [
                    'time_entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Successfully sent {$sentCount} warning(s).");

        return Command::SUCCESS;
    }
}
