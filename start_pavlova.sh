#!/bin/bash
cd ~/api/pavlova

# Création du fichier de log s'il n'existe pas
touch pavlova.log

# Nettoyage des anciens logs (optionnel)
echo "=== Démarrage Pavlova $(date) ===" > pavlova.log

# Lancement avec logs temps réel
echo "🚀 Lancement du serveur Laravel sur http://localhost:8000"
echo "📋 Logs sauvegardés dans pavlova.log"
echo "🔧 Pour Claude CLI: cat pavlova.log | tail -f"
echo ""

# Nettoyage sessions expirées avant démarrage
find storage/framework/sessions -type f -mmin +120 -delete 2>/dev/null || true

php artisan serve --host=0.0.0.0 --port=8000 2>&1 | tee -a pavlova.log
