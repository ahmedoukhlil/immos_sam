<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('affectation', function (Blueprint $table) {
            // Ajouter un index sur idLocalisation si il n'existe pas déjà
            if (!$this->hasIndex('affectation', 'affectation_idlocalisation_index')) {
                $table->index('idLocalisation', 'affectation_idlocalisation_index');
            }
        });

        Schema::table('emplacement', function (Blueprint $table) {
            // Ajouter un index sur idLocalisation si il n'existe pas déjà
            if (!$this->hasIndex('emplacement', 'emplacement_idlocalisation_index')) {
                $table->index('idLocalisation', 'emplacement_idlocalisation_index');
            }
            // Ajouter un index sur idAffectation si il n'existe pas déjà
            if (!$this->hasIndex('emplacement', 'emplacement_idaffectation_index')) {
                $table->index('idAffectation', 'emplacement_idaffectation_index');
            }
            // Index composite pour les requêtes combinées
            if (!$this->hasIndex('emplacement', 'emplacement_localisation_affectation_index')) {
                $table->index(['idLocalisation', 'idAffectation'], 'emplacement_localisation_affectation_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectation', function (Blueprint $table) {
            $table->dropIndex('affectation_idlocalisation_index');
        });

        Schema::table('emplacement', function (Blueprint $table) {
            $table->dropIndex('emplacement_idlocalisation_index');
            $table->dropIndex('emplacement_idaffectation_index');
            $table->dropIndex('emplacement_localisation_affectation_index');
        });
    }

    /**
     * Vérifie si un index existe déjà
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $indexes = $connection->select(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$databaseName, $table, $indexName]
        );
        
        return !empty($indexes);
    }
};
