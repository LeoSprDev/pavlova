<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'Laravel'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],
    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Laravel Framework Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\Filament\AdminPanelProvider::class,
        App\Providers\RouteServiceProvider::class,
        /*
         * Package Service Providers...
         */
        Spatie\Permission\PermissionServiceProvider::class,
        Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,
        Livewire\LivewireServiceProvider::class,
        App\Providers\FilamentServiceProvider::class,
        Filament\FilamentServiceProvider::class,
        Filament\Forms\FormsServiceProvider::class,
        Filament\Tables\TablesServiceProvider::class,
        Filament\Notifications\NotificationsServiceProvider::class,
        Filament\Support\SupportServiceProvider::class,
        Filament\Infolists\InfolistsServiceProvider::class, // Added for Filament v3
        Filament\Actions\ActionsServiceProvider::class, // Added for Filament v3
        Filament\Widgets\WidgetsServiceProvider::class, // Added for Filament v3


    ])->toArray(),
    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
    ])->toArray(),
];
