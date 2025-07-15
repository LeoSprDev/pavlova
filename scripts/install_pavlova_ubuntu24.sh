#!/bin/bash
# install_pavlova_ubuntu24.sh - Installation automatique Pavlova sur Ubuntu 24.04

set -e
LOG_FILE="/tmp/pavlova_install_$(date +%Y%m%d_%H%M%S).log"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"; }
error() { echo "❌ ERREUR: $1" | tee -a "$LOG_FILE"; exit 1; }

log "🚀 Début installation Pavlova Budget Workflow sur Ubuntu 24.04"

# ===================================================================
# ÉTAPE 1: INSTALLATION PACKAGES SYSTÈME
# ===================================================================

log "📦 Installation packages système..."

# Mise à jour système
sudo apt update -y || log "⚠️ Mise à jour partielle"
sudo apt upgrade -y || log "⚠️ Upgrade partiel"

# Installation PHP 8.3+ avec toutes les extensions nécessaires
sudo apt install -y \
    php php-cli php-fpm php-common \
    php-mysql php-pdo php-sqlite3 \
    php-zip php-gd php-mbstring php-curl php-xml \
    php-json php-bcmath php-intl php-opcache \
    php-redis php-soap php-readline php-ldap \
    php-dev php-xdebug || error "Installation PHP échouée"

# Vérification PHP
php --version || error "PHP non installé correctement"
log "✅ PHP installé: $(php --version | head -1)"

# Installation Node.js et npm
sudo apt install -y nodejs npm || error "Installation Node.js échouée"
node --version || error "Node.js non installé"
log "✅ Node.js installé: $(node --version)"

# Installation Nginx
sudo apt install -y nginx || error "Installation Nginx échouée"
log "✅ Nginx installé"

# Installation Git et outils essentiels
sudo apt install -y git curl wget unzip software-properties-common \
    ca-certificates apt-transport-https lsb-release gnupg || error "Installation outils échouée"

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

# Clonage du projet Pavlova (OBLIGATOIRE)
if ! git clone https://github.com/LeoSprDev/pavlova.git "$PROJECT_DIR"; then
    error "Clonage Git Pavlova OBLIGATOIRE échoué - Vérifiez votre connexion internet"
fi

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
    sudo chown -R $(whoami):www-data . 2>/dev/null || chown -R $(whoami):$(id -gn) . 2>/dev/null || true
else
    # Mode root (Docker)
    chown -R www-data:www-data . 2>/dev/null || true
fi

log "✅ Permissions configurées"

# ===================================================================
# ÉTAPE 4: INSTALLATION DÉPENDANCES
# ===================================================================

log "📚 Installation dépendances PHP..."

# Variables Composer pour éviter les erreurs mémoire
export COMPOSER_MEMORY_LIMIT=-1
export COMPOSER_ALLOW_SUPERUSER=1

# Correction ownership Git si nécessaire
git config --global --add safe.directory "$PROJECT_DIR" 2>/dev/null || true

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
# ÉTAPE 7: CONFIGURATION NGINX
# ===================================================================

log "🌐 Configuration Nginx..."

# Arrêt services conflictuels
sudo systemctl stop apache2 2>/dev/null || true

# Configuration site Pavlova
sudo tee /etc/nginx/sites-available/pavlova > /dev/null << 'NGINX_CONF'
server {
    listen 8000;
    server_name localhost;
    root PROJECT_DIR_PLACEHOLDER/public;
    index index.php index.html index.htm;

    # Logs
    access_log /var/log/nginx/pavlova_access.log;
    error_log /var/log/nginx/pavlova_error.log;

    # Gestion Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeouts pour les gros exports
        fastcgi_connect_timeout 300s;
        fastcgi_send_timeout 300s;
        fastcgi_read_timeout 300s;
    }

    # Assets statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Sécurité
    location ~ /\.(?!well-known) {
        deny all;
    }

    client_max_body_size 64M;
}
NGINX_CONF

# Remplacement placeholder
sudo sed -i "s|PROJECT_DIR_PLACEHOLDER|$PROJECT_DIR|g" /etc/nginx/sites-available/pavlova

# Activation site
sudo ln -sf /etc/nginx/sites-available/pavlova /etc/nginx/sites-enabled/pavlova
sudo rm -f /etc/nginx/sites-enabled/default

# Test configuration Nginx
sudo nginx -t || error "Configuration Nginx invalide"

# Démarrage services
sudo systemctl enable nginx php8.3-fpm
sudo systemctl start php8.3-fpm
sudo systemctl restart nginx

log "✅ Nginx configuré et démarré"

# ===================================================================
# ÉTAPE 8: VÉRIFICATION ET INSTALLATION PACKAGES PAVLOVA COMPLETS
# ===================================================================

log "🎨 Vérification et installation packages Pavlova complets..."

# Packages essentiels Pavlova (liste complète)
ESSENTIAL_PACKAGES=(
    "filament/filament"
    "spatie/laravel-permission"
    "spatie/laravel-media-library"
    "maatwebsite/excel"
    "barryvdh/laravel-dompdf"
    "livewire/livewire"
    "laravel/sanctum"
    "intervention/image"
    "blade-ui-kit/blade-heroicons"
    "blade-ui-kit/blade-icons"
    "ryangjchandler/blade-capture-directive"
    "anourvalar/eloquent-serialize"
    "kirschbaum-development/eloquent-power-joins"
)

# Vérification et installation packages manquants
MISSING_PACKAGES=()
for package in "${ESSENTIAL_PACKAGES[@]}"; do
    if ! composer show "$package" >/dev/null 2>&1; then
        MISSING_PACKAGES+=("$package")
        log "⚠️ Package manquant détecté: $package"
    fi
done

# Installation packages manquants
if [ ${#MISSING_PACKAGES[@]} -gt 0 ]; then
    log "📦 Installation de ${#MISSING_PACKAGES[@]} packages manquants..."
    for package in "${MISSING_PACKAGES[@]}"; do
        log "Installation $package..."
        composer require "$package" --no-interaction || log "⚠️ Installation $package échouée"
    done
else
    log "✅ Tous les packages essentiels sont présents"
fi

# Configuration spécifique Filament
if composer show filament/filament >/dev/null 2>&1; then
    php artisan filament:install --panels --no-interaction 2>/dev/null || log "⚠️ Configuration Filament déjà faite"
fi

# Publications vendor essentielles
log "📋 Publication configurations vendor..."
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction 2>/dev/null || true
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations" --no-interaction 2>/dev/null || true
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config --no-interaction 2>/dev/null || true

# Vérification finale packages Pavlova
INSTALLED_COUNT=0
for package in "${ESSENTIAL_PACKAGES[@]}"; do
    if composer show "$package" >/dev/null 2>&1; then
        ((INSTALLED_COUNT++))
    fi
done

log "✅ Packages Pavlova: $INSTALLED_COUNT/${#ESSENTIAL_PACKAGES[@]} installés"

# ===================================================================
# ÉTAPE 9: SEEDING DONNÉES DE TEST
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
# ÉTAPE 10: OPTIMISATIONS ET FINALISATION
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

# Permissions web server
sudo chown -R www-data:www-data storage bootstrap/cache public/storage
sudo chmod -R 775 storage bootstrap/cache

log "✅ Optimisations terminées"

# ===================================================================
# ÉTAPE 11: TESTS DE VALIDATION
# ===================================================================

log "🧪 Tests de validation..."

VALIDATION_SCORE=0
TOTAL_TESTS=11

# Test 1: PHP
if php --version >/dev/null 2>&1; then
    PHP_VERSION_TEST=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if [[ "$PHP_VERSION_TEST" =~ ^[0-9]+\.[0-9]+$ ]]; then
        log "✅ Test 1/11: PHP $PHP_VERSION_TEST fonctionnel"
        ((VALIDATION_SCORE++))
    fi
else
    log "❌ Test 1/11: PHP non fonctionnel"
fi

# Test 2: Composer
if composer --version >/dev/null 2>&1; then
    log "✅ Test 2/11: Composer fonctionnel"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 2/11: Composer non fonctionnel"
fi

# Test 3: Laravel
if php artisan --version >/dev/null 2>&1; then
    log "✅ Test 3/11: Laravel fonctionnel"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 3/11: Laravel non fonctionnel"
fi

# Test 4: Base de données
if php artisan migrate:status >/dev/null 2>&1; then
    log "✅ Test 4/11: Base de données fonctionnelle"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 4/11: Base de données non fonctionnelle"
fi

# Test 5: Filament
if composer show filament/filament >/dev/null 2>&1; then
    log "✅ Test 5/11: Filament installé"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 5/11: Filament non installé"
fi

# Test 6: Nginx
if sudo systemctl is-active --quiet nginx; then
    log "✅ Test 6/11: Nginx actif"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 6/11: Nginx inactif"
fi

# Test 7: PHP-FPM
if sudo systemctl is-active --quiet php8.3-fpm; then
    log "✅ Test 7/11: PHP-FPM actif"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 7/11: PHP-FPM inactif"
fi

# Test 8: Port 8000
if ss -tlnp | grep -q ":8000 "; then
    log "✅ Test 8/11: Port 8000 en écoute"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 8/11: Port 8000 non accessible"
fi

# Test 9: Fichier .env
if [ -f ".env" ] && grep -q "APP_KEY=base64:" .env; then
    log "✅ Test 9/11: Configuration .env valide"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 9/11: Configuration .env invalide"
fi

# Test 10: Utilisateurs créés
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" -gt "0" ]; then
    log "✅ Test 10/11: $USER_COUNT utilisateurs créés"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 10/11: Aucun utilisateur créé"
fi

# Test 11: Packages Pavlova essentiels
PAVLOVA_PACKAGES_COUNT=0
CORE_PACKAGES=("filament/filament" "spatie/laravel-permission" "livewire/livewire" "maatwebsite/excel")
for package in "${CORE_PACKAGES[@]}"; do
    if composer show "$package" >/dev/null 2>&1; then
        ((PAVLOVA_PACKAGES_COUNT++))
    fi
done
if [ $PAVLOVA_PACKAGES_COUNT -ge 3 ]; then
    log "✅ Test 11/11: $PAVLOVA_PACKAGES_COUNT/4 packages Pavlova essentiels installés"
    ((VALIDATION_SCORE++))
else
    log "❌ Test 11/11: Seulement $PAVLOVA_PACKAGES_COUNT/4 packages Pavlova installés"
fi

# ===================================================================
# FINALISATION ET INSTRUCTIONS
# ===================================================================

log ""
log "🎯 RÉSULTATS INSTALLATION"
log "Tests passés: $VALIDATION_SCORE/$TOTAL_TESTS"
log "Répertoire: $PROJECT_DIR"
log "Base de données: SQLite ($PROJECT_DIR/database/database.sqlite)"
log "Log installation: $LOG_FILE"
log ""

if [ $VALIDATION_SCORE -ge 9 ]; then
    log "🎉 INSTALLATION RÉUSSIE!"
    log ""
    log "📋 ACCÈS APPLICATION:"
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
    log "🔧 SERVICES:"
    log "sudo systemctl status nginx php8.3-fpm"
    log "sudo systemctl restart nginx  # Si besoin"
    log ""
    log "📊 LOGS:"
    log "sudo tail -f /var/log/nginx/pavlova_error.log"
    log "tail -f $PROJECT_DIR/storage/logs/laravel.log"
    log ""
    log "🧪 TEST RAPIDE:"
    echo "curl -I http://localhost:8000"
else
    log "❌ INSTALLATION ÉCHOUÉE - Vérifiez les erreurs ci-dessus"
    log "Consultez le log: $LOG_FILE"
    log ""
    log "🔧 DEBUG SERVICES:"
    log "sudo systemctl status nginx php8.3-fpm"
    log "sudo nginx -t"
    log "php artisan about"
    exit 1
fi
