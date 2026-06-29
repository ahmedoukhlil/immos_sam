<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Cette migration corrige la structure de la table users si elle a été créée
     * avec l'ancienne structure (name/email) au lieu de la nouvelle (users/mdp)
     */
    public function up(): void
    {
        // Vérifier si la table existe
        if (!Schema::hasTable('users')) {
            // Créer la table avec la bonne structure
            DB::statement("
                CREATE TABLE `users` (
                    `idUser` INT NOT NULL AUTO_INCREMENT,
                    `users` VARCHAR(255) NOT NULL,
                    `mdp` VARCHAR(255) NOT NULL,
                    `role` VARCHAR(50) NOT NULL,
                    PRIMARY KEY (`idUser`),
                    UNIQUE INDEX `users_users_unique` (`users`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return;
        }

        // Vérifier si la colonne 'users' existe
        $columns = DB::select("SHOW COLUMNS FROM `users`");
        $columnNames = array_column($columns, 'Field');
        
        // Si la colonne 'users' n'existe pas mais 'name' existe, on doit migrer
        if (!in_array('users', $columnNames) && in_array('name', $columnNames)) {
            // Ajouter la colonne 'users' si elle n'existe pas
            if (!in_array('users', $columnNames)) {
                DB::statement("ALTER TABLE `users` ADD COLUMN `users` VARCHAR(255) NULL AFTER `id`");
            }
            
            // Copier les données de 'name' vers 'users'
            DB::statement("UPDATE `users` SET `users` = `name` WHERE `users` IS NULL");
            
            // Rendre la colonne NOT NULL
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `users` VARCHAR(255) NOT NULL");
            
            // Ajouter l'index unique
            try {
                DB::statement("ALTER TABLE `users` ADD UNIQUE INDEX `users_users_unique` (`users`)");
            } catch (\Exception $e) {
                // L'index existe peut-être déjà, on ignore
            }
        }
        
        // Vérifier si la colonne 'mdp' existe, sinon créer depuis 'password'
        if (!in_array('mdp', $columnNames) && in_array('password', $columnNames)) {
            // Ajouter la colonne 'mdp' si elle n'existe pas
            DB::statement("ALTER TABLE `users` ADD COLUMN `mdp` VARCHAR(255) NULL AFTER `users`");
            
            // Copier les données de 'password' vers 'mdp'
            DB::statement("UPDATE `users` SET `mdp` = `password` WHERE `mdp` IS NULL");
            
            // Rendre la colonne NOT NULL
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `mdp` VARCHAR(255) NOT NULL");
        }
        
        // Vérifier si la colonne 'role' existe
        if (!in_array('role', $columnNames)) {
            DB::statement("ALTER TABLE `users` ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'agent' AFTER `mdp`");
        }
        
        // Vérifier si la clé primaire est 'idUser' ou 'id'
        $primaryKey = DB::select("SHOW KEYS FROM `users` WHERE Key_name = 'PRIMARY'");
        if (!empty($primaryKey) && $primaryKey[0]->Column_name === 'id' && !in_array('idUser', $columnNames)) {
            // Renommer la colonne 'id' en 'idUser'
            DB::statement("ALTER TABLE `users` CHANGE COLUMN `id` `idUser` INT NOT NULL AUTO_INCREMENT");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne rien faire en reverse pour éviter de perdre des données
    }
};
