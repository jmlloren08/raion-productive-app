<?php

namespace App\Http\Controllers;

use App\Models\ProductiveContactEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contactEntries = ProductiveContactEntry::with([
            'company',
            'person',
            'invoice',
            'subsidiary',
            'purchaseOrder',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/contact-entries/index', [
            'contactEntries' => [
                'data' => $contactEntries->through(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'type' => $entry->type,
                        'contactable_type' => $entry->contactable_type,
                        'name' => $entry->name,
                        'company' => [
                            'name' => $entry->company?->name,
                        ],
                        'person' => [
                            'first_name' => $entry->person?->first_name,
                        ],
                        'invoice' => [
                            'number' => $entry->invoice?->number,
                        ],
                        'subsidiary' => [
                            'name' => $entry->subsidiary?->name,
                        ],
                        'purchaseOrder' => [
                            'subject' => $entry->purchaseOrder?->subject,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $contactEntries->currentPage(),
                    'from' => $contactEntries->firstItem(),
                    'last_page' => $contactEntries->lastPage(),
                    'per_page' => $contactEntries->perPage(),
                    'to' => $contactEntries->lastItem(),
                    'total' => $contactEntries->total(),
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
    public function show(ProductivecontactEntry $productivecontactEntry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivecontactEntry $productivecontactEntry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivecontactEntry $productivecontactEntry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivecontactEntry $productivecontactEntry)
    {
        //
    }
}
