<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTimeEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timeEntries = ProductiveTimeEntry::with([
            'person',
            'service',
            'task',
            'approver',
            'updater',
            'rejecter',
            'creator',
            'lastActor',
            'personSubsidiary',
            'dealSubsidiary',
            'timesheet',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/time-entries/index', [
            'timeEntries' => [
                'data' => $timeEntries->through(function ($timeEntry) {
                    return [
                        'id' => $timeEntry->id,
                        'type' => $timeEntry->type,
                        'date' => $timeEntry->date,
                        'time' => $timeEntry->time,
                        'person' => [
                            'first_name' => $timeEntry->person?->first_name,
                        ],
                        'service' => [
                            'name' => $timeEntry->service?->name,
                        ],
                        'task' => [
                            'title' => $timeEntry->task?->title,
                        ],
                        'approver' => [
                            'first_name' => $timeEntry->approver?->first_name,
                        ],
                        'updater' => [
                            'first_name' => $timeEntry->updater?->first_name,
                        ],
                        'rejecter' => [
                            'first_name' => $timeEntry->rejecter?->first_name,
                        ],
                        'creator' => [
                            'first_name' => $timeEntry->creator?->first_name,
                        ],
                        'lastActor' => [
                            'first_name' => $timeEntry->lastActor?->first_name,
                        ],
                        'personSubsidiary' => [
                            'name' => $timeEntry->personSubsidiary?->name,
                        ],
                        'dealSubsidiary' => [
                            'name' => $timeEntry->dealSubsidiary?->name,
                        ],
                        'timesheet' => [
                            'date' => $timeEntry->timesheet?->date,
                        ],
                        'created_at_api' => $timeEntry->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $timeEntries->currentPage(),
                    'from' => $timeEntries->firstItem(),
                    'last_page' => $timeEntries->lastPage(),
                    'per_page' => $timeEntries->perPage(),
                    'to' => $timeEntries->lastItem(),
                    'total' => $timeEntries->total(),
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
    public function show(ProductiveTimeEntry $productiveTimeEntry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTimeEntry $productiveTimeEntry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTimeEntry $productiveTimeEntry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTimeEntry $productiveTimeEntry)
    {
        //
    }
}
