<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TestPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Test Page';
    protected static ?string $title = 'Test Page';
    protected static string $view = 'filament.pages.test-page';
}