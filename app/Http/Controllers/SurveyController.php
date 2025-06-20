<?php

namespace App\Http\Controllers;

use App\Models\ProductiveSurvey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $surveys = ProductiveSurvey::with([
            'project',
            'creator',
            'updater',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/surveys/index', [
            'surveys' => [
                'data' => $surveys->through(function ($survey) {
                    return [
                        'id' => $survey->id,
                        'type' => $survey->type,
                        'title' => $survey->title,
                        'project' => [
                            'name' => $survey->project?->name,
                        ],
                        'creator' => [
                            'first_name' => $survey->creator?->first_name,
                        ],
                        'updater' => [
                            'first_name' => $survey->updater?->first_name,
                        ],
                        'created_at_api' => $survey->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $surveys->currentPage(),
                    'from' => $surveys->firstItem(),
                    'last_page' => $surveys->lastPage(),
                    'per_page' => $surveys->perPage(),
                    'to' => $surveys->lastItem(),
                    'total' => $surveys->total(),
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
    public function show(ProductiveSurvey $productiveSurvey)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveSurvey $productiveSurvey)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveSurvey $productiveSurvey)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveSurvey $productiveSurvey)
    {
        //
    }
}
