<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;

class ThemeToggle extends Component
{
    public $currentTheme = 'light';
    public $buttonClass = 'theme-toggle me-3';

    public function mount($class = null)
    {
        $this->currentTheme = Session::get('theme', 'light');
        if ($class) {
            $this->buttonClass = $class;
        }
    }

    public function toggleTheme()
    {
        // Cycle through themes: light -> dark -> blue -> green -> purple -> orange -> light
        $themes = ['light', 'dark', 'blue', 'green', 'purple', 'orange'];
        $currentIndex = array_search($this->currentTheme, $themes);
        $nextIndex = ($currentIndex + 1) % count($themes);
        $this->currentTheme = $themes[$nextIndex];
        
        Session::put('theme', $this->currentTheme);
        
        // Dispatch event to update the HTML data attribute
        $this->dispatch('theme-changed', theme: $this->currentTheme);
    }

    public function render()
    {
        return view('livewire.theme-toggle');
    }
}
