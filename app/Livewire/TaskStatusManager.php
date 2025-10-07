<?php

namespace App\Livewire;

use App\Models\TaskStatus;
use Livewire\Component;
use Livewire\WithPagination;

class TaskStatusManager extends Component
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
        $statuses = TaskStatus::orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.task-status-manager', compact('statuses'));
    }

    public function create()
    {
        $this->validate();
        
        TaskStatus::create([
            'name' => $this->name,
            'color' => $this->color,
            'is_default' => $this->is_default
        ]);

        $this->resetForm();
        session()->flash('message', 'Status created successfully.');
    }

    public function edit($id)
    {
        $status = TaskStatus::findOrFail($id);
        $this->editingId = $id;
        $this->name = $status->name;
        $this->color = $status->color;
        $this->is_default = $status->is_default;
        $this->showForm = true;
    }

    public function update()
    {
        $this->validate();
        
        $status = TaskStatus::findOrFail($this->editingId);
        $status->update([
            'name' => $this->name,
            'color' => $this->color,
            'is_default' => $this->is_default
        ]);

        $this->resetForm();
        session()->flash('message', 'Status updated successfully.');
    }

    public function delete($id)
    {
        $status = TaskStatus::findOrFail($id);
        
        // Check if it's a default status
        if ($status->is_default) {
            session()->flash('error', 'Cannot delete default status.');
            return;
        }

        // Check if status is being used by tasks
        if ($status->tasks()->count() > 0) {
            session()->flash('error', 'Cannot delete status that is being used by tasks.');
            return;
        }

        $status->delete();
        session()->flash('message', 'Status deleted successfully.');
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
