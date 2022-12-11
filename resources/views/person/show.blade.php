<x-guest-layout>
    <x-slot name="title">
        {{ $person->name }}
    </x-slot>
    <x-slot name="short">
        was born in {{ $person->born_at }}<br>
        This makes them a member of the <b>{{ $generation->title }}</b>.
    </x-slot>
    <div class="row items-center">
    <div class="card mb-3" style="max-width: 720px;">
        <div class="row g-0">
            <div class="col-md-4">
                <img src="{{ $person->image }}" class="img-fluid rounded-start" alt="...">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title text-xl">{{ $person->name }}</h5>
                    <p class="card-text">was born on {{ $person->born_at }}<br>
                        This makes them a member of <a href="{{ route("generation.show",["generation" => $generation->slug ]) }}"><b>{{ $generation->title }}</b></a>
                    </p>

                    <p class="text-justify">
                        <small><i>{{ $wikidata }}</i></small>
                    </p>
                </div>
            </div>
        </div>
    </div>
    </div>
</x-guest-layout>
