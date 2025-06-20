<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTimesheet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TimesheetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $timesheets = ProductiveTimesheet::with([
            'person',
            'creator',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/timesheets/index', [
            'timesheets' => [
                'data' => $timesheets->through(function ($timesheet) {
                    return [
                        'id' => $timesheet->id,
                        'type' => $timesheet->type,
                        'date' => $timesheet->date,
                        'person' => [
                            'first_name' => $timesheet->person?->first_name,
                        ],
                        'creator' => [
                            'first_name' => $timesheet->creator?->first_name,
                        ],
                        'created_at_api' => $timesheet->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $timesheets->currentPage(),
                    'from' => $timesheets->firstItem(),
                    'last_page' => $timesheets->lastPage(),
                    'per_page' => $timesheets->perPage(),
                    'to' => $timesheets->lastItem(),
                    'total' => $timesheets->total(),
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
    public function show(ProductiveTimesheet $productiveTimesheet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTimesheet $productiveTimesheet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTimesheet $productiveTimesheet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTimesheet $productiveTimesheet)
    {
        //
    }
}
