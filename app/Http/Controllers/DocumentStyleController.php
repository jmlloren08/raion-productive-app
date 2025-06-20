<?php

namespace App\Http\Controllers;

use App\Models\ProductiveDocumentStyle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentStyleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documentStyles = ProductiveDocumentStyle::with([
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/document-styles/index', [
            'documentStyles' => [
                'data' => $documentStyles->through(function ($style) {
                    return [
                        'id' => $style->id,
                        'type' => $style->type,
                        'name' => $style->name,
                        'attachment' => [
                            'name' => $style->attachment?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $documentStyles->currentPage(),
                    'from' => $documentStyles->firstItem(),
                    'last_page' => $documentStyles->lastPage(),
                    'per_page' => $documentStyles->perPage(),
                    'to' => $documentStyles->lastItem(),
                    'total' => $documentStyles->total(),
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
    public function show(ProductiveDocumentStyle $productiveDocumentStyle)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveDocumentStyle $productiveDocumentStyle)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveDocumentStyle $productiveDocumentStyle)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveDocumentStyle $productiveDocumentStyle)
    {
        //
    }
}
