<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SourceFinancement;

class SourceFinancementSeeder extends Seeder
{
    private array $sourcesFinancement = [
        ['CodeSourceFin' => 'FP',   'SourceFin' => 'Fonds Propres'],
        ['CodeSourceFin' => 'PART', 'SourceFin' => 'Partenariat'],
        ['CodeSourceFin' => 'SUBV', 'SourceFin' => 'Subvention de l\'Etat'],
    ];

    public function run(): void
    {
        foreach ($this->sourcesFinancement as $source) {
            SourceFinancement::updateOrCreate(
                ['CodeSourceFin' => $source['CodeSourceFin']],
                $source
            );
        }

        $this->command->info('✅ Sources de financement créées : ' . count($this->sourcesFinancement));
    }
}
