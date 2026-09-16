<?php

namespace Tests\Feature;

use App\Services\PaieCalculService;
use Tests\TestCase;

class PaieCalculAncienneteTest extends TestCase
{
    public function test_taux_art_56(): void
    {
        $calcul = new PaieCalculService;

        $this->assertSame(0.0, $calcul->tauxAnciennete(0));
        $this->assertSame(0.0, $calcul->tauxAnciennete(1));
        $this->assertSame(0.02, $calcul->tauxAnciennete(2));
        $this->assertSame(0.05, $calcul->tauxAnciennete(5));
        $this->assertSame(0.40, $calcul->tauxAnciennete(50));
    }

    public function test_montant_arrondi_half_up(): void
    {
        $calcul = new PaieCalculService;

        $this->assertSame(0, $calcul->montantAnciennete(100_000, 1));
        $this->assertSame(2_000, $calcul->montantAnciennete(100_000, 2));
        $this->assertSame(5_000, $calcul->montantAnciennete(100_000, 5));
        $this->assertSame(40_000, $calcul->montantAnciennete(100_000, 50));
        $this->assertSame(1, $calcul->arrondirFcfa(0.5));
    }

    public function test_fin_annee_arbre_et_formation(): void
    {
        $calcul = new PaieCalculService;

        $this->assertSame(105_000, $calcul->montantFinAnnee(100_000, 5_000));
        $this->assertSame(25_000, $calcul->montantArbreNoel(25_000, 0));
        $this->assertSame(50_000, $calcul->montantArbreNoel(25_000, 2));
        $this->assertSame(75_000, $calcul->montantArbreNoel(25_000, 5));
        $this->assertSame(85_000, $calcul->montantFormationAfrique(100_000));
    }

    public function test_bareme_mission(): void
    {
        $calcul = new PaieCalculService;

        $this->assertSame(250_000, $calcul->tauxJournalierMissionLocale('DG', null, false));
        $this->assertSame(200_000, $calcul->tauxJournalierMissionLocale('DG', null, true));
        $this->assertSame(150_000, $calcul->tauxJournalierMissionLocale('CS', 1200, false));
        $this->assertSame(100_000, $calcul->tauxJournalierMissionLocale(null, 800, false));
        $this->assertSame(400_000, $calcul->tauxJournalierMissionEtranger('DG', true));
        $this->assertSame(500_000, $calcul->tauxJournalierMissionEtranger('DG', false));
    }
}
