<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = ProductiveTag::latest()->paginate(10);

        return Inertia::render('datasets/tags/index', [
            'tags' => [
                'data' => $tags->through(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'type' => $tag->type,
                        'name' => $tag->name,
                        'color' => $tag->color,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $tags->currentPage(),
                    'from' => $tags->firstItem(),
                    'last_page' => $tags->lastPage(),
                    'per_page' => $tags->perPage(),
                    'to' => $tags->lastItem(),
                    'total' => $tags->total(),
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
    public function show(ProductiveTag $productiveTag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTag $productiveTag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTag $productiveTag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTag $productiveTag)
    {
        //
    }
}
