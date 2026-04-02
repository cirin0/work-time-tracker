<?php

namespace App\Console\Commands;

use App\Models\TimeEntry;
use App\Notifications\WorkSessionAutoClosedNotification;
use App\Services\LatenessCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseWorkSessions extends Command
{
    protected $signature = 'work-sessions:auto-close {--hours=5 : Hours after which to auto-close sessions}';

    protected $description = 'Automatically close work sessions that have been active for more than specified hours';

    public function __construct(
        protected LatenessCalculator $latenessCalculator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        if ($hours <= 0) {
            $this->error('Hours must be a positive integer.');
            return Command::FAILURE;
        }

        $this->info("Checking for work sessions active for more than {$hours} hours...");

        $cutoffTime = now()->subHours($hours);

        // Find all active sessions that started before the cutoff time
        $entries = TimeEntry::query()
            ->whereNull('stop_time')
            ->where('start_time', '<=', $cutoffTime)
            ->with('user')
            ->get();

        if ($entries->isEmpty()) {
            $this->info('No sessions found that need auto-closing.');
            return Command::SUCCESS;
        }

        $closedCount = 0;

        foreach ($entries as $entry) {
            try {
                $startTime = $entry->start_time;
                $stopTime = $startTime->copy()->addHours($hours);
                $duration = $hours * 3600; // Convert to seconds

                // Calculate early leave and overtime based on the auto-close time
                $user = $entry->user;
                $earlyLeaveData = $this->latenessCalculator->calculateEarlyLeave($user, $stopTime);

                // For auto-closed sessions, we don't count overtime
                // This prevents penalizing users who forgot to close their session
                $overtimeMinutes = null;

                $entry->update([
                    'stop_time' => $stopTime,
                    'duration' => $duration,
                    'auto_closed' => true,
                    'stop_comment' => 'Автоматично закрито системою після ' . $hours . ' годин',
                    'early_leave_minutes' => $earlyLeaveData['early_leave_minutes'],
                    'scheduled_end_time' => $earlyLeaveData['scheduled_end_time'],
                    'overtime_minutes' => $overtimeMinutes,
                ]);

                // Send FCM notification to user
                if ($user && $user->fcm_token) {
                    $user->notify(new WorkSessionAutoClosedNotification($entry));
                }

                $closedCount++;
                $this->info("Closed session for user {$user->name} (ID: {$user->id})");

                Log::info('Work session auto-closed', [
                    'time_entry_id' => $entry->id,
                    'user_id' => $user->id,
                    'start_time' => $startTime->toDateTimeString(),
                    'stop_time' => $stopTime->toDateTimeString(),
                    'hours' => $hours,
                ]);

            } catch (\Exception $e) {
                $this->error("Failed to close session {$entry->id}: {$e->getMessage()}");
                Log::error('Failed to auto-close work session', [
                    'time_entry_id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Successfully auto-closed {$closedCount} work session(s).");

        return Command::SUCCESS;
    }
}
