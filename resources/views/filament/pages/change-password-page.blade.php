<x-filament-panels::page>
    <div class="mx-auto max-w-2xl">
        
        <div class="bg-warning-50 dark:bg-warning-500/20 border border-warning-200 dark:border-warning-400/30 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-warning-600 dark:text-warning-400 mr-3" />
                <div>
                    <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                        🔒 Changement de mot de passe obligatoire
                    </h3>
                    <p class="text-sm text-warning-700 dark:text-warning-300 mt-1">
                        Pour des raisons de sécurité, vous devez modifier votre mot de passe lors de votre première connexion.
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="changePassword" class="space-y-6">
            {{ $this->form }}
            
            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                <x-filament::button
                    type="submit"
                    size="lg"
                    class="w-full sm:w-auto"
                >
                    <x-heroicon-m-check-circle class="h-5 w-5 mr-2" />
                    Mettre à jour mon mot de passe
                </x-filament::button>
            </div>
        </form>
        
        <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                📋 Critères de sécurité requis :
            </h3>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                <li>• Minimum 8 caractères</li>
                <li>• Au moins une lettre majuscule</li>
                <li>• Au moins une lettre minuscule</li>
                <li>• Au moins un chiffre</li>
                <li>• Au moins un caractère spécial</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
