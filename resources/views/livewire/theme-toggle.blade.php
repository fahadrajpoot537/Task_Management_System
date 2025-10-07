<div>
    <button class="{{ $buttonClass }}" wire:click="toggleTheme" type="button" title="Switch Theme ({{ ucfirst($currentTheme) }})">
        <i class="bi {{ $currentTheme === 'dark' ? 'bi-moon' : ($currentTheme === 'light' ? 'bi-sun' : 'bi-palette') }}" id="theme-icon"></i>
    </button>
</div>
