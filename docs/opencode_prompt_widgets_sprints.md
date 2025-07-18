# 🚀 PROMPT OPENCODE SPÉCIALISÉ - Widgets Métier par Sprints - Pavlova

## 🎯 **MISSION OPENCODE FOCUS**

Tu es un **agent de développement IA autonome** utilisant **OpenCode** (alternative open source à Claude Code). Tu travailles sur l'application **Budget Workflow Pavlova**, une application Laravel de gestion budgétaire avec workflow d'approbation 5 niveaux.

**📋 WIDGETS MÉTIER** (3 sprints priorisés par impact business)

**📍 RÉPERTOIRE DE TRAVAIL :** `/home/admin_ia/api/pavlova/`

**⚠️ IMPORTANT :** Tu n'es PAS Codex qui travaille sur une VM éphémère. Tu es OpenCode et tu agis **DIRECTEMENT sur la machine locale Fedora 42**. Tes modifications sont **PERMANENTES**.

---

## 📚 **ÉTAPE 1 OBLIGATOIRE - LECTURE DOCUMENTATION PROJET**

### **📖 Comprendre le Contexte Métier AVANT Coding**
```bash
# TOUJOURS commencer par lire la documentation
cd /home/admin_ia/api/pavlova
echo "=== 🚀 DÉBUT SESSION OPENCODE - WIDGETS MÉTIER $(date) ===" | tee -a pavlova.log

# LECTURE OBLIGATOIRE - Documentation métier
echo "📚 PRIORITÉ ABSOLUE : Lire documentation projet"
echo "1. Cahier des charges : /home/admin_ia/api/pavlova/doc/cahier_charges_pavlova_v2.md"
echo "2. Guide utilisateur : /home/admin_ia/api/pavlova/doc/guide_utilisateur_pavlova_v2.md"
echo "3. Autres fichiers doc/ : $(ls doc/*.md | head -5)"

# Analyser structure projet
echo "📁 Analyse structure projet :"
echo "  - Modèles Laravel : $(find app/Models -name "*.php" | wc -l) fichiers"
echo "  - Resources Filament : $(find app/Filament/Resources -name "*Resource.php" | wc -l) fichiers"
echo "  - Widgets existants : $(find app/Filament/Widgets -name "*.php" | wc -l) fichiers"
echo "  - Migrations appliquées : $(php artisan migrate:status | grep -c "Ran")/$(php artisan migrate:status | wc -l)"
```

---

## 🔧 **ÉTAPE 2 - SERVEUR ET INFRASTRUCTURE**

### **🚀 Gestion Serveur et Logs**
```bash
# 1. Vérification port 8000 (OBLIGATOIRE)
echo "🔍 Vérification port 8000 :"
if lsof -i :8000 >/dev/null 2>&1; then
    echo "✅ Application déjà en cours sur port 8000"
    echo "Test HTTP :"
    curl -s -o /dev/null -w "Code HTTP: %{http_code}" http://localhost:8000
else
    echo "❌ Port 8000 libre - Lancement nécessaire"
    
    # Lancement avec start_pavlova.sh
    if [ -f "start_pavlova.sh" ]; then
        echo "🚀 Lancement application avec start_pavlova.sh :"
        ./start_pavlova.sh &
        sleep 5
        
        # Vérification démarrage
        if lsof -i :8000 >/dev/null 2>&1; then
            echo "✅ Application démarrée avec succès"
            curl -s -o /dev/null -w "Code HTTP: %{http_code}" http://localhost:8000
        else
            echo "❌ Échec démarrage - Vérification logs :"
            tail -10 pavlova.log 2>/dev/null || echo "Pas de logs pavlova.log"
        fi
    else
        echo "❌ start_pavlova.sh non trouvé"
        echo "Utilisation fallback php artisan serve :"
        php artisan serve --host=0.0.0.0 --port=8000 &
    fi
fi

# 2. Monitoring logs temps réel (OBLIGATOIRE)
echo "📋 Monitoring logs temps réel activé :"
tail -f pavlova.log &
echo "Logs pavlova.log : $(ls -la pavlova.log 2>/dev/null || echo 'Fichier manquant')"

# 3. Environnement technique
echo "🔧 Environnement technique :"
echo "  - PHP : $(php --version | head -1)"
echo "  - Composer : $(composer --version)"
echo "  - Laravel : $(php artisan --version)"
echo "  - Répertoire : $(pwd)"
```

### **⚠️ Permissions Sudo Disponibles**
```bash
# Tu peux utiliser sudo SANS mot de passe si nécessaire
# ATTENTION : Utilise sudo avec précaution uniquement si requis
# Exemples autorisés : 
# - sudo chown pour permissions fichiers
# - sudo systemctl si services système requis
# - sudo apt install si packages manquants

echo "🔐 Permissions sudo disponibles (utilisation prudente)"
```

---

## 📋 **CONTEXTE PROJET - INFRASTRUCTURE EXISTANTE**

### ✅ **ARCHITECTURE LARAVEL + FILAMENT OPÉRATIONNELLE**
- **Framework** : Laravel 10+ avec PHP 8.1+ ✅
- **Interface Admin** : Filament v3 avec widgets existants ✅
- **Base de données** : SQLite + PostgreSQL avec données réelles ✅
- **Authentification** : Spatie Laravel Permission avec 6 rôles ✅

### ✅ **RÔLES UTILISATEURS CONFIGURÉS**
```php
// EXISTANT dans database/seeders/RolePermissionSeeder.php
'administrateur'           → Accès complet système
'responsable-budget'       → Gestion budgets globaux tous services  
'service-achat'           → Validation achats + création commandes
'responsable-direction'    → Validation stratégique + KPI globaux
'responsable-service'      → Gestion hiérarchique d'un service
'service-demandeur'       → Création demandes + validation réceptions
```

### ✅ **MODÈLES DE DONNÉES OPÉRATIONNELS**
```php
// EXISTANT avec relations complètes
DemandeDevis   → statuts: pending_manager, pending_direction, pending_achat, ordered, delivered
BudgetLigne    → montant_ht_prevu, montant_engage, montant_depense_reel
Service        → nom, actif, users
User           → service_id, roles via Spatie Permission
Commande       → demande_devis_id, statut, montant_ht, fournisseur_nom
```

### ✅ **WIDGETS EXISTANTS À CONSERVER**
```php
// NE PAS MODIFIER - Fonctionnels
BudgetStatsWidget         → 6 stats avec calculs par rôle ✅
ExecutiveStatsWidget      → Métriques direction ✅  
BudgetConsumptionWidget   → Graphiques Chart.js ✅
WorkflowTimelineWidget    → Timeline workflow ✅
```

---

## 🏃‍♂️ **SPRINT 1 - IMPACT MAXIMUM (Widgets critiques opérationnels)**

### **🎯 OBJECTIF SPRINT 1**
Créer les **2 widgets** qui ont l'**impact business immédiat** le plus fort pour optimiser le workflow quotidien.

### **📋 WIDGET 1.1 : WorkflowKanbanWidget**

#### **Contexte Métier**
- **Utilisateurs cibles** : `service-achat`, `responsable-budget`, `responsable-direction`
- **Problème résolu** : Vue dispersée des demandes, goulots d'étranglement workflow
- **Impact attendu** : Traitement +50% plus rapide des demandes

#### **Spécifications Techniques**
```bash
# Commandes OpenCode
cd /workspace/pavlova && pwd

# Créer widget Kanban
php artisan make:filament-widget WorkflowKanbanWidget

# Créer vue Blade associée
mkdir -p resources/views/filament/widgets
touch resources/views/filament/widgets/workflow-kanban-widget.blade.php
```

#### **Code à Implémenter**
```php
// CRÉER app/Filament/Widgets/WorkflowKanbanWidget.php
<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\DemandeDevis;
use Illuminate\Support\Facades\Auth;

class WorkflowKanbanWidget extends Widget
{
    protected static string $view = 'filament.widgets.workflow-kanban-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;
    
    // SÉCURITÉ : Filtrage par rôle
    public static function canView(): bool {
        return auth()->user()?->hasAnyRole([
            'service-achat', 'responsable-budget', 'responsable-direction'
        ]) ?? false;
    }
    
    public function getKanbanData(): array
    {
        $user = Auth::user();
        $columns = $this->getColumnsForRole($user);
        
        $data = [];
        foreach ($columns as $status => $config) {
            $query = DemandeDevis::where('statut', $status)
                ->with(['serviceDemandeur', 'budgetLigne', 'createdBy']);
                
            // FILTRAGE SÉCURISÉ par rôle
            if ($user->hasRole('service-demandeur') && $user->service_id) {
                $query->where('service_demandeur_id', $user->service_id);
            }
            
            $demandes = $query->latest()->limit(8)->get();
            
            $data[$status] = [
                'label' => $config['label'],
                'color' => $config['color'],
                'icon' => $config['icon'],
                'count' => $query->count(),
                'demandes' => $demandes,
                'action_available' => $config['action'] ?? false
            ];
        }
        
        return $data;
    }
    
    private function getColumnsForRole($user): array
    {
        if ($user->hasRole('service-achat')) {
            return [
                'pending_achat' => [
                    'label' => 'À Valider Achat',
                    'color' => 'warning',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'action' => true
                ],
                'approved_achat' => [
                    'label' => 'Créer Commande', 
                    'color' => 'info',
                    'icon' => 'heroicon-o-shopping-cart',
                    'action' => true
                ],
                'ordered' => [
                    'label' => 'Commandes Passées',
                    'color' => 'success', 
                    'icon' => 'heroicon-o-check-circle'
                ],
                'pending_reception' => [
                    'label' => 'En Livraison',
                    'color' => 'purple',
                    'icon' => 'heroicon-o-truck'
                ]
            ];
        }
        
        // Vue globale pour responsable-budget et direction
        return [
            'pending_manager' => [
                'label' => 'Responsable Service',
                'color' => 'yellow',
                'icon' => 'heroicon-o-user'
            ],
            'pending_direction' => [
                'label' => 'Direction',
                'color' => 'blue', 
                'icon' => 'heroicon-o-building-office'
            ],
            'pending_achat' => [
                'label' => 'Service Achat',
                'color' => 'purple',
                'icon' => 'heroicon-o-shopping-bag'
            ],
            'ordered' => [
                'label' => 'Commandées',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle'
            ]
        ];
    }
}
```

#### **Vue Blade Kanban**
```html
<!-- CRÉER resources/views/filament/widgets/workflow-kanban-widget.blade.php -->
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            📋 Workflow Kanban - Vue Métier
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getKanbanData() as $status => $column)
            <div class="kanban-column bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <!-- Header colonne -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-o-user class="w-5 h-5 text-{{ $column['color'] }}-500" />
                        <h3 class="font-semibold text-sm">{{ $column['label'] }}</h3>
                    </div>
                    <span class="bg-{{ $column['color'] }}-100 text-{{ $column['color'] }}-800 text-xs px-2 py-1 rounded-full">
                        {{ $column['count'] }}
                    </span>
                </div>
                
                <!-- Cartes demandes -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($column['demandes'] as $demande)
                    <div class="kanban-card bg-white dark:bg-gray-700 p-3 rounded border border-gray-200 hover:shadow-md transition-shadow cursor-pointer"
                         onclick="window.open('/admin/demande-devis/{{ $demande->id }}', '_blank')">
                        
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-medium text-sm truncate">
                                {{ Str::limit($demande->denomination, 25) }}
                            </h4>
                            <span class="text-xs text-gray-500">
                                #{{ $demande->id }}
                            </span>
                        </div>
                        
                        <div class="text-xs text-gray-600 space-y-1">
                            <div>💰 {{ number_format($demande->prix_total_ttc, 0) }}€</div>
                            <div>🏢 {{ $demande->serviceDemandeur?->nom }}</div>
                            <div>⏱️ {{ $demande->created_at->diffForHumans() }}</div>
                        </div>
                        
                        @if($column['action_available'] ?? false)
                        <div class="mt-2 pt-2 border-t">
                            <button class="text-xs bg-{{ $column['color'] }}-500 text-white px-2 py-1 rounded hover:bg-{{ $column['color'] }}-600">
                                {{ $status === 'pending_achat' ? 'Valider' : 'Créer Commande' }}
                            </button>
                        </div>
                        @endif
                    </div>
                    @endforeach
                    
                    @if($column['demandes']->isEmpty())
                    <div class="text-center text-gray-400 text-sm py-8">
                        Aucune demande
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Auto-refresh -->
        <script>
            setInterval(() => {
                if (typeof Livewire !== 'undefined') {
                    Livewire.emit('$refresh');
                }
            }, 30000); // Refresh toutes les 30 secondes
        </script>
    </x-filament::section>
</x-filament-widgets::widget>
```

### **📋 WIDGET 1.2 : BudgetAlertsWidget**

#### **Contexte Métier**
- **Utilisateurs cibles** : `responsable-budget`, `responsable-direction`
- **Problème résolu** : Détection tardive des dépassements budget
- **Impact attendu** : Prévention 90% des dépassements non autorisés

#### **Code à Implémenter**
```php
// CRÉER app/Filament/Widgets/BudgetAlertsWidget.php
<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\{BudgetLigne, Service, DemandeDevis};
use Illuminate\Support\Facades\Auth;

class BudgetAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.budget-alerts-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
    
    // SÉCURITÉ : Seuls les responsables budget/direction
    public static function canView(): bool {
        return auth()->user()?->hasAnyRole([
            'responsable-budget', 'responsable-direction'
        ]) ?? false;
    }
    
    public function getAlertsData(): array
    {
        return [
            'critiques' => $this->getCriticalAlerts(),
            'avertissements' => $this->getWarningAlerts(),
            'info' => $this->getInfoAlerts(),
            'stats' => $this->getGlobalStats()
        ];
    }
    
    private function getCriticalAlerts(): array
    {
        $alerts = [];
        
        // Services en dépassement (> 100%)
        $servicesDepassement = Service::whereHas('budgetLignes', function($query) {
            $query->whereRaw('montant_depense_reel + montant_engage > montant_ht_prevu');
        })->with('budgetLignes')->get();
        
        foreach ($servicesDepassement as $service) {
            $depassement = $service->budgetLignes->sum(function($budget) {
                return ($budget->montant_depense_reel + $budget->montant_engage) - $budget->montant_ht_prevu;
            });
                          
            if ($depassement > 0) {
                $alerts[] = [
                    'type' => 'critique',
                    'service' => $service->nom,
                    'message' => "Dépassement de " . number_format($depassement, 0) . "€",
                    'action' => 'Réallocation urgente requise',
                    'url' => '/admin/budget-lignes?tableFilters[service_id][value]=' . $service->id
                ];
            }
        }
        
        return $alerts;
    }
    
    private function getWarningAlerts(): array
    {
        $alerts = [];
        
        // Budgets proches saturation (80-100%)
        $budgetsRisque = BudgetLigne::with('service')->get()->filter(function($budget) {
            $taux = $budget->getTauxUtilisation();
            return $taux >= 80 && $taux <= 100;
        });
        
        foreach ($budgetsRisque as $budget) {
            $tauxUtilisation = $budget->getTauxUtilisation();
            $alerts[] = [
                'type' => 'warning',
                'service' => $budget->service->nom,
                'ligne' => $budget->intitule,
                'taux' => round($tauxUtilisation, 1),
                'message' => "Budget à " . round($tauxUtilisation, 1) . "% - Attention",
                'action' => 'Surveiller nouvelles demandes'
            ];
        }
        
        return $alerts;
    }
    
    private function getInfoAlerts(): array
    {
        $alerts = [];
        
        // Demandes bloquées > 3 jours
        $demandesBloquees = DemandeDevis::whereIn('statut', [
            'pending_manager', 'pending_direction', 'pending_achat'
        ])->where('updated_at', '<', now()->subDays(3))
          ->with(['serviceDemandeur'])
          ->get();
          
        foreach ($demandesBloquees as $demande) {
            $alerts[] = [
                'type' => 'info',
                'service' => $demande->serviceDemandeur->nom,
                'demande' => $demande->denomination,
                'statut' => $demande->statut,
                'jours' => $demande->updated_at->diffInDays(now()),
                'message' => "Bloquée depuis " . $demande->updated_at->diffInDays(now()) . " jours",
                'action' => 'Relancer approbateur'
            ];
        }
        
        return $alerts;
    }
    
    private function getGlobalStats(): array
    {
        return [
            'services_ok' => Service::whereHas('budgetLignes', function($query) {
                $query->whereRaw('((montant_depense_reel + montant_engage) / montant_ht_prevu * 100) < 80');
            })->count(),
            'services_attention' => Service::whereHas('budgetLignes', function($query) {
                $query->whereRaw('((montant_depense_reel + montant_engage) / montant_ht_prevu * 100) BETWEEN 80 AND 100');
            })->count(),
            'services_depassement' => Service::whereHas('budgetLignes', function($query) {
                $query->whereRaw('montant_depense_reel + montant_engage > montant_ht_prevu');
            })->count()
        ];
    }
}
```

---

## 🏃‍♂️ **SPRINT 2 - CONFORT UTILISATEUR (Widgets ergonomiques)**

### **📋 WIDGET 2.1 : NotificationCenterWidget**

#### **Contexte Métier**
- **Utilisateurs cibles** : **Tous les rôles** (contenu filtré par rôle)
- **Problème résolu** : Notifications éparpillées, oublis d'actions
- **Impact attendu** : Réduction 70% des oublis, meilleure réactivité

#### **Code à Implémenter**
```php
// CRÉER app/Filament/Widgets/NotificationCenterWidget.php
<?php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationCenterWidget extends Widget
{
    protected static string $view = 'filament.widgets.notification-center-widget';
    protected static ?int $sort = 1;
    
    public function getNotificationsData(): array
    {
        $user = Auth::user();
        
        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $this->getRecentNotifications($user),
            'actions_required' => $this->getActionsRequired($user),
            'role_specific' => $this->getRoleSpecificNotifications($user)
        ];
    }
    
    private function getRecentNotifications($user): array
    {
        return $user->notifications()
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'body' => $notification->data['body'] ?? '',
                    'icon' => $notification->data['icon'] ?? 'heroicon-o-bell',
                    'color' => $notification->data['color'] ?? 'info',
                    'url' => $notification->data['url'] ?? null,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at
                ];
            })->toArray();
    }
    
    private function getActionsRequired($user): array
    {
        $actions = [];
        
        if ($user->hasRole('service-achat')) {
            $count = \App\Models\DemandeDevis::where('statut', 'pending_achat')->count();
            if ($count > 0) {
                $actions[] = [
                    'type' => 'approval',
                    'count' => $count,
                    'label' => "Demandes à valider achat",
                    'url' => '/admin/demande-devis?tableFilters[statut][value]=pending_achat',
                    'color' => 'warning'
                ];
            }
        }
        
        if ($user->hasRole('responsable-budget')) {
            $count = \App\Models\DemandeDevis::where('statut', 'pending_direction')->count();
            if ($count > 0) {
                $actions[] = [
                    'type' => 'budget',
                    'count' => $count,
                    'label' => "Validations budget requises",
                    'url' => '/admin/demande-devis?tableFilters[statut][value]=pending_direction',
                    'color' => 'info'
                ];
            }
        }
        
        if ($user->hasRole('responsable-service')) {
            $count = \App\Models\DemandeDevis::where('statut', 'pending_manager')
                ->whereHas('serviceDemandeur', function($q) use ($user) {
                    $q->where('id', $user->service_id);
                })->count();
            if ($count > 0) {
                $actions[] = [
                    'type' => 'manager',
                    'count' => $count,
                    'label' => "Demandes à valider (service)",
                    'url' => '/admin/demande-devis?tableFilters[statut][value]=pending_manager',
                    'color' => 'success'
                ];
            }
        }
        
        return $actions;
    }
}
```

### **📋 WIDGET 2.2 : FournisseurPerformanceWidget**

#### **Contexte Métier**
- **Utilisateurs cibles** : `service-achat`, `responsable-direction`
- **Problème résolu** : Choix fournisseurs non optimisés
- **Impact attendu** : Amélioration 25% délais livraison

#### **Code à Implémenter**
```php
// CRÉER app/Filament/Widgets/FournisseurPerformanceWidget.php
<?php
namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\{DemandeDevis, Commande};
use Illuminate\Support\Facades\DB;

class FournisseurPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Performance Fournisseurs';
    protected static ?int $sort = 4;
    
    public static function canView(): bool {
        return auth()->user()?->hasAnyRole([
            'service-achat', 'responsable-direction'
        ]) ?? false;
    }
    
    protected function getData(): array
    {
        $fournisseurs = $this->getTopFournisseurs();
        
        return [
            'datasets' => [
                [
                    'label' => 'Délai Moyen (jours)',
                    'data' => $fournisseurs->pluck('delai_moyen')->toArray(),
                    'backgroundColor' => '#10B981',
                ],
                [
                    'label' => 'Nombre Commandes',
                    'data' => $fournisseurs->pluck('nb_commandes')->toArray(),
                    'backgroundColor' => '#3B82F6',
                ]
            ],
            'labels' => $fournisseurs->pluck('nom')->toArray(),
        ];
    }
    
    private function getTopFournisseurs()
    {
        return DB::table('demande_devis')
            ->join('commandes', 'demande_devis.id', '=', 'commandes.demande_devis_id')
            ->select(
                'demande_devis.fournisseur_propose as nom',
                DB::raw('COUNT(*) as nb_commandes'),
                DB::raw('AVG(JULIANDAY(commandes.updated_at) - JULIANDAY(commandes.date_commande)) as delai_moyen'),
                DB::raw('AVG(demande_devis.prix_total_ttc) as montant_moyen')
            )
            ->where('demande_devis.statut', 'delivered_confirmed')
            ->whereNotNull('demande_devis.fournisseur_propose')
            ->groupBy('demande_devis.fournisseur_propose')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByDesc('nb_commandes')
            ->limit(8)
            ->get()
            ->map(function($item) {
                return [
                    'nom' => $item->nom,
                    'nb_commandes' => $item->nb_commandes,
                    'delai_moyen' => round($item->delai_moyen ?? 0, 1),
                    'montant_moyen' => round($item->montant_moyen, 2)
                ];
            });
    }
    
    protected function getType(): string
    {
        return 'bar';
    }
}
```

---

## 🏃‍♂️ **SPRINT 3 - NICE TO HAVE (Widgets de confort)**

### **📋 WIDGET 3.1 : MesDemandesWidget** (Service-demandeur)
### **📋 WIDGET 3.2 : TendancesStrategiquesWidget** (Direction)

---

## 🧪 **TESTS UTILISATEURS ET WORKFLOW COLLABORATIFS**

### **⚠️ MÉTHODOLOGIE IMPORTANTE**
Tu demandes à l'utilisateur de tester dans son navigateur, tu analyses les logs en temps réel et corriges les problèmes détectés. **Tests collaboratifs obligatoires** avant et après chaque widget créé.

### **🔐 Comptes Utilisateurs Configurés**
```bash
# Tests multi-rôles avec comptes existants
echo "👥 Comptes utilisateurs pour tests :"
echo "  - admin@test.local / password - ADMINISTRATEUR COMPLET"
echo "  - responsable.budget@test.local / password - Gestion budgets"
echo "  - service.achat@test.local / password - Validation achats"
echo "  - demandeur@service.local / password - Tests service demandeur"
echo "  - direction@company.local / password - Validation direction"

# Test connexions rapide
echo "🧪 TESTS DEMANDÉS - Authentification rapide :"
echo "Testez http://localhost:8000/admin avec admin@test.local / password"
echo "Confirmez que le dashboard s'affiche correctement"
```

### **📊 Tests Widgets par Sprint**
```bash
echo "🎯 TESTS WIDGETS PAR SPRINT :"
echo "Sprint 1 : WorkflowKanbanWidget + BudgetAlertsWidget"
echo "  - Test avec role service-achat : Vue Kanban opérationnelle ?"
echo "  - Test avec role responsable-budget : Alertes visibles ?"
echo ""
echo "Sprint 2 : NotificationCenterWidget + FournisseurPerformanceWidget" 
echo "  - Test notifications par rôle : Contenu filtré ?"
echo "  - Test analytics fournisseurs : Graphiques corrects ?"
```

---

## 🚀 **COMMANDES OPENCODE - EXÉCUTION SPRINTS**

### **📋 Session OpenCode Recommandée**
```bash
# OpenCode est déjà installé et configuré
# Démarrage session dans le bon répertoire
cd /home/admin_ia/api/pavlova

# Commandes OpenCode avec modèle recommandé
opencode --model claude-3.7-sonnet

# Ou alternative si Claude indisponible
opencode --model gpt-4o-latest
opencode --model gemini-2.0-pro
```

### **📝 Commandes Laravel pour Widgets**
```bash
# TOUJOURS commencer par ceci
cd /workspace/pavlova && pwd && ls -la

# Vérifier environnement Laravel
php --version && php artisan --version

# Sprint 1 - Widget 1: Kanban
php artisan make:filament-widget WorkflowKanbanWidget
mkdir -p resources/views/filament/widgets

# Sprint 1 - Widget 2: Alertes Budget  
php artisan make:filament-widget BudgetAlertsWidget

# Enregistrer widgets dans AdminPanelProvider
# AJOUTER dans app/Providers/Filament/AdminPanelProvider.php :
# \App\Filament\Widgets\WorkflowKanbanWidget::class,
# \App\Filament\Widgets\BudgetAlertsWidget::class,
```

### **Tests Validation Sprint 1**
```bash
# Test widgets accessibles
php artisan tinker --execute="
echo 'Test WorkflowKanbanWidget...';
\$widget = new \App\Filament\Widgets\WorkflowKanbanWidget();
echo 'Kanban data: ' . count(\$widget->getKanbanData());

echo 'Test BudgetAlertsWidget...';  
\$widget2 = new \App\Filament\Widgets\BudgetAlertsWidget();
echo 'Alerts data: ' . count(\$widget2->getAlertsData());
"

# Test interface admin
php artisan serve --host=0.0.0.0 --port=8000 &
curl -I http://localhost:8000/admin

# Test par rôle utilisateur
# Se connecter avec admin@test.local (responsable-budget)
# Se connecter avec achat@test.local (service-achat)
```

---

## 📝 **DOCUMENTATION OBLIGATOIRE**

### **📋 Compte Rendu de Session Obligatoire**
```bash
# À CHAQUE SESSION, créer compte rendu détaillé
touch doc/compte_rendu_kimi_tache_14072025_23H00.md

# Template obligatoire pour le rapport
cat > doc/compte_rendu_kimi_tache_14072025_23H00.md << 'EOF'
# 🤖 RAPPORT OPENCODE - Widgets Métier Sprints - 14/07/2025 23H00

## ⏱️ Session de Travail
- **Début :** [Heure de début]
- **Répertoire :** /home/admin_ia/api/pavlova/
- **Objectif :** Développement widgets métier par sprints
- **Sprint focus :** [1/2/3] - [Widgets ciblés]

## 🔧 Serveur et Infrastructure  
- **Port 8000 :** [Statut initial - libre/occupé]
- **Script start_pavlova.sh :** [Succès/échec lancement]
- **Monitoring logs :** [Actif via tail -f pavlova.log]
- **Test HTTP :** [Code retourné par curl]

## 📚 Documentation Projet Analysée
- **Cahier des charges :** ✅ Lu et compris
- **Guide utilisateur :** ✅ Workflows métier assimilés  
- **Architecture technique :** [Résumé des éléments clés]
- **Rôles utilisateurs :** [6 rôles identifiés et testés]

## 🧪 Tests Collaboratifs Effectués

### Tests Sprint 1 - WorkflowKanbanWidget
- **Test service-achat :** [Résultat vue board métier]
- **Test responsable-budget :** [Résultat vue globale]
- **Actions rapides :** [Boutons Valider/Rejeter fonctionnels ?]
- **Logs erreurs :** [Analyse pavlova.log pendant tests]

### Tests Sprint 1 - BudgetAlertsWidget  
- **Alertes critiques :** [Détection dépassements OK/KO]
- **Alertes warning :** [Seuils 80-100% détectés ?]
- **Performance :** [Temps calcul acceptable ?]

## 🔧 Développement Widgets Réalisé

### [Heure] - WorkflowKanbanWidget Créé
- **Fichier :** app/Filament/Widgets/WorkflowKanbanWidget.php
- **Vue Blade :** resources/views/filament/widgets/workflow-kanban-widget.blade.php
- **Sécurité :** Filtrage par rôle implémenté
- **Fonctionnalités :** [Liste des fonctionnalités développées]
- **Tests :** [Résultats tests utilisateur]

### [Heure] - BudgetAlertsWidget Créé
- **Fichier :** app/Filament/Widgets/BudgetAlertsWidget.php
- **Algorithmes :** [Méthodes de calcul alertes]
- **Performance :** [Temps exécution mesuré]
- **Tests :** [Validation avec données réelles]

## 📊 Logs Analysés
- **Erreurs critiques :** [Nombre et nature]
- **Warnings PHP :** [Détail des avertissements]
- **Requêtes lentes :** [Optimisations appliquées]
- **Logs métier :** [Workflow, permissions, calculs]

## ✅ Validations Finales
- **Tests par rôle :** [Résultats pour chaque profil utilisateur]
- **Performance :** [Temps de réponse widgets < 2s]
- **Responsive :** [Affichage mobile/tablette correct]
- **Intégration :** [Widgets ajoutés AdminPanelProvider sans conflit]

## 🎯 Objectifs Sprint Atteints
- **Sprint 1 :** ✅/❌ [WorkflowKanbanWidget + BudgetAlertsWidget]
- **Sprint 2 :** ✅/❌ [NotificationCenterWidget + FournisseurPerformanceWidget]  
- **Sprint 3 :** ✅/❌ [MesDemandesWidget + TendancesStrategiquesWidget]

## 🔮 Prochaines Sessions Recommandées
1. [Corrections à apporter si nécessaire]
2. [Optimisations performance identifiées]
3. [Fonctionnalités supplémentaires demandées]

## 💡 Découvertes et Apprentissages
- **Code insights :** [Éléments architecture découverts]
- **Bonnes pratiques :** [Patterns Laravel/Filament appliqués]
- **Optimisations :** [Améliorations code suggérées]
EOF

echo "📋 Fichier rapport créé : doc/compte_rendu_kimi_tache_14072025_23H00.md"
echo "À mettre à jour tout au long de la session"
```

### **📋 Analyse Logs Obligatoire**
```bash
# Surveillance continue logs application
echo "📊 ANALYSE LOGS OBLIGATOIRE :"
echo "1. Logs pavlova.log : $(ls -la pavlova.log 2>/dev/null || echo 'À créer')"
echo "2. Logs Laravel : storage/logs/laravel.log"
echo "3. Monitoring temps réel : tail -f pavlova.log (déjà actif)"

# Commandes utiles analyse logs
echo "🔍 Commandes analyse logs :"
echo "  - Erreurs critiques : grep -i 'error\|exception\|fatal' pavlova.log"
echo "  - Warnings : grep -i 'warning\|notice' pavlova.log"  
echo "  - Workflows : grep -i 'workflow\|approval\|statut' pavlova.log"
echo "  - Permissions : grep -i 'permission\|unauthorized\|403' pavlova.log"
```

---

## 📝 **DOCUMENTATION OBLIGATOIRE**

À chaque sprint complété, créer :
`doc/opencode_rapport_widgets_sprint_X_YYYY-MM-DD.md`

```markdown
# 🤖 RAPPORT OPENCODE - Widgets Sprint X - [Date]

## ⏱️ Session Focus Sprint X
- **Début :** [Heure]
- **Widgets cibles :** [Liste widgets sprint]
- **Rôles impactés :** [Rôles utilisateurs]

## ✅ Actions Réalisées

### [Heure] - Widget WorkflowKanbanWidget
- ✅ Widget créé avec vue Kanban métier
- ✅ Filtrage sécurisé par rôle utilisateur  
- ✅ Actions rapides intégrées
- ✅ Auto-refresh 30 secondes

### [Heure] - Widget BudgetAlertsWidget
- ✅ Détection alertes critiques/warning/info
- ✅ Calculs temps réel dépassements
- ✅ Actions recommandées par alerte

## 🧪 Tests de Validation Effectués
- ✅ Sécurité rôles : [Résultat]
- ✅ Performance widgets : [Résultat]  
- ✅ Interface responsive : [Résultat]
- ✅ Actions métier : [Résultat]

## 🎯 IMPACT BUSINESS MESURÉ
- **Productivité** : +X% traitement demandes
- **Prévention** : X% dépassements évités
- **Réactivité** : -X jours délai moyen
- **Satisfaction utilisateurs** : Feedback positif
```

---

## ⚠️ **CONTRAINTES CRITIQUES OPENCODE**

### **🔒 Sécurité et Permissions**
- ✅ **Filtrage obligatoire** : Chaque widget DOIT filtrer par rôle
- ✅ **Méthode canView()** : Contrôler accès widget par rôle
- ✅ **Données service** : Respecter cloisonnement service-demandeur

### **🚫 Ne PAS Casser l'Existant**
- ✅ **Widgets existants** : Ne JAMAIS modifier BudgetStatsWidget, ExecutiveStatsWidget
- ✅ **Navigation** : Ajouter dans AdminPanelProvider sans conflits
- ✅ **Base données** : Aucune modification structure/migration

### **⚡ Performance et UX**
- ✅ **Lazy loading** : Widgets lourds avec skeleton
- ✅ **Auto-refresh** : 30-60 secondes selon criticité
- ✅ **Responsive** : Support mobile/tablette
- ✅ **Actions rapides** : Boutons métier intégrés

---

## 🎯 **OBJECTIFS RÉUSSITE PAR SPRINT**

### **Sprint 1 Réussi Si :**
1. ✅ **WorkflowKanbanWidget** → Service-achat traite demandes 50% plus vite
2. ✅ **BudgetAlertsWidget** → Responsable-budget détecte 90% dépassements à temps
3. ✅ **Tests utilisateurs** → Feedback positif sur ergonomie métier

### **Sprint 2 Réussi Si :**
4. ✅ **NotificationCenterWidget** → 70% moins d'oublis actions
5. ✅ **FournisseurPerformanceWidget** → Meilleur choix fournisseurs

### **Sprint 3 Réussi Si :**
6. ✅ **Widgets personnels** → Chaque rôle a SA vue métier optimisée

**🚀 GO OPENCODE ! Exécute Sprint 1 puis 2 puis 3 !**
