<?php

namespace App\Http\Controllers;

use App\Models\ProductiveCfo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomFieldOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customFieldOptions = ProductiveCfo::with([
            'customField',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/custom-field-options/index', [
            'customFieldOptions' => [
                'data' => $customFieldOptions->through(function ($option) {
                    return [
                        'id' => $option->id,
                        'type' => $option->type,
                        'name' => $option->name,
                        'customField' => [
                            'name' => $option->customField?->name,
                        ],
                        'archived_at' => $option->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $customFieldOptions->currentPage(),
                    'from' => $customFieldOptions->firstItem(),
                    'last_page' => $customFieldOptions->lastPage(),
                    'per_page' => $customFieldOptions->perPage(),
                    'to' => $customFieldOptions->lastItem(),
                    'total' => $customFieldOptions->total(),
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
    public function show(ProductiveCfo $productiveCfo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveCfo $productiveCfo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveCfo $productiveCfo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveCfo $productiveCfo)
    {
        //
    }
}
