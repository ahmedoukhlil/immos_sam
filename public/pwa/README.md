# Application PWA Scanner d'Inventaire

Application Progressive Web App pour scanner les QR codes des biens lors des inventaires.

## Structure des fichiers

```
public/pwa/
├── index.html          # Page principale de l'application
├── manifest.json       # Configuration PWA
├── service-worker.js   # Service Worker (cache, offline, sync)
├── app.js             # Logique JavaScript principale
├── styles.css         # Styles Tailwind pour l'application
├── README.md          # Documentation
└── assets/
    ├── icons/         # Icônes PWA (à créer)
    └── sounds/        # Sons de feedback (à créer)
```

## Fichiers à créer

### Icônes PWA

Vous devez créer les icônes suivantes dans `assets/icons/` :

- `icon-72x72.png` (72x72 pixels)
- `icon-96x96.png` (96x96 pixels)
- `icon-128x128.png` (128x128 pixels)
- `icon-144x144.png` (144x144 pixels)
- `icon-152x152.png` (152x152 pixels)
- `icon-192x192.png` (192x192 pixels) - **Requis**
- `icon-384x384.png` (384x384 pixels)
- `icon-512x512.png` (512x512 pixels) - **Requis**

**Recommandations pour les icônes :**
- Format PNG avec transparence
- Design simple et reconnaissable
- Couleur principale : #4F46E5 (indigo)
- Fond transparent ou blanc
- Icône de scanner QR code ou logo de l'application

**Outils pour créer les icônes :**
- [PWA Asset Generator](https://github.com/onderceylan/pwa-asset-generator)
- [RealFaviconGenerator](https://realfavicongenerator.net/)
- [Favicon.io](https://favicon.io/)

### Sons de feedback

Vous devez créer les fichiers audio suivants dans `assets/sounds/` :

- `beep-success.mp3` - Son de succès (scan réussi)
- `beep-error.mp3` - Son d'erreur (scan échoué)

**Recommandations pour les sons :**
- Format MP3
- Durée courte (0.5 à 1 seconde)
- Volume modéré
- Fréquence agréable (éviter les sons stridents)

**Ressources gratuites :**
- [Freesound.org](https://freesound.org/)
- [Zapsplat](https://www.zapsplat.com/)
- [Mixkit](https://mixkit.co/free-sound-effects/)

## Installation et utilisation

### 1. Créer les icônes et sons

Suivez les instructions ci-dessus pour créer les fichiers manquants.

### 2. Accéder à l'application

Ouvrez votre navigateur et accédez à :
```
http://localhost:8000/pwa/
```

### 3. Installer l'application

Sur mobile :
1. Ouvrir l'application dans le navigateur
2. Cliquer sur le menu du navigateur (⋮)
3. Sélectionner "Ajouter à l'écran d'accueil" ou "Installer l'application"

Sur desktop (Chrome/Edge) :
1. Une icône d'installation apparaîtra dans la barre d'adresse
2. Cliquer sur "Installer"

### 4. Utilisation

1. **Sélectionner un inventaire** : Choisir l'inventaire en cours dans le menu déroulant
2. **Démarrer le scan** : Cliquer sur "Démarrer le scan"
3. **Autoriser la caméra** : Accepter l'accès à la caméra
4. **Scanner** : Pointer la caméra vers le QR code du bien
5. **Résultat** : Le scan est automatiquement enregistré (en ligne) ou stocké localement (hors ligne)

## Fonctionnalités

### Mode hors ligne
- Les scans sont stockés localement dans IndexedDB
- Synchronisation automatique lorsque la connexion revient
- Synchronisation manuelle via le bouton "Synchroniser"

### Service Worker
- Cache des assets pour fonctionnement offline
- Synchronisation en arrière-plan
- Mise à jour automatique

### Statistiques
- Total de scans effectués
- Scans réussis
- Scans échoués
- Scans en attente de synchronisation

## API requise

L'application nécessite les endpoints API suivants :

### GET /api/inventaires/en-cours
Retourne la liste des inventaires en cours.

**Réponse :**
```json
[
  {
    "id": 1,
    "annee": 2025,
    "statut": "en_cours"
  }
]
```

### POST /api/inventaires/{id}/scan
Enregistre un scan de bien.

**Body :**
```json
{
  "bien_id": 123,
  "statut_scan": "present",
  "localisation_reelle_id": null,
  "etat_constate": null,
  "commentaire": "",
  "date_scan": "2025-11-24T14:00:00Z"
}
```

## Configuration

### Modifier l'URL de l'API

Dans `app.js`, modifier la constante `CONFIG.API_BASE_URL` :
```javascript
const CONFIG = {
  API_BASE_URL: 'https://votre-domaine.com',
  // ...
};
```

### Personnaliser les couleurs

Dans `styles.css`, modifier les variables CSS :
```css
:root {
  --primary-color: #4F46E5;
  --success-color: #10B981;
  --error-color: #EF4444;
  // ...
}
```

## Compatibilité

- ✅ Chrome/Edge (Android, Desktop)
- ✅ Safari (iOS 11.3+)
- ✅ Firefox (Android, Desktop)
- ✅ Samsung Internet

## Support

Pour toute question ou problème, consultez la documentation Laravel ou contactez l'équipe de développement.

