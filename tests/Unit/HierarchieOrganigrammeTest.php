<?php

namespace Tests\Unit;

use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use App\Services\HierarchieOrganigramme;
use PHPUnit\Framework\TestCase;

class HierarchieOrganigrammeTest extends TestCase
{
    public function test_le_service_information_remonte_jusqu_a_flora_sauf_chef_de_bureau_unique(): void
    {
        $plan = (new HierarchieOrganigramme)->calculer([
            $this->personne(1, 5, 'Directeur Général', Direction::class, 1, null, 1),
            $this->personne(78, 3, 'Chef de service', Service::class, 1, 1, 1),
            $this->personne(96, 2, 'Chef de bureau', Bureau::class, 2, 1, 1),
            $this->personne(98, 1, 'Agent', Bureau::class, 2, 1, 1),
            $this->personne(99, 2, 'Chef de bureau', Bureau::class, 3, 1, 1),
            $this->personne(100, 2, 'Chef de bureau', Bureau::class, 3, 1, 1),
            $this->personne(101, 1, 'Agent', Bureau::class, 3, 1, 1),
            $this->personne(94, 1, 'Agent', Bureau::class, 1, 1, 1),
        ]);

        $liens = [];
        foreach ($plan['liens'] as $lien) {
            $liens[$lien['agent_id']] = $lien['superieur_id'];
        }

        $this->assertNull($liens[1]);
        $this->assertSame(1, $liens[78]);
        $this->assertSame(78, $liens[96]);
        $this->assertSame(96, $liens[98]);
        $this->assertSame(78, $liens[99]);
        $this->assertSame(78, $liens[100]);
        $this->assertSame(78, $liens[101]);
        $this->assertSame(78, $liens[94]);

        $nominations = array_map(
            fn (array $nomination) => $nomination['agent_id'].'@'.$nomination['structure_id'],
            $plan['nominations'],
        );
        $this->assertContains('1@1', $nominations);
        $this->assertContains('78@1', $nominations);
        $this->assertContains('96@2', $nominations);
        $this->assertNotContains('99@3', $nominations);
        $this->assertNotContains('100@3', $nominations);
        $this->assertCount(1, $plan['ambigus']);
    }

    /**
     * @return array<string, mixed>
     */
    private function personne(int $id, int $rang, string $poste, string $type, int $structure, ?int $service, ?int $direction): array
    {
        return [
            'agent_id' => $id,
            'affectation_id' => $id,
            'rang' => $rang,
            'poste' => $poste,
            'type' => $type,
            'structure_id' => $structure,
            'service_id' => $service,
            'direction_id' => $direction,
            'date_debut' => '2023-10-04',
        ];
    }
}
