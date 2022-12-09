<x-guest-layout>
    <x-slot name="title">
        The Generationz
    </x-slot>
    <x-slot name="short">
        These are the different generation definitions.
    </x-slot>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
        @foreach($generations as $generation)
            <div class="col">
                <div class="card shadow-sm">
                    <a href="{{  route('generation.show',['generation' => $generation->slug ]) }}">
                        <img class="bd-placeholder-img card-img-top" width="100%" height="225"
                             aria-label="{{ $generation->title }} Thumbnail" src="{{ $generation->image }}">
                    </a>
                    <div class="card-body">
                        <h3 class="text-xl"><a
                                href="{{  route('generation.show',['generation' => $generation->slug ]) }}">{{ $generation->title }}</a>
                        </h3>
                        <p class="card-text">{{ $generation->description }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="text-primary">born in {{ $generation->first_year }} - {{ $generation->last_year }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    </div>
</x-guest-layout>
