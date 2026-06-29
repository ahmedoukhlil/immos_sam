<?php

namespace App\Services;

use App\Models\Bien;
use App\Models\Localisation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Génère un QR code pour un bien
     * 
     * Le QR code contient les informations du bien au format JSON :
     * {
     *     "type": "bien",
     *     "id": $bien->id,
     *     "code": $bien->code_inventaire
     * }
     * 
     * @param Bien $bien Le bien pour lequel générer le QR code
     * @return string Le chemin relatif du QR code généré (qrcodes/biens/{code_inventaire}.png)
     * @throws \Exception Si la génération échoue
     */
    public function generateForBien(Bien $bien): string
    {
        try {
            // Vérifier que le bien a un code_inventaire
            if (empty($bien->code_inventaire)) {
                throw new \Exception("Le bien doit avoir un code d'inventaire pour générer un QR code.");
            }

            // Préparer les données JSON pour le QR code
            $data = json_encode([
                'type' => 'bien',
                'id' => $bien->id,
                'code' => $bien->code_inventaire,
            ]);

            // Nettoyer le code_inventaire pour le nom de fichier (remplacer les caractères invalides)
            $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '_', $bien->code_inventaire);
            
            // Définir le chemin de sauvegarde (utiliser SVG car PNG nécessite ImageMagick)
            $path = "qrcodes/biens/{$safeCode}.svg";

            // Créer le dossier s'il n'existe pas
            $this->ensureDirectoryExists('qrcodes/biens');

            // Chemin complet pour la génération
            $fullPath = storage_path('app/public/' . $path);

            // Supprimer l'ancien QR code s'il existe
            if ($bien->qr_code_path && Storage::disk('public')->exists($bien->qr_code_path)) {
                Storage::disk('public')->delete($bien->qr_code_path);
            }

            // Générer le QR code en SVG (ne nécessite pas ImageMagick)
            QrCode::format('svg')
                ->size(300)
                ->errorCorrection('H') // Niveau de correction d'erreur élevé
                ->generate($data, $fullPath);

            // Vérifier que le fichier a bien été créé
            if (!file_exists($fullPath) || !Storage::disk('public')->exists($path)) {
                throw new \Exception("Le QR code n'a pas pu être créé : {$path}");
            }

            // Mettre à jour le chemin dans le modèle
            $bien->update(['qr_code_path' => $path]);

            // Logger la génération réussie
            Log::info("QR code généré pour le bien {$bien->code_inventaire}", [
                'bien_id' => $bien->id,
                'code_inventaire' => $bien->code_inventaire,
                'path' => $path,
            ]);

            return $path;
        } catch (\Exception $e) {
            // Logger l'erreur
            Log::error("Erreur lors de la génération du QR code pour le bien {$bien->code_inventaire}", [
                'bien_id' => $bien->id,
                'code_inventaire' => $bien->code_inventaire,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Génère un QR code pour une localisation
     * 
     * Le QR code contient les informations de la localisation au format JSON :
     * {
     *     "type": "localisation",
     *     "id": $localisation->id,
     *     "code": $localisation->code
     * }
     * 
     * @param Localisation $localisation La localisation pour laquelle générer le QR code
     * @return string Le chemin relatif du QR code généré (qrcodes/localisations/{code}.png)
     * @throws \Exception Si la génération échoue
     */
    public function generateForLocalisation(Localisation $localisation): string
    {
        try {
            // Préparer les données JSON pour le QR code
            $data = json_encode([
                'type' => 'localisation',
                'id' => $localisation->id,
                'code' => $localisation->code,
            ]);

            // Définir le chemin de sauvegarde (utiliser SVG car PNG nécessite ImageMagick)
            $path = "qrcodes/localisations/{$localisation->code}.svg";

            // Créer le dossier s'il n'existe pas
            $this->ensureDirectoryExists('qrcodes/localisations');

            // Chemin complet pour la génération
            $fullPath = storage_path('app/public/' . $path);

            // Générer le QR code en SVG (ne nécessite pas ImageMagick, plus grand pour être scanné de loin)
            QrCode::format('svg')
                ->size(400)
                ->errorCorrection('H') // Niveau de correction d'erreur élevé
                ->generate($data, $fullPath);

            // Vérifier que le fichier a bien été créé
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception("Le QR code n'a pas pu être créé : {$path}");
            }

            // Mettre à jour le chemin dans le modèle
            $localisation->update(['qr_code_path' => $path]);

            // Logger la génération réussie
            Log::info("QR code généré pour la localisation {$localisation->code}", [
                'localisation_id' => $localisation->id,
                'code' => $localisation->code,
                'path' => $path,
            ]);

            return $path;
        } catch (\Exception $e) {
            // Logger l'erreur
            Log::error("Erreur lors de la génération du QR code pour la localisation {$localisation->code}", [
                'localisation_id' => $localisation->id,
                'code' => $localisation->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Génère plusieurs QR codes en batch
     * 
     * Cette méthode permet de générer plusieurs QR codes de manière optimisée.
     * Les erreurs individuelles sont gérées sans stopper le processus global.
     * 
     * @param Collection $items Collection de biens ou localisations
     * @param string $type Type d'items : 'bien' ou 'localisation'
     * @return array Tableau des chemins générés avec succès
     * @throws \InvalidArgumentException Si le type n'est pas valide
     */
    public function generateBatch(Collection $items, string $type): array
    {
        if (!in_array($type, ['bien', 'localisation'])) {
            throw new \InvalidArgumentException("Le type doit être 'bien' ou 'localisation', '{$type}' fourni.");
        }

        $generatedPaths = [];
        $errors = [];

        Log::info("Début de la génération en batch de QR codes", [
            'type' => $type,
            'count' => $items->count(),
        ]);

        foreach ($items as $item) {
            try {
                if ($type === 'bien' && $item instanceof Bien) {
                    $path = $this->generateForBien($item);
                    $generatedPaths[] = $path;
                } elseif ($type === 'localisation' && $item instanceof Localisation) {
                    $path = $this->generateForLocalisation($item);
                    $generatedPaths[] = $path;
                } else {
                    $errors[] = [
                        'item_id' => $item->id ?? 'unknown',
                        'error' => "L'item n'est pas une instance de " . ($type === 'bien' ? 'Bien' : 'Localisation'),
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'item_id' => $item->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::warning("Erreur lors de la génération du QR code pour un item en batch", [
                    'type' => $type,
                    'item_id' => $item->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Fin de la génération en batch de QR codes", [
            'type' => $type,
            'success' => count($generatedPaths),
            'errors' => count($errors),
        ]);

        // Logger les erreurs s'il y en a
        if (!empty($errors)) {
            Log::warning("Erreurs lors de la génération en batch", [
                'type' => $type,
                'errors' => $errors,
            ]);
        }

        return $generatedPaths;
    }

    /**
     * Supprime un QR code du storage
     * 
     * @param string $path Le chemin relatif du QR code à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function deleteQRCode(string $path): bool
    {
        try {
            // Vérifier que le fichier existe
            if (!Storage::disk('public')->exists($path)) {
                Log::warning("Tentative de suppression d'un QR code inexistant", [
                    'path' => $path,
                ]);
                return false;
            }

            // Supprimer le fichier
            $deleted = Storage::disk('public')->delete($path);

            if ($deleted) {
                Log::info("QR code supprimé avec succès", [
                    'path' => $path,
                ]);
            } else {
                Log::warning("Échec de la suppression du QR code", [
                    'path' => $path,
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Erreur lors de la suppression du QR code", [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * S'assure que le dossier existe, le crée s'il n'existe pas
     * 
     * @param string $directory Le chemin du dossier (relatif à storage/app/public)
     * @return void
     * @throws \Exception Si la création du dossier échoue
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        // Utiliser Storage pour créer le dossier de manière cohérente avec Laravel
        if (!Storage::disk('public')->exists($directory)) {
            try {
                Storage::disk('public')->makeDirectory($directory);
                
                Log::info("Dossier créé pour les QR codes", [
                    'directory' => $directory,
                ]);
            } catch (\Exception $e) {
                throw new \Exception("Impossible de créer le dossier : {$directory}. Erreur : " . $e->getMessage());
            }
        }

        // Vérifier les permissions d'écriture
        $fullPath = storage_path('app/public/' . $directory);
        if (!is_writable($fullPath)) {
            throw new \Exception("Le dossier n'est pas accessible en écriture : {$fullPath}");
        }
    }

    /**
     * Décode un QR code (méthode utilitaire pour le futur)
     * 
     * @param string $qrCodeData Les données du QR code à décoder
     * @return array|null Les données décodées ou null si le décodage échoue
     */
    public function decode(string $qrCodeData): ?array
    {
        try {
            // Tenter de décoder le JSON
            $decoded = json_decode($qrCodeData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Erreur lors du décodage du QR code", [
                    'error' => json_last_error_msg(),
                    'data' => substr($qrCodeData, 0, 100), // Logger seulement les 100 premiers caractères
                ]);
                return null;
            }

            // Vérifier que les données ont la structure attendue
            if (!isset($decoded['type']) || !isset($decoded['id']) || !isset($decoded['code'])) {
                Log::warning("Structure de données QR code invalide", [
                    'data' => $decoded,
                ]);
                return null;
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error("Erreur lors du décodage du QR code", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
