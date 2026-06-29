# Immos GIMTEL — Fonctionnalités & Apports pour la Gestion des Immobilisations

**Organisme :** GIMTEL  
**Version :** 1.0.0 — Mai 2026

---

## Pourquoi une application de gestion des immobilisations ?

La gestion manuelle du patrimoine (tableaux Excel, classeurs papier) expose l'organisation à des risques concrets : biens non localisés, amortissements calculés à la main avec des erreurs, inventaires physiques longs et peu fiables. **Immos GIMTEL** répond à ces problèmes avec un système centralisé, conforme au CGI Mauritanie 2023, exploitable depuis un bureau comme depuis un téléphone sur le terrain.

---

## Vue d'ensemble des modules

```
┌─────────────────────────────────────────────────────────────┐
│                      Immos GIMTEL                           │
├──────────────┬──────────────┬──────────────┬────────────────┤
│   Patrimoine │ Amortissements│  Inventaires │  Administration│
│   & Biens    │   (CGI MR)   │  + PWA scan  │  & Paramètres  │
└──────────────┴──────────────┴──────────────┴────────────────┘
```

---

## 1. Recensement et suivi du patrimoine

### Ce que permet le module

- Enregistrer chaque bien avec toutes ses caractéristiques : désignation, catégorie fiscale, localisation précise (site → service → salle), état, nature juridique, source de financement, valeur et date d'acquisition.
- Créer **N exemplaires identiques en une seule saisie** — chacun reçoit automatiquement un numéro d'ordre unique.
- Rechercher, filtrer et trier la liste par n'importe quel critère (catégorie, localisation, état, source de financement).
- Accéder à la **fiche complète** d'un bien : code-barres, tableau d'amortissement, historique des transferts.

### Apport pour la gestion des immobilisations

| Avant | Avec Immos GIMTEL |
|---|---|
| Liste Excel non structurée, doublons fréquents | Base unique, cohérente, filtrée en temps réel |
| Localisation des biens inconnue ou approximative | Adresse précise à 3 niveaux : site / service / salle |
| Aucune traçabilité de l'origine des biens | Nature juridique et source de financement enregistrées sur chaque fiche |

---

## 2. Codification automatique des biens

### Structure du code-barres

Chaque bien reçoit un identifiant unique généré automatiquement selon la structure :

```
NatureJuridique / Désignation / CatégorieCGI / Année / SourceFinancement / NumOrdre

Exemple : PR/OFIX/MINF/2023/FP/0142
           │    │     │    │   │   └─ N° séquentiel unique
           │    │     │    │   └───── Fonds Propres
           │    │     │    └───────── Année d'acquisition
           │    │     └────────────── Matériel Informatique (CGI)
           │    └──────────────────── Ordinateur Fixe
           └───────────────────────── Propriété
```

Ce code est encodé au format **Code 128**, lisible par toute douchette de scan standard, et imprimé sur l'étiquette physique collée sur le bien.

### Apport

Identifier un bien au premier coup d'œil sans ouvrir aucun écran — sa nature, sa catégorie fiscale, son année d'entrée et sa source de financement sont toutes encodées dans le code.

---

## 3. Étiquetage physique des biens

### Fonctionnalités

- **Étiquette individuelle** téléchargeable en PDF depuis la fiche du bien (désignation, numéro d'ordre, code-barres, localisation, valeur, année).
- **Impression en masse** : sélectionner plusieurs biens et générer un PDF regroupé, avec deux formats :
  - Par **emplacement** — 21 étiquettes par page A4, regroupées par salle
  - Par **intervalle** — 33 étiquettes par page A4, triées par numéro d'ordre

### Apport

Passer de l'inventaire papier à l'inventaire scanné sans rupture : les étiquettes Code 128 sont compatibles avec toutes les douchettes du commerce et avec la caméra des smartphones via la PWA.

---

## 4. Calcul automatique des amortissements (CGI Mauritanie)

### Règles appliquées

- Méthode **linéaire** avec **prorata temporis** pour la première année
- Seuil d'amortissabilité : **50 000 MRU** (en dessous, le bien est comptabilisé en charge)
- 16 catégories fiscales configurées conformément au **CGI Mauritanie 2023 (Art. 25)** :

| Catégorie | Durée | Taux |
|---|---|---|
| Matériel informatique (MINF) | 4 ans | 25 % |
| Matériel et mobilier de bureau (MMOB) | 10 ans | 10 % |
| Matériel de transport (MTRP) | 4 ans | 25 % |
| Matériel d'exploitation (MEXP) | 5 ans | 20 % |
| Construction usage commercial (CCOM) | 25 ans | 4 % |
| Logiciels informatiques — 2 ans (LG02) | 2 ans | 50 % |
| *(+ 10 autres catégories)* | | |

### Tableau d'amortissement par bien

Depuis la fiche de chaque bien amortissable : dotation annuelle, cumul des amortissements, **Valeur Nette Comptable (VNC)** pour chaque exercice, indicateur visuel du % amorti et des années restantes.

### Apport pour la gestion des immobilisations

| Avant | Avec Immos GIMTEL |
|---|---|
| Calculs manuels dans Excel, risque d'erreur | Calculs automatiques, conformes au CGI, sans intervention |
| VNC impossible à consulter rapidement | VNC affichée en temps réel sur chaque fiche et dans le tableau de bord |
| Prorata temporis souvent ignoré | Calculé automatiquement à partir de la date de mise en service |

---

## 5. Suivi des transferts de biens

### Fonctionnalités

- Enregistrer le déplacement d'un bien d'une localisation vers une autre en quelques clics (localisation / service / salle de destination + motif).
- Le bien est immédiatement mis à jour. L'**historique complet des transferts** est conservé avec date, origine, destination et agent responsable.

### Apport

Éliminer les "biens fantômes" : un bien signalé dans une salle mais physiquement ailleurs est retracé grâce à l'historique. La commission d'inventaire dispose d'un journal d'audit complet.

---

## 6. Inventaires physiques assistés

### Organisation de l'inventaire

1. **Créer** un inventaire : définir l'année, les localisations à couvrir, assigner les agents.
2. **Conduire** l'inventaire : les agents scannent sur le terrain via la PWA mobile (voir module suivant).
3. **Suivre** la progression en temps réel : tableau de bord avec % d'avancement, taux de conformité, statut par localisation.
4. **Clôturer** : fige définitivement les résultats, génère le rapport final.

### Statuts des biens en inventaire

| Statut | Comment attribué |
|---|---|
| **Présent** | Scanné par l'agent à l'emplacement attendu |
| **Déplacé** | Code-barres trouvé dans une autre localisation |
| **Détérioré** | Agent sélectionne le statut + photo optionnelle |
| **Absent** | Non scanné au moment de la clôture |

### Rapports d'inventaire

- **Export PDF** : rapport formel paginé, prêt à archiver ou soumettre à la commission.
- **Export Excel** : 6 onglets — synthèse globale, présents, absents, déplacés, non scannés, performance par agent.

### Apport

| Avant | Avec Immos GIMTEL |
|---|---|
| Inventaire sur papier, ressaisie manuelle des résultats | Résultats en temps réel, zéro ressaisie |
| Biens déplacés non détectés | Détection automatique lors du scan |
| Rapport produit après plusieurs jours | Rapport généré en un clic à la clôture |

---

## 7. Application PWA de scan terrain

### Principe

L'application PWA (Progressive Web App) est l'interface mobile des agents de terrain. Elle fonctionne **sans installation** dans le navigateur du téléphone ou de la tablette. Les mêmes identifiants que l'application web sont utilisés.

### Workflow de scan en 3 phases

```
Phase 1               Phase 2                Phase 3
─────────────         ──────────────────     ─────────────────
Scanner le QR    →    Scanner chaque    →    Terminer le scan
code de la salle      bien (code-barres)     de l'emplacement
                       + choisir statut      (envoi au serveur)
                       + photo si détérioré
```

**Détection automatique des biens déplacés :** si le code-barres appartient à un bien enregistré dans une autre salle, la PWA le classe automatiquement comme *Déplacé* sans action de l'agent.

### Mode hors ligne

Les scans effectués sans connexion réseau sont **mis en file d'attente** sur le terminal. Dès qu'une connexion est disponible, la PWA synchronise automatiquement les données (lots de 50 scans). Indispensable dans les bâtiments avec une couverture réseau partielle.

### Suivi en temps réel côté administrateur

Pendant que les agents scannent sur le terrain, le tableau de bord de l'inventaire se met à jour en continu — statut par localisation (*En attente → En cours → Terminé*), compteurs présents / absents / déplacés / détériorés.

### Apport

| Avant | Avec Immos GIMTEL PWA |
|---|---|
| Agents avec des listes papier, stylo, puis ressaisie | Scan direct depuis le téléphone, résultats envoyés instantanément |
| Impossible de détecter un bien déplacé sur le terrain | Détection automatique par comparaison avec la base |
| L'administrateur attend la fin de l'inventaire pour avoir des chiffres | Visibilité en temps réel depuis son bureau |
| Zones sans Wi-Fi bloquent l'opération | Mode hors ligne avec synchronisation automatique au retour en réseau |

---

## 8. Tableau de bord et indicateurs clés

Le tableau de bord présente en temps réel :

| Indicateur | Utilité |
|---|---|
| **Total des immobilisations** | Vue globale du patrimoine recensé |
| **Valeur totale d'acquisition** | Masse financière immobilisée (en MRU) |
| **Valeur Nette Comptable (VNC)** | Valeur résiduelle après amortissements |
| **Biens amortissables** | Biens éligibles selon le seuil CGI (≥ 50 000 MRU) |
| **Inventaire en cours** | Avancement de l'opération active |
| **Graphiques** | Répartition par catégorie et par localisation |

---

## 9. Gestion des accès et des rôles

Trois niveaux d'accès couvrent tous les profils de l'organisation :

| Profil | Accès |
|---|---|
| **Administrateur** | Accès complet — paramètres, utilisateurs, clôture d'inventaire, suppressions |
| **Technicien (Agent)** | Ajout et modification de biens, transferts, scan terrain, exports |
| **Occupant** | Consultation uniquement — tableau de bord et liste des biens |

---

## 10. Exports et interopérabilité

- **Excel** — liste des biens avec tous les filtres appliqués, exportable à tout moment
- **PDF étiquettes** — prêt à imprimer sur papier auto-collant
- **PDF rapport d'inventaire** — document formel pour la commission
- **Excel rapport d'inventaire** — données structurées en 6 onglets pour analyse externe

---

## Synthèse des gains pour GIMTEL

| Domaine | Gain concret |
|---|---|
| **Fiabilité des données** | Base unique, centralisée, sans doublons ni incohérences de localisation |
| **Conformité fiscale** | Amortissements calculés automatiquement selon le CGI Mauritanie 2023 |
| **Rapidité des inventaires** | Scan par code-barres sur smartphone, résultats en temps réel, rapport en un clic |
| **Traçabilité** | Historique complet des transferts, journal d'audit disponible à tout moment |
| **Mobilité** | PWA opérationnelle sans installation, même en zone avec réseau intermittent |
| **Décision** | Tableau de bord avec VNC, taux de conformité et répartition du patrimoine |

---

*Document interne GIMTEL — Juin 2026*  
*Référence législative : Code Général des Impôts Mauritanie 2023, Art. 25 point 9*
