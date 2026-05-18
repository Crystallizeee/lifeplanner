<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

// @see LP-US-AC-2026-001 | Auth — Register (single-user system)
#[Layout('components.layouts.guest')]
#[Title('Register — LifePlanner SIM')]
class Register extends Component
{
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password, // Auto-hashed via cast
            'currency' => 'IDR',
        ]);

        // Seed default premium categories for the new user automatically
        $user->seedDefaultCategories();

        Auth::login($user);

        session()->regenerate();

        $this->dispatch('toast', message: 'Registrasi Berhasil! Selamat Datang 🌟', type: 'success');

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
