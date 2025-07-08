#!/bin/bash

# Script de démarrage de l'application Budget & Workflow
# Usage: ./start_app.sh

echo "🚀 Démarrage de l'application Budget & Workflow..."

# Se placer dans le bon répertoire
cd /var/www/pavlova

# Vérifier si une instance de l'application tourne déjà
echo "🔍 Vérification des instances en cours..."
EXISTING_PID=$(pgrep -f "php artisan serve.*8000")

if [ ! -z "$EXISTING_PID" ]; then
    echo "⚠️  Instance détectée (PID: $EXISTING_PID). Arrêt en cours..."
    kill $EXISTING_PID
    
    # Attendre que le processus se termine
    sleep 2
    
    # Vérifier si le processus est toujours actif
    if kill -0 $EXISTING_PID 2>/dev/null; then
        echo "🔥 Arrêt forcé du processus..."
        kill -9 $EXISTING_PID
        sleep 1
    fi
    
    echo "✅ Instance précédente arrêtée"
else
    echo "✅ Aucune instance en cours détectée"
fi

# Désactiver les variables d'environnement PostgreSQL qui interfèrent
unset DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD

# Vérifier que la base de données existe
if [ ! -f "database/database.sqlite" ]; then
    echo "❌ Base de données SQLite non trouvée. Création..."
    touch database/database.sqlite
    php artisan migrate:fresh --seed
fi

# Nettoyer le cache
echo "🧹 Nettoyage du cache..."
rm -rf bootstrap/cache/* storage/framework/cache/* storage/framework/sessions/* storage/framework/views/* 2>/dev/null

# Démarrer le serveur
echo "🌐 Démarrage du serveur sur http://localhost:8000/admin"
echo "👤 Connexion admin: admin@test.local / password"
echo "📖 Guide utilisateur: doc/GUIDE_UTILISATEUR.md"
echo ""
echo "Appuyez sur Ctrl+C pour arrêter le serveur"
echo ""

php artisan serve --host=0.0.0.0 --port=8000