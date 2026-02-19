# Schema de sortie Gemini – Extraction d'opportunités

Ce document définit la structure JSON attendue de Gemini lors de l'extraction d'opportunités de financement à partir de contenu web.

## Structure JSON requise

```json
{
  "title": "string",
  "description": "string | null",
  "funding_type": "string",
  "deadline": "string | null",
  "industry": "string | null",
  "stage": "string | null",
  "funding_min": "number | null",
  "funding_max": "number | null"
}
```

## Spécifications des champs (alignées sur la base de données)

| Champ | Type | Obligatoire | Description |
|-------|------|--------------|-------------|
| **title** | string | Oui | Titre de l'opportunité. Texte brut uniquement. Aucune entité HTML (&#8211;, &amp;, etc.), aucun tag, aucun slogan marketing. Exemple : "Global Youth Grant 2025" et non "Opportunity Desk – The one stop place for global opportunities!". |
| **description** | string \| null | Oui | Description complète : lieu, organisateur, objectif, éligibilité. 300-1500 caractères. Pour hackathons : lieu, organisateur, dates. |
| **funding_type** | string | Oui | Exactement une des valeurs : `grant`, `equity`, `debt`, `prize`, `other`. Hackathons → prize. |
| **deadline** | string \| null | Non | YYYY-MM-DD. Pour plages ("Du 1 oct 2025 au 30 avr 2026") : utiliser la date de fin → 2026-04-30. Mois français supportés. |
| **industry** | string \| null | Non | Secteur d'activité (ex. tech, santé, agriculture). |
| **stage** | string \| null | Non | Stade de la startup : `seed`, `series-a`, `growth`, etc. |
| **funding_min** | number \| null | Non | Montant minimum en valeur numérique. Convertir "10k" → 10000, "1M" → 1000000. |
| **funding_max** | number \| null | Non | Montant maximum en valeur numérique. |

## Règles de formatage

1. **Tous les champs texte** : texte brut uniquement. Décoder les entités HTML (&#8211; → –, &amp; → &, &quot; → ", etc.).
2. **title** : extraire uniquement le nom de l'opportunité, pas le nom du site ou des slogans. Si le titre contient " – " ou " | ", prendre la partie pertinente (souvent la première).
3. **Montants** : toujours des nombres. "10 000 $" → 10000, "1 million" → 1000000.
4. **Pas de markdown** : pas de blocs de code, pas d'explication, uniquement l'objet JSON.

## Exemples de sortie valide

**Hackathon / événement :**
```json
{
  "title": "GOVATHON 2025",
  "description": "Dakar. Organized by Ministry of Communication, Telecommunications and Digital. Senegal's public service reform hackathon. Oct 1 2025 - Apr 30 2026. Digitalization of public services.",
  "funding_type": "prize",
  "deadline": "2026-04-30",
  "industry": "government",
  "stage": null,
  "funding_min": null,
  "funding_max": null
}
```

**Subvention :**
```json
{
  "title": "Grants, Resources for Sustainability",
  "description": "Funding opportunities for NGOs working on sustainability projects.",
  "funding_type": "grant",
  "deadline": "2025-03-15",
  "industry": "environment",
  "stage": null,
  "funding_min": 10000,
  "funding_max": 100000
}
```
