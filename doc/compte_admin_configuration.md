# 🔐 Configuration Compte Administrateur

## Compte Admin par Défaut

Le système utilise maintenant **admin@test.local** comme compte administrateur principal.

### Informations de Connexion
- **Email**: admin@test.local
- **Mot de passe**: password (par défaut)
- **Service**: Administration (ADMIN)
- **Rôles**: administrateur
- **Accès**: Interface admin Filament complète

## Configuration Automatique

### Via Seeder
```bash
php artisan db:seed --class=AdminUserSeeder
```

### Via Commande Artisan
```bash
# Configuration par défaut
php artisan admin:setup

# Configuration personnalisée
php artisan admin:setup --email=admin@mondomaine.com --password=motdepasse123 --name="Admin Principal"
```

## Fonctionnalités Admin

Le compte admin@test.local a accès à:

### ✅ CRUD Complet
- **Services**: Création, modification, suppression services
- **Utilisateurs**: Gestion complète utilisateurs et rôles
- **Demandes**: Visualisation toutes demandes workflow
- **Budget**: Gestion lignes budgétaires

### ✅ Dashboards
- Widgets globaux tous rôles
- Statistiques système complètes
- Rapports inter-services

### ✅ Workflow
- Validation à tous niveaux si besoin
- Supervision globale processus
- Gestion exceptions et escalades

## Service Administration

Le service "Administration" (code: ADMIN) est automatiquement créé:
- **Nom**: Administration
- **Code**: ADMIN
- **Description**: Service administration système
- **Statut**: Actif
- **Budget**: 0€ (administratif)

## Sécurité

### Recommandations
1. **Changez le mot de passe** après première connexion
2. **Utilisez un email valide** en production
3. **Activez l'authentification 2FA** si disponible
4. **Auditez les accès** régulièrement

### Permissions
Le rôle "administrateur" a **TOUTES** les permissions:
- Gestion utilisateurs et rôles
- Configuration système
- Accès données sensibles
- Modifications structure

## Dépannage

### Problèmes Courants

**Compte non créé**
```bash
php artisan admin:setup --email=admin@test.local
```

**Permissions manquantes**
```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan admin:setup
```

**Service manquant**
```bash
php artisan admin:setup
# Le service Administration sera créé automatiquement
```

### Vérification État
```bash
php artisan tinker --execute="
\$admin = User::where('email', 'admin@test.local')->first();
echo 'Admin: ' . \$admin->name . PHP_EOL;
echo 'Service: ' . \$admin->service->nom . PHP_EOL;
echo 'Rôles: ' . \$admin->roles->pluck('name')->implode(', ') . PHP_EOL;
"
```

## Première Connexion

1. Ouvrez `/admin/login`
2. Connectez-vous avec admin@test.local / password
3. Changez le mot de passe immédiatement
4. Configurez votre profil utilisateur
5. Explorez l'interface admin

## Développement

Pour réinitialiser complètement:
```bash
# Réinitialiser base de données
php artisan migrate:fresh

# Recréer rôles et permissions
php artisan db:seed --class=RolePermissionSeeder

# Recréer admin
php artisan admin:setup

# Données de test (optionnel)
php artisan db:seed --class=TestUsersSeeder
```

---
**Configuration terminée** ✅  
Le compte admin@test.local est maintenant opérationnel pour l'interface d'administration.