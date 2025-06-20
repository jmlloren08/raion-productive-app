<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTimeEntryVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeEntryVersionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timeEntryVersions = ProductiveTimeEntryVersion::with([
            'creator',
            'timeEntry',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/time-entry-versions/index', [
            'timeEntryVersions' => [
                'data' => $timeEntryVersions->through(function ($version) {
                    return [
                        'id' => $version->id,
                        'type' => $version->type,
                        'event' => $version->event,
                        'object_changes' => $version->object_changes,
                        'timeEntry' => [
                            'date' => $version->timeEntry?->date,
                            'time' => $version->timeEntry?->time,
                        ],
                        'item_type' => $version->item_type,
                        'creator' => [
                            'first_name' => $version->creator?->first_name,
                        ],
                        'created_at_api' => $version->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $timeEntryVersions->currentPage(),
                    'from' => $timeEntryVersions->firstItem(),
                    'last_page' => $timeEntryVersions->lastPage(),
                    'per_page' => $timeEntryVersions->perPage(),
                    'to' => $timeEntryVersions->lastItem(),
                    'total' => $timeEntryVersions->total(),
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
    public function show(ProductiveTimeEntryVersion $productiveTimeEntryVersion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTimeEntryVersion $productiveTimeEntryVersion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTimeEntryVersion $productiveTimeEntryVersion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTimeEntryVersion $productiveTimeEntryVersion)
    {
        //
    }
}
