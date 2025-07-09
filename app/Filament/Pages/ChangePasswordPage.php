<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class ChangePasswordPage extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static string $view = 'filament.pages.change-password-page';

    use InteractsWithForms;

    public ?string $new_password = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('new_password')
                ->label('Nouveau mot de passe')
                ->password()
                ->required(),
        ]);
    }

    public function changePassword(): void
    {
        $user = auth()->user();
        $user->update([
            'password' => Hash::make($this->new_password),
            'first_login' => false,
            'password_changed_at' => now(),
        ]);

        $this->notify('success', 'Mot de passe mis à jour.');
        redirect()->route('filament.admin.pages.dashboard');
    }
}
