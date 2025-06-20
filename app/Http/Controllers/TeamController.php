<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teams = ProductiveTeam::latest()->paginate(10);

        return Inertia::render('datasets/teams/index', [
            'teams' => [
                'data' => $teams->through(function ($team) {
                    return [
                        'id' => $team->id,
                        'type' => $team->type,
                        'name' => $team->name,
                        'color_id' => $team->color_id,
                        'icon_id' => $team->icon_id,
                        'members_included' => $team->members_included,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $teams->currentPage(),
                    'from' => $teams->firstItem(),
                    'last_page' => $teams->lastPage(),
                    'per_page' => $teams->perPage(),
                    'to' => $teams->lastItem(),
                    'total' => $teams->total(),
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
    public function show(ProductiveTeam $productiveTeam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTeam $productiveTeam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTeam $productiveTeam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTeam $productiveTeam)
    {
        //
    }
}
