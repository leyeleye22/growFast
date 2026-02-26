# 🚀 GrowFast

> Plateforme backend Laravel pour la découverte et le matching d'opportunités de financement pour startups.

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Database](https://img.shields.io/badge/DB-MySQL%20%7C%20PostgreSQL%20%7C%20SQLite-4169E1?style=flat-square)](https://laravel.com/docs/database)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

---

## 📋 Table des matières

- [Vue d'ensemble](#-vue-densemble)
- [Fonctionnalités](#-fonctionnalités)
- [Stack technique](#-stack-technique)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Admin Filament](#-admin-filament)
- [Architecture](#-architecture)
- [API](#-api)
- [Documentation Swagger](#-documentation-swagger)
- [Commandes Artisan](#-commandes-artisan)
- [Tests](#-tests)
- [Sécurité](#-sécurité)

---

## 🎯 Vue d'ensemble

**GrowFast** est une API REST conçue pour connecter les startups aux opportunités de financement (grants, equity, debt). Le système intègre :

- **Authentification JWT** avec support Google et LinkedIn OAuth
- **Gestion d'abonnements** (free / premium)
- **Pipeline de scraping** pour collecter des opportunités
- **Extraction IA** pour structurer les données brutes
- **Moteur de matching** pondéré (stage, industrie, pays, financement)
- **Admin Filament** : dashboard, métriques, ressources CRUD, pages de test (matching, scraping)

---

## ✨ Fonctionnalités

| Module | Description |
|--------|-------------|
| **Auth** | JWT, refresh tokens, blacklist, OAuth Google/LinkedIn |
| **Subscriptions** | Plans free/premium, contrôle d'accès par tier |
| **Opportunities** | CRUD, scopes (active, premium, free), filtrage par date |
| **Scraping** | Sources configurables, stratégies extensibles, détection de doublons |
| **AI Pipeline** | Extraction structurée (titre, deadline, funding), création de drafts |
| **Matching** | Score pondéré (stage 30%, industrie 30%, pays 20%, etc.) |
| **Admin Filament** | Dashboard, Users/Startups/Opportunities, test matching, test scraping |
| **Permissions** | Spatie (manage_opportunities, run_scraper, manage_subscriptions) |

---

## 🛠 Stack technique

| Composant | Technologie |
|-----------|-------------|
| Framework | Laravel 12 |
| Base de données | MySQL / PostgreSQL / SQLite |
| Auth | tymon/jwt-auth |
| Admin | Filament v5 |
| Permissions | spatie/laravel-permission |
| OAuth | laravel/socialite + socialiteproviders/linkedin |
| Tests | PHPUnit 11 |

---

## 📦 Prérequis

- **PHP** 8.2+
- **Composer** 2.x
- **MySQL** 8+ / **PostgreSQL** 15+ / **SQLite**
- **Node.js** 18+ (pour Vite)
- Extensions PHP : `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`

---

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-org/growFast.git
cd growFast
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 4. Base de données

```bash
php artisan migrate
php artisan db:seed
# ou pour repartir de zéro avec données de démo :
php artisan migrate:fresh --seed
```

### 5. Lancer l'application

```bash
php artisan serve
php artisan queue:listen
```

### 6. Accès admin

- **URL** : `http://localhost:8000/admin`
- **Email** : `admin@growfast.com`
- **Mot de passe** : `password` (après `php artisan db:seed`)

---

## ⚙️ Configuration

### Variables d'environnement principales

| Variable | Description | Exemple |
|----------|-------------|---------|
| `DB_CONNECTION` | Connexion BDD | `mysql`, `pgsql` ou `sqlite` |
| `DB_DATABASE` | Nom de la base | `growfast` |
| `GEMINI_API_KEY` | Clé API Gemini (extraction IA) | Optionnel, fallback regex |
| `JWT_SECRET` | Clé secrète JWT | Générée par `jwt:secret` |
| `JWT_TTL` | Durée de vie du token (min) | `60` |
| `JWT_REFRESH_TTL` | Fenêtre de refresh (min) | `20160` |
| `GOOGLE_CLIENT_ID` | OAuth Google | Client ID |
| `GOOGLE_CLIENT_SECRET` | OAuth Google | Client Secret |
| `GOOGLE_REDIRECT_URI` | Callback Google | `{APP_URL}/api/auth/google/callback` |
| `LINKEDIN_CLIENT_ID` | OAuth LinkedIn | Client ID |
| `LINKEDIN_CLIENT_SECRET` | OAuth LinkedIn | Client Secret |
| `LINKEDIN_REDIRECT_URI` | Callback LinkedIn | `{APP_URL}/api/auth/linkedin/callback` |

### Configuration OAuth

**Google** : [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials → Create OAuth 2.0 Client ID

**LinkedIn** : [LinkedIn Developers](https://www.linkedin.com/developers/) → Create App → Auth → OAuth 2.0 settings

---

## 🎛 Admin Filament

L'admin Filament fournit une interface complète pour gérer la plateforme.

### Accès

| URL | Description |
|-----|-------------|
| `/admin` | Dashboard et connexion |
| `/admin/users` | Liste et édition des utilisateurs |
| `/admin/startups` | Liste et édition des startups |
| `/admin/opportunities` | CRUD des opportunités |
| `/admin/test-matching-page` | Tester le matching (sélection startup → résultats) |
| `/admin/test-scraping-page` | Tester l'extraction (contenu brut → prix, date, etc.) |

### Dashboard

- **Stats** : inscriptions du jour, startups, opportunités, matches
- **Graphique** : inscriptions sur les 7 derniers jours
- **Filtres** : par industrie, pays, statut

### Ressources

| Ressource | Actions |
|-----------|---------|
| **Users** | Liste, édition |
| **Startups** | Liste, édition, action « Tester le matching » |
| **Opportunities** | CRUD complet |

### Pages de test

- **Tester le Matching** : choisir une startup et afficher les opportunités matchées avec scores et breakdown
- **Tester le Scraping** : coller du contenu brut (HTML/texte) et extraire titre, montants, date limite, type, industrie, stade (Gemini AI ou regex)

### Seeders

| Seeder | Données |
|--------|---------|
| `AdminDataSeeder` | Admin, industries, stages, opportunités |
| `StartupDataSeeder` | Utilisateurs startup (marie@startup.io, jean@innovate.com, aisha@agritech.com) avec startups |

---

## 🏗 Architecture

```
app/
├── Console/Commands/          # scrape:run, matches:recalculate
├── Enums/                     # SubscriptionStatus, OpportunityStatus, FundingType
├── Filament/
│   ├── Pages/                 # Custom Filament pages
│   ├── Resources/             # UserResource, StartupResource, OpportunityResource
│   └── Widgets/                # StatsOverviewWidget, RegistrationsChartWidget
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              # AuthController, GoogleAuthController, LinkedInAuthController
│   │   └── Api/               # OpportunityController
│   ├── Middleware/            # CheckSubscriptionAccess
│   └── Requests/              # StoreOpportunityRequest, UploadDocumentRequest
├── Jobs/                      # ProcessScrapedEntryJob
├── Models/                    # User, Opportunity, Startup, Subscription, etc.
├── Policies/                  # OpportunityPolicy, DocumentPolicy
├── Services/
│   ├── AI/                    # OpportunityExtractor
│   ├── Scraping/              # ScraperManager, AbstractScraper, Strategies/
│   └── OpportunityMatchingService
└── Traits/                   # HasUuid
```

### Flux de données

```
[Sources web] → ScraperManager → scraped_entries
                                    ↓
                            ProcessScrapedEntryJob
                                    ↓
                            OpportunityExtractor (AI)
                                    ↓
                            Opportunity (status: pending)
                                    ↓
                            OpportunityMatchingService
                                    ↓
                            opportunity_matches
```

---

## 📡 API

### Authentification

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/auth/register` | Inscription |
| `POST` | `/api/auth/login` | Connexion (email/password) |
| `GET` | `/api/auth/google` | Redirection OAuth Google |
| `GET` | `/api/auth/google/callback` | Callback Google |
| `GET` | `/api/auth/linkedin` | Redirection OAuth LinkedIn |
| `GET` | `/api/auth/linkedin/callback` | Callback LinkedIn |
| `POST` | `/api/auth/logout` | Déconnexion *(auth)* |
| `POST` | `/api/auth/refresh` | Rafraîchir le token *(auth)* |
| `GET` | `/api/auth/me` | Utilisateur connecté *(auth)* |

### Opportunités

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/opportunities` | Liste paginée *(auth)* |
| `GET` | `/api/opportunities/{id}` | Détail *(auth, policy)* |
| `POST` | `/api/opportunities` | Créer *(auth, manage_opportunities)* |
| `PUT` | `/api/opportunities/{id}` | Modifier *(auth, manage_opportunities)* |
| `DELETE` | `/api/opportunities/{id}` | Supprimer *(auth, manage_opportunities)* |

### Startups

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/startups` | Liste des startups de l'utilisateur *(auth)* |
| `POST` | `/api/startups` | Créer une startup *(auth)* |
| `GET` | `/api/startups/{id}` | Détail *(auth, policy)* |
| `PUT` | `/api/startups/{id}` | Modifier *(auth, policy)* |
| `DELETE` | `/api/startups/{id}` | Supprimer *(auth, policy)* |

### Abonnements

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/subscriptions` | Liste des plans disponibles *(auth)* |
| `GET` | `/api/subscriptions/my` | Abonnement actif de l'utilisateur *(auth)* |
| `POST` | `/api/user-subscriptions/subscribe` | S'abonner *(auth)* |
| `POST` | `/api/user-subscriptions/cancel` | Annuler l'abonnement *(auth)* |

### Documents

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/startups/{id}/documents` | Liste des documents *(auth)* |
| `POST` | `/api/startups/{id}/documents` | Upload document *(auth, multipart)* |
| `DELETE` | `/api/startups/{id}/documents/{docId}` | Supprimer *(auth, policy)* |

### Matching

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/startups/{id}/matches` | Opportunités matchées pour la startup *(auth)* |

### Scraping (admin)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/scraping/run` | Lancer le scraping *(auth, run_scraper)* |

### Suggestions d'opportunités (communauté)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/opportunity-suggestions` | Proposer une opportunité *(public ou auth)* |

### Documentation Swagger

La documentation interactive de l'API est disponible via **Swagger UI** (L5-Swagger) :

| URL | Description |
|-----|-------------|
| `/api/swagger-ui` | Interface Swagger UI |

**Génération des docs** :
```bash
php artisan l5-swagger:generate
```

En mode `APP_DEBUG=true`, la documentation est régénérée automatiquement à chaque requête.

**Authentification** : Cliquez sur « Authorize » et saisissez `Bearer {votre_token_jwt}`.

### Headers requis (routes protégées)

```
Authorization: Bearer {access_token}
```

### Exemple de réponse login

```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

## ⌨️ Commandes Artisan

| Commande | Description |
|----------|-------------|
| `php artisan scrape:run` | Lance le scraping sur toutes les sources actives |
| `php artisan matches:recalculate` | Recalcule les scores de matching pour toutes les startups |
| `php artisan jwt:secret` | Génère la clé JWT dans `.env` |

### Planification (scheduler)

Le recalcul des matches est exécuté quotidiennement via `Schedule::command('matches:recalculate')->daily()`.

---

## 🧪 Tests

```bash
composer test
# ou
php artisan test
```

### Couverture des tests

| Fichier | Couverture |
|---------|------------|
| `AuthTest` | Register, login, logout, refresh, me |
| `SubscriptionTest` | Accès premium, expiration, unicité |
| `ScrapingTest` | Commande scrape, doublons, job processing |
| `OpportunityAccessTest` | Politique free/premium |
| `MatchingEngineTest` | Score et tri |
| `StartupControllerTest` | CRUD startups, policy |
| `OpportunityControllerTest` | CRUD opportunities, admin/startup roles |
| `SubscriptionControllerTest` | Liste plans, mon abonnement |
| `UserSubscriptionControllerTest` | Subscribe, cancel |
| `DocumentControllerTest` | Upload, list, delete |
| `MatchingControllerTest` | Matches par startup |
| `OpportunitySuggestionControllerTest` | Suggestions publiques/auth |
| `ScrapingControllerTest` | Trigger scrape (admin) |
| `OpportunityMatchingServiceTest` | Matching, breakdown |
| `GeminiServiceTest` | isConfigured, extract |

---

## 🔒 Sécurité

- **JWT** : Blacklist activée, tokens révocables
- **Uploads** : Validation MIME + taille (max 10 MB)
- **Policies** : Un startup ne peut pas accéder aux documents d'un autre
- **Middleware** : `subscription` pour les routes premium
- **Permissions** : `manage_opportunities`, `run_scraper`, `manage_subscriptions`

---

## 📄 Licence

MIT License. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
