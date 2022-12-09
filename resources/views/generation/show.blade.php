<x-guest-layout>
    <x-slot name="title">
        {{ $generation->title }}
    </x-slot>
    <x-slot name="short">
        born in {{ $generation->first_year }} - {{ $generation->last_year }}<br>
        {{ $generation->description }}
    </x-slot>

    <h3>Memorable Moments</h3>
    <h3>Memorable People</h3>
    <h3>Memorable Quotes</h3>




</x-guest-layout>
