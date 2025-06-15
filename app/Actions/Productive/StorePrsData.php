<?php

namespace App\Actions\Productive;

use App\Actions\Productive\Store\StorePaymentReminderSequence;
use App\Actions\Productive\Store\StorePaymentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StorePrsData extends AbstractAction
{
    protected array $requiredKeys = [
        'payment_reminder_sequences',
        'payment_reminders'
    ];

    public function __construct(
        private StorePaymentReminderSequence $storePaymentReminderSequenceAction,
        private StorePaymentReminder $storePaymentReminderAction,
    ) {}

    /**
     * Store payment reminder sequences data in the database
     *
     * @param array $parameters
     * @return bool
     */
    public function handle(array $parameters = []): bool
    {
        $data = $parameters['data'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$data) {
            throw new \Exception('Data is required');
        }

        // Check if at least one of the required keys is present
        $hasRequiredData = false;
        foreach ($this->requiredKeys as $key) {
            if (!empty($data[$key])) {
                $hasRequiredData = true;
                break;
            }
        }

        if (!$hasRequiredData) {
            throw new \Exception('At least one of the following data types must be present: ' . implode(', ', $this->requiredKeys));
        }

        try {
            DB::beginTransaction();

            // First validate that we have data to store
            if (
                empty($data['payment_reminder_sequences']) &&
                empty($data['payment_reminders'])
            ) {
                if ($command instanceof Command) {
                    $command->warn('No payment reminder sequences data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store payment reminder sequences
            if (!empty($data['payment_reminder_sequences'])) {
                if ($command instanceof Command) {
                    $command->info('Storing payment reminder sequences...');
                }

                $sequencesSuccess = 0;
                $sequencesError = 0;
                foreach ($data['payment_reminder_sequences'] as $sequenceData) {
                    try {
                        $this->storePaymentReminderSequenceAction->handle([
                            'sequenceData' => $sequenceData,
                            'command' => $command
                        ]);
                        $sequencesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store payment reminder sequence (ID: {$sequenceData['id']}): " . $e->getMessage());
                        }
                        $sequencesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Payment Reminder Sequences: {$sequencesSuccess} stored successfully, {$sequencesError} failed");
                }
            }

            // Store payment reminders
            if (!empty($data['payment_reminders'])) {
                if ($command instanceof Command) {
                    $command->info('Storing payment reminders...');
                }

                $remindersSuccess = 0;
                $remindersError = 0;
                foreach ($data['payment_reminders'] as $reminderData) {
                    try {
                        $this->storePaymentReminderAction->handle([
                            'reminderData' => $reminderData,
                            'command' => $command
                        ]);
                        $remindersSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store payment reminder (ID: {$reminderData['id']}): " . $e->getMessage());
                        }
                        $remindersError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Payment Reminders: {$remindersSuccess} stored successfully, {$remindersError} failed");
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing payment reminder sequences data: ' . $e->getMessage());
            }
            return false;
        }
    }
} 