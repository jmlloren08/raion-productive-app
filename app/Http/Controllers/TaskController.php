<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTask;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = ProductiveTask::with([
            'project',
            'creator',
            'assignee',
            'lastActor',
            'taskList',
            'parentTask',
            'workflowStatus',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/tasks/index', [
            'tasks' => [
                'data' => $tasks->through(function ($task) {
                    return [
                        'id' => $task->id,
                        'type' => $task->type,
                        'title' => $task->title,
                        'project' => [
                            'name' => $task->project?->name,
                        ],
                        'creator' => [
                            'first_name' => $task->creator?->first_name,
                        ],
                        'assignee' => [
                            'first_name' => $task->assignee?->first_name,
                        ],
                        'lastActor' => [
                            'first_name' => $task->lastActor?->first_name,
                        ],
                        'taskList' => [
                            'name' => $task->taskList?->name,
                        ],
                        'parentTask' => [
                            'title' => $task->parentTask?->title,
                        ],
                        'workflowStatus' => [
                            'name' => $task->workflowStatus?->name,
                        ],
                        'attachment' => [
                            'name' => $task->attachment?->name,
                        ],
                        'created_at_api' => $task->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'from' => $tasks->firstItem(),
                    'last_page' => $tasks->lastPage(),
                    'per_page' => $tasks->perPage(),
                    'to' => $tasks->lastItem(),
                    'total' => $tasks->total(),
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductiveTask $productiveTask)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTask $productiveTask)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTask $productiveTask)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTask $productiveTask)
    {
        //
    }
}
