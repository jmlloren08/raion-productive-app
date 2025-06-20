<?php

namespace App\Http\Controllers;

use App\Models\ProductiveCustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customFields = ProductiveCustomField::with([
            'project',
            'section',
            'survey',
            'person',
            'cfo',
        ])->latest()->paginate(10);

        return inertia('datasets/custom-fields/index', [
            'customFields' => [
                'data' => $customFields->through(function ($field) {
                    return [
                        'id' => $field->id,
                        'type' => $field->type,
                        'name' => $field->name,
                        'project' => [
                            'name' => $field->project?->name,
                        ],
                        'section' => [
                            'name' => $field->section?->name,
                        ],
                        'survey' => [
                            'title' => $field->survey?->title,
                        ],
                        'person' => [    
                            'first_name' => $field->person?->first_name,
                        ],
                        'cfo' => [
                            'name' => $field->cfo?->name,
                        ],
                        'created_at_api' => $field->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $customFields->currentPage(),
                    'from' => $customFields->firstItem(),
                    'last_page' => $customFields->lastPage(),
                    'per_page' => $customFields->perPage(),
                    'to' => $customFields->lastItem(),
                    'total' => $customFields->total(),
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
    public function show(ProductiveCustomField $productiveCustomField)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveCustomField $productiveCustomField)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveCustomField $productiveCustomField)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveCustomField $productiveCustomField)
    {
        //
    }
}
