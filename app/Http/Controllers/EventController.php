<?php

namespace App\Http\Controllers;

use App\Models\ProductiveEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = ProductiveEvent::latest()->paginate(10);

        return Inertia::render('datasets/events/index', [
            'events' => [
                'data' => $events->through(function ($event) {
                    return [
                        'id' => $event->id,
                        'type' => $event->type,
                        'name' => $event->name,
                        'event_type_id' => $event->event_type_id,
                        'archived_at' => $event->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'from' => $events->firstItem(),
                    'last_page' => $events->lastPage(),
                    'per_page' => $events->perPage(),
                    'to' => $events->lastItem(),
                    'total' => $events->total(),
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
    public function show(ProductiveEvent $productiveEvent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveEvent $productiveEvent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveEvent $productiveEvent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveEvent $productiveEvent)
    {
        //
    }
}
