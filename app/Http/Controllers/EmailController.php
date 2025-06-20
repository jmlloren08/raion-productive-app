<?php

namespace App\Http\Controllers;

use App\Models\ProductiveEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $emails = ProductiveEmail::with([
            'creator',
            'deal',
            'invoice',
            'prs',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/emails/index', [
            'emails' => [
                'data' => $emails->through(function ($email) {
                   return [
                       'id' => $email->id,
                       'type' => $email->type,
                       'name' => $email->name,
                       'creator' => [
                           'first_name' => $email->creator?->first_name,
                       ],
                       'deal' => [
                           'name' => $email->deal?->name,
                       ],
                       'invoice' => [
                           'number' => $email->invoice?->number,
                       ],
                       'prs' => [
                           'name' => $email->prs?->name,
                       ],
                       'attachment' => [
                           'name' => $email->attachment?->name,
                       ],
                       'created_at_api' => $email->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $emails->currentPage(),
                    'from' => $emails->firstItem(),
                    'last_page' => $emails->lastPage(),
                    'per_page' => $emails->perPage(),
                    'to' => $emails->lastItem(),
                    'total' => $emails->total(),
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
    public function show(ProductiveEmail $productiveEmail)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveEmail $productiveEmail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveEmail $productiveEmail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveEmail $productiveEmail)
    {
        //
    }
}
