# Manuel d'utilisation — Immos GIMTEL
## Système de Gestion des Immobilisations

**Version :** 1.0.0
**Date :** Mai 2026
**Organisme :** GIMTEL
**Référence fiscale :** Code Général des Impôts Mauritanie 2023 (Art. 25, point 9)

---

## Table des matières

1. [Présentation générale](#1-présentation-générale)
2. [Connexion et accès](#2-connexion-et-accès)
3. [Tableau de bord](#3-tableau-de-bord)
4. [Gestion des immobilisations](#4-gestion-des-immobilisations)
5. [Amortissements](#5-amortissements)
6. [Transferts](#6-transferts)
7. [Inventaires](#7-inventaires)
8. [Application PWA de scan terrain](#8-application-pwa-de-scan-terrain)
9. [Paramètres](#9-paramètres)
10. [Gestion des utilisateurs](#10-gestion-des-utilisateurs)
11. [Rôles et permissions](#11-rôles-et-permissions)
12. [Codification des immobilisations](#12-codification-des-immobilisations)
13. [Questions fréquentes](#13-questions-fréquentes)

---

## 1. Présentation générale

**Immos GIMTEL** est une application web de gestion du patrimoine immobilisé de GIMTEL. Elle permet de :

- Recenser et suivre l'ensemble des biens de l'organisation
- Calculer automatiquement les amortissements conformément au CGI Mauritanie
- Réaliser des inventaires physiques assistés par QR code / code-barres
- Générer des étiquettes et des rapports d'inventaire
- Tracer l'historique des transferts de biens entre localisations

L'application est accessible depuis un navigateur web à l'adresse fournie par l'administrateur système.

---

## 2. Connexion et accès

### 2.1 Se connecter

1. Ouvrir le navigateur et saisir l'adresse de l'application
2. Sur la page de connexion, saisir :
   - **Identifiant** : votre nom d'utilisateur
   - **Mot de passe** : votre mot de passe
3. Cliquer sur **Se connecter**

> La session expire automatiquement après une période d'inactivité. Un message d'avertissement s'affiche avant l'expiration.

### 2.2 Modifier son mot de passe

1. Cliquer sur son nom en haut à droite
2. Sélectionner **Profil**
3. Renseigner l'ancien mot de passe puis le nouveau (deux fois)
4. Cliquer sur **Enregistrer**

### 2.3 Se déconnecter

Cliquer sur son nom en haut à droite puis sur **Déconnexion**.

---

## 3. Tableau de bord

Le tableau de bord est la page d'accueil après connexion. Il présente une vue synthétique du patrimoine :

| Indicateur | Description |
|---|---|
| **Total des immobilisations** | Nombre de biens enregistrés dans le système |
| **Valeur totale d'acquisition** | Somme des valeurs d'achat en MRU |
| **Valeur nette comptable (VNC)** | Valeur résiduelle après déduction des amortissements |
| **Biens amortissables** | Nombre de biens éligibles à l'amortissement (valeur ≥ 50 000 MRU) |
| **Inventaire en cours** | Statut et progression de l'inventaire actif |

Des graphiques de répartition par catégorie et par localisation sont également affichés.

---

## 4. Gestion des immobilisations

### 4.1 Consulter la liste des biens

Menu **Immobilisations → Liste**

La liste affiche tous les biens avec leurs informations principales. Des filtres permettent de restreindre l'affichage :

- Recherche par désignation, code ou numéro d'ordre
- Filtre par catégorie CGI
- Filtre par localisation
- Filtre par état (Neuf / Bon état / Défectueux)
- Filtre par source de financement

Il est possible de trier chaque colonne en cliquant sur son en-tête.

### 4.2 Ajouter un bien

Menu **Immobilisations → Ajouter**

Renseigner les champs suivants :

| Champ | Obligatoire | Description |
|---|---|---|
| **Désignation** | Oui | Sélectionner dans la liste (ex : Ordinateur fixe, Bureau) |
| **Catégorie** | Oui | Renseignée automatiquement selon la désignation |
| **État** | Oui | Neuf / Bon état / Défectueux (Bon état par défaut) |
| **Localisation** | Oui | Site géographique du bien |
| **Affectation** | Oui | Direction ou service utilisateur |
| **Emplacement** | Oui | Salle ou bureau précis |
| **Nature juridique** | Oui | Propriété / Don / Transfert (Propriété par défaut) |
| **Source de financement** | Oui | Fonds propres / Don / Transfert (Fonds propres par défaut) |
| **Année d'acquisition** | Non | Année d'achat (année en cours par défaut) |
| **Valeur d'acquisition (MRU)** | Non | Montant d'achat. Les biens < 50 000 MRU sont comptabilisés en charge |
| **Date de mise en service** | Non | Date à partir de laquelle l'amortissement commence (prorata temporis) |
| **Quantité** | Oui | Nombre d'exemplaires identiques à créer (chacun reçoit un numéro unique) |

> **Gain de temps :** les champs État, Nature juridique et Source de financement sont pré-remplis avec les valeurs les plus fréquentes. Ne les modifier que si le bien est une exception.

Cliquer sur **Enregistrer** pour valider. Un code d'inventaire unique est généré automatiquement.

### 4.3 Modifier un bien

1. Dans la liste, cliquer sur l'icône de modification (crayon) à droite du bien
2. Modifier les champs souhaités
3. Cliquer sur **Modifier**

### 4.4 Consulter le détail d'un bien

Cliquer sur le nom du bien dans la liste pour accéder à sa fiche complète. La fiche affiche :

- Toutes les informations du bien
- Le **code-barres** (format Code 128) généré depuis la codification
- Le **tableau d'amortissement** complet si le bien est amortissable
- L'historique des transferts

### 4.5 Télécharger l'étiquette d'un bien

Depuis la fiche du bien, cliquer sur **Télécharger l'étiquette**. L'étiquette PDF contient :

- La désignation et le numéro d'ordre
- Le code-barres lisible par les douchettes de scan
- La localisation et l'affectation
- L'année d'acquisition et la valeur

### 4.6 Impression en masse d'étiquettes

Depuis la liste des biens :

1. Sélectionner les biens à étiqueter (cases à cocher)
2. Cliquer sur **Imprimer les étiquettes**
3. Choisir le format :
   - **Par emplacement** — regroupées par salle (21 par page A4)
   - **Par intervalle** — triées par numéro d'ordre (33 par page A4)
   - **Tous les emplacements** — fichier unique regroupé

### 4.7 Exporter les biens en Excel

Depuis la liste, cliquer sur **Exporter Excel**. Le fichier généré contient toutes les colonnes avec les filtres appliqués.

### 4.8 Supprimer un bien

La suppression est une suppression douce (soft delete) : le bien est masqué de la liste active mais conservé dans la base. Seul un administrateur peut effectuer cette action.

---

## 5. Amortissements

Menu **Immobilisations → Amortissements**

### 5.1 Conditions d'amortissabilité (CGI Mauritanie, Art. 25)

Un bien est amortissable si les trois conditions suivantes sont réunies :

1. Sa **valeur d'acquisition est ≥ 50 000 MRU**
2. Sa **date de mise en service** est renseignée
3. Sa **catégorie** dispose d'une durée et d'un taux définis

### 5.2 Catégories et taux officiels

Les taux sont ceux du tableau officiel du CGI Mauritanie 2023 (page 19) :

| Code | Catégorie | Durée | Taux |
|------|-----------|-------|------|
| FRET | Frais d'établissement | 2 ans | 50 % |
| CIND | Construction à usage industriel | 20 ans | 5 % |
| CCOM | Construction à usage commercial et d'habitation | 25 ans | 4 % |
| MTRP | Matériel de transport | 4 ans | 25 % |
| MEXP | Matériel d'exploitation | 5 ans | 20 % |
| MCEX | Matériel complexe d'exploitation | 10 ans | 10 % |
| MOUT | Matériel et outillage | 5 ans | 20 % |
| MINF | Matériel informatique | 4 ans | 25 % |
| LG02 | Logiciels informatiques (2 ans) | 2 ans | 50 % |
| LG04 | Logiciels informatiques (4 ans) | 4 ans | 25 % |
| LG08 | Logiciels informatiques (8 ans) | 8 ans | 12,5 % |
| MMOB | Matériel et mobilier de bureau | 10 ans | 10 % |
| IAAM | Installations, agencements, aménagements | 10 ans | 10 % |
| BNPO | Bateaux et navires de pêche d'occasion | 6 ans | 16,66 % |
| BNPN | Bateaux et navires de pêche neufs | 8 ans | 12,5 % |
| AAER | Avions et aéronefs civils | 20 ans | 5 % |

### 5.3 Méthode de calcul

L'application applique la **méthode linéaire** avec **prorata temporis** pour la première année :

- Dotation annuelle = Valeur d'acquisition × Taux
- Année 1 : Dotation annuelle × (jours d'utilisation dans l'année / 365)
- Une année complémentaire est ajoutée automatiquement si un prorata a été appliqué en année 1

### 5.4 Lire le tableau d'amortissement

Depuis la fiche d'un bien amortissable, le tableau indique pour chaque exercice :

- La **dotation** de l'année
- Le **cumul** des amortissements
- La **Valeur Nette Comptable (VNC)** en fin d'exercice

Un indicateur visuel montre le pourcentage déjà amorti et les années restantes.

---

## 6. Transferts

### 6.1 Transférer un bien

Menu **Immobilisations → Transfert**

1. Rechercher et sélectionner le bien à transférer
2. Choisir la nouvelle localisation, affectation et emplacement de destination
3. Ajouter une observation (motif du transfert)
4. Cliquer sur **Confirmer le transfert**

Le bien est immédiatement mis à jour dans sa nouvelle localisation. L'ancienne localisation est conservée dans l'historique.

### 6.2 Consulter l'historique des transferts

Menu **Immobilisations → Historique Transferts**

L'historique liste tous les mouvements passés avec la date, l'origine, la destination et l'agent ayant effectué le transfert.

---

## 7. Inventaires

### 7.1 Principe

Un inventaire physique permet de vérifier la présence et l'état réel de tous les biens sur le terrain. Il est organisé par localisation et peut être conduit par plusieurs agents simultanément via une application mobile de scan QR code / code-barres.

Un seul inventaire peut être actif à la fois dans le système.

### 7.2 Créer et démarrer un inventaire

Menu **Inventaires → Nouveau**

La création se fait en 3 étapes guidées :

**Étape 1 — Paramètres généraux**
- Sélectionner l'**année** de référence (généralement N-1)
- Renseigner la **date de début**
- Ajouter une observation optionnelle

**Étape 2 — Sélection des localisations**
- Cocher les localisations à inventorier
- Le nombre de biens attendus par localisation est affiché automatiquement
- Utiliser **Tout sélectionner** pour inclure tous les sites d'un coup

**Étape 3 — Assignation des agents**
- Assigner un ou plusieurs agents à chaque localisation
- L'assignation globale permet d'affecter un agent à toutes les localisations sélectionnées en une seule action

Cliquer sur **Démarrer l'inventaire** pour lancer l'opération.

### 7.3 Suivre la progression

Menu **Inventaires → [nom de l'inventaire]**

Le tableau de bord de l'inventaire affiche en temps réel :

- La **progression globale** (% de localisations terminées)
- Le **taux de conformité** (% de biens présents)
- La liste des localisations avec leur statut : En attente / En cours / Terminé
- Les statistiques par statut de scan

### 7.4 Statuts d'un scan

Chaque bien scanné sur le terrain reçoit l'un des statuts suivants :

| Statut | Signification |
|---|---|
| **Présent** | Le bien est à son emplacement attendu |
| **Déplacé** | Le bien a été trouvé dans une localisation différente de celle enregistrée |
| **Absent** | Le bien n'a pas été trouvé lors du passage |
| **Détérioré** | Le bien est endommagé ou hors service |

### 7.5 Rapport d'inventaire

Depuis la page de l'inventaire, cliquer sur **Rapport** pour accéder au rapport détaillé.

Deux exports sont disponibles :
- **Export PDF** — rapport formel paginé, prêt à imprimer et à archiver
- **Export Excel** — données brutes structurées en plusieurs onglets :
  - Synthèse globale
  - Biens présents
  - Biens absents
  - Biens déplacés
  - Biens non scannés
  - Mouvements détectés
  - Performance des agents par localisation

### 7.6 Clôturer un inventaire

Depuis la page de l'inventaire, cliquer sur **Clôturer l'inventaire**.

> Cette action est **irréversible**. Elle enregistre la date de fin et fige définitivement les résultats. Seul un administrateur peut clôturer un inventaire.

---

## 8. Application PWA de scan terrain

### 8.1 Présentation

L'application PWA (*Progressive Web App*) de scan est une application mobile accessible depuis le navigateur du téléphone ou de la tablette des agents de terrain. Elle est conçue pour fonctionner **sans installation** — il suffit d'ouvrir l'URL dans un navigateur mobile et, optionnellement, d'ajouter le raccourci à l'écran d'accueil.

Elle est complémentaire à l'application web principale : pendant qu'un administrateur pilote l'inventaire depuis un poste fixe, les agents de terrain utilisent la PWA pour scanner les biens sur place.

**Qui peut utiliser la PWA ?**
- Les utilisateurs avec le rôle **Agent** ou **Administrateur**
- Les simples occupants n'ont pas accès à la PWA

### 8.2 Connexion à la PWA

1. Ouvrir le navigateur mobile et saisir l'URL de la PWA (fournie par l'administrateur)
2. Sur l'écran de connexion, saisir :
   - **Identifiant** : votre nom d'utilisateur (même que l'application web)
   - **Mot de passe** : votre mot de passe
3. Appuyer sur **Se connecter**

La PWA reçoit un **jeton d'authentification sécurisé** (Sanctum token) qui est conservé localement. Vous restez connecté d'une session à l'autre jusqu'à déconnexion explicite.

> Si votre compte a été créé avant la mise à jour PWA, le mot de passe est automatiquement migré au format sécurisé lors de la première connexion mobile — aucune action supplémentaire n'est requise.

### 8.3 Workflow de scan terrain

Le scan terrain se déroule en trois phases successives :

#### Phase 1 — Scanner le QR code de l'emplacement

1. À l'entrée de chaque salle ou zone, repérer l'**affiche QR code** de l'emplacement (imprimable depuis Paramètres → Emplacements dans l'application web)
2. Dans la PWA, appuyer sur **Scanner un emplacement**
3. Pointer la caméra vers le QR code de l'emplacement
4. La PWA charge automatiquement la **liste des biens attendus** dans cet emplacement ainsi que ceux déjà scannés lors de sessions précédentes

#### Phase 2 — Scanner les biens un par un

Pour chaque bien physiquement présent dans la salle :

1. Appuyer sur **Scanner un bien**
2. Pointer la caméra vers le **code-barres** apposé sur le bien (format Code 128)
3. Sélectionner le **statut** constaté :
   - **Présent** — le bien est à sa place, en bon état
   - **Détérioré** — le bien est endommagé ou hors service
4. Optionnel — si le bien est **détérioré**, prendre une **photo** directement depuis la PWA pour documenter l'état

> **Détection automatique des biens déplacés :** si le code-barres scanné appartient à un bien enregistré dans un autre emplacement, la PWA l'identifie automatiquement comme **Déplacé** sans action supplémentaire de l'agent.

Répéter l'opération pour chaque bien visible dans la salle. Les biens de la liste attendue qui n'ont pas été scannés restent en statut **Absent** jusqu'à la clôture.

#### Phase 3 — Terminer le scan de l'emplacement

Lorsque tous les biens visibles ont été scannés :

1. Appuyer sur **Terminer le scan**
2. La PWA envoie l'ensemble des résultats au serveur et marque l'emplacement comme **Terminé** dans le tableau de bord de l'inventaire

Passer ensuite à l'emplacement suivant et recommencer depuis la Phase 1.

### 8.4 Mode hors ligne (synchronisation par lot)

La PWA prend en charge les environnements avec une connectivité réseau intermittente :

- Les scans effectués **sans connexion** sont mis en file d'attente localement sur le terminal
- Dès qu'une connexion est disponible, la PWA envoie automatiquement un **lot de synchronisation** (jusqu'à 50 scans par envoi) vers le serveur
- L'agent peut vérifier l'état de synchronisation depuis le menu de la PWA

> Il est recommandé de synchroniser avant de quitter un site pour s'assurer que les données ne restent pas bloquées sur le terminal.

### 8.5 Capture photo pour les biens détériorés

Lorsqu'un bien est scanné avec le statut **Détérioré** :

1. La PWA propose automatiquement d'ouvrir la caméra
2. Prendre la photo documentant le dommage
3. La photo est envoyée au serveur et archivée dans le dossier de l'inventaire

Les photos sont accessibles depuis la fiche du bien dans l'application web, dans la section inventaire.

### 8.6 Statuts des scans

| Statut | Comment il est attribué | Signification |
|---|---|---|
| **Présent** | Sélectionné manuellement par l'agent | Le bien est à son emplacement enregistré, en état normal |
| **Déplacé** | Détecté automatiquement par la PWA | Le code-barres a été trouvé dans un emplacement différent de celui enregistré |
| **Détérioré** | Sélectionné manuellement par l'agent | Le bien est endommagé, une photo peut être jointe |
| **Absent** | Attribué automatiquement à la clôture | Le bien figurait dans la liste attendue mais n'a pas été scanné |

### 8.7 Suivi en temps réel depuis l'application web

Pendant que les agents scannent sur le terrain, l'administrateur peut suivre la progression en direct :

- Menu **Inventaires → [nom de l'inventaire]**
- Le tableau de bord se met à jour à chaque scan reçu du serveur
- La colonne **Statut** de chaque localisation passe de *En attente* → *En cours* → *Terminé* au fur et à mesure
- Les statistiques (présents, absents, déplacés, détériorés) sont recalculées en continu

### 8.8 Bonnes pratiques terrain

| Recommandation | Pourquoi |
|---|---|
| Toujours scanner le QR emplacement avant les biens | Garantit que les scans sont rattachés au bon emplacement |
| Synchroniser avant de quitter un site | Évite la perte de données si le terminal est éteint |
| Photographier systématiquement les biens détériorés | Constitue une preuve pour la commission d'inventaire |
| Marquer les biens sans étiquette lisible | Signaler à l'administrateur pour réimpression de l'étiquette |
| Ne pas fermer la PWA en cours de scan | La file hors ligne n'est pas perdue mais vérifier la synchronisation |

---

## 9. Paramètres

Le menu **Paramètres** regroupe les référentiels de l'application. Ces données structurent l'ensemble du système et doivent être configurées avant toute saisie de biens.

### 9.1 Catégories

Menu **Paramètres → Catégories**

Les catégories sont les catégories fiscales issues du CGI Mauritanie. Elles déterminent le taux et la durée d'amortissement appliqués à chaque bien.

**Ajouter une catégorie :**
1. Cliquer sur **Ajouter une catégorie**
2. Renseigner le **code** (4 caractères, ex : MINF, MMOB)
3. Renseigner le **libellé** exact tel qu'il figure dans le CGI
4. Saisir la **durée** en années — le taux est calculé automatiquement (taux = 100 / durée)
5. Vérifier ou ajuster le **taux** si nécessaire
6. Indiquer le **type CGI** (libellé officiel de référence)
7. Cliquer sur **Enregistrer**

> Une catégorie ne peut pas être supprimée si des désignations ou des biens l'utilisent.

### 9.2 Désignations

Menu **Paramètres → Désignations**

Les désignations sont les types de biens répertoriés (Bureau, Ordinateur fixe, Véhicule 4x4...). Chaque désignation est obligatoirement rattachée à une catégorie CGI.

**Ajouter une désignation :**
1. Cliquer sur **Ajouter une désignation**
2. Saisir le **nom** du bien
3. Sélectionner la **catégorie** correspondante
4. Renseigner un **code abrégé** ou utiliser le bouton **Auto** pour le générer automatiquement
5. Cliquer sur **Enregistrer**

> Lorsqu'une désignation est sélectionnée dans le formulaire d'ajout d'un bien, la catégorie est renseignée automatiquement.

### 9.3 Localisations

Menu **Paramètres → Localisations**

Les localisations représentent les sites géographiques de l'organisation (bâtiments, campus, antennes régionales).

Pour chaque localisation, renseigner un nom et un code. Il est possible de télécharger l'**étiquette QR code** de la localisation pour l'afficher à l'entrée du site lors des inventaires.

### 9.4 Affectations

Menu **Paramètres → Affectations**

Les affectations représentent les directions, services ou départements. Chaque affectation est rattachée à une localisation.

### 9.5 Emplacements

Menu **Paramètres → Emplacements**

Les emplacements représentent les salles, bureaux ou zones précises à l'intérieur d'une affectation. C'est le niveau le plus fin de localisation d'un bien.

Chaque emplacement dispose d'un QR code imprimable qui permet aux agents de terrain d'identifier rapidement la zone à scanner lors d'un inventaire.

**Ordre de création recommandé :**
Localisation → Affectation → Emplacement

---

## 10. Gestion des utilisateurs

Menu **Utilisateurs** (administrateurs uniquement)

### 10.1 Créer un utilisateur

1. Cliquer sur **Ajouter un utilisateur**
2. Renseigner le nom complet, l'identifiant de connexion et le mot de passe initial
3. Sélectionner le **rôle** approprié (voir section 10)
4. Cliquer sur **Enregistrer**

L'utilisateur pourra modifier son mot de passe depuis son profil dès sa première connexion.

### 10.2 Modifier un utilisateur

Cliquer sur l'icône de modification (crayon) à droite de l'utilisateur dans la liste.

### 10.3 Consulter les rôles

Menu **Utilisateurs → Rôles**

Permet de consulter les permissions associées à chaque rôle défini dans l'application.

---

## 11. Rôles et permissions

L'application distingue trois niveaux d'accès :

| Fonctionnalité | Administrateur | Technicien | Occupant |
|---|:---:|:---:|:---:|
| Tableau de bord | ✓ | ✓ | ✓ |
| Consulter les biens | ✓ | ✓ | ✓ |
| Ajouter / Modifier un bien | ✓ | ✓ | — |
| Supprimer un bien | ✓ | — | — |
| Transférer un bien | ✓ | ✓ | — |
| Gérer les paramètres (catégories, désignations...) | ✓ | — | — |
| Démarrer / Clôturer un inventaire | ✓ | — | — |
| Participer à un inventaire (scan terrain) | ✓ | ✓ | — |
| Gérer les utilisateurs | ✓ | — | — |
| Exporter Excel / PDF | ✓ | ✓ | — |
| Imprimer les étiquettes | ✓ | ✓ | — |

---

## 12. Codification des immobilisations

Chaque immobilisation reçoit automatiquement un **code-barres unique** construit selon la structure suivante :

```
CodeNatJur / CodeDesignation / CodeCategorie / Année / CodeSourceFin / NumOrdre
```

**Exemple :** `PR/OFIX/MINF/2023/FP/0142`

| Segment | Valeur exemple | Signification |
|---|---|---|
| `PR` | Nature juridique | PR = Propriété |
| `OFIX` | Désignation | Ordinateur Fixe |
| `MINF` | Catégorie CGI | Matériel informatique |
| `2023` | Année | Année d'acquisition |
| `FP` | Source de financement | FP = Fonds Propres |
| `0142` | NumOrdre | Numéro séquentiel unique dans la base |

Ce code est encodé au format **Code 128** et imprimé sur l'étiquette physique du bien. Il est lisible par toute douchette de scan standard.

La richesse de cette codification permet d'identifier au premier coup d'œil, sans ouvrir aucun écran :
- Le **propriétaire juridique** du bien
- Sa **nature** et sa **catégorie fiscale**
- Son **année d'entrée** dans le patrimoine
- Sa **source de financement**

---

## 13. Questions fréquentes

**Un bien apparaît sans tableau d'amortissement, pourquoi ?**
Trois causes possibles : (1) la valeur d'acquisition est inférieure à 50 000 MRU — le bien est comptabilisé en charge et non amortissable, (2) la date de mise en service n'est pas renseignée, (3) la catégorie du bien ne dispose pas de durée d'amortissement. Modifier la fiche du bien pour corriger.

**Je ne trouve pas la désignation d'un bien dans la liste.**
La désignation n'existe pas encore dans le référentiel. Contacter l'administrateur pour l'ajouter dans Paramètres → Désignations avant de créer le bien.

**Comment corriger la catégorie d'un bien mal classé ?**
Modifier le bien (icône crayon) et changer sa désignation. La catégorie se met à jour automatiquement. Si la désignation correcte n'existe pas, la créer d'abord dans les paramètres.

**Puis-je créer plusieurs biens identiques en une seule saisie ?**
Oui. Le champ **Quantité** dans le formulaire d'ajout permet de créer N exemplaires identiques en une seule opération. Chacun reçoit un NumOrdre distinct et donc un code-barres unique.

**Que faire si je clôture un inventaire par erreur ?**
La clôture est irréversible depuis l'interface. Contacter l'administrateur système pour une intervention directe sur la base de données.

**Peut-on mener deux inventaires simultanément ?**
Non. Un seul inventaire peut être actif à la fois. Le précédent doit être clôturé avant d'en créer un nouveau.

**Le code-barres d'un bien a changé après une modification, est-ce normal ?**
Oui, si vous avez modifié la désignation, la nature juridique ou la source de financement. Le code est recalculé automatiquement. Réimprimer et recoller l'étiquette sur le bien concerné.

**Comment préparer les agents avant un inventaire terrain ?**
Imprimer les QR codes des emplacements (Paramètres → Emplacements → Télécharger QR codes) et les afficher dans chaque salle. Les agents scannent d'abord le QR code de l'emplacement puis les codes-barres de chaque bien présent.

**Peut-on exporter la liste des biens avant de faire l'inventaire ?**
Oui. Depuis Immobilisations → Liste, cliquer sur **Exporter Excel**. Ce fichier peut servir de support papier de secours si les terminaux de scan ne sont pas disponibles.

---

*Document produit par GIMTEL — Usage interne*
*Référence législative : Code Général des Impôts Mauritanie 2023, Art. 25 point 9, page 19*
