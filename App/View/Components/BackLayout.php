<?php

// app/View/Components/Back.php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BackLayout extends Component
{
    public ?string $to;

    public function __construct(?string $to = null)
    {
        $this->to = $to;
    }

    public function render(): View|Closure|string
    {
        return view('components.back');
    }
}
