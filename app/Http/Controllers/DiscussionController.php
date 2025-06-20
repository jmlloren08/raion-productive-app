<?php

namespace App\Http\Controllers;

use App\Models\ProductiveDiscussion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiscussionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dicussions = ProductiveDiscussion::with([
            'page',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/discussions/index', [
            'discussions' => [
                'data' => $dicussions->through(function ($discussion) {
                    return [
                        'id' => $discussion->id,
                        'type' => $discussion->type,
                        'excerpt' => $discussion->excerpt,
                        'page' => [
                            'title' => $discussion->page?->title,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $dicussions->currentPage(),
                    'from' => $dicussions->firstItem(),
                    'last_page' => $dicussions->lastPage(),
                    'per_page' => $dicussions->perPage(),
                    'to' => $dicussions->lastItem(),
                    'total' => $dicussions->total(),
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
    public function show(ProductiveDiscussion $productiveDiscussion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveDiscussion $productiveDiscussion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveDiscussion $productiveDiscussion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveDiscussion $productiveDiscussion)
    {
        //
    }
}
