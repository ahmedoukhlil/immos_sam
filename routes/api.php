<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BienController;
use App\Http\Controllers\Api\InventaireController;
use App\Http\Controllers\Api\LocalisationController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes pour Progressive Web App (PWA)
|--------------------------------------------------------------------------
|
| Ces routes sont utilisées par l'application mobile PWA pour scanner
| les QR codes et effectuer les inventaires.
|
| Toutes les routes sont préfixées par /api/v1
|
| NOTE IMPORTANTE: Les contrôleurs API doivent être créés dans 
| app/Http/Controllers/Api/ avant d'activer ces routes.
|
*/

Route::prefix('v1')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Authentification API
    |----------------------------------------------------------------------
    |
    | Routes pour l'authentification via l'API (utilise Sanctum)
    |
    */
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('api.logout');

    /*
    |----------------------------------------------------------------------
    | Routes Protégées (nécessitent authentification Sanctum)
    |----------------------------------------------------------------------
    |
    | Toutes les routes ci-dessous nécessitent un token d'authentification
    | valide (Sanctum).
    |
    */
    Route::middleware('auth:sanctum')->group(function () {

        /*
        |------------------------------------------------------------------
        | Inventaires API
        |------------------------------------------------------------------
        |
        | Routes pour gérer les inventaires depuis l'application mobile
        |
        */
        Route::prefix('inventaires')->name('api.inventaires.')->group(function () {
            // Récupérer l'inventaire en cours pour l'utilisateur
            Route::get('/current', [InventaireController::class, 'current'])->name('current');
            
            // Récupérer les localisations assignées à l'utilisateur pour un inventaire
            Route::get('/{inventaire}/mes-localisations', [InventaireController::class, 'mesLocalisations'])
                ->name('mes-localisations');
            
            // Démarrer le scan d'une localisation
            Route::post('/{inventaire}/demarrer-localisation', [InventaireController::class, 'demarrerLocalisation'])
                ->name('demarrer-localisation');
            
            // Terminer le scan d'une localisation
            Route::post('/{inventaire}/terminer-localisation', [InventaireController::class, 'terminerLocalisation'])
                ->name('terminer-localisation');
            
            // Statistiques d'un inventaire
            Route::get('/{inventaire}/stats', [InventaireController::class, 'stats'])
                ->name('stats');
        });

        /*
        |------------------------------------------------------------------
        | Localisations API
        |------------------------------------------------------------------
        |
        | Routes pour récupérer les informations des localisations
        |
        */
        Route::prefix('localisations')->name('api.localisations.')->group(function () {
            // Récupérer une localisation par son code QR
            Route::get('/by-code/{code}', [LocalisationController::class, 'byCode'])
                ->name('by-code');
            
            // Récupérer une localisation par son ID
            Route::get('/{localisation}', [LocalisationController::class, 'show'])
                ->name('show');
            
            // Récupérer tous les biens d'une localisation
            Route::get('/{localisation}/biens', [LocalisationController::class, 'biens'])
                ->name('biens');
        });

        /*
        |------------------------------------------------------------------
        | États API (pour PWA modal)
        |------------------------------------------------------------------
        */
        Route::get('/etats', [ScanController::class, 'getEtats'])
            ->name('api.etats.index');

        /*
        |------------------------------------------------------------------
        | Emplacements API (Nouveau Workflow)
        |------------------------------------------------------------------
        |
        | Routes pour le nouveau workflow : scan par emplacement
        |
        */
        Route::prefix('emplacements')->name('api.emplacements.')->group(function () {
            // Récupérer tous les biens affectés à un emplacement
            Route::get('/{idEmplacement}/biens', [ScanController::class, 'getBiensByEmplacement'])
                ->name('biens');
            
            // Enregistrer un scan pour un emplacement
            Route::post('/{idEmplacement}/scan', [ScanController::class, 'storeScanEmplacement'])
                ->name('scan');
            
            // Terminer le scan d'un emplacement et calculer les écarts
            Route::post('/{idEmplacement}/terminer', [ScanController::class, 'terminerScanEmplacement'])
                ->name('terminer');
        });

        /*
        |------------------------------------------------------------------
        | Biens API
        |------------------------------------------------------------------
        |
        | Routes pour récupérer les informations des biens
        |
        */
        Route::prefix('biens')->name('api.biens.')->group(function () {
            // Récupérer un bien par son code inventaire
            Route::get('/by-code/{code}', [BienController::class, 'byCode'])
                ->name('by-code');
            // Détails d'un bien
            Route::get('/{bien}', [BienController::class, 'show'])
                ->name('show');
        });

        /*
        |------------------------------------------------------------------
        | Scans API
        |------------------------------------------------------------------
        |
        | Routes pour enregistrer les scans depuis l'application mobile
        |
        */
        Route::prefix('inventaires')->name('api.scans.')->group(function () {
            // Enregistrer un scan de bien
            Route::post('/{inventaire}/scan', [ScanController::class, 'store'])
                ->name('store');
        });
    });
});
