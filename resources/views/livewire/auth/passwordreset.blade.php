<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Password;

new
#[Layout('components.layouts.auth')]
#[Title('Password Reset')]
class extends Component {


    #[Validate('required|email')]
    public $email;

    public function submit()
    {
        $this->validate();

        $status = Password::sendResetLink(
            ['email' => $this->email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('status', $status);
            $this->success('Password reset link sent to your email.');
            $this->reset('email');

            return;
        }

        $this->addError('email', __($status));
        $this->error('Failed to send password reset link.');
    }
}; ?>

<div>
    <x-form wire:submit='submit'>
        <x-errors title="Oops!" description="Please, fix them." icon="o-face-frown" />

        @if (session('status'))
            <x-alert title="{{ __(session('status')) }}" icon="o-information-circle" />
        @endif

        <x-input label="Email" wire:model="email" id="email" name="email" placeholder="Email" icon="o-user" />

        <div class="flex justify-end">
            <x-button label="Send reset link" type="submit" class="btn-primary" />
        </div>
    </x-form>
</div>
