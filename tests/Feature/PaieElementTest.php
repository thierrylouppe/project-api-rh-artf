<?php

namespace Tests\Feature;

use App\Enums\CodePaieElement;
use App\Enums\ModeCalculPaieElement;
use App\Enums\NaturePaieElement;
use App\Models\PaieElement;
use App\Models\User;
use Database\Seeders\PaieElementSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaieElementTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, PaieElementSeeder::class]);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($this->rh, ['*']);
    }

    public function test_seeder_cree_les_codes_ccn(): void
    {
        $this->assertSame(count(CodePaieElement::cases()), PaieElement::query()->count());

        $this->getJson('/api/paie/elements')
            ->assertOk()
            ->assertJsonCount(count(CodePaieElement::cases()), 'data');

        $anciennete = $this->element(CodePaieElement::PRIME_ANCIENNETE);
        $this->assertTrue($anciennete->systeme);
        $this->assertTrue($anciennete->actif);
        $this->assertSame(ModeCalculPaieElement::FORMULE_CCN, $anciennete->mode_calcul);
        $this->assertFalse($anciennete->aParametrer());

        $responsabilite = $this->element(CodePaieElement::PRIME_RESPONSABILITE);
        $this->assertNull($responsabilite->montant_defaut);
        $this->assertTrue($responsabilite->aParametrer());
        $this->assertSame(['CB', 'CS', 'CSR', 'DD', 'DC', 'DG'], $responsabilite->fonction_sigles);

        $retraite = $this->element(CodePaieElement::INDEMNITE_RETRAITE);
        $this->assertFalse($retraite->actif);
        $this->assertSame('119', $retraite->article_ccn);

        $deces = $this->element(CodePaieElement::CAPITAL_DECES);
        $this->assertFalse($deces->actif);
        $this->assertSame('121', $deces->article_ccn);
    }

    public function test_filtre_nature_et_actif(): void
    {
        $primes = $this->getJson('/api/paie/elements?nature=prime')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($primes);
        foreach ($primes as $item) {
            $this->assertSame('prime', $item['nature']);
        }

        $inactifs = $this->getJson('/api/paie/elements?actif=0')
            ->assertOk()
            ->json('data');

        $codes = collect($inactifs)->pluck('code')->all();
        $this->assertContains(CodePaieElement::INDEMNITE_RETRAITE->value, $codes);
        $this->assertContains(CodePaieElement::CAPITAL_DECES->value, $codes);
    }

    public function test_crud_element_non_systeme(): void
    {
        $created = $this->postJson('/api/paie/elements', [
            'code' => 'retenue_avance',
            'libelle' => 'Avance sur salaire',
            'nature' => NaturePaieElement::RETENUE->value,
            'periodicite' => 'ponctuel',
            'mode_calcul' => ModeCalculPaieElement::MONTANT_FIXE->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'retenue_avance')
            ->assertJsonPath('data.sens', 'retenue')
            ->assertJsonPath('data.systeme', false)
            ->assertJsonPath('data.a_parametrer', true);

        $id = $created->json('data.id');

        $this->putJson("/api/paie/elements/{$id}", [
            'libelle' => 'Avance sur salaire (maj)',
            'montant_defaut' => 25000,
        ])
            ->assertOk()
            ->assertJsonPath('data.libelle', 'Avance sur salaire (maj)')
            ->assertJsonPath('data.montant_defaut', 25000)
            ->assertJsonPath('data.a_parametrer', false);

        $this->getJson("/api/paie/elements/{$id}")
            ->assertOk()
            ->assertJsonPath('data.code', 'retenue_avance');

        $this->deleteJson("/api/paie/elements/{$id}")
            ->assertOk();

        $this->getJson("/api/paie/elements/{$id}")
            ->assertNotFound();
    }

    public function test_code_ccn_reserve_et_non_supprimable(): void
    {
        $this->postJson('/api/paie/elements', [
            'code' => CodePaieElement::PRIME_ANCIENNETE->value,
            'libelle' => 'Doublon',
            'nature' => NaturePaieElement::PRIME->value,
            'periodicite' => 'mensuel',
            'mode_calcul' => ModeCalculPaieElement::MONTANT_FIXE->value,
        ])->assertStatus(422);

        $element = $this->element(CodePaieElement::PRIME_RESPONSABILITE);

        $this->deleteJson("/api/paie/elements/{$element->id}")
            ->assertStatus(422);

        $this->putJson("/api/paie/elements/{$element->id}", [
            'mode_calcul' => ModeCalculPaieElement::FORMULE_CCN->value,
        ])->assertStatus(422);

        $this->putJson("/api/paie/elements/{$element->id}", [
            'code' => 'prime_autre',
        ])->assertStatus(422);
    }

    public function test_element_ccn_montant_modifiable(): void
    {
        $element = $this->element(CodePaieElement::PRIME_RESPONSABILITE);

        $this->putJson("/api/paie/elements/{$element->id}", [
            'code' => $element->code,
            'libelle' => $element->libelle,
            'nature' => $element->nature->value,
            'montant_defaut' => 75000,
            'actif' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.montant_defaut', 75000)
            ->assertJsonPath('data.systeme', true)
            ->assertJsonPath('data.mode_calcul', ModeCalculPaieElement::MONTANT_FIXE->value)
            ->assertJsonPath('data.a_parametrer', false);
    }

    public function test_interdit_sans_permission(): void
    {
        $sansDroit = User::factory()->create();
        Sanctum::actingAs($sansDroit, ['*']);

        $this->getJson('/api/paie/elements')->assertForbidden();
        $this->postJson('/api/paie/elements', [
            'code' => 'x',
            'libelle' => 'X',
            'nature' => 'prime',
            'periodicite' => 'mensuel',
            'mode_calcul' => 'montant_fixe',
        ])->assertForbidden();
    }

    private function element(CodePaieElement $code): PaieElement
    {
        return PaieElement::query()->where('code', $code->value)->firstOrFail();
    }
}
