<?php

namespace App\Services;

use App\Models\Gesimmo;
use App\Models\Code;
use Illuminate\Support\Facades\Log;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorHTML;

class BarcodeService
{
    /**
     * Sauvegarde un code-barres SVG généré par jsbarcode côté client
     * 
     * @param Gesimmo $gesimmo L'immobilisation pour laquelle sauvegarder le code-barres
     * @param string $svgContent Le contenu SVG généré par jsbarcode
     * @return string Le SVG sauvegardé
     * @throws \Exception Si la sauvegarde échoue
     */
    public function saveBarcodeFromClient(Gesimmo $gesimmo, string $svgContent): string
    {
        try {
            // Nettoyer et valider le SVG
            if (empty($svgContent) || !str_starts_with(trim($svgContent), '<svg')) {
                throw new \Exception("Le contenu SVG n'est pas valide.");
            }

            // Sauvegarder ou mettre à jour dans la table codes
            $codeModel = Code::updateOrCreate(
                ['idGesimmo' => $gesimmo->NumOrdre],
                ['barcode' => $svgContent] // Stocker le SVG directement dans la base de données
            );

            Log::info("Code-barres SVG sauvegardé pour l'immobilisation", [
                'NumOrdre' => $gesimmo->NumOrdre,
            ]);

            return $svgContent;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la sauvegarde du code-barres SVG", [
                'NumOrdre' => $gesimmo->NumOrdre ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Génère un code-barres Code 128 pour une immobilisation
     * 
     * Le code-barres contient le code d'immobilisation au format:
     * CodeNatJur/CodeDesignation/CodeCategorie/Année/CodeSourceFin/NumOrdre
     * 
     * @param Gesimmo $gesimmo L'immobilisation pour laquelle générer le code-barres
     * @return string Le code-barres en base64 (format PNG ou SVG)
     * @throws \Exception Si la génération échoue
     */
    public function generateForGesimmo(Gesimmo $gesimmo): string
    {
        try {
            // Charger les relations nécessaires pour générer le code
            $gesimmo->load(['natureJuridique', 'designation', 'categorie', 'sourceFinancement']);
            
            // Générer le code d'immobilisation
            $codeValue = $gesimmo->code_formate;
            
            if (empty($codeValue)) {
                throw new \Exception("Impossible de générer le code d'immobilisation. Vérifiez que toutes les relations sont définies.");
            }

            // Générer le code-barres Code 128 en HTML (meilleure compatibilité avec DomPDF)
            // Le HTML génère le code-barres avec des divs et CSS, ce qui fonctionne mieux dans les PDF
            // Paramètres : widthFactor=1 (barres plus fines), height=40 (hauteur adaptée)
            $generator = new BarcodeGeneratorHTML();
            $barcodeHTML = $generator->getBarcode($codeValue, $generator::TYPE_CODE_128, 1, 40);
            
            // Logger la génération réussie
            Log::info("Code-barres Code 128 généré pour l'immobilisation", [
                'NumOrdre' => $gesimmo->NumOrdre,
                'code' => $codeValue,
            ]);

            // Sauvegarder ou mettre à jour dans la table codes (stockage du HTML directement)
            $codeModel = Code::updateOrCreate(
                ['idGesimmo' => $gesimmo->NumOrdre],
                ['barcode' => $barcodeHTML] // Stocker le HTML directement dans la base de données
            );

            return $barcodeHTML;
        } catch (\Exception $e) {
            // Logger l'erreur
            Log::error("Erreur lors de la génération du code-barres pour l'immobilisation", [
                'NumOrdre' => $gesimmo->NumOrdre ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Génère un code-barres Code 128 en PNG pour une immobilisation
     * 
     * @param Gesimmo $gesimmo L'immobilisation pour laquelle générer le code-barres
     * @return string Le code-barres en base64 (format PNG)
     * @throws \Exception Si la génération échoue
     */
    public function generatePNGForGesimmo(Gesimmo $gesimmo): string
    {
        try {
            // Charger les relations nécessaires
            $gesimmo->load(['natureJuridique', 'designation', 'categorie', 'sourceFinancement']);
            
            // Générer le code d'immobilisation
            $codeValue = $gesimmo->code_formate;
            
            if (empty($codeValue)) {
                throw new \Exception("Impossible de générer le code d'immobilisation.");
            }

            // Générer le code-barres Code 128 en PNG
            $generator = new BarcodeGeneratorPNG();
            $barcodePNG = $generator->getBarcode($codeValue, $generator::TYPE_CODE_128, 2, 50);
            
            // Convertir en base64 pour stockage
            $barcodeBase64 = base64_encode($barcodePNG);

            // Sauvegarder dans la table codes
            $codeModel = Code::updateOrCreate(
                ['idGesimmo' => $gesimmo->NumOrdre],
                ['barcode' => $barcodeBase64]
            );

            return $barcodeBase64;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du code-barres PNG", [
                'NumOrdre' => $gesimmo->NumOrdre ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Génère un code-barres Code 128 en HTML pour affichage direct
     * 
     * @param Gesimmo $gesimmo L'immobilisation pour laquelle générer le code-barres
     * @return string Le code-barres en HTML
     * @throws \Exception Si la génération échoue
     */
    public function generateHTMLForGesimmo(Gesimmo $gesimmo): string
    {
        try {
            // Charger les relations nécessaires
            $gesimmo->load(['natureJuridique', 'designation', 'categorie', 'sourceFinancement']);
            
            // Générer le code d'immobilisation
            $codeValue = $gesimmo->code_formate;
            
            if (empty($codeValue)) {
                throw new \Exception("Impossible de générer le code d'immobilisation.");
            }

            // Générer le code-barres Code 128 en HTML
            $generator = new BarcodeGeneratorHTML();
            $barcode = $generator->getBarcode($codeValue, $generator::TYPE_CODE_128, 2, 50);

            return $barcode;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du code-barres HTML", [
                'NumOrdre' => $gesimmo->NumOrdre ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Génère plusieurs codes-barres en batch
     * 
     * @param \Illuminate\Database\Eloquent\Collection $gesimmos Collection d'immobilisations
     * @return array Tableau des codes-barres générés avec succès
     */
    public function generateBatch(\Illuminate\Database\Eloquent\Collection $gesimmos): array
    {
        $generated = [];
        $errors = [];

        Log::info("Début de la génération en batch de codes-barres", [
            'count' => $gesimmos->count(),
        ]);

        foreach ($gesimmos as $gesimmo) {
            try {
                $barcode = $this->generateForGesimmo($gesimmo);
                $generated[] = [
                    'NumOrdre' => $gesimmo->NumOrdre,
                    'code' => $gesimmo->code_formate,
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'NumOrdre' => $gesimmo->NumOrdre ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::warning("Erreur lors de la génération du code-barres en batch", [
                    'NumOrdre' => $gesimmo->NumOrdre ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Fin de la génération en batch de codes-barres", [
            'success' => count($generated),
            'errors' => count($errors),
        ]);

        return $generated;
    }
}
