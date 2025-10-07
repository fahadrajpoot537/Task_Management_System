<?php

namespace App\Livewire;

use App\Models\TaskPriority;
use Livewire\Component;
use Livewire\WithPagination;

class TaskPriorityManager extends Component
{
    use WithPagination;

    public $name = '';
    public $color = 'secondary';
    public $is_default = false;
    public $editingId = null;
    public $showForm = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'color' => 'required|string|max:50',
        'is_default' => 'boolean'
    ];

    public function render()
    {
        $priorities = TaskPriority::orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.task-priority-manager', compact('priorities'));
    }

    public function create()
    {
        $this->validate();
        
        TaskPriority::create([
            'name' => $this->name,
            'color' => $this->color,
            'is_default' => $this->is_default
        ]);

        $this->resetForm();
        session()->flash('message', 'Priority created successfully.');
    }

    public function edit($id)
    {
        $priority = TaskPriority::findOrFail($id);
        $this->editingId = $id;
        $this->name = $priority->name;
        $this->color = $priority->color;
        $this->is_default = $priority->is_default;
        $this->showForm = true;
    }

    public function update()
    {
        $this->validate();
        
        $priority = TaskPriority::findOrFail($this->editingId);
        $priority->update([
            'name' => $this->name,
            'color' => $this->color,
            'is_default' => $this->is_default
        ]);

        $this->resetForm();
        session()->flash('message', 'Priority updated successfully.');
    }

    public function delete($id)
    {
        $priority = TaskPriority::findOrFail($id);
        
        // Check if it's a default priority
        if ($priority->is_default) {
            session()->flash('error', 'Cannot delete default priority.');
            return;
        }

        // Check if priority is being used by tasks
        if ($priority->tasks()->count() > 0) {
            session()->flash('error', 'Cannot delete priority that is being used by tasks.');
            return;
        }

        $priority->delete();
        session()->flash('message', 'Priority deleted successfully.');
    }

    public function resetForm()
    {
        $this->name = '';
        $this->color = 'secondary';
        $this->is_default = false;
        $this->editingId = null;
        $this->showForm = false;
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->resetForm();
        }
    }
}
