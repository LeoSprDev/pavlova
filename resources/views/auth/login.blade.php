<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Workflow Pavlova - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <div class="mx-auto h-20 w-20 bg-indigo-600 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-white text-2xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Budget Workflow Pavlova</h2>
            <p class="mt-2 text-sm text-gray-600">Connectez-vous à votre espace</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-2"></i>
                        <div>
                            <h3 class="text-sm font-medium text-red-800">Erreur de connexion</h3>
                            @foreach ($errors->all() as $error)
                                <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form class="space-y-6" method="POST" action="{{ route('auth.login') }}">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-envelope mr-1"></i> Adresse email
                    </label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           required 
                           value="{{ old('email') }}"
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="votre.email@company.local">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        <i class="fas fa-lock mr-1"></i> Mot de passe
                    </label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="Votre mot de passe">
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" 
                               name="remember" 
                               type="checkbox" 
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Se souvenir de moi
                        </label>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <div class="text-center">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Comptes de test disponibles :</h3>
                    <div class="grid grid-cols-1 gap-2 text-xs text-gray-600">
                        <div class="bg-gray-50 p-2 rounded">
                            <strong>Admin :</strong> admin@test.local / password
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                            <strong>Agent IT :</strong> agent.IT@test.local / password
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                            <strong>Responsable IT :</strong> responsable.IT@test.local / password
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                            <strong>Agent RH :</strong> agent.RH@test.local / password
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center text-sm text-gray-500">
            <p>&copy; 2025 Budget Workflow Pavlova - Système de gestion budgétaire</p>
        </div>
    </div>
</body>
</html>