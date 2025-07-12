<?php

namespace App\Filament\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class SkeletonLoader extends Component
{
    public function render(): View
    {
        return view('filament.components.skeleton-loader');
    }
}
