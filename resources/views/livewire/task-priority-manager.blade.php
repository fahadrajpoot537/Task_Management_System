<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Task Priority Management</h5>
                    <button wire:click="toggleForm" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Priority
                    </button>
                </div>
                <div class="card-body">
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if ($showForm)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">{{ $editingId ? 'Edit Priority' : 'Add New Priority' }}</h6>
                            </div>
                            <div class="card-body">
                                <form wire:submit.prevent="{{ $editingId ? 'update' : 'create' }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Priority Name</label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                       id="name" wire:model="name" placeholder="Enter priority name">
                                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="color" class="form-label">Color</label>
                                                <select class="form-select @error('color') is-invalid @enderror" 
                                                        id="color" wire:model="color">
                                                    <option value="primary">Primary (Blue)</option>
                                                    <option value="secondary">Secondary (Gray)</option>
                                                    <option value="success">Success (Green)</option>
                                                    <option value="danger">Danger (Red)</option>
                                                    <option value="warning">Warning (Yellow)</option>
                                                    <option value="info">Info (Cyan)</option>
                                                    <option value="light">Light</option>
                                                    <option value="dark">Dark</option>
                                                </select>
                                                @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_default" wire:model="is_default">
                                            <label class="form-check-label" for="is_default">
                                                Mark as Default Priority
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-circle"></i> {{ $editingId ? 'Update' : 'Create' }}
                                        </button>
                                        <button type="button" wire:click="resetForm" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Color</th>
                                    <th>Type</th>
                                    <th>Tasks Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($priorities as $priority)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $priority->color }}">{{ $priority->name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $priority->color }}">{{ ucfirst($priority->color) }}</span>
                                        </td>
                                        <td>
                                            @if($priority->is_default)
                                                <span class="badge bg-success">Default</span>
                                            @else
                                                <span class="badge bg-secondary">Custom</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $priority->tasks_count ?? $priority->tasks()->count() }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button wire:click="edit({{ $priority->id }})" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                @if(!$priority->is_default)
                                                    <button wire:click="delete({{ $priority->id }})" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this priority?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No priorities found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $priorities->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>