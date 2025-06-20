<?php

namespace App\Http\Controllers;

use App\Models\ProductivePeople;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $persons = ProductivePeople::with([
            'manager',
            'company',
            'subsidiary',
            'apa',
            'team',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/people/index', [
            'persons' => [
                'data' => $persons->through(function ($person) {
                    return [
                        'id' => $person->id,
                        'type' => $person->type,
                        'first_name' => $person->first_name,
                        'last_name' => $person->last_name,
                        'manager' => [
                            'first_name' => $person->manager?->first_name,
                        ],
                        'company' => [
                            'name' => $person->company?->name,
                        ],
                        'subsidiary' => [
                            'name' => $person->subsidiary?->name,
                        ],
                        'apa' => [
                            'target_type' => $person->apa?->target_type,
                        ],
                        'team' => [
                            'name' => $person->team?->name,
                        ],
                        'created_at_api' => $person->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $persons->currentPage(),
                    'from' => $persons->firstItem(),
                    'last_page' => $persons->lastPage(),
                    'per_page' => $persons->perPage(),
                    'to' => $persons->lastItem(),
                    'total' => $persons->total(),
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
    public function show(ProductivePeople $productivePeople)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivePeople $productivePeople)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivePeople $productivePeople)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivePeople $productivePeople)
    {
        //
    }
}
