## Tables de la Base de Données

### 1. Table `users` - Utilisateurs

Gestion des utilisateurs et authentification.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idUser` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique de l'utilisateur |
| `users` | VARCHAR | NOT NULL, UNIQUE | Nom d'utilisateur |
| `mdp` | VARCHAR | NOT NULL | Mot de passe (non hashé) |
| `role` | VARCHAR | NOT NULL | Rôle : `superuser`, `immobilisation`, `stock` |

**Relations :** Aucune

**Modèle Laravel suggéré :**
```php
class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'idUser';
    public $timestamps = false;
    
    protected $fillable = ['users', 'mdp', 'role'];
}
```

---

### 2. Table `gesimmo` - Immobilisations

Table principale pour les immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `NumOrdre` | INT | PRIMARY KEY, AUTO_INCREMENT | Numéro d'ordre unique |
| `idDesignation` | INT | FOREIGN KEY → `designation.id` | Référence à la désignation |
| `idCategorie` | INT | FOREIGN KEY → `categorie.idCategorie` | Référence à la catégorie |
| `idEtat` | INT | FOREIGN KEY → `etat.idEtat` | Référence à l'état |
| `idEmplacement` | INT | FOREIGN KEY → `emplacement.idEmplacement` | Référence à l'emplacement |
| `idNatJur` | INT | FOREIGN KEY → `naturejurdique.idNatJur` | Référence à la nature juridique |
| `idSF` | INT | FOREIGN KEY → `sourcefinancement.idSF` | Référence à la source de financement |
| `DateAcquisition` | DATE | NULL | Date d'acquisition |
| `Observations` | TEXT | NULL | Observations/Remarques |

**Relations :**
- `belongsTo(Designation::class, 'idDesignation', 'id')`
- `belongsTo(Categorie::class, 'idCategorie', 'idCategorie')`
- `belongsTo(Etat::class, 'idEtat', 'idEtat')`
- `belongsTo(Emplacement::class, 'idEmplacement', 'idEmplacement')`
- `belongsTo(NatureJuridique::class, 'idNatJur', 'idNatJur')`
- `belongsTo(SourceFinancement::class, 'idSF', 'idSF')`
- `hasOne(Code::class, 'idGesimmo', 'NumOrdre')`

**Modèle Laravel suggéré :**
```php
class Gesimmo extends Model
{
    protected $table = 'gesimmo';
    protected $primaryKey = 'NumOrdre';
    public $timestamps = false;
    
    protected $fillable = [
        'idDesignation', 'idCategorie', 'idEtat', 
        'idEmplacement', 'idNatJur', 'idSF', 
        'DateAcquisition', 'Observations'
    ];
    
    protected $casts = [
        'DateAcquisition' => 'date',
    ];
    
    public function designation() {
        return $this->belongsTo(Designation::class, 'idDesignation', 'id');
    }
    
    public function categorie() {
        return $this->belongsTo(Categorie::class, 'idCategorie', 'idCategorie');
    }
    
    public function etat() {
        return $this->belongsTo(Etat::class, 'idEtat', 'idEtat');
    }
    
    public function emplacement() {
        return $this->belongsTo(Emplacement::class, 'idEmplacement', 'idEmplacement');
    }
    
    public function natureJuridique() {
        return $this->belongsTo(NatureJuridique::class, 'idNatJur', 'idNatJur');
    }
    
    public function sourceFinancement() {
        return $this->belongsTo(SourceFinancement::class, 'idSF', 'idSF');
    }
    
    public function code() {
        return $this->hasOne(Code::class, 'idGesimmo', 'NumOrdre');
    }
    
    // Accessor pour générer le code d'immobilisation
    public function getCodeAttribute() {
        return sprintf(
            '%s/%s/%s/%s/%s/%s',
            $this->natureJuridique->CodeNatJur ?? '',
            $this->designation->CodeDesignation ?? '',
            $this->categorie->CodeCategorie ?? '',
            $this->DateAcquisition ? $this->DateAcquisition->format('Y') : '',
            $this->sourceFinancement->CodeSourceFin ?? '',
            $this->NumOrdre
        );
    }
}
```

---

### 3. Table `designation` - Désignations

Désignations des immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `designation` | VARCHAR | NOT NULL | Nom de la désignation |
| `CodeDesignation` | VARCHAR | NULL | Code de la désignation |
| `idCat` | INT | FOREIGN KEY → `categorie.idCategorie` | Référence à la catégorie |

**Relations :**
- `belongsTo(Categorie::class, 'idCat', 'idCategorie')`
- `hasMany(Gesimmo::class, 'idDesignation', 'id')`

**Modèle Laravel suggéré :**
```php
class Designation extends Model
{
    protected $table = 'designation';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = ['designation', 'CodeDesignation', 'idCat'];
    
    public function categorie() {
        return $this->belongsTo(Categorie::class, 'idCat', 'idCategorie');
    }
    
    public function immobilisations() {
        return $this->hasMany(Gesimmo::class, 'idDesignation', 'id');
    }
}
```

---

### 4. Table `categorie` - Catégories

Catégories d'immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idCategorie` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `Categorie` | VARCHAR | NOT NULL | Nom de la catégorie |
| `CodeCategorie` | VARCHAR | NULL | Code de la catégorie |

**Relations :**
- `hasMany(Designation::class, 'idCat', 'idCategorie')`
- `hasMany(Gesimmo::class, 'idCategorie', 'idCategorie')`

**Modèle Laravel suggéré :**
```php
class Categorie extends Model
{
    protected $table = 'categorie';
    protected $primaryKey = 'idCategorie';
    public $timestamps = false;
    
    protected $fillable = ['Categorie', 'CodeCategorie'];
    
    public function designations() {
        return $this->hasMany(Designation::class, 'idCat', 'idCategorie');
    }
    
    public function immobilisations() {
        return $this->hasMany(Gesimmo::class, 'idCategorie', 'idCategorie');
    }
}
```

---

### 5. Table `etat` - États

États des immobilisations (Bon, Détérioré, etc.).

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idEtat` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `Etat` | VARCHAR | NOT NULL | Nom de l'état |
| `CodeEtat` | VARCHAR | NULL | Code de l'état |

**Relations :**
- `hasMany(Gesimmo::class, 'idEtat', 'idEtat')`

**Modèle Laravel suggéré :**
```php
class Etat extends Model
{
    protected $table = 'etat';
    protected $primaryKey = 'idEtat';
    public $timestamps = false;
    
    protected $fillable = ['Etat', 'CodeEtat'];
    
    public function immobilisations() {
        return $this->hasMany(Gesimmo::class, 'idEtat', 'idEtat');
    }
}
```

---

### 6. Table `emplacement` - Emplacements

Emplacements physiques des immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idEmplacement` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `Emplacement` | VARCHAR | NOT NULL | Nom de l'emplacement |
| `CodeEmplacement` | VARCHAR | NULL | Code de l'emplacement |
| `idAffectation` | INT | FOREIGN KEY → `affectation.idAffectation` | Référence à l'affectation |
| `idLocalisation` | INT | FOREIGN KEY → `localisation.idLocalisation` | Référence à la localisation |

**Relations :**
- `belongsTo(Affectation::class, 'idAffectation', 'idAffectation')`
- `belongsTo(Localisation::class, 'idLocalisation', 'idLocalisation')`
- `hasMany(Gesimmo::class, 'idEmplacement', 'idEmplacement')`
- `hasMany(Entree::class, 'idEmplacement', 'idEmplacement')`

**Modèle Laravel suggéré :**
```php
class Emplacement extends Model
{
    protected $table = 'emplacement';
    protected $primaryKey = 'idEmplacement';
    public $timestamps = false;
    
    protected $fillable = ['Emplacement', 'CodeEmplacement', 'idAffectation', 'idLocalisation'];
    
    public function affectation() {
        return $this->belongsTo(Affectation::class, 'idAffectation', 'idAffectation');
    }
    
    public function localisation() {
        return $this->belongsTo(Localisation::class, 'idLocalisation', 'idLocalisation');
    }
    
    public function immobilisations() {
        return $this->hasMany(Gesimmo::class, 'idEmplacement', 'idEmplacement');
    }
    
    public function entrees() {
        return $this->hasMany(Entree::class, 'idEmplacement', 'idEmplacement');
    }
}
```

---

### 7. Table `affectation` - Affectations

Affectations des emplacements.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idAffectation` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `Affectation` | VARCHAR | NOT NULL | Nom de l'affectation |
| `CodeAffectation` | VARCHAR | NULL | Code de l'affectation |

**Relations :**
- `hasMany(Emplacement::class, 'idAffectation', 'idAffectation')`

**Modèle Laravel suggéré :**
```php
class Affectation extends Model
{
    protected $table = 'affectation';
    protected $primaryKey = 'idAffectation';
    public $timestamps = false;
    
    protected $fillable = ['Affectation', 'CodeAffectation'];
    
    public function emplacements() {
        return $this->hasMany(Emplacement::class, 'idAffectation', 'idAffectation');
    }
}
```

---

### 8. Table `localisation` - Localisations

Localisations géographiques.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idLocalisation` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `Localisation` | VARCHAR | NOT NULL | Nom de la localisation |
| `CodeLocalisation` | VARCHAR | NULL | Code de la localisation |

**Relations :**
- `hasMany(Emplacement::class, 'idLocalisation', 'idLocalisation')`

**Modèle Laravel suggéré :**
```php
class Localisation extends Model
{
    protected $table = 'localisation';
    protected $primaryKey = 'idLocalisation';
    public $timestamps = false;
    
    protected $fillable = ['Localisation', 'CodeLocalisation'];
    
    public function emplacements() {
        return $this->hasMany(Emplacement::class, 'idLocalisation', 'idLocalisation');
    }
}
```

---

### 9. Table `naturejurdique` - Natures Juridiques

Natures juridiques des immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idNatJur` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `NatJur` | VARCHAR | NOT NULL | Nom de la nature juridique |
| `CodeNatJur` | VARCHAR | NULL | Code de la nature juridique |

**Relations :**
- `hasMany(Gesimmo::class, 'idNatJur', 'idNatJur')`

**Modèle Laravel suggéré :**
```php
class NatureJuridique extends Model
{
    protected $table = 'naturejurdique';
    protected $primaryKey = 'idNatJur';
    public $timestamps = false;
    
    protected $fillable = ['NatJur', 'CodeNatJur'];
    
    public function immobilisations() {
        return $this->hasMany(Gesimmo::class, 'idNatJur', 'idNatJur');
    }
}
```

---

### 10. Table `sourcefinancement` - Sources de Financement

Sources de financement des immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idSF` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `SourceFin` | VARCHAR | NOT NULL | Nom de la source de financement |
| `CodeSourceFin` | VARCHAR | NULL | Code de la source de financement |

**Relations :**
- `hasMany(Gesimmo::class, 'idSF', 'idSF')`

**Modèle Laravel suggéré :**
```php
class SourceFinancement extends Model
{
    protected $table = 'sourcefinancement';
    protected $primaryKey = 'idSF';
    public $timestamps = false;
    
    protected $fillable = ['SourceFin', 'CodeSourceFin'];
    
    public function immobilisations() {
        return $this->hasMany(Gesimmo::class, 'idSF', 'idSF');
    }
}
```

---

### 11. Table `produits` - Produits Consommables

Produits consommables en stock.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idProduit` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `Produit` | VARCHAR | NOT NULL | Nom du produit |
| `Unite` | VARCHAR | NULL | Unité de mesure (kg, L, pièce, etc.) |

**Relations :**
- `hasMany(Entree::class, 'idProduit', 'idProduit')`
- `hasMany(Sortie::class, 'idProduit', 'idProduit')`

**Modèle Laravel suggéré :**
```php
class Produit extends Model
{
    protected $table = 'produits';
    protected $primaryKey = 'idProduit';
    public $timestamps = false;
    
    protected $fillable = ['Produit', 'Unite'];
    
    public function entrees() {
        return $this->hasMany(Entree::class, 'idProduit', 'idProduit');
    }
    
    public function sorties() {
        return $this->hasMany(Sortie::class, 'idProduit', 'idProduit');
    }
    
    // Accessor pour calculer le stock disponible
    public function getStockDisponibleAttribute() {
        $totalEntree = $this->entrees()->sum('Quantite');
        $totalSortie = $this->sorties()->sum('Quantite');
        return $totalEntree - $totalSortie;
    }
}
```

---

### 12. Table `entree` - Entrées de Stock

Enregistrements des entrées de produits en stock.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idEntree` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique (supposé) |
| `idProduit` | INT | FOREIGN KEY → `produits.idProduit` | Référence au produit |
| `idEmplacement` | INT | FOREIGN KEY → `emplacement.idEmplacement` | Référence à l'emplacement |
| `DateEntree` | DATE | NOT NULL | Date d'entrée |
| `Quantite` | DECIMAL | NOT NULL | Quantité entrée |

**Relations :**
- `belongsTo(Produit::class, 'idProduit', 'idProduit')`
- `belongsTo(Emplacement::class, 'idEmplacement', 'idEmplacement')`

**Modèle Laravel suggéré :**
```php
class Entree extends Model
{
    protected $table = 'entree';
    protected $primaryKey = 'idEntree';
    public $timestamps = false;
    
    protected $fillable = ['idProduit', 'idEmplacement', 'DateEntree', 'Quantite'];
    
    protected $casts = [
        'DateEntree' => 'date',
        'Quantite' => 'decimal:2',
    ];
    
    public function produit() {
        return $this->belongsTo(Produit::class, 'idProduit', 'idProduit');
    }
    
    public function emplacement() {
        return $this->belongsTo(Emplacement::class, 'idEmplacement', 'idEmplacement');
    }
}
```

---

### 13. Table `sortie` - Sorties de Stock

Enregistrements des sorties de produits du stock.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `idSortie` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique |
| `idProduit` | INT | FOREIGN KEY → `produits.idProduit` | Référence au produit |
| `Quantite` | DECIMAL | NOT NULL | Quantité sortie |
| `DateSortie` | DATETIME | NOT NULL | Date et heure de sortie |
| `SrvcDmndr` | VARCHAR | NULL | Service demandeur |
| `Observations` | TEXT | NULL | Observations |

**Relations :**
- `belongsTo(Produit::class, 'idProduit', 'idProduit')`

**Modèle Laravel suggéré :**
```php
class Sortie extends Model
{
    protected $table = 'sortie';
    protected $primaryKey = 'idSortie';
    public $timestamps = false;
    
    protected $fillable = [
        'idProduit', 'Quantite', 'DateSortie', 
        'SrvcDmndr', 'Observations'
    ];
    
    protected $casts = [
        'DateSortie' => 'datetime',
        'Quantite' => 'decimal:2',
    ];
    
    public function produit() {
        return $this->belongsTo(Produit::class, 'idProduit', 'idProduit');
    }
}
```

---

### 14. Table `codes` - Codes-barres

Codes-barres associés aux immobilisations.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT | Identifiant unique (supposé) |
| `idGesimmo` | INT | FOREIGN KEY → `gesimmo.NumOrdre` | Référence à l'immobilisation |
| `barcode` | TEXT | NULL | Image du code-barres (base64 ou chemin) |

**Relations :**
- `belongsTo(Gesimmo::class, 'idGesimmo', 'NumOrdre')`

**Modèle Laravel suggéré :**
```php
class Code extends Model
{
    protected $table = 'codes';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = ['idGesimmo', 'barcode'];
    
    public function immobilisation() {
        return $this->belongsTo(Gesimmo::class, 'idGesimmo', 'NumOrdre');
    }
}
```

---

## Schéma des Relations

```
users (isolé)

gesimmo (table principale)
├── designation (idDesignation)
├── categorie (idCategorie)
├── etat (idEtat)
├── emplacement (idEmplacement)
│   ├── affectation (idAffectation)
│   └── localisation (idLocalisation)
├── naturejurdique (idNatJur)
├── sourcefinancement (idSF)
└── codes (NumOrdre → idGesimmo)

produits
├── entree (idProduit)
│   └── emplacement (idEmplacement)
└── sortie (idProduit)
```

---

## Requêtes SQL Utiles

### Génération du code d'immobilisation
```sql
SELECT CONCAT_WS('/', 
    CodeNatJur, 
    CodeDesignation, 
    CodeCategorie, 
    DATE_FORMAT(DateAcquisition, '%Y'), 
    CodeSourceFin, 
    NumOrdre
) AS Code
FROM gesimmo
JOIN naturejurdique ON gesimmo.idNatJur = naturejurdique.idNatJur
JOIN designation ON gesimmo.idDesignation = designation.id
JOIN categorie ON gesimmo.idCategorie = categorie.idCategorie
JOIN sourcefinancement ON gesimmo.idSF = sourcefinancement.idSF
WHERE gesimmo.NumOrdre = ?
```

### Calcul du stock disponible
```sql
SELECT 
    p.Produit,
    p.Unite,
    COALESCE(SUM(e.Quantite), 0) AS totalEntree,
    COALESCE(SUM(s.Quantite), 0) AS totalSortie,
    (COALESCE(SUM(e.Quantite), 0) - COALESCE(SUM(s.Quantite), 0)) AS stockDisponible
FROM produits p
LEFT JOIN entree e ON p.idProduit = e.idProduit
LEFT JOIN sortie s ON p.idProduit = s.idProduit
GROUP BY p.idProduit
HAVING stockDisponible > 0
```

---

## Notes Importantes pour Laravel

1. **Pas de timestamps** : Toutes les tables n'utilisent pas `created_at` et `updated_at`. Définir `public $timestamps = false;` dans tous les modèles.

2. **Clés primaires personnalisées** : Plusieurs tables utilisent des noms de clés primaires non standards (`idUser`, `NumOrdre`, `idCategorie`, etc.). Toujours spécifier `protected $primaryKey`.

3. **Noms de colonnes** : Certaines colonnes utilisent des noms en français ou des conventions non standards. Utiliser les noms exacts de la base de données.

4. **Relations** : Toutes les relations sont basées sur des clés étrangères explicites. Vérifier les noms de colonnes dans les relations.

5. **Codes générés** : Le code d'immobilisation est généré dynamiquement à partir de plusieurs tables. Utiliser un accessor dans le modèle `Gesimmo`.

6. **Validation des stocks** : Avant une sortie, vérifier que la quantité disponible est suffisante (somme des entrées - somme des sorties).

---

## Migration Laravel Suggérée

Pour créer les migrations Laravel, vous pouvez utiliser cette structure comme référence. Notez que vous devrez adapter les types de données selon votre version de MySQL.

```php
// Exemple pour la table gesimmo
Schema::create('gesimmo', function (Blueprint $table) {
    $table->integer('NumOrdre')->autoIncrement();
    $table->integer('idDesignation');
    $table->integer('idCategorie');
    $table->integer('idEtat');
    $table->integer('idEmplacement');
    $table->integer('idNatJur');
    $table->integer('idSF');
    $table->date('DateAcquisition')->nullable();
    $table->text('Observations')->nullable();
    
    $table->foreign('idDesignation')->references('id')->on('designation');
    $table->foreign('idCategorie')->references('idCategorie')->on('categorie');
    $table->foreign('idEtat')->references('idEtat')->on('etat');
    $table->foreign('idEmplacement')->references('idEmplacement')->on('emplacement');
    $table->foreign('idNatJur')->references('idNatJur')->on('naturejurdique');
    $table->foreign('idSF')->references('idSF')->on('sourcefinancement');
});
```

---

## Points d'Attention

1. **Sécurité** : La table `users` stocke les mots de passe en clair. Pour Laravel, utiliser le système de hashage `bcrypt` de Laravel.

2. **Intégrité référentielle** : Vérifier que toutes les clés étrangères ont des contraintes appropriées dans la base de données.

3. **Index** : Considérer l'ajout d'index sur les colonnes fréquemment utilisées dans les jointures et les recherches.

4. **Normalisation** : La structure semble bien normalisée, mais vérifier les dépendances fonctionnelles si nécessaire.

---