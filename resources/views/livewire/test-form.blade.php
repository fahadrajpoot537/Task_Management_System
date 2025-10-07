<div>
    <h2>Livewire Test</h2>
    
    <div class="alert alert-info">
        <strong>Test Status:</strong> {{ $testStatus }}
    </div>
    
    <button class="btn btn-primary" wire:click="toggleTest">
        Toggle Test ({{ $testStatus ? 'ON' : 'OFF' }})
    </button>
    
    @if($testStatus)
        <div class="card mt-3">
            <div class="card-body">
                <h5>Form is working!</h5>
                <p>Livewire is functioning correctly.</p>
            </div>
        </div>
    @endif
</div>
