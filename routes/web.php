<?php

use App\Http\Controllers\BienController;
use App\Http\Controllers\InventaireController;
use App\Http\Controllers\LocalisationController;
use App\Http\Controllers\QRCodeEmplacementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Publiques
|--------------------------------------------------------------------------
*/

// La page d'accueil affiche directement le formulaire de login
Route::get('/', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('home');

/*
|--------------------------------------------------------------------------
| Routes d'Authentification
|--------------------------------------------------------------------------
|
| Les routes d'authentification (login, register, password reset, etc.)
| sont gérées par Laravel Breeze et se trouvent dans le fichier
| auth.php (si configuré) ou directement dans ce fichier.
|
*/

// Inclusion des routes d'authentification (si le fichier existe)
if (file_exists(__DIR__.'/auth.php')) {
    require __DIR__.'/auth.php';
}

/*
|--------------------------------------------------------------------------
| Routes Authentifiées
|--------------------------------------------------------------------------
|
| Toutes les routes ci-dessous nécessitent que l'utilisateur soit authentifié.
|
*/

Route::middleware(['auth', 'session.timeout'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Dashboard
    |----------------------------------------------------------------------
    |
    | Accessible à tous les utilisateurs authentifiés (admin et agent).
    |
    */
    Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Profil Utilisateur
    |----------------------------------------------------------------------
    |
    | Accessible à tous les utilisateurs authentifiés pour modifier
    | leur mot de passe.
    |
    */
    Route::get('/profil', \App\Livewire\Profil::class)->name('profil');

    /*
    |----------------------------------------------------------------------
    | Routes avec Middleware 'inventory' (Admin + Agent)
    |----------------------------------------------------------------------
    |
    | Ces routes sont accessibles aux administrateurs et aux agents.
    | Elles concernent la gestion des localisations, biens, inventaires
    | et rapports.
    |
    */
    Route::middleware(['inventory'])->group(function () {

        /*
        |------------------------------------------------------------------
        | Localisations
        |------------------------------------------------------------------
        |
        | Gestion des localisations (bureaux, ateliers, etc.)
        |
        */
        Route::prefix('localisations')->name('localisations.')->group(function () {
            // Liste des localisations
            Route::get('/', \App\Livewire\Localisations\ListeLocalisations::class)->name('index');
            
            // Export Excel des localisations (doit être avant les routes avec paramètres)
            Route::get('/export/excel', [LocalisationController::class, 'exportExcel'])->name('export-excel');
            
            // Impression en masse d'étiquettes
            Route::post('/imprimer-etiquettes', [LocalisationController::class, 'imprimerEtiquettes'])->name('imprimer-etiquettes');
            
            // Création d'une localisation
            Route::get('/create', \App\Livewire\Localisations\FormLocalisation::class)->name('create');
            
            // Génération du QR code d'une localisation
            Route::get('/{localisation}/qr-code', [LocalisationController::class, 'generateQRCode'])->name('qr-code');
            
            // Téléchargement de l'étiquette d'une localisation
            Route::get('/{localisation}/etiquette', [LocalisationController::class, 'downloadEtiquette'])->name('etiquette');
            
            // Édition d'une localisation
            Route::get('/{localisation}/edit', \App\Livewire\Localisations\FormLocalisation::class)->name('edit');
            
            // Détails d'une localisation (doit être en dernier car il capture toutes les routes)
            Route::get('/{localisation}', \App\Livewire\Localisations\DetailLocalisation::class)->name('show');
        });

        /*
        |------------------------------------------------------------------
        | Emplacements
        |------------------------------------------------------------------
        |
        | Gestion des emplacements (liés à Localisation et Affectation)
        |
        */
        Route::prefix('emplacements')->name('emplacements.')->group(function () {
            // Liste des emplacements
            Route::get('/', \App\Livewire\Emplacements\ListeEmplacements::class)->name('index');
            
            // Création d'un emplacement
            Route::get('/create', \App\Livewire\Emplacements\FormEmplacement::class)->name('create');
            
            // Détails d'un emplacement (avec QR code)
            Route::get('/{emplacement}', \App\Livewire\Emplacements\DetailEmplacement::class)->name('show');
            
            // Édition d'un emplacement
            Route::get('/{emplacement}/edit', \App\Livewire\Emplacements\FormEmplacement::class)->name('edit');
        });

        /*
        |------------------------------------------------------------------
        | Affectations
        |------------------------------------------------------------------
        |
        | Gestion des affectations
        |
        */
        Route::prefix('affectations')->name('affectations.')->group(function () {
            // Liste des affectations
            Route::get('/', \App\Livewire\Affectations\ListeAffectations::class)->name('index');
            
            // Création d'une affectation
            Route::get('/create', \App\Livewire\Affectations\FormAffectation::class)->name('create');
            
            // Édition d'une affectation
            Route::get('/{affectation}/edit', \App\Livewire\Affectations\FormAffectation::class)->name('edit');
        });

        /*
        |------------------------------------------------------------------
        | Catégories
        |------------------------------------------------------------------
        */
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', \App\Livewire\Categories\ListeCategories::class)->name('index');
            Route::get('/create', \App\Livewire\Categories\FormCategorie::class)->name('create');
            Route::get('/{categorie}/edit', \App\Livewire\Categories\FormCategorie::class)->name('edit');
        });

        /*
        |------------------------------------------------------------------
        | Désignations
        |------------------------------------------------------------------
        |
        | Gestion des désignations
        |
        */
        Route::prefix('designations')->name('designations.')->group(function () {
            // Liste des désignations
            Route::get('/', \App\Livewire\Designations\ListeDesignations::class)->name('index');
            
            // Création d'une désignation
            Route::get('/create', \App\Livewire\Designations\FormDesignation::class)->name('create');
            
            // Édition d'une désignation
            Route::get('/{designation}/edit', \App\Livewire\Designations\FormDesignation::class)->name('edit');
        });

        /*
        |------------------------------------------------------------------
        | QR Codes des Emplacements (Pour PWA Scanner)
        |------------------------------------------------------------------
        |
        | Génération des QR codes pour scanner les emplacements via la PWA
        |
        */
        Route::prefix('qrcodes/emplacements')->name('qrcodes.')->group(function () {
            // Page principale de génération des QR codes
            Route::get('/', [QRCodeEmplacementController::class, 'index'])->name('emplacements');
            
            // Générer un QR code SVG pour un emplacement
            Route::get('/{idEmplacement}/generate', [QRCodeEmplacementController::class, 'generate'])->name('generate');
            
            // Télécharger PDF de tous les QR codes (avec filtres)
            Route::get('/pdf', [QRCodeEmplacementController::class, 'downloadPdf'])->name('emplacements.pdf');
            
            // Imprimer les QR codes sélectionnés
            Route::post('/print', [QRCodeEmplacementController::class, 'printSelected'])->name('print-selected');
        });

        /*
        |------------------------------------------------------------------
        | Biens
        |------------------------------------------------------------------
        |
        | Gestion des biens (mobilier, informatique, véhicules, etc.)
        |
        */
        Route::prefix('biens')->name('biens.')->group(function () {
            // Liste des biens
            Route::get('/', \App\Livewire\Biens\ListeBiens::class)->name('index');
            
            // Création d'un bien
            Route::get('/create', \App\Livewire\Biens\FormBien::class)->name('create');
            
            // Amortissements
            Route::get('/amortissements', \App\Livewire\Biens\ListeAmortissements::class)->name('amortissements');
            
            // Transfert d'un bien
            Route::get('/transfert', \App\Livewire\Biens\TransfertBien::class)->name('transfert');
            
            // Historique des transferts
            Route::get('/transfert/historique', \App\Livewire\Biens\HistoriqueTransferts::class)->name('transfert.historique');
            
            // Édition d'un bien
            Route::get('/{bien}/edit', \App\Livewire\Biens\FormBien::class)->name('edit');
            
            // Détails d'un bien
            Route::get('/{bien}', \App\Livewire\Biens\DetailBien::class)->name('show');
            
            // Génération du QR code d'un bien
            Route::get('/{bien}/qr-code', [BienController::class, 'generateQRCode'])->name('qr-code');
            
            // Téléchargement de l'étiquette d'un bien (avec code-barres côté client)
            Route::post('/{bien}/etiquette', [BienController::class, 'downloadEtiquetteWithBarcode'])->name('etiquette.with-barcode');
            // Téléchargement de l'étiquette d'un bien (avec code-barres de la base)
            Route::get('/{bien}/etiquette', [BienController::class, 'downloadEtiquette'])->name('etiquette');
            
            // Impression en masse d'étiquettes
            Route::post('/imprimer-etiquettes', [BienController::class, 'imprimerEtiquettes'])->name('imprimer-etiquettes');
            
            // Impression groupée par emplacement (21 étiquettes par page A4)
            Route::post('/imprimer-etiquettes-par-emplacement', [BienController::class, 'imprimerEtiquettesParEmplacement'])->name('imprimer-etiquettes-par-emplacement');
            // Impression de toutes les étiquettes, groupées par emplacement (un seul fichier)
            Route::post('/imprimer-etiquettes-tous-emplacements', [BienController::class, 'imprimerEtiquettesTousEmplacements'])->name('imprimer-etiquettes-tous-emplacements');
            // Impression groupée par intervalle de NumOrdre (33 étiquettes par page A4)
            Route::post('/imprimer-etiquettes-par-intervalle', [BienController::class, 'imprimerEtiquettesParIntervalle'])->name('imprimer-etiquettes-par-intervalle');
            
            // Export Excel des biens
            Route::get('/export/excel', [BienController::class, 'exportExcel'])->name('export-excel');
        });

        /*
        |------------------------------------------------------------------
        | Inventaires
        |------------------------------------------------------------------
        |
        | Gestion des inventaires annuels
        |
        */
        Route::prefix('inventaires')->name('inventaires.')->group(function () {
            // Liste des inventaires
            Route::get('/', \App\Livewire\Inventaires\ListeInventaires::class)->name('index');
            
            // Création/démarrage d'un inventaire
            Route::get('/create', \App\Livewire\Inventaires\DemarrerInventaire::class)->name('create');
            
            // Dashboard d'un inventaire (vue principale)
            Route::get('/{inventaire}', \App\Livewire\Inventaires\DashboardInventaire::class)->name('show');
            
            // Rapport détaillé d'un inventaire
            Route::get('/{inventaire}/rapport', \App\Livewire\Inventaires\RapportInventaire::class)->name('rapport');
            
            // Export PDF d'un inventaire
            Route::get('/{inventaire}/export-pdf', [InventaireController::class, 'exportPDF'])->name('export-pdf');
            
            // Export Excel d'un inventaire
            Route::get('/{inventaire}/export-excel', [InventaireController::class, 'exportExcel'])->name('export-excel');
            
            // Clôture d'un inventaire
            Route::post('/{inventaire}/cloturer', [InventaireController::class, 'cloturer'])->name('cloturer');
        });

        /*
        |------------------------------------------------------------------
        | Rapports
        |------------------------------------------------------------------
        |
        | Génération de rapports personnalisés
        |
        */
        // Routes de rapports désactivées pour l'instant (composants à créer)
        // Route::prefix('rapports')->name('rapports.')->group(function () {
        //     // Page de génération de rapports
        //     Route::get('/', \App\Livewire\Rapports\GenerateurRapport::class)->name('index');
        //     
        //     // Génération d'un rapport
        //     Route::post('/generer', [\App\Http\Controllers\RapportController::class, 'generer'])->name('generer');
        // });
    });

    /*
    |----------------------------------------------------------------------
    | Routes avec Middleware 'admin' (Administrateur uniquement)
    |----------------------------------------------------------------------
    |
    | Ces routes sont accessibles uniquement aux administrateurs.
    |
    */
    Route::middleware(['admin'])->group(function () {

        /*
        |------------------------------------------------------------------
        | Utilisateurs
        |------------------------------------------------------------------
        |
        | Gestion des utilisateurs (création, modification, etc.)
        |
        */
        Route::prefix('users')->name('users.')->group(function () {
            // Liste des utilisateurs
            Route::get('/', \App\Livewire\Users\ListeUsers::class)->name('index');
            
            // Création d'un utilisateur
            Route::get('/create', \App\Livewire\Users\FormUser::class)->name('create');
            
            // Édition d'un utilisateur
            Route::get('/{user}/edit', \App\Livewire\Users\FormUser::class)->name('edit');
            
            // Gestion des rôles RBAC
            Route::get('/roles', \App\Livewire\Users\GestionRoles::class)->name('roles');
        });

        /*
        |------------------------------------------------------------------
        | Paramètres
        |------------------------------------------------------------------
        |
        | Configuration générale de l'application
        |
        */
        // Route::get('/settings', \App\Livewire\Settings\ParametresGeneraux::class)->name('settings.index');

    });
});
