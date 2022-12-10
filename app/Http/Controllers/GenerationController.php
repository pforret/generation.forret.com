<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Generation;
use App\Models\Person;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerationController extends Controller
{
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
                "generations" => $generations
            ], 200);

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Generation $generation
     * @return \Illuminate\Http\Response
     */
    public function show(Generation $generation)
    {
        $middle = round(($generation->first_year + $generation->last_year) / 2);
        $events["child"] = Event::whereBetween('happened_at', [$this->formatDateYmd($middle, 6), $this->formatDateYmd($middle, 12)])->orderBy('happened_at')->get()->toArray();
        $events["puberty"] = Event::whereBetween('happened_at', [$this->formatDateYmd($middle, 13), $this->formatDateYmd($middle, 20)])->orderBy('happened_at')->get()->toArray();
        $events["adult"] = Event::whereBetween('happened_at', [$this->formatDateYmd($middle, 21), $this->formatDateYmd($middle, 60)])->orderBy('happened_at')->get()->toArray();
        $events["retired"] = Event::whereBetween('happened_at', [$this->formatDateYmd($middle, 61), $this->formatDateYmd($middle, 80)])->orderBy('happened_at')->get()->toArray();

        $people = Person::whereBetween('born_at', [$this->formatDateYmd($generation->first_year),$this->formatDateYmd($generation->last_year+1)])->get()->toArray();

        $quotes = Quote::whereGenerationId($generation->id)->get()->toArray();

        Log::info(print_r($events, true));
        return response()
            ->view('generation.show', [
                "generation" => $generation,
                "events" => $events,
                "people" => $people,
                "quotes" => $quotes,
            ], 200);
    }

    private function formatDateYmd(int $year, int $nb_years = 0): string
    {
        return sprintf("%04d-%02d-%02d", $year + $nb_years, 1, 1);
    }


}
