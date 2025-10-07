<?php

namespace App\Livewire\Auth;

use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ];

    public function register()
    {
        $this->validate();

        // Get the employee role (default role for new registrations)
        $employeeRole = Role::where('name', 'employee')->first();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'role_id' => $employeeRole->id,
        ]);

        Auth::login($user);

        // Log the registration action
        Log::createLog($user->id, 'register', 'User registered');

        session()->regenerate();

        return redirect('/dashboard');
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.auth');
    }
}
