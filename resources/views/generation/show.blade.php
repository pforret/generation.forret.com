<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Lorem Ipsum is simply dummy text of the printing and typesetting industry.
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg my-1 p-4">
                <div style="width:100%;" class="p-5">
                    <div class="grid-cols-3">
                            <div class="grid-cols-3">
                                <h3 class="text-2xl font-bold"><a href="{{  route('generation.show',['generation' => $generation->slug ]) }}">{{ $generation->title }}</a></h3>
                                <p>{{ $generation->description }}</p>
                                <p>born in {{ $generation->first_year }} - {{ $generation->last_year }}</p>
                            </div>
                </div>
                </div>
            </div>
</x-guest-layout>
