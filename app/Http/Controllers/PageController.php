<?php

namespace App\Http\Controllers;

use App\Models\ProductivePage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pages = ProductivePage::with([
            'creator',
            'project',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/pages/index', [
            'pages' => [
                'data' => $pages->through(function ($page) {
                    return [
                        'id' => $page->id,
                        'type' => $page->type,
                        'title' => $page->title,
                        'creator' => [
                            'first_name' => $page->creator?->first_name,
                        ],
                        'project' => [
                            'name' => $page->project?->name,
                        ],
                        'attachment' => [
                            'name' => $page->attachment?->name,
                        ],
                        'created_at_api' => $page->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $pages->currentPage(),
                    'from' => $pages->firstItem(),
                    'last_page' => $pages->lastPage(),
                    'per_page' => $pages->perPage(),
                    'to' => $pages->lastItem(),
                    'total' => $pages->total(),
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
    public function show(ProductivePage $productivePage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivePage $productivePage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivePage $productivePage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivePage $productivePage)
    {
        //
    }
}
