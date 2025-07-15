#!/bin/bash
# install_pavlova_fedora.sh - Installation automatique Pavlova sur Fedora/RedHat

set -e
LOG_FILE="/tmp/pavlova_install_$(date +%Y%m%d_%H%M%S).log"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"; }
error() { echo "❌ ERREUR: $1" | tee -a "$LOG_FILE"; exit 1; }

log "🚀 Début installation Pavlova Budget Workflow sur $(hostnamectl | grep Operating)"

# ===================================================================
# ÉTAPE 1: INSTALLATION PACKAGES SYSTÈME
# ===================================================================

log "📦 Installation packages système..."

# Mise à jour système
sudo dnf update -y || log "⚠️ Mise à jour partielle"

# Installation PHP 8.1+ avec toutes les extensions nécessaires
sudo dnf install -y \
    php php-cli php-fpm php-common \
    php-mysqlnd php-pdo php-pdo_sqlite php-sqlite3 \
    php-zip php-gd php-mbstring php-curl php-xml \
    php-json php-bcmath php-intl php-opcache \
    php-redis php-process php-openssl php-fileinfo || error "Installation PHP échouée"

# Vérification PHP
php --version || error "PHP non installé correctement"
log "✅ PHP installé: $(php --version | head -1)"

# Installation Node.js et npm
sudo dnf install -y nodejs npm || error "Installation Node.js échouée"
node --version || error "Node.js non installé"
log "✅ Node.js installé: $(node --version)"

# Installation Git et outils essentiels
sudo dnf install -y git curl wget unzip which || error "Installation outils échouée"

# Installation Composer
log "📦 Installation Composer..."
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    rm composer-setup.php
    error "Checksum Composer invalide"
fi

php composer-setup.php --quiet
rm composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

composer --version || error "Composer non installé"
log "✅ Composer installé: $(composer --version | head -1)"

# ===================================================================
# ÉTAPE 2: CLONAGE ET PRÉPARATION PROJET
# ===================================================================

log "📂 Clonage et préparation projet..."

# Définition répertoire de travail
PROJECT_DIR="$HOME/pavlova"
[ -d "$PROJECT_DIR" ] && { log "⚠️ Répertoire $PROJECT_DIR existe, suppression..."; rm -rf "$PROJECT_DIR"; }

# Clonage du projet (adapter l'URL selon votre dépôt)
git clone https://github.com/LeoSprDev/pavlova.git "$PROJECT_DIR" || {
    log "⚠️ Clonage Git échoué, création structure manuelle..."
    mkdir -p "$PROJECT_DIR"
    cd "$PROJECT_DIR"
    
    # Si pas de Git, créer structure Laravel de base
    composer create-project laravel/laravel . --prefer-dist --no-dev || error "Création projet Laravel échouée"
}

cd "$PROJECT_DIR" || error "Impossible d'accéder au répertoire projet"
log "✅ Projet dans: $(pwd)"

# Vérification structure Laravel
[ ! -f "artisan" ] && error "Structure Laravel invalide - fichier artisan manquant"
[ ! -f "composer.json" ] && error "Structure Laravel invalide - composer.json manquant"
log "✅ Structure Laravel valide"

# ===================================================================
# ÉTAPE 3: CONFIGURATION PERMISSIONS ET RÉPERTOIRES
# ===================================================================

log "🔧 Configuration permissions et répertoires..."

# Création répertoires essentiels
mkdir -p storage/{framework/{cache,sessions,views},logs} bootstrap/cache database public/storage

# Permissions Laravel
chmod -R 755 storage bootstrap/cache
chmod +x artisan

# Ownership optimal
if [ "$(id -u)" != "0" ]; then
    # Mode utilisateur normal
    chown -R $(whoami):$(id -gn) . 2>/dev/null || true
else
    # Mode root (Docker)
    chown -R apache:apache . 2>/dev/null || chown -R nginx:nginx . 2>/dev/null || true
fi

log "✅ Permissions configurées"

# ===================================================================
# ÉTAPE 4: INSTALLATION DÉPENDANCES
# ===================================================================

log "📚 Installation dépendances PHP..."

# Variables Composer pour éviter les erreurs mémoire
export COMPOSER_MEMORY_LIMIT=-1
export COMPOSER_ALLOW_SUPERUSER=1

# Installation dépendances avec retry
for i in {1..3}; do
    if composer install --optimize-autoloader --no-interaction; then
        log "✅ Dépendances Composer installées"
        break
    else
        log "⚠️ Tentative $i/3 échouée, retry..."
        [ $i -eq 3 ] && error "Installation Composer échouée définitivement"
        sleep 5
    fi
done

# Installation dépendances Node.js si package.json existe
if [ -f "package.json" ]; then
    log "📚 Installation dépendances Node.js..."
    npm install --production || log "⚠️ Installation npm avec warnings"
    log "✅ Dépendances Node.js installées"
fi

# ===================================================================
# ÉTAPE 5: CONFIGURATION ENVIRONNEMENT
# ===================================================================

log "⚙️ Configuration environnement Laravel..."

# Copie et adaptation .env
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        log "⚠️ Création .env manuel..."
        cat > .env << 'EOF'
APP_NAME="Budget Workflow Pavlova"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Base SQLite pour tests rapides
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Cache fichier (pas de Redis requis)
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail de test
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@pavlova.test"
MAIL_FROM_NAME="Budget Workflow Pavlova"

# Configuration Filament
FILAMENT_FILESYSTEM_DISK=local
FILAMENT_DEFAULT_AVATAR_PROVIDER=ui-avatars

# Configuration Pavlova
PAVLOVA_WORKFLOW_LEVELS=5
PAVLOVA_BUDGET_CURRENCY=EUR
PAVLOVA_UPLOAD_MAX_SIZE=64M
EOF
    fi
fi

# Mise à jour chemin base SQLite pour chemin absolu
sed -i "s|DB_DATABASE=.*|DB_DATABASE=$PROJECT_DIR/database/database.sqlite|" .env

# Génération clé application
php artisan key:generate --force || error "Génération clé Laravel échouée"
log "✅ Clé application générée"

# ===================================================================
# ÉTAPE 6: CONFIGURATION BASE DE DONNÉES
# ===================================================================

log "🗄️ Configuration base de données SQLite..."

# Création fichier SQLite
touch database/database.sqlite
chmod 664 database/database.sqlite

# Test connexion base
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connexion DB OK';" || error "Connexion base échouée"

# Exécution migrations
if php artisan migrate:status | grep -q "No migrations found"; then
    log "⚠️ Aucune migration trouvée, création basique..."
    
    # Création migration utilisateurs basique si pas existante
    php artisan make:migration create_users_table --create=users 2>/dev/null || true
    php artisan make:migration create_services_table --create=services 2>/dev/null || true
fi

# Exécution migrations
php artisan migrate --force || log "⚠️ Migrations exécutées avec warnings"

log "✅ Base de données configurée"

# ===================================================================
# ÉTAPE 7: INSTALLATION PACKAGES PAVLOVA SPÉCIFIQUES
# ===================================================================

log "🎨 Installation packages Pavlova..."

# Installation Filament si pas présent
composer show filament/filament >/dev/null 2>&1 || {
    log "Installation Filament..."
    composer require filament/filament --no-interaction || log "⚠️ Installation Filament échouée"
    php artisan filament:install --panels --no-interaction || log "⚠️ Configuration Filament échouée"
}

# Installation Spatie Permission si pas présent
composer show spatie/laravel-permission >/dev/null 2>&1 || {
    log "Installation Spatie Permission..."
    composer require spatie/laravel-permission --no-interaction || log "⚠️ Installation Spatie Permission échouée"
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction || true
}

# Installation autres packages essentiels
PACKAGES=(
    "maatwebsite/excel"
    "barryvdh/laravel-dompdf"
    "spatie/laravel-media-library"
)

for package in "${PACKAGES[@]}"; do
    composer show "$package" >/dev/null 2>&1 || {
        log "Installation $package..."
        composer require "$package" --no-interaction || log "⚠️ Installation $package échouée"
    }
done

log "✅ Packages Pavlova installés"

# ===================================================================
# ÉTAPE 8: SEEDING DONNÉES DE TEST
# ===================================================================

log "👥 Création utilisateurs et données de test..."

# Création seeder utilisateurs test si pas existant
SEEDER_FILE="database/seeders/TestUsersSeeder.php"
if [ ! -f "$SEEDER_FILE" ]; then
    log "Création seeder utilisateurs test..."
    
    cat > "$SEEDER_FILE" << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateurs de test avec mots de passe identiques
        $users = [
            ['name' => 'Administrateur', 'email' => 'admin@test.local'],
            ['name' => 'Agent IT', 'email' => 'agent.IT@test.local'],
            ['name' => 'Responsable IT', 'email' => 'responsable.IT@test.local'],
            ['name' => 'Agent RH', 'email' => 'agent.RH@test.local'],
            ['name' => 'Responsable RH', 'email' => 'responsable.RH@test.local'],
            ['name' => 'Agent MKT', 'email' => 'agent.MKT@test.local'],
            ['name' => 'Responsable MKT', 'email' => 'responsable.MKT@test.local'],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
EOF

    # Enregistrement seeder dans DatabaseSeeder
    if ! grep -q "TestUsersSeeder" database/seeders/DatabaseSeeder.php; then
        sed -i '/public function run():/a\        $this->call(TestUsersSeeder::class);' database/seeders/DatabaseSeeder.php
    fi
fi

# Exécution seeders
php artisan db:seed --class=TestUsersSeeder --force || log "⚠️ Seeding utilisateurs avec warnings"

# Création utilisateur admin Filament
php artisan make:filament-user --name="Admin Test" --email="admin@test.local" --password="password" --no-interaction 2>/dev/null || log "⚠️ Utilisateur admin Filament déjà existant"

log "✅ Utilisateurs de test créés"

# ===================================================================
# ÉTAPE 9: OPTIMISATIONS ET FINALISATION
# ===================================================================

log "⚡ Optimisations finales..."

# Nettoyage cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Optimisations production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Création lien symbolique storage
php artisan storage:link --force || log "⚠️ Lien storage déjà existant"

# Compilation assets si disponible
if [ -f "package.json" ] && command -v npm >/dev/null; then
    npm run build || npm run dev || log "⚠️ Compilation assets échouée"
fi

# Optimisation Composer
composer dump-autoload --optimize

# Permissions finales
chmod -R 755 storage bootstrap/cache public/storage
find storage -type f -exec chmod 644 {} \;

log "✅ Optimisations terminées"

# ===================================================================
# ÉTAPE 10: TESTS DE VALIDATION
# ===================================================================

log "🧪 Tests de validation..."

TESTS_PASSED=0
TOTAL_TESTS=8

# Test 1: PHP fonctionnel
if php --version >/dev/null 2>&1; then
    log "✅ Test 1/8: PHP fonctionnel"
    ((TESTS_PASSED++))
else
    log "❌ Test 1/8: PHP non fonctionnel"
fi

# Test 2: Laravel accessible
if php artisan --version >/dev/null 2>&1; then
    log "✅ Test 2/8: Laravel accessible"
    ((TESTS_PASSED++))
else
    log "❌ Test 2/8: Laravel non accessible"
fi

# Test 3: Base de données
if php artisan migrate:status >/dev/null 2>&1; then
    log "✅ Test 3/8: Base de données fonctionnelle"
    ((TESTS_PASSED++))
else
    log "❌ Test 3/8: Base de données non fonctionnelle"
fi

# Test 4: Filament installé
if composer show filament/filament >/dev/null 2>&1; then
    log "✅ Test 4/8: Filament installé"
    ((TESTS_PASSED++))
else
    log "❌ Test 4/8: Filament non installé"
fi

# Test 5: Fichier .env présent
if [ -f ".env" ] && grep -q "APP_KEY=base64:" .env; then
    log "✅ Test 5/8: Configuration .env valide"
    ((TESTS_PASSED++))
else
    log "❌ Test 5/8: Configuration .env invalide"
fi

# Test 6: Base SQLite accessible
if [ -f "database/database.sqlite" ] && [ -r "database/database.sqlite" ]; then
    log "✅ Test 6/8: Base SQLite accessible"
    ((TESTS_PASSED++))
else
    log "❌ Test 6/8: Base SQLite non accessible"
fi

# Test 7: Utilisateurs créés
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" -gt "0" ]; then
    log "✅ Test 7/8: $USER_COUNT utilisateurs créés"
    ((TESTS_PASSED++))
else
    log "❌ Test 7/8: Aucun utilisateur créé"
fi

# Test 8: Port 8000 libre
if ! ss -tlnp | grep -q ":8000 "; then
    log "✅ Test 8/8: Port 8000 disponible"
    ((TESTS_PASSED++))
else
    log "❌ Test 8/8: Port 8000 occupé"
fi

# ===================================================================
# FINALISATION ET INSTRUCTIONS
# ===================================================================

log ""
log "🎯 RÉSULTATS INSTALLATION"
log "Tests passés: $TESTS_PASSED/$TOTAL_TESTS"
log "Répertoire: $PROJECT_DIR"
log "Base de données: SQLite ($PROJECT_DIR/database/database.sqlite)"
log "Log installation: $LOG_FILE"
log ""

if [ $TESTS_PASSED -ge 6 ]; then
    log "🎉 INSTALLATION RÉUSSIE!"
    log ""
    log "📋 COMMANDES DE DÉMARRAGE:"
    log "cd $PROJECT_DIR"
    log "php artisan serve --host=0.0.0.0 --port=8000"
    log ""
    log "🌐 ACCÈS APPLICATION:"
    log "URL: http://localhost:8000"
    log "Admin: http://localhost:8000/admin"
    log ""
    log "👥 COMPTES DE TEST (mot de passe: password):"
    log "- admin@test.local (Administrateur)"
    log "- agent.IT@test.local (Agent IT)"
    log "- responsable.IT@test.local (Responsable IT)"
    log "- agent.RH@test.local (Agent RH)"
    log "- responsable.RH@test.local (Responsable RH)"
    log "- agent.MKT@test.local (Agent MKT)"
    log "- responsable.MKT@test.local (Responsable MKT)"
    log ""
    log "🚀 DÉMARRAGE RAPIDE:"
    echo "cd $PROJECT_DIR && php artisan serve --host=0.0.0.0 --port=8000"
else
    log "❌ INSTALLATION ÉCHOUÉE - Vérifiez les erreurs ci-dessus"
    log "Consultez le log: $LOG_FILE"
    exit 1
fi

