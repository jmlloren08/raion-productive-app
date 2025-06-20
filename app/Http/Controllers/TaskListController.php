<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTaskList;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $taskLists = ProductiveTaskList::with([
            'project',
            'board',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/task-lists/index', [
            'taskLists' => [
                'data' => $taskLists->through(function ($taskList) {
                    return [
                        'id' => $taskList->id,
                        'type' => $taskList->type,
                        'name' => $taskList->name,
                        'project' => [
                            'name' => $taskList->project?->name,
                        ],
                        'board' => [
                            'name' => $taskList->board?->name,
                        ],
                        'archived_at' => $taskList->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $taskLists->currentPage(),
                    'from' => $taskLists->firstItem(),
                    'last_page' => $taskLists->lastPage(),
                    'per_page' => $taskLists->perPage(),
                    'to' => $taskLists->lastItem(),
                    'total' => $taskLists->total(),
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
    public function show(ProductiveTaskList $productiveTaskList)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTaskList $productiveTaskList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTaskList $productiveTaskList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTaskList $productiveTaskList)
    {
        //
    }
}
