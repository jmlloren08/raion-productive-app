<?php

namespace App\Actions\Productive;

use App\Actions\Productive\Store\StoreTaskList;
use App\Actions\Productive\Store\StoreTask;
use App\Actions\Productive\Store\StoreTodo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreTaskListsData extends AbstractAction
{
    /**
     * Required keys in the data array
     */
    protected array $requiredKeys = [
        'task_lists',
        'tasks',
        'todos'
    ];

    /**
     * Constructor
     */
    public function __construct(
        private readonly StoreTaskList $storeTaskListAction,
        private readonly StoreTask $storeTaskAction,
        private readonly StoreTodo $storeTodoAction
    ) {}

    /**
     * Store task lists, tasks, and todos data in the database
     *
     * @param array $parameters
     * @return bool
     * @throws \Exception
     */
    public function handle(array $parameters = []): bool
    {
        $data = $parameters['data'] ?? null;
        $command = $parameters['command'] ?? null;

        if (!$data) {
            throw new \Exception('Data is required');
        }

        // Check for required data
        foreach ($this->requiredKeys as $key) {
            if (!isset($data[$key])) {
                $message = "Missing required key '{$key}' in data";
                if ($command instanceof Command) {
                    $command->warn($message);
                }
                Log::warning($message);
                return false;
            }
        }

        try {
            DB::beginTransaction();

            // Store task lists
            $taskListSuccessCount = 0;
            $taskListErrorCount = 0;

            if ($command instanceof Command) {
                $command->info("\nStoring task lists...");
            }

            foreach ($data['task_lists'] as $taskList) {
                try {
                    $this->storeTaskListAction->handle([
                        'taskListData' => $taskList,
                        'command' => $command
                    ]);
                    $taskListSuccessCount++;
                } catch (\Exception $e) {
                    $taskListErrorCount++;
                    if ($command instanceof Command) {
                        $command->error("Failed to store task list {$taskList['id']}: " . $e->getMessage());
                    }
                    Log::error("Failed to store task list {$taskList['id']}: " . $e->getMessage());
                }
            }

            // Store tasks
            $taskSuccessCount = 0;
            $taskErrorCount = 0;

            if ($command instanceof Command) {
                $command->info("\nStoring tasks...");
            }

            foreach ($data['tasks'] as $task) {
                try {
                    $this->storeTaskAction->handle([
                        'taskData' => $task,
                        'command' => $command
                    ]);
                    $taskSuccessCount++;
                } catch (\Exception $e) {
                    $taskErrorCount++;
                    if ($command instanceof Command) {
                        $command->error("Failed to store task {$task['id']}: " . $e->getMessage());
                    }
                    Log::error("Failed to store task {$task['id']}: " . $e->getMessage());
                }
            }

            // Store todos
            $todoSuccessCount = 0;
            $todoErrorCount = 0;

            if ($command instanceof Command) {
                $command->info("\nStoring todos...");
            }

            foreach ($data['todos'] as $todo) {
                try {
                    $this->storeTodoAction->handle([
                        'todoData' => $todo,
                        'command' => $command
                    ]);
                    $todoSuccessCount++;
                } catch (\Exception $e) {
                    $todoErrorCount++;
                    if ($command instanceof Command) {
                        $command->error("Failed to store todo {$todo['id']}: " . $e->getMessage());
                    }
                    Log::error("Failed to store todo {$todo['id']}: " . $e->getMessage());
                }
            }

            DB::commit();

            if ($command instanceof Command) {
                $command->info("\nTask Lists Storage Summary:");
                $command->info("  Successfully stored: {$taskListSuccessCount}");
                $command->info("  Failed to store: {$taskListErrorCount}");
                $command->info("\nTasks Storage Summary:");
                $command->info("  Successfully stored: {$taskSuccessCount}");
                $command->info("  Failed to store: {$taskErrorCount}");
                $command->info("\nTodos Storage Summary:");
                $command->info("  Successfully stored: {$todoSuccessCount}");
                $command->info("  Failed to store: {$todoErrorCount}");
            }

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error("Failed to store task lists, tasks, and todos data: " . $e->getMessage());
            }
            Log::error("Failed to store task lists, tasks, and todos data: " . $e->getMessage());
            throw $e;
        }
    }
} 