<?php

namespace App\Http\Controllers;

use App\Models\ProductiveSection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sections = ProductiveSection::with([
            'deal',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/sections/index', [
            'sections' => [
                'data' => $sections->through(function ($section) {
                    return [
                        'id' => $section->id,
                        'type' => $section->type,
                        'name' => $section->name,
                        'deal' => [
                            'name' => $section->deal?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $sections->currentPage(),
                    'from' => $sections->firstItem(),
                    'last_page' => $sections->lastPage(),
                    'per_page' => $sections->perPage(),
                    'to' => $sections->lastItem(),
                    'total' => $sections->total(),
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
    public function show(ProductiveSection $productiveSection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveSection $productiveSection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveSection $productiveSection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveSection $productiveSection)
    {
        //
    }
}
