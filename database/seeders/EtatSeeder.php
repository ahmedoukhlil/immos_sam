<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Etat;

class EtatSeeder extends Seeder
{
    private array $etats = [
        ['CodeEtat' => 'NEUF', 'Etat' => 'Neuf'],
        ['CodeEtat' => 'BON',  'Etat' => 'Bon'],
        ['CodeEtat' => 'HU',   'Etat' => 'Hors usage'],
    ];

    public function run(): void
    {
        foreach ($this->etats as $etat) {
            Etat::updateOrCreate(
                ['CodeEtat' => $etat['CodeEtat']],
                $etat
            );
        }

        $this->command->info('✅ États créés : ' . count($this->etats));
    }
}
