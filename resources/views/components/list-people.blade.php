@if($people)
    <h2 class="text-3xl m-2">{{ $title }}</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        @foreach($people as $person)
            <div class="col-lg-1">
                <img src="{{ $person["image"] ?? "/images/people/empty.jpg" }}" class="img-fluid rounded-start" alt="...">
            </div>
            <div class="col-lg-2">
                <div class="card-body">
                    <h5 class="card-title text-xl">{{$person["name"]}}</h5>
                    <div class="card-text small">
                        <span class="badge bg-primary text-white">{{$person["category"]}}</span>
                        <span class="badge bg-primary text-white">{{$person["born_at"]}}</span>
                        <div class="overflow-auto" style="height: 2em" >
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
