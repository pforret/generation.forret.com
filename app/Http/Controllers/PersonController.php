<?php

namespace App\Http\Controllers;

use App\Helpers\CleanWiki;
use App\Models\Generation;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminated\Wikipedia\Wikipedia;
use Soundasleep\Html2Text;

class PersonController extends Controller
{
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
                "people" => $people
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
        $birth_year = date("Y",strtotime($person->born_at));
        $generation = Generation::where("first_year","<=",$birth_year)
            ->where("last_year",">=",$birth_year)
            ->first();
        $wikidata = CleanWiki::get($person->name,120);
        return response()
            ->view('person.show', [
                "person" => $person,
                "generation" => $generation,
                "wikidata" => $wikidata,
            ], 200);
    }

}
