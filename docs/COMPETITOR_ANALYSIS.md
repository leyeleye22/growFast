# Analyse comparative des plateformes de financement

> Scraping et analyse des features de F6S, Easy Grants, VC4A, Grantly vs GrowFast

**Note** : F6S utilise une bot detection qui bloque l'accès automatisé. Les infos F6S proviennent de recherches web.

---

## 1. F6S (f6s.com)

**Cible** : Startups globales  
**Accès** : Bot detection active – scraping limité

### Features identifiées (sources secondaires)
| Feature | Description |
|---------|-------------|
| **Programmes** | Accelerators, startup programs, investment funds, GAN accelerators |
| **Événements** | Startup events, contests, angel groups |
| **Deals** | AWS credits, Notion, HubSpot, MongoDB, discounts startup |
| **Corporate innovation** | Opportunités corporate |
| **Services** | R&D tax credits, EIS, SEIS, SR&ED, accounting |
| **Inscription** | Email + social (probable) – site bloqué |

### Points forts
- Large écosystème (programmes, deals, events)
- Modèle freemium avec deals partenaires

---

## 2. Easy Grants (easygrants.us)

**Cible** : Nonprofits, school districts, healthcare, local governments, public services  
**Modèle** : Consulting / service humain, pas de plateforme self-serve

### Features
| Feature | Description |
|---------|-------------|
| **Identification** | Expert identifie les financements adaptés à la mission |
| **Accompagnement** | Du premier pas à l’accompagnement complet |
| **Scheduling** | Prise de rendez-vous avec un expert |
| **Secteurs** | Nonprofits, écoles, santé, gouvernements, services publics |
| **Inscription** | Pas d’inscription classique – formulaire de contact / scheduling |

### Points forts
- Expertise humaine (1B$+ levés)
- Accompagnement personnalisé

### Différence avec GrowFast
- Easy Grants = service consulting ; GrowFast = plateforme self-serve
- Pas de matching algorithmique, pas de base d’opportunités publique

---

## 3. VC4A (vc4a.com)

**Cible** : Entrepreneurs Afrique, LATAM, marchés émergents  
**Modèle** : Plateforme communautaire + abonnement Pro

### Inscription
- **Email** : email + mot de passe (règles : 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial, 8+ caractères)
- **Social** : Comptes sociaux supportés
- **Venture profile** : Formulaire détaillé (~5 min)

### Champs du profil Venture (ventures/add)
| Champ | Type | Notes |
|-------|------|-------|
| Company name | string | |
| Tagline | string | En quelques mots |
| Founding date | date | |
| Pitch | text | Court et concis |
| Pitch video URL | url | Instagram, YouTube, Vimeo, etc. |
| Full address | address | Autocomplete |
| Phone | string | Avec indicatif pays |
| Stage | enum | Idea/Concept, Startup, Growth, Mature |
| Customer type | multi | B2B, B2B2B, B2B2C, B2C, B2G, Non-profits |
| Sectors | multi (max 3) | 50+ secteurs (Agribusiness, ICT, Fintech, etc.) |
| Countries targeting | multi | Liste pays ciblés |
| Website | url | |
| Social media | url | |

### Features
| Feature | Description |
|---------|-------------|
| **Venture profile** | Profil public, fundraising campaign |
| **Startup Academy** | Cours gratuits |
| **Mentorship** | Candidature au mentorat |
| **Programs** | Candidature aux programmes de soutien |
| **Investor network** | 1200+ investisseurs, 28k+ ventures |
| **Investor dashboard** | Recherche par deal size, stage, sector, country |
| **Alertes** | Alertes selon investment thesis |
| **Pricing** | Basic free, Lite free (3 searches/jour), Pro $39.99/mois |

---

## 4. Grantly (grantlyai.net)

**Cible** : Small businesses US (retail, startups, consultants, nonprofits)  
**Modèle** : Freemium – Free Forever + Pro $29/mo

### Inscription
- **Start Free** : Pas de carte requise
- **Business profile** : Industry, location, ownership (2 min)

### Features
| Feature | Description |
|---------|-------------|
| **AI Matching** | 1000+ grants, matching selon profil |
| **Match score** | Score % + détail (Industry ✓, Location ✓, Revenue ✓, Stage ✓) |
| **Grant Autofiller** | Upload PDF → extraction questions → réponses → autofill |
| **Ada** | Assistant IA (consultant) |
| **User-submitted grants** | Formulaire "Know a Grant? Share It" |
| **Pricing** | Free : 5 autofills/mois, Pro : illimité + export + reminders |

### Champs de matching
- Industry
- Location (state/region)
- Revenue size
- Business stage
- Ownership type (minority, women, veteran)

### Transparence
- Chaque match affiche les critères remplis
- Pas de "black box"

---

## 5. Synthèse comparative

| Feature | F6S | Easy Grants | VC4A | Grantly | **GrowFast** |
|---------|-----|-------------|------|---------|--------------|
| Matching algorithmique | ? | ❌ | ✅ | ✅ | ✅ |
| Score + breakdown | ? | ❌ | ? | ✅ | ✅ (API) |
| Profil détaillé | ? | ❌ | ✅ | ✅ | Partiel |
| Tagline | ? | ❌ | ✅ | ❌ | ❌ |
| Founding date | ? | ❌ | ✅ | ❌ | ❌ |
| Pitch video | ? | ❌ | ✅ | ❌ | ❌ |
| Customer type (B2B/B2C) | ? | ❌ | ✅ | ❌ | ❌ |
| Revenue/size | ? | ❌ | ❌ | ✅ | ❌ |
| Ownership (minority, etc.) | ? | ❌ | ❌ | ✅ | ❌ |
| Multi-pays cible | ? | ❌ | ✅ | ❌ | ❌ |
| Grant autofiller | ❌ | ❌ | ❌ | ✅ | ❌ |
| User-submitted grants | ? | ❌ | ❌ | ✅ | ❌ |
| OAuth (Google/LinkedIn) | ? | ❌ | ✅ | ❌ | ✅ |
| Subscriptions | Deals | Service | $39.99 | $29 | Plans |
| Cible | Startups | Nonprofits | Afrique/LATAM | US SMB | Startups |

---

## 6. Recommandations pour GrowFast

### Priorité haute (alignement concurrence)
1. **Tagline** sur startup
2. **Founding date** sur startup
3. **Pitch video URL** sur startup
4. **Phone** sur startup
5. **Social media** sur startup
6. **Customer type** (B2B, B2C, etc.) – enum ou multi-select
7. **Revenue min/max** – pour matching funding
8. **Ownership type** – minority, women, veteran (optionnel)
9. **User-submitted opportunities** – formulaire communauté

### Priorité moyenne
10. **Eligibility criteria** (JSON) sur opportunity – pour transparence "why match"
11. **Multi-countries** sur startup (pays ciblés)

### Priorité basse
12. Grant autofiller (PDF) – feature lourde, type Grantly
13. Deals/discounts – type F6S
