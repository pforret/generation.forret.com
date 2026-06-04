<?php

namespace App\Http\Controllers;

use App\Helpers\CleanWiki;
use App\Models\Person;
use App\Services\GenerationAnchor;

class PersonController extends Controller
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
        $people = Person::orderBy('born_at', 'desc')->get();

        return response()
            ->view('person.index', [
                'people' => $people,
            ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Person  $person
     * @return \Illuminate\Http\Response
     */
    public function show(Person $person)
    {
        $birth_year = (int) date('Y', strtotime($person->born_at));
        $generation = $this->anchor->generationForYear($birth_year);
        $wikidata = CleanWiki::get($person->name, 120);

        return response()
            ->view('person.show', [
                'person' => $person,
                'generation' => $generation,
                'wikidata' => $wikidata,
            ], 200);
    }
}
