<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms;

class ChangePasswordPage extends Page implements HasForms
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $slug = 'change-password';

    protected static string $view = 'filament.pages.change-password-page';

    use InteractsWithForms;

    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('current_password')
                ->password()
                ->required()
                ->label('Mot de passe actuel'),
            Forms\Components\TextInput::make('new_password')
                ->password()
                ->required()
                ->confirmed()
                ->minLength(8)
                ->label('Nouveau mot de passe'),
            Forms\Components\TextInput::make('new_password_confirmation')
                ->password()
                ->required()
                ->label('Confirmer le nouveau mot de passe'),
        ]);
    }

    public function changePassword(): void
    {
        $data = $this->form->getState();

        if (!Hash::check($data['current_password'], auth()->user()->password)) {
            $this->addError('current_password', 'Mot de passe actuel incorrect.');
            return;
        }

        auth()->user()->update([
            'password' => Hash::make($data['new_password']),
            'force_password_change' => false,
            'last_password_change' => now(),
        ]);

        $this->notify('success', 'Mot de passe changé avec succès.');
        redirect()->route('filament.admin.pages.dashboard');
    }
}
