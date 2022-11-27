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
                    <svg class="bd-placeholder-img card-img-top" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                        <title></title>
                        <rect width="100%" height="100%" fill="#55595c"></rect><text x="50%" y="50%" fill="#eceeef" dy=".3em">{{ $generation->image }}</text></svg>

                    <div class="card-body">
                        <h3><a href="{{  route('generation.show',['generation' => $generation->slug ]) }}">{{ $generation->title }}</a></h3>
                        <p class="card-text">{{ $generation->description }} test</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <!--
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary">View</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary">Edit</button>
                            </div>
                            -->
                            <p class="text-primary">born in {{ $generation->first_year }} - {{ $generation->last_year }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</x-guest-layout>
