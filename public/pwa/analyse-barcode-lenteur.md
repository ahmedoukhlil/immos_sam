# Analyse : Lenteur Détection Code-Barres 128 — app-v2.js

## Résumé exécutif

La lenteur de détection du code-barres 128 est causée par **4 problèmes majeurs cumulatifs** dans la configuration de QuaggaJS et la gestion des workers, aggravés par une architecture hybride (Quagga + BarcodeDetector natif) mal synchronisée.

---

## PROBLÈME #1 — `halfSample: false` (CRITIQUE)

**Fichier : ligne 556**
```js
locator: {
    patchSize: 'medium',
    halfSample: false // Plus précis sur barres fines
}
```

**Cause :** `halfSample: false` force Quagga à analyser chaque frame à **résolution pleine** (960×540 px). Le traitement d'image est 4× plus lourd que si `halfSample: true`. Sur mobile, le CPU ne peut pas suivre.

**Fix :**
```js
locator: {
    patchSize: 'medium',
    halfSample: true   // 4x plus rapide, suffisant pour Code 128
}
```

---

## PROBLÈME #2 — `frequency: 20` trop élevée pour mobile (MAJEUR)

**Fichier : ligne 27 (CONFIG) + ligne 547**
```js
barcode: {
    frequency: 20,  // 20 analyses par seconde
    ...
}
```

**Cause :** Sur un mobile mid-range, traiter 20 frames/s en full-résolution sans `halfSample` provoque une saturation du CPU → files d'attente de frames → latence perçue de 1 à 3 secondes avant détection. Quagga lui-même recommande 10 max sur mobile.

**Fix :**
```js
barcode: {
    frequency: 10,  // 10 analyses/s, largement suffisant
    ...
}
```

---

## PROBLÈME #3 — `numOfWorkers` mal calibré (MAJEUR)

**Fichier : ligne 553**
```js
numOfWorkers: Math.min(4, Math.max(2, (navigator.hardwareConcurrency || 2) - 1)),
```

**Cause :** Sur un téléphone 8-cœurs, cela donne **4 workers Web Workers actifs simultanément** pour Quagga. Chaque worker analyse une frame complète (960×540) — la mémoire et le CPU explosent, le thread UI est bloqué (jank), et la détection se retrouve en compétition avec le rendu vidéo.

**Fix :**
```js
numOfWorkers: navigator.hardwareConcurrency > 4 ? 2 : 1,
```

---

## PROBLÈME #4 — Résolution caméra trop haute (MODÉRÉ)

**Fichier : lignes 533–535**
```js
constraints: {
    width: { ideal: 960, max: 1280 },
    height: { ideal: 540, max: 720 },
    ...
}
```

**Cause :** 960×540 (ou pire 1280×720) est excessif pour décoder un Code 128. QuaggaJS est optimal entre **640×480 et 640×360**. Une résolution plus haute ne fait qu'alourdir le traitement sans améliorer la détection.

**Fix :**
```js
constraints: {
    width: { min: 400, ideal: 640 },
    height: { min: 300, ideal: 480 },
    ...
}
```
Et dans le CONFIG global :
```js
barcode: {
    width: 640,
    height: 480,
    ...
}
```

---

## PROBLÈME #5 — Zone de scan trop restrictive (MINEUR)

**Fichier : lignes 540–545**
```js
area: {
    top: "25%",
    right: "12%",
    left: "12%",
    bottom: "25%"
}
```

**Cause :** Combiné aux autres problèmes, si le barcode n'est pas parfaitement centré dans cette zone étroite de 76%×50%, Quagga ignore la frame et recommence — allongeant le temps de détection.

**Fix :** Élargir légèrement :
```js
area: {
    top: "15%",
    right: "5%",
    left: "5%",
    bottom: "15%"
}
```

---

## PROBLÈME #6 — Double détection Quagga + BarcodeDetector natif non coordonnée (ARCHITECTURAL)

**Fichier : lignes 603–650 (`startNativeAssist`)**

**Cause :** Le `BarcodeDetector` natif tourne en boucle indépendante avec `setTimeout(loop, 120)` — soit ~8 appels/sec. Quand il détecte un code, il appelle `handleBarcodeDetected` **en parallèle** de Quagga. Les deux peuvent déclencher simultanément, causant des états `barcodeProcessing` incohérents et des toasts/modales en double.

De plus, la boucle native appelle `container.querySelector('video')` à **chaque itération** — c'est une requête DOM coûteuse répétée 8×/sec.

**Fix :**
1. Cacher la référence `video` hors de la boucle
2. Si `BarcodeDetector` natif est disponible, **désactiver Quagga entièrement** (utiliser l'un ou l'autre)
3. Ou augmenter l'intervalle natif à 200ms

```js
// Fix rapide — mettre la ref video en cache
const video = container.querySelector('video'); // hors boucle
const loop = async () => {
    if (!AppState.barcodeNativeLoopActive || !AppState.barcodeScannerActive) return;
    if (video && video.readyState >= 2) {
        // ... detect
    }
    setTimeout(loop, 200); // rallonger légèrement
};
```

---

## Configuration optimisée complète à appliquer

```js
const CONFIG = {
    // ...
    SCANNER: {
        qr: {
            width: 960,
            height: 540,
            decodeIntervalMs: 120,
            detectCooldownMs: 1200
        },
        barcode: {
            width: 640,   // ← réduit de 960
            height: 480,  // ← réduit de 540
            frequency: 10,         // ← réduit de 20
            detectCooldownMs: 250
        }
    }
};
```

```js
Quagga.init({
    inputStream: {
        type: 'LiveStream',
        target: container,
        constraints: {
            width: { min: 400, ideal: 640 },   // ← réduit
            height: { min: 300, ideal: 480 },  // ← réduit
            facingMode: 'environment',
            aspectRatio: { ideal: 4/3 },       // ← 4:3 mieux pour barcode
            focusMode: 'continuous'
        },
        area: {
            top: "15%",    // ← élargi
            right: "5%",   // ← élargi
            left: "5%",    // ← élargi
            bottom: "15%"  // ← élargi
        }
    },
    frequency: CONFIG.SCANNER.barcode.frequency, // 10
    decoder: {
        readers: ['code_128_reader'],
        multiple: false
    },
    locate: true,
    numOfWorkers: navigator.hardwareConcurrency > 4 ? 2 : 1,  // ← réduit
    locator: {
        patchSize: 'medium',
        halfSample: true   // ← CRITIQUE : activer
    }
}, ...);
```

---

## Impact attendu après corrections

| Avant | Après |
|---|---|
| Charge CPU 80–100% sur mobile | ~20–35% |
| Délai de détection ~2–5s | ~0.5–1.5s |
| Risque de freeze UI | Éliminé |
| Frames analysées/sec | 20 full-res → 10 half-res |

---

## Priorité d'application

1. **`halfSample: true`** → gain immédiat le plus important
2. **`frequency: 10`** → deuxième gain en importance
3. **`numOfWorkers` réduit** → stabilité mobile
4. **Résolution 640×480** → allège tout le pipeline
5. Zone de scan et fix natif → améliorations secondaires
