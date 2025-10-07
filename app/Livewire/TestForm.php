<?php

namespace App\Livewire;

use Livewire\Component;

class TestForm extends Component
{
    public $testStatus = false;

    public function toggleTest()
    {
        $this->testStatus = !$this->testStatus;
    }

    public function render()
    {
        return view('livewire.test-form')
            ->layout('layouts.app');
    }
}
