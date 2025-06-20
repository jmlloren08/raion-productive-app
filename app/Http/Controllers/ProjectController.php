<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductiveProject;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = ProductiveProject::with([
            'company',
            'projectManager',
            'lastActor',
            'workflow'
        ])->latest()->paginate(10);

        return Inertia::render('datasets/projects/index', [
            'projects' => [
                'data' => $projects->through(function ($project) {
                    return [
                        'id' => $project->id,
                        'type' => $project->type,
                        'name' => $project->name,
                        'company' => [
                            'name' => $project->company?->name
                        ],
                        'projectManager' => [
                            'first_name' => $project->projectManager?->first_name,
                        ],
                        'lastActor' => [
                            'first_name' => $project->lastActor?->first_name,
                        ],
                        'workflow' => [
                            'name' => $project->workflow?->name
                        ],
                        'last_activity_at' => $project->last_activity_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $projects->currentPage(),
                    'from' => $projects->firstItem(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'to' => $projects->lastItem(),
                    'total' => $projects->total(),
                ],
            ],
        ]);
    }

    public function show(string $id)
    {
        return ProductiveProject::with('company')
            ->findOrFail($id);
    }
}
