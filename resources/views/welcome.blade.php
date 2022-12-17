<x-guest-layout>
    <x-slot name="header">Generationz</x-slot>
    <x-slot name="title">Welcome to GenerationZ</x-slot>
    <x-slot name="short">

    </x-slot>

    <h2 class="text-3xl m-2">Generations</h2>
    <div class="row">
        @foreach($generations as $generation)
            <div class="col-sm-4">
                <div class="card bg-gray-50" style="xwidth: 24rem;">
                    <a
                        href="{{ route("generation.show",[ "generation" => $generation->slug ]) }}"><img
                            src="{{ $generation->image }}" class="card-img-top img-thumbnail" alt="..."></a>
                </div>
            </div>
        @endforeach
    </div>


</x-guest-layout>
