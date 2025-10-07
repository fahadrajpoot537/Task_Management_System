<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ProfileEdit extends Component
{
    use WithFileUploads;

    public $user;
    public $name = '';
    public $email = '';
    public $phone = '';
    public $bio = '';
    public $avatar;
    public $currentAvatar;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:20',
        'bio' => 'nullable|string|max:500',
        'avatar' => 'nullable|image|max:2048', // 2MB max
    ];

    public function mount()
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
        $this->bio = $this->user->bio ?? '';
        $this->currentAvatar = $this->user->avatar;
    }

    public function updateProfile()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
        ];

        // Handle avatar upload
        if ($this->avatar) {
            // Delete old avatar if exists
            if ($this->user->avatar && Storage::disk('public')->exists($this->user->avatar)) {
                Storage::disk('public')->delete($this->user->avatar);
            }

            // Store new avatar
            $avatarPath = $this->avatar->store('avatars', 'public');
            $data['avatar'] = $avatarPath;
        }

        $this->user->update($data);

        session()->flash('success', 'Profile updated successfully!');
        
        // Refresh the user data
        $this->user->refresh();
        $this->currentAvatar = $this->user->avatar;
    }

    public function removeAvatar()
    {
        if ($this->user->avatar && Storage::disk('public')->exists($this->user->avatar)) {
            Storage::disk('public')->delete($this->user->avatar);
        }

        $this->user->update(['avatar' => null]);
        $this->currentAvatar = null;
        $this->avatar = null;

        session()->flash('success', 'Avatar removed successfully!');
    }

    public function render()
    {
        return view('livewire.user.profile-edit');
    }
}
