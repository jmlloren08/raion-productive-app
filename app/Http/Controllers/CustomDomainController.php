<?php

namespace App\Http\Controllers;

use App\Models\ProductiveCustomDomain;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customDomains = ProductiveCustomDomain::with([
            'subsidiary',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/custom-domains/index', [
            'customDomains' => [
                'data' => $customDomains->through(function ($domain) {
                    return [
                        'id' => $domain->id,
                        'type' => $domain->type,
                        'name' => $domain->name,
                        'verified_at' => $domain->verified_at,
                        'subsidiary' => [
                            'name' => $domain->subsidiary?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $customDomains->currentPage(),
                    'from' => $customDomains->firstItem(),
                    'last_page' => $customDomains->lastPage(),
                    'per_page' => $customDomains->perPage(),
                    'to' => $customDomains->lastItem(),
                    'total' => $customDomains->total(),
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
    public function show(ProductiveCustomDomain $productiveCustomDomain)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveCustomDomain $productiveCustomDomain)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveCustomDomain $productiveCustomDomain)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveCustomDomain $productiveCustomDomain)
    {
        //
    }
}
