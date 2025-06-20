<?php

namespace App\Http\Controllers;

use App\Models\ProductiveDocumentType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documentTypes = ProductiveDocumentType::with([
            'subsidiary',
            'documentStyle',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/document-types/index', [
            'documentTypes' => [
                'data' => $documentTypes->through(function ($type) {
                    return [
                        'id' => $type->id,
                        'type' => $type->type,
                        'name' => $type->name,
                        'subsidiary' => [
                            'name' => $type->subsidiary?->name,
                        ],
                        'documentStyle' => [
                            'name' => $type->documentStyle?->name,
                        ],
                        'attachment' => [
                            'name' => $type->attachment?->name,
                        ],
                        'archived_at' => $type->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $documentTypes->currentPage(),
                    'from' => $documentTypes->firstItem(),
                    'last_page' => $documentTypes->lastPage(),
                    'per_page' => $documentTypes->perPage(),
                    'to' => $documentTypes->lastItem(),
                    'total' => $documentTypes->total(),
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
    public function show(ProductiveDocumentType $productiveDocumentType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveDocumentType $productiveDocumentType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveDocumentType $productiveDocumentType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveDocumentType $productiveDocumentType)
    {
        //
    }
}
