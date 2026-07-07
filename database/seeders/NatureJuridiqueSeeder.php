<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NatureJuridique;

class NatureJuridiqueSeeder extends Seeder
{
    private array $naturesJuridiques = [
        ['CodeNatJur' => 'PROP', 'NatJur' => 'Propriété'],
        ['CodeNatJur' => 'DON',  'NatJur' => 'Don'],
    ];

    public function run(): void
    {
        foreach ($this->naturesJuridiques as $nature) {
            NatureJuridique::updateOrCreate(
                ['CodeNatJur' => $nature['CodeNatJur']],
                $nature
            );
        }

        $this->command->info('✅ Natures juridiques créées : ' . count($this->naturesJuridiques));
    }
}
