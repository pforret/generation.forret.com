<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Models\Quote;
use App\Services\GenerationAnchor;

class GenerationController extends Controller
{
    public function __construct(private GenerationAnchor $anchor)
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $generations = Generation::orderBy('generations.first_year', 'desc')->get();

        return response()
            ->view('generation.index', [
                'generations' => $generations,
            ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Generation  $generation
     * @return \Illuminate\Http\Response
     */
    public function show(Generation $generation)
    {
        $events = array_map(
            fn ($stage) => $stage->toArray(),
            $this->anchor->eventsByLifeStage($generation)
        );

        $people = $this->anchor->peopleBornIn($generation)->toArray();

        $quotes = Quote::whereGenerationId($generation->id)->get()->toArray();

        return response()
            ->view('generation.show', [
                'generation' => $generation,
                'events' => $events,
                'people' => $people,
                'quotes' => $quotes,
            ], 200);
    }
}
