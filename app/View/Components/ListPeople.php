<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ListPeople extends Component
{
    private string $title;
    private array $people;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $title, array $people)
    {
        $this->title = $title;
        $this->people = $people;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.list-people',[
            "title" => $this->title,
            "people" => $this->people,
        ]);
    }
}
