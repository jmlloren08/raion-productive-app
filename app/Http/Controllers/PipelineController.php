<?php

namespace App\Http\Controllers;

use App\Models\ProductivePipeline;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PipelineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pipelines = ProductivePipeline::with([
            'creator',
            'updater',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/pipelines/index', [
            'pipelines' => [
                'data' => $pipelines->through(function ($pipeline) {
                    return [
                        'id' => $pipeline->id,
                        'type' => $pipeline->type,
                        'name' => $pipeline->name,
                        'creator' => [
                            'first_name' => $pipeline->creator?->first_name,
                        ],
                        'updater' => [
                            'first_name' => $pipeline->updater?->first_name,
                        ],
                        'created_at_api' => $pipeline->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $pipelines->currentPage(),
                    'from' => $pipelines->firstItem(),
                    'last_page' => $pipelines->lastPage(),
                    'per_page' => $pipelines->perPage(),
                    'to' => $pipelines->lastItem(),
                    'total' => $pipelines->total(),
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
    public function show(ProductivePipeline $productivePipeline)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivePipeline $productivePipeline)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivePipeline $productivePipeline)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivePipeline $productivePipeline)
    {
        //
    }
}
