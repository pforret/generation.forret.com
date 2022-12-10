<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ListEvents extends Component
{
    private array $events;
    private string $title;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $title, array $events)
    {
        $this->title = $title;
        $this->events = $events;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.list-events',[
            "events" => $this->events,
            "title" => $this->title,
        ]);
    }
}
