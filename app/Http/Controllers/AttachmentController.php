<?php

namespace App\Http\Controllers;

use App\Models\ProductiveAttachment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttachmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attachments = ProductiveAttachment::with([
            'creator',
            'invoice',
            'purchaseOrder',
            'bill',
            'email',
            'page',
            'expense',
            'comment',
            'task',
            'documentStyle',
            'documentType',
            'deal',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/attachments/index', [
            'attachments' => [
                'data' => $attachments->through(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'type' => $attachment->type,
                        'name' => $attachment->name,
                        'creator' => [
                            'first_name' => $attachment->creator?->first_name,
                        ],
                        'invoice' => [
                            'number' => $attachment->invoice?->number,
                        ],
                        'purchaseOrder' => [
                            'subject' => $attachment->purchaseOrder?->subject,
                        ],
                        'bill' => [
                            'total_cost' => $attachment->bill?->total_cost,
                        ],
                        'email' => [
                            'subject' => $attachment->email?->subject,
                        ],
                        'page' => [
                            'title' => $attachment->page?->title,
                        ],
                        'expense' => [
                            'name' => $attachment->expense?->name,
                        ],
                        'comment' => [
                            'body' => $attachment->comment?->body,
                        ],
                        'task' => [
                            'title' => $attachment->task?->title,
                        ],
                        'documentStyle' => [
                            'name' => $attachment->documentStyle?->name,
                        ],
                        'documentType' => [
                            'name' => $attachment->documentType?->name,
                        ],
                        'deal' => [
                            'name' => $attachment->deal?->name,
                        ],
                        'created_at_api' => $attachment->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $attachments->currentPage(),
                    'from' => $attachments->firstItem(),
                    'last_page' => $attachments->lastPage(),
                    'per_page' => $attachments->perPage(),
                    'to' => $attachments->lastItem(),
                    'total' => $attachments->total(),
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
    public function show(ProductiveAttachment $productiveAttachment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveAttachment $productiveAttachment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveAttachment $productiveAttachment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveAttachment $productiveAttachment)
    {
        //
    }
}
