<x-guest-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Brightfish Proposal
        </h2>
    </x-slot>
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg my-1 p-4">
                <div style="width:100%;" class="p-5">
                    <h1 class="p-2 text-2xl font-extrabold" style="background-color: #0051a9; color: #FFFFFF; ">Generations</h1>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg my-1 p-4">
                <div style="width:100%;" class="p-5">
                    <div class="grid-cols-3">

                        @foreach($generations as $generation)
                            <div class="grid-cols-3">
                                <h3>{{ $generation->title }}</h3>
                                <p>{{ $generation->description }}</p>
                                <p>born in {{ $generation->first_year }} - {{ $generation->last_year }}</p>
                            </div>
                        @endforeach
                </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
