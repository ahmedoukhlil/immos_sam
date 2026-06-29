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
        // Vérifier si la table existe déjà (elle a peut-être été créée manuellement)
        if (Schema::hasTable('inventaires')) {
            return;
        }
        
        Schema::create('inventaires', function (Blueprint $table) {
            $table->id();
            $table->year('annee');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['en_preparation', 'en_cours', 'termine', 'cloture'])->default('en_preparation');
            $table->foreignId('created_by')->constrained('users', 'idUser')->onDelete('restrict');
            $table->foreignId('closed_by')->nullable()->constrained('users', 'idUser')->onDelete('set null');
            $table->text('observation')->nullable();
            $table->timestamps();
            
            // Index
            $table->index('annee');
            $table->index('statut');
            $table->unique('annee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaires');
    }
};
