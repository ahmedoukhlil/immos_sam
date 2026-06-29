<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Création de la table users selon immos.md
        if (!Schema::hasTable('users')) {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
