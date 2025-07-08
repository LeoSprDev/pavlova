# 📖 GUIDE UTILISATEUR - BUDGET & WORKFLOW

## 🔐 CONNEXION À L'APPLICATION

**URL d'accès :** http://localhost:8000/admin

### Comptes de test disponibles :

| Rôle | Email | Mot de passe | Permissions |
|------|-------|--------------|-------------|
| **Administrateur** | admin@test.local | password | Accès total |
| **Responsable Budget** | budget@test.local | password | Validation budgets globale |
| **Service Achat** | achat@test.local | password | Validation achats |
| **Demandeur Service IT** | demandeur.SX-IT@test.local | password | Gestion budget service IT |
| **Demandeur Service RH** | demandeur.SY-RH@test.local | password | Gestion budget service RH |
| **Demandeur Service Marketing** | demandeur.SZ-MKT@test.local | password | Gestion budget service Marketing |

## 🎯 WORKFLOWS PRINCIPAUX

### 1. CRÉER UNE LIGNE BUDGÉTAIRE (Service Demandeur)
1. Aller dans **Budget Lignes** → **Nouveau**
2. Renseigner : service, date prévue, intitulé, montant HT
3. Le TTC se calcule automatiquement
4. **Statut initial :** "Non validé"

### 2. VALIDER UNE LIGNE BUDGÉTAIRE (Responsable Budget)  
1. Aller dans **Budget Lignes**
2. Sélectionner ligne(s) → **Actions groupées** → **Valider budgets**
3. **Statut :** "Validé définitivement"

### 3. CRÉER UNE DEMANDE DE DEVIS (Service Demandeur)
1. **Prérequis :** Ligne budgétaire validée avec budget disponible
2. Aller dans **Demandes Devis** → **Nouveau**
3. Sélectionner ligne budgétaire d'imputation
4. Renseigner produit, quantité, prix, justification
5. **Statut initial :** "En attente validation budget"

### 4. WORKFLOW D'APPROBATION 3 NIVEAUX

#### Niveau 1 : Validation Budget
- **Qui :** Responsable Budget
- **Action :** Approuver/Rejeter la demande  
- **Critères :** Cohérence budgétaire, disponibilité enveloppe
- **Résultat :** Statut "Approuvé budget" ou "Rejeté"

#### Niveau 2 : Validation Achat
- **Qui :** Service Achat
- **Action :** Approuver/Rejeter + Créer commande
- **Critères :** Fournisseur valide, conditions commerciales
- **Résultat :** Statut "Approuvé achat" + Commande générée

#### Niveau 3 : Réception Livraison  
- **Qui :** Service Demandeur (original)
- **Action :** Confirmer réception + Upload bon de livraison
- **Critères :** Conformité produit reçu
- **Résultat :** Statut "Livré" + Budget ligne mis à jour

## 🎨 DASHBOARDS ET STATISTIQUES

### Dashboard Service Demandeur
- **Budget disponible** de votre service
- **Demandes en cours** d'approbation  
- **Livraisons attendues**
- **Taux de consommation** budgétaire

### Dashboard Responsable Budget
- **Budget total organisation**
- **Demandes à valider** (en attente)
- **Alertes dépassement** budgétaire
- **Répartition par service**

### Dashboard Service Achat
- **Commandes en cours**
- **Fournisseurs performance**
- **Délais livraison** moyens

## 🚨 GESTION DES ALERTES

### Alertes Automatiques Budget
- **Seuil 90% :** Notification d'avertissement
- **Seuil 95% :** Alerte critique
- **Dépassement :** Blocage nouvelles demandes + Email responsable

### Relances Fournisseurs
- **J+3 après échéance :** 1ère relance automatique
- **J+6 :** 2ème relance avec copie service achat  
- **J+8 :** 3ème relance + escalade responsable budget

## 📊 EXPORTS ET RAPPORTS

### Exports Disponibles
- **Budget complet** par service (Excel multi-onglets)
- **Historique demandes** avec workflow
- **Performance fournisseurs** avec indicateurs qualité
- **Synthèse consommation** budgétaire

## ⚠️ POINTS D'ATTENTION

### Cloisonnement Sécurisé
- **Service Demandeur :** Voit UNIQUEMENT son service
- **Responsable Budget :** Vision globale organisation
- **Service Achat :** Demandes en validation achat

### Contraintes Budgétaires
- Impossible de créer demande > budget disponible
- Validation automatique cohérence montants
- Mise à jour temps réel budget consommé

## 🆘 SUPPORT ET DÉPANNAGE

### Problèmes Fréquents
1. **"Budget insuffisant" :** Vérifier ligne budgétaire validée et disponible
2. **"Accès interdit" :** Vérifier rôle utilisateur et service d'affectation  
3. **"Workflow bloqué" :** Contacter responsable étape suivante

### Contacts Support
- **Technique :** admin@test.local
- **Fonctionnel :** budget@test.local

## 🚀 DÉMARRAGE RAPIDE

### Pour démarrer l'application :
```bash
cd /var/www/pavlova
unset DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD
php artisan serve --host=0.0.0.0 --port=8000
```

### Données de test disponibles :
- **3 services** : IT, RH, Marketing
- **12 lignes budgétaires** avec différents scénarios
- **12 demandes de devis** à différents stades
- **1 commande** en cours de livraison

### Scénarios de test inclus :
1. **Budget dépassé** - pour tester les alertes
2. **Workflow en cours** - demande en attente validation budget
3. **Demande approuvée** - en attente validation achat
4. **Commande en cours** - avec suivi livraison

## ✅ VALIDATION FINALE

L'application est considérée comme **VALIDÉE** quand :
- ✅ Un testeur humain peut se connecter et utiliser tous les workflows
- ✅ Les 3 rôles principaux fonctionnent avec cloisonnement
- ✅ Le workflow 3 niveaux fonctionne de bout en bout  
- ✅ Les alertes et notifications sont opérationnelles
- ✅ Les exports/imports fonctionnent
- ✅ La documentation permet à un nouvel utilisateur d'être autonome

**🎉 L'application Budget & Workflow est maintenant prête pour les tests utilisateurs !**