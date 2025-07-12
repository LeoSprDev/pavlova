# 🏢 Budget Workflow Pavlova
### Application de Gestion Budgétaire d'Entreprise

## 📋 Description
- Workflow d'approbation 5 niveaux
- Interface Filament avec dashboards
- Gestion budgets par service
- Exports Excel/PDF avancés
- Notifications temps réel

## 🔧 Installation Rapide
### Option 1: Installation Automatique
```bash
./scripts/check_prerequisites.sh
./scripts/install_pavlova.sh
./scripts/setup_project.sh
```
### Option 2: Installation Manuelle
Suivez les étapes détaillées ci-dessous pour installer chaque composant manuellement.
### Option 3: Docker (si disponible)
Une configuration Docker pourra être ajoutée ultérieurement.

## 💻 Prérequis Système
- OS: Ubuntu 20.04+, Debian 11+, CentOS/RHEL 8+
- RAM: minimum 2GB (4GB recommandé)
- Disque: 10GB libre
- Accès sudo et internet
- Ports 80 et 443 ouverts

## 🚀 Démarrage Rapide
1. Vérifiez les prérequis avec `check_prerequisites.sh`
2. Installez le système avec `install_pavlova.sh`
3. Configurez le projet avec `setup_project.sh`
4. Accédez à l'application depuis votre navigateur

## 👥 Comptes Utilisateur
- Admin: admin@test.local / password
- Budget: budget@test.local / password
- Achat: achat@test.local / password
- ...

## 🔧 Configuration
- Base de données PostgreSQL 16 par défaut
- Cache Redis avec fallback fichier
- Emails configurables via `.env`
- Permissions renforcées sur `/var/www/pavlova`

## 📊 Fonctionnalités
- Gestion Budget complète
- Workflow d'approbation avancé
- Exports et Rapports détaillés
- Tableaux de Bord personnalisés

## 🆘 Dépannage
Consultez les logs dans `/tmp/pavlova_*.log` en cas d'erreur.

## 🏗️ Architecture
Stack Laravel 10, PostgreSQL, Nginx, Redis.

## 👨‍💻 Développement
Clonez le dépôt, installez les dépendances avec Composer et npm, puis lancez `php artisan serve` pour un environnement local.
