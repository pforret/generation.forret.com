<x-guest-layout>
    <x-slot name="title">
        {{ $generation->title }}
    </x-slot>
    <x-slot name="short">
        born in {{ $generation->first_year }} - {{ $generation->last_year }}<br>
        {{ $generation->description }}
    </x-slot>
    <div class="lg:justify-center">
        <img src="{{ $generation->image }}" class="img-fluid" alt="{{ $generation->title }}">

    </div>
    <h2 class="text-3xl m-2">Memorable Quotes</h2>
    <dl>
        @foreach($quotes as $quote)
            <dt><a href="{{$quote["url"]}}">{{$quote["title"]}}</a> ({{$quote["author"]}})</dt>
            <dd>{{ $quote["description"] }}</dd>
        @endforeach
    </dl>

    <!--
    <x-list-events title="Memorable events during Childhood" :events="$events['child']"/>
    <x-list-events title="Memorable events during Puberty" :events="$events['puberty']"/>
    <x-list-events title="Memorable events during Adulthood" :events="$events['adult']"/>
    <x-list-events title="Memorable events during Retirement" :events="$events['retired']"/>
-->
    <x-list-people title="Memorable personalities in this generation" :people="$people"/>



</x-guest-layout>
