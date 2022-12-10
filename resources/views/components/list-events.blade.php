@if($events)
    <h2 class="text-3xl m-2">{{ $title }}</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        @foreach($events as $event)
            <div class="col p-0">
                <div class="card border-0">
                    <div class="card-body border-0" style="height: 10em">
                        <h5 class="card-title">{{$event["title"]}}</h5>
                        <div class="card-text small">
                            <span class="badge bg-primary text-white">{{$event["category"]}}</span>
                            <span class="badge bg-primary text-white">{{$event["happened_at"]}}</span>
                            <div class="overflow-auto" >
                                {{ $event["description"]  }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
