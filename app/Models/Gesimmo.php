<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Gesimmo extends Model
{
    use HasFactory;

    protected $table = 'gesimmo';
    protected $primaryKey = 'NumOrdre';
    public $timestamps = false;

    protected $fillable = [
        'idDesignation', 'idCategorie', 'idEtat', 
        'idEmplacement', 'idNatJur', 'idSF', 
        'DateAcquisition', 'Observations',
        'valeur_acquisition', 'date_mise_en_service',
    ];

    protected $casts = [
        'DateAcquisition' => 'integer',
        'valeur_acquisition' => 'decimal:2',
        'date_mise_en_service' => 'date',
    ];

    /**
     * Get the route key for the model.
     * Permet d'utiliser NumOrdre dans les routes au lieu de 'id'
     */
    public function getRouteKeyName()
    {
        return 'NumOrdre';
    }

    /**
     * RELATIONS
     */

    /**
     * Relation avec la désignation
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'idDesignation', 'id');
    }

    /**
     * Relation avec la catégorie
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'idCategorie', 'idCategorie');
    }

    /**
     * Relation avec l'état
     */
    public function etat(): BelongsTo
    {
        return $this->belongsTo(Etat::class, 'idEtat', 'idEtat');
    }

    /**
     * Relation avec l'emplacement
     * L'emplacement est la table centrale qui lie LocalisationImmo et Affectation
     */
    public function emplacement(): BelongsTo
    {
        return $this->belongsTo(Emplacement::class, 'idEmplacement', 'idEmplacement');
    }

    /**
     * Relation avec la nature juridique
     */
    public function natureJuridique(): BelongsTo
    {
        return $this->belongsTo(NatureJuridique::class, 'idNatJur', 'idNatJur');
    }

    /**
     * Relation avec la source de financement
     */
    public function sourceFinancement(): BelongsTo
    {
        return $this->belongsTo(SourceFinancement::class, 'idSF', 'idSF');
    }

    /**
     * Relation avec le code-barres
     */
    public function code(): HasOne
    {
        return $this->hasOne(Code::class, 'idGesimmo', 'NumOrdre');
    }

    /**
     * ACCESSORS
     */

    /**
     * Génère le code d'immobilisation au format: CodeNatJur/CodeDesignation/CodeCategorie/Année/CodeSourceFin/NumOrdre
     * 
     * Note: Le code formaté est utilisé pour générer le code-barres Code 128.
     * Il doit être unique et refléter les caractéristiques de l'immobilisation.
     * Les relations avec Emplacement, Localisation et Affectation sont utilisées
     * pour l'affichage mais ne font pas partie du code formaté.
     */
    public function getCodeFormateAttribute(): string
    {
        // S'assurer que les relations sont chargées
        if (!$this->relationLoaded('natureJuridique')) {
            $this->load('natureJuridique');
        }
        if (!$this->relationLoaded('designation')) {
            $this->load('designation');
        }
        if (!$this->relationLoaded('categorie')) {
            $this->load('categorie');
        }
        if (!$this->relationLoaded('sourceFinancement')) {
            $this->load('sourceFinancement');
        }
        
        // DateAcquisition contient l'année (ex: 2019)
        $annee = ($this->DateAcquisition && $this->DateAcquisition > 1970) ? $this->DateAcquisition : '';
        
        // Construire le code formaté avec les codes des relations
        $codeNatJur = $this->natureJuridique->CodeNatJur ?? '';
        $codeDesignation = $this->designation->CodeDesignation ?? '';
        $codeCategorie = $this->categorie->CodeCategorie ?? '';
        $codeSourceFin = $this->sourceFinancement->CodeSourceFin ?? '';
        
        return sprintf(
            '%s/%s/%s/%s/%s/%s',
            $codeNatJur,
            $codeDesignation,
            $codeCategorie,
            $annee,
            $codeSourceFin,
            $this->NumOrdre
        );
    }

    /**
     * Génère et sauvegarde le code-barres Code 128 pour cette immobilisation
     * 
     * @return string Le code-barres en SVG (base64 ou SVG direct)
     */
    public function generateBarcode(): string
    {
        $barcodeService = app(\App\Services\BarcodeService::class);
        return $barcodeService->generateForGesimmo($this);
    }
}
