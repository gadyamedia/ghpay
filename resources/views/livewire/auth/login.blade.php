<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;

new
#[Layout('components.layouts.auth')]
#[Title('Login')]
class extends Component {


    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('required|boolean')]
    public bool $remember = false;

    public function mount()
    {
        if (auth()->user()) {
            return redirect('/');
        }
    }

    public function login()
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // check if intended url
        if (Session::has('url.intended')) {
            return redirect()->intended();
        } else {
            if (auth()->user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } else {
                return redirect()->route('public.form', ['slug' => 'default']);
            }

        }
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <x-form wire:submit='login'>
        <x-errors title="Oops!" description="Please, fix them." icon="o-face-frown" />
        <x-input label="Email" wire:model="email" id="email" name="email" placeholder="Email" icon="o-user" />
        <x-password label="Password" id="password" name="password" wire:model="password" placeholder="Password" clearable />
        <x-checkbox label="Remember Me" wire:model="remember" />
        <div class="flex justify-end">
            <x-button label="Login" type="submit" class="btn-primary" />
        </div>
    </x-form>
</div>

