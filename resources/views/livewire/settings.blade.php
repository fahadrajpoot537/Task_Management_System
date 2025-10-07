<div>
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">
                <i class="bi bi-gear me-2"></i>Settings
            </h3>
            <p class="text-muted mb-0">Customize your application experience</p>
        </div>
        <div class="card-body">
            <!-- Theme Selection -->
            <div class="mb-5">
                <h5 class="mb-3">
                    <i class="bi bi-palette me-2"></i>Choose Your Theme
                </h5>
                <p class="text-muted mb-4">Select a theme that matches your style and preferences</p>
                
                <div class="row g-3">
                    @foreach($themes as $themeKey => $theme)
                        <div class="col-md-6 col-lg-4">
                            <div class="theme-card {{ $currentTheme === $themeKey ? 'active' : '' }}" 
                                 wire:click="changeTheme('{{ $themeKey }}')"
                                 style="cursor: pointer;">
                                <div class="theme-preview">
                                    <div class="theme-colors">
                                        @foreach($theme['colors'] as $color)
                                            <div class="color-swatch" style="background-color: {{ $color }}"></div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="theme-info">
                                    <h6 class="theme-name">{{ $theme['name'] }}</h6>
                                    <p class="theme-description">{{ $theme['description'] }}</p>
                                    @if($currentTheme === $themeKey)
                                        <span class="badge bg-primary">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Font Family Selection -->
            <div class="mb-5">
                <h5 class="mb-3">
                    <i class="bi bi-type me-2"></i>Choose Your Font Style
                </h5>
                <p class="text-muted mb-4">Select a font that matches your reading preference</p>
                
                <div class="row g-3">
                    @foreach($fontFamilies as $fontKey => $font)
                        <div class="col-md-6 col-lg-4">
                            <div class="font-card {{ $currentFontFamily === $fontKey ? 'active' : '' }}" 
                                 wire:click="changeFontFamily('{{ $fontKey }}')"
                                 style="cursor: pointer;">
                                <div class="font-preview" style="font-family: {{ $font['font'] }}">
                                    <div class="font-sample">
                                        <h6 style="font-family: {{ $font['font'] }}">Aa Bb Cc</h6>
                                        <p style="font-family: {{ $font['font'] }}">Sample Text</p>
                                    </div>
                                </div>
                                <div class="font-info">
                                    <h6 class="font-name">{{ $font['name'] }}</h6>
                                    <p class="font-description">{{ $font['description'] }}</p>
                                    @if($currentFontFamily === $fontKey)
                                        <span class="badge bg-primary">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Font Size Selection -->
            <div class="mb-5">
                <h5 class="mb-3">
                    <i class="bi bi-arrows-expand me-2"></i>Choose Your Font Size
                </h5>
                <p class="text-muted mb-4">Select a font size that's comfortable for reading</p>
                
                <div class="row g-3">
                    @foreach($fontSizes as $sizeKey => $size)
                        <div class="col-md-6 col-lg-3">
                            <div class="font-size-card {{ $currentFontSize === $sizeKey ? 'active' : '' }}" 
                                 wire:click="changeFontSize('{{ $sizeKey }}')"
                                 style="cursor: pointer;">
                                <div class="font-size-preview" style="font-size: {{ $size['size'] }}">
                                    <div class="font-size-sample">
                                        <h6 style="font-size: {{ $size['size'] }}">Aa Bb Cc</h6>
                                        <p style="font-size: {{ $size['size'] }}">Sample Text</p>
                                    </div>
                                </div>
                                <div class="font-size-info">
                                    <h6 class="font-size-name">{{ $size['name'] }}</h6>
                                    <p class="font-size-description">{{ $size['description'] }}</p>
                                    @if($currentFontSize === $sizeKey)
                                        <span class="badge bg-primary">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Current Settings Info -->
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="bi bi-info-circle me-2"></i>Current Settings
                </h6>
                <p class="mb-1"><strong>Theme:</strong> {{ $themes[$currentTheme]['name'] }} - {{ $themes[$currentTheme]['description'] }}</p>
                <p class="mb-1"><strong>Font:</strong> {{ $fontFamilies[$currentFontFamily]['name'] }} - {{ $fontFamilies[$currentFontFamily]['description'] }}</p>
                <p class="mb-0"><strong>Size:</strong> {{ $fontSizes[$currentFontSize]['name'] }} - {{ $fontSizes[$currentFontSize]['description'] }}</p>
            </div>
        </div>
    </div>
</div>
