<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use Illuminate\Http\Request;

class GenerationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $generations = Generation::orderBy('generations.first_year','desc')->get();
        return response()
            ->view('generation.index', [
                "generations" => $generations
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
        return response()
            ->view('generation.show', [
                "generation" => $generation
            ], 200);
    }


}
