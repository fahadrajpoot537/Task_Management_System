<?php

namespace App\Livewire;

use App\Models\TaskCategory;
use Livewire\Component;
use Livewire\WithPagination;

class TaskCategoryManager extends Component
{
    use WithPagination;

    public $name = '';
    public $icon = 'bi-list-task';
    public $color = 'secondary';
    public $is_default = false;
    public $editingId = null;
    public $showForm = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'icon' => 'required|string|max:50',
        'color' => 'required|string|max:50',
        'is_default' => 'boolean'
    ];

    public function render()
    {
        $categories = TaskCategory::orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.task-category-manager', compact('categories'));
    }

    public function create()
    {
        $this->validate();
        
        TaskCategory::create([
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_default' => $this->is_default
        ]);

        $this->resetForm();
        session()->flash('message', 'Category created successfully.');
    }

    public function edit($id)
    {
        $category = TaskCategory::findOrFail($id);
        $this->editingId = $id;
        $this->name = $category->name;
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->is_default = $category->is_default;
        $this->showForm = true;
    }

    public function update()
    {
        $this->validate();
        
        $category = TaskCategory::findOrFail($this->editingId);
        $category->update([
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_default' => $this->is_default
        ]);

        $this->resetForm();
        session()->flash('message', 'Category updated successfully.');
    }

    public function delete($id)
    {
        $category = TaskCategory::findOrFail($id);
        
        // Check if it's a default category
        if ($category->is_default) {
            session()->flash('error', 'Cannot delete default category.');
            return;
        }

        // Check if category is being used by tasks
        if ($category->tasks()->count() > 0) {
            session()->flash('error', 'Cannot delete category that is being used by tasks.');
            return;
        }

        $category->delete();
        session()->flash('message', 'Category deleted successfully.');
    }

    public function resetForm()
    {
        $this->name = '';
        $this->icon = 'bi-list-task';
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
