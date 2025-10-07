<div>
    <form wire:submit="login">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   id="email" wire:model="email" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                   id="password" wire:model="password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" wire:model="remember">
            <label class="form-check-label" for="remember">
                Remember Me
            </label>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Sign In
            </button>
        </div>
    </form>

    <div class="text-center mt-3">
        <p class="mb-0">Don't have an account? 
            <a href="{{ route('register') }}" class="text-decoration-none">Sign up here</a>
        </p>
    </div>
</div>
