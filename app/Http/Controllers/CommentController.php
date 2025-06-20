<?php

namespace App\Http\Controllers;

use App\Models\ProductiveComment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comments = ProductiveComment::with([
            'company',
            'creator',
            'deal',
            'discussion',
            'invoice',
            'person',
            'pinnedBy',
            'task',
            'purchaseOrder',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/comments/index', [
            'comments' => [
                'data' => $comments->through(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'type' => $comment->type,
                        'body' => $comment->body,
                        'company' => [
                            'name' => $comment->company?->name,
                        ],
                        'creator' => [
                            'first_name' => $comment->creator?->first_name,
                        ],
                        'deal' => [
                            'name' => $comment->deal?->name,
                        ],
                        'discussion' => [
                            'excerpt' => $comment->discussion?->excerpt,
                        ],
                        'invoice' => [
                            'number' => $comment->invoice?->number,
                        ],
                        'person' => [
                            'first_name' => $comment->person?->first_name,
                        ],
                        'pinnedBy' => [
                            'first_name' => $comment->pinnedBy?->first_name,
                        ],
                        'task' => [
                            'title' => $comment->task?->title,
                        ],
                        'purchaseOrder' => [
                            'subject' => $comment->purchaseOrder?->subject,
                        ],
                        'attachment' => [
                            'name' => $comment->attachment?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $comments->currentPage(),
                    'from' => $comments->firstItem(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'to' => $comments->lastItem(),
                    'total' => $comments->total(),
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
    public function show(ProductiveComment $productiveComment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveComment $productiveComment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveComment $productiveComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveComment $productiveComment)
    {
        //
    }
}
