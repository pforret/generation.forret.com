<x-guest-layout>
    <x-slot name="title">
        {{ $generation->title }}
    </x-slot>
    <x-slot name="short">
        born in {{ $generation->first_year }} - {{ $generation->last_year }}<br>
        {{ $generation->description }}
    </x-slot>

    <h2 class="text-3xl m-2">Memorable Moments</h2>

    <h3 class="text-2xl m-1">During childhood</h3>
    @foreach($events["child"] as $event)
    <li>{{$event["happened_at"]}}: {{$event["title"]}}</li>
    @endforeach

    <h3 class="text-2xl m-1">During puberty</h3>
    @foreach($events["puberty"] as $event)
        <li>{{$event["happened_at"]}}: {{$event["title"]}}</li>
    @endforeach

    <h3 class="text-2xl m-1">During adulthood</h3>
    @foreach($events["adult"] as $event)
        <li>{{$event["happened_at"]}}: {{$event["title"]}}</li>
    @endforeach

    <h3 class="text-2xl m-1">During retirement</h3>
    @foreach($events["retired"] as $event)
        <li>{{$event["happened_at"]}}: {{$event["title"]}}</li>
    @endforeach


    <h2 class="text-3xl m-2">Memorable People</h2>
    @foreach($people as $person)
        <li>{{$person["born_at"]}}: {{$person["name"]}}</li>
    @endforeach


    <h2 class="text-3xl m-2">Memorable Quotes</h2>
    <dl>
    @foreach($quotes as $quote)
        <dt><a href="{{$quote["url"]}}">{{$quote["title"]}}</a> ({{$quote["author"]}})</dt>
        <dd>{{ $quote["description"] }}</dd>
    @endforeach
    </dl>

</x-guest-layout>
