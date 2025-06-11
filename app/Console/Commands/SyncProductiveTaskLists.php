<?php

namespace App\Console\Commands;

use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\Fetch\FetchTaskLists;
use App\Actions\Productive\Fetch\FetchTasks;
use App\Actions\Productive\Fetch\FetchTodos;
use App\Actions\Productive\StoreTaskListsData;
use App\Actions\Productive\ValidateDataIntegrity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProductiveTaskLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:productive-task-lists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync task lists, tasks, and todos from Productive API to MySQL database';

    /**
     * Data array to store fetched data
     */
    private array $data = [
        'task_lists' => [],
        'tasks' => [],
        'todos' => []
    ];

    /**
     * Constructor
     */
    public function __construct(
        private readonly InitializeClient $initializeClientAction,
        private readonly FetchTaskLists $fetchTaskListsAction,
        private readonly FetchTasks $fetchTasksAction,
        private readonly FetchTodos $fetchTodosAction,
        private readonly StoreTaskListsData $storeTaskListsDataAction,
        private readonly ValidateDataIntegrity $validateDataIntegrityAction
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Productive task lists, tasks, and todos sync...');
        $startTime = microtime(true);

        try {
            // Output configuration values for debugging
            $this->info('Configuration:');
            $this->info('- API URL: ' . config('productive.api_url'));
            $this->info('- Token: ' . substr(config('productive.token'), 0, 10) . '...');
            $this->info('- Organization ID: ' . config('productive.organization_id'));

            // Initialize API client
            $this->info("\nInitializing API client...");
            $apiClient = $this->initializeClientAction->handle();

            // Fetch task lists
            $taskListsResult = $this->fetchTaskListsAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);
            
            if ($taskListsResult['success']) {
                $this->data['task_lists'] = $taskListsResult['task_lists'];
                $this->info("Successfully fetched " . count($taskListsResult['task_lists']) . " task lists");
            } else {
                $this->error('Failed to fetch task lists: ' . ($taskListsResult['error'] ?? 'Unknown error'));
                return 1;
            }

            // Fetch tasks
            $tasksResult = $this->fetchTasksAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);
            
            if ($tasksResult['success']) {
                $this->data['tasks'] = $tasksResult['tasks'];
                $this->info("Successfully fetched " . count($tasksResult['tasks']) . " tasks");
            } else {
                $this->error('Failed to fetch tasks: ' . ($tasksResult['error'] ?? 'Unknown error'));
                return 1;
            }

            // Fetch todos
            $todosResult = $this->fetchTodosAction->handle([
                'apiClient' => $apiClient,
                'command' => $this
            ]);
            
            if ($todosResult['success']) {
                $this->data['todos'] = $todosResult['todos'];
                $this->info("Successfully fetched " . count($todosResult['todos']) . " todos");
            } else {
                $this->error('Failed to fetch todos: ' . ($todosResult['error'] ?? 'Unknown error'));
                return 1;
            }

            // Store data
            $this->info("\nStoring data...");
            $this->storeTaskListsDataAction->handle([
                'data' => $this->data,
                'command' => $this
            ]);

            // Validate data integrity
            $this->info("\nValidating data integrity...");
            $this->validateDataIntegrityAction->handle([
                'command' => $this
            ]);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->info("\nSync completed successfully!");
            $this->info("Execution time: {$executionTime} seconds");
            $this->info("Task Lists synced: " . count($this->data['task_lists']));
            $this->info("Tasks synced: " . count($this->data['tasks']));
            $this->info("Todos synced: " . count($this->data['todos']));

            return 0;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('Productive task lists, tasks, and todos sync failed: ' . $e->getMessage());
            return 1;
        }
    }
} 