<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;

class Settings extends Component
{
    public $currentTheme = 'light';
    public $currentFontFamily = 'Inter';
    public $currentFontSize = 'medium';
    
    public $themes = [
        'light' => [
            'name' => 'Light Theme',
            'description' => 'Clean and bright interface',
            'colors' => ['#f8fafc', '#ffffff', '#e2e8f0', '#64748b', '#1e293b']
        ],
        'dark' => [
            'name' => 'Dark Theme',
            'description' => 'Easy on the eyes',
            'colors' => ['#1e293b', '#334155', '#475569', '#94a3b8', '#f1f5f9']
        ],
        'blue' => [
            'name' => 'Ocean Blue',
            'description' => 'Calming light blue tones',
            'colors' => ['#f0f9ff', '#e0f2fe', '#bae6fd', '#7dd3fc', '#0c4a6e']
        ],
        'green' => [
            'name' => 'Forest Green',
            'description' => 'Natural light green palette',
            'colors' => ['#f0fdf4', '#dcfce7', '#bbf7d0', '#86efac', '#14532d']
        ],
        'purple' => [
            'name' => 'Royal Purple',
            'description' => 'Elegant light purple shades',
            'colors' => ['#faf5ff', '#f3e8ff', '#e9d5ff', '#c084fc', '#581c87']
        ],
        'orange' => [
            'name' => 'Sunset Orange',
            'description' => 'Warm light orange vibes',
            'colors' => ['#fff7ed', '#fed7aa', '#fdba74', '#fb923c', '#9a3412']
        ]
    ];

    public $fontFamilies = [
        'Inter' => [
            'name' => 'Inter',
            'description' => 'Modern and clean',
            'font' => 'Inter, system-ui, sans-serif'
        ],
        'Roboto' => [
            'name' => 'Roboto',
            'description' => 'Google\'s signature font',
            'font' => 'Roboto, system-ui, sans-serif'
        ],
        'Open Sans' => [
            'name' => 'Open Sans',
            'description' => 'Friendly and readable',
            'font' => 'Open Sans, system-ui, sans-serif'
        ],
        'Lato' => [
            'name' => 'Lato',
            'description' => 'Elegant and professional',
            'font' => 'Lato, system-ui, sans-serif'
        ],
        'Poppins' => [
            'name' => 'Poppins',
            'description' => 'Geometric and modern',
            'font' => 'Poppins, system-ui, sans-serif'
        ],
        'Montserrat' => [
            'name' => 'Montserrat',
            'description' => 'Urban and sophisticated',
            'font' => 'Montserrat, system-ui, sans-serif'
        ],
        'Source Sans Pro' => [
            'name' => 'Source Sans Pro',
            'description' => 'Adobe\'s clean typeface',
            'font' => 'Source Sans Pro, system-ui, sans-serif'
        ],
        'Nunito' => [
            'name' => 'Nunito',
            'description' => 'Rounded and friendly',
            'font' => 'Nunito, system-ui, sans-serif'
        ],
        'Raleway' => [
            'name' => 'Raleway',
            'description' => 'Elegant and thin',
            'font' => 'Raleway, system-ui, sans-serif'
        ],
        'Ubuntu' => [
            'name' => 'Ubuntu',
            'description' => 'Humanist and warm',
            'font' => 'Ubuntu, system-ui, sans-serif'
        ],
        'Playfair Display' => [
            'name' => 'Playfair Display',
            'description' => 'Elegant serif font',
            'font' => 'Playfair Display, serif'
        ],
        'Merriweather' => [
            'name' => 'Merriweather',
            'description' => 'Readable serif font',
            'font' => 'Merriweather, serif'
        ]
    ];

    public $fontSizes = [
        'small' => [
            'name' => 'Small',
            'description' => 'Compact text',
            'size' => '14px'
        ],
        'medium' => [
            'name' => 'Medium',
            'description' => 'Standard size',
            'size' => '16px'
        ],
        'large' => [
            'name' => 'Large',
            'description' => 'Easy to read',
            'size' => '18px'
        ],
        'xlarge' => [
            'name' => 'Extra Large',
            'description' => 'Very readable',
            'size' => '20px'
        ]
    ];

    public function mount()
    {
        $this->currentTheme = Session::get('theme', 'light');
        $this->currentFontFamily = Session::get('font_family', 'Inter');
        $this->currentFontSize = Session::get('font_size', 'medium');
    }

    public function changeTheme($theme)
    {
        $this->currentTheme = $theme;
        Session::put('theme', $theme);
        
        // Dispatch event to update the HTML data attribute
        $this->dispatch('theme-changed', theme: $theme);
        
        // Show success message
        session()->flash('success', 'Theme changed to ' . $this->themes[$theme]['name']);
    }

    public function changeFontFamily($fontFamily)
    {
        $this->currentFontFamily = $fontFamily;
        Session::put('font_family', $fontFamily);
        
        // Dispatch event to update the font family
        $this->dispatch('font-family-changed', fontFamily: $fontFamily);
        
        // Show success message
        session()->flash('success', 'Font changed to ' . $this->fontFamilies[$fontFamily]['name']);
    }

    public function changeFontSize($fontSize)
    {
        $this->currentFontSize = $fontSize;
        Session::put('font_size', $fontSize);
        
        // Dispatch event to update the font size
        $this->dispatch('font-size-changed', fontSize: $fontSize);
        
        // Show success message
        session()->flash('success', 'Font size changed to ' . $this->fontSizes[$fontSize]['name']);
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
