<x-guest-layout>
    <x-slot name="title">
        The Generationz
    </x-slot>
    <x-slot name="short">
        These are the different generation definitions.
    </x-slot>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-6 g-3">
        @foreach($people as $person)
            <div class="col">
                <div class="card shadow-sm">
                    <a href="{{ route('person.show',["person" => $person["id"]]) }}">
                        <img class="bd-placeholder-img card-img-top" width="100%" height="225"
                             aria-label="{{ $person->name }} Thumbnail" src="{{ $person->image ?? "/images/people/-.jpg"}}">
                    </a>
                    <div class="card-body">
                        <h3 class="text-xl"><a
                                href="{{ route('person.show',["person" => $person["id"]]) }}">{{ $person->name }}</a>
                        </h3>
                        <p class="card-text"><small>{{ Str::limit($person->description,50) }}</small></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="text-primary">born in {{ $person->born_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    </div>
</x-guest-layout>
