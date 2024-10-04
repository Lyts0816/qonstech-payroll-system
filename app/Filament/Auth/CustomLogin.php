<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Login;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Filament\Actions\Action;
use Illuminate\Validation\ValidationException;

class CustomLogin extends Login
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getLoginFormComponent(),
                        $this->getPasswordComponent(),
                        $this->getRememberFormComponent(),
                    
                        
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label(__('Username'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('Password'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL ) ? 'email' : 'UserName';
        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
            'data.password' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label(__('Forgot Password?'))
                ->action(function () {
                    $this->resetPasswordModal(); // Trigger the modal for password reset
                })
                ->button()
                ->extraAttributes(['tabindex' => 3]),
        ];
    }


    protected function resetPasswordModal(): void
    {
        // Define the modal for resetting the password
        $this->modal()
            ->title(__('Reset Password'))
            ->schema([
                TextInput::make('email')
                    ->label(__('Email'))
                    ->required()
                    ->email()
                    ->placeholder(__('Enter your email address')),
            ])
            ->actions([
                Action::make('sendResetLink')
                    ->label(__('Send Password Reset Link'))
                    ->action(function ($data) {
                        $response = Password::sendResetLink(['email' => $data['email']]);

                        if ($response === Password::RESET_LINK_SENT) {
                            Notification::make()
                                ->title(__('Password reset link sent!'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('Error sending reset link.'))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('close')
                    ->label(__('Close'))
                    ->color('secondary')
                    ->modalClose(),
            ])
            ->open(); // Ensure the modal opens when triggered
    }

}

