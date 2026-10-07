<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Fonction;
use App\Models\User;
use App\Services\RepriseHierarchieGestRhService;
use Database\Seeders\AgentsGestRhSeeder;
use Illuminate\Console\Command;

class ReprendreHierarchieGestRhCommand extends Command
{
    protected $signature = 'rh:reprendre-hierarchie {--verifier-seule : Compare le dump et la base sans écrire}';

    protected $description = 'Pose les nominations et les N+1 à partir des fonctions et affectations du dump gestRHdb';

    public function handle(RepriseHierarchieGestRhService $service): int
    {
        $controle = $this->confronter();
        foreach ($controle['corrections'] as $correction) {
            $this->warn($correction);
        }
        if ($controle['erreurs'] !== []) {
            $this->error(count($controle['erreurs']).' écart(s) entre le dump gestRHdb et les affectations actuelles.');
            foreach (array_slice($controle['erreurs'], 0, 30) as $ecart) {
                $this->line(' - '.$ecart);
            }
            if (count($controle['erreurs']) > 30) {
                $this->line(' - … '.(count($controle['erreurs']) - 30).' autre(s)');
            }

            return self::FAILURE;
        }

        $this->info('Dump et base concordent sur la structure courante.');

        if ($this->option('verifier-seule')) {
            return self::SUCCESS;
        }

        $createdBy = User::query()->where('email', 'admin@artf.cg')->value('id');
        if ($createdBy === null) {
            $this->error('Utilisateur admin@artf.cg introuvable.');

            return self::FAILURE;
        }

        $bilan = $service->appliquer((int) $createdBy);

        $this->info(sprintf(
            '%d agents placés. Nominations créées : %d (déjà présentes : %d). Liens N+1 écrits : %d (déjà à jour : %d).',
            $bilan['personnes'],
            $bilan['nominations_creees'],
            $bilan['nominations_deja'],
            $bilan['liens_mis_a_jour'],
            $bilan['liens_deja'],
        ));

        if ($bilan['ambigus'] !== []) {
            $this->warn('Structures avec plusieurs chefs du même niveau — pas de nomination, le N+1 remonte au niveau suivant :');
            foreach ($bilan['ambigus'] as $ambigu) {
                $this->line(' - '.$ambigu);
            }
        }

        if ($bilan['sans_superieur'] !== []) {
            $this->line('Sans supérieur : '.implode(', ', $bilan['sans_superieur']));
        }

        return self::SUCCESS;
    }

    /**
     * Le dump a parfois deux fiches pour le même matricule. La plus récente
     * (identifiant le plus élevé) est la fiche courante.
     *
     * @return array{erreurs: list<string>, corrections: list<string>}
     */
    private function confronter(): array
    {
        $dump = $this->fichesCourantes((new AgentsGestRhSeeder)->extrairePlacements());
        $agents = Agent::query()->with(['fonction', 'affectationActive'])->get();
        $parCle = [];
        $parMatricule = [];

        foreach ($agents as $agent) {
            $date = $agent->date_naissance?->toDateString();
            $cle = $this->norm($agent->nom).'|'.$this->norm($agent->prenom).'|'.($date ?? '');
            $parCle[$cle][] = $agent;
            if ($agent->matricule) {
                $parMatricule[mb_strtoupper(trim($agent->matricule))][] = $agent;
            }
        }

        $erreurs = [];
        $corrections = [];
        $vus = [];

        foreach ($dump as $fiche) {
            if ($fiche['conflit']) {
                $erreurs[] = $fiche['nom'].' '.$fiche['prenom'].' : deux placements contradictoires dans le dump';
                continue;
            }

            if ($fiche['placement'] === null) {
                continue;
            }

            $agent = $this->trouverAgent($fiche, $parMatricule, $parCle);
            if (! $agent instanceof Agent) {
                $erreurs[] = $fiche['nom'].' '.$fiche['prenom'].' : agent introuvable en base';
                continue;
            }

            $vus[$agent->id] = true;
            $fonction = $agent->fonction?->nom ?? 'Agent';
            if ($this->norm($fonction) !== $this->norm($fiche['fonction'])) {
                $fonctionId = Fonction::query()->where('nom', $fiche['fonction'])->value('id');
                if ($fonctionId === null) {
                    $erreurs[] = $fiche['nom'].' '.$fiche['prenom'].' : fonction « '.$fiche['fonction'].' » absente du référentiel';
                    continue;
                }
                if (! $this->option('verifier-seule')) {
                    $agent->update(['fonction_id' => $fonctionId]);
                }
                $corrections[] = trim($agent->nom.' '.$agent->prenom)
                    .' : fonction alignée sur la fiche dump la plus récente (« '.$fiche['fonction'].' », matricule '.($fiche['matricule'] ?? '—').')';
            }

            $affectation = $agent->affectationActive;
            $placement = $fiche['placement'];
            if ($affectation === null
                || $affectation->structurable_type !== $placement['type']
                || (int) $affectation->structurable_id !== $placement['id']) {
                $erreurs[] = $fiche['nom'].' '.$fiche['prenom'].' : structure dump différente de l\'affectation active';
            }
        }

        foreach ($agents as $agent) {
            if ($agent->affectationActive === null || isset($vus[$agent->id])) {
                continue;
            }
            $erreurs[] = $agent->nom.' '.$agent->prenom.' : affectation active absente du dump';
        }

        return ['erreurs' => $erreurs, 'corrections' => $corrections];
    }

    /**
     * @param  list<array<string, mixed>>  $fiches
     * @return list<array<string, mixed>>
     */
    private function fichesCourantes(array $fiches): array
    {
        $retenues = [];
        $parMatricule = [];

        foreach ($fiches as $fiche) {
            if ($fiche['matricule'] === null) {
                $retenues[] = $fiche;
                continue;
            }
            $parMatricule[$fiche['matricule']][] = $fiche;
        }

        foreach ($parMatricule as $groupe) {
            usort($groupe, fn (array $a, array $b) => $a['ancien_id'] <=> $b['ancien_id']);
            $retenues[] = $groupe[array_key_last($groupe)];
        }

        return $retenues;
    }

    /**
     * @param  array<string, mixed>  $fiche
     * @param  array<string, list<Agent>>  $parMatricule
     * @param  array<string, list<Agent>>  $parCle
     */
    private function trouverAgent(array $fiche, array $parMatricule, array $parCle): ?Agent
    {
        if ($fiche['matricule'] !== null) {
            $liste = $parMatricule[$fiche['matricule']] ?? [];
            if (count($liste) === 1) {
                return $liste[0];
            }
        }

        $liste = $parCle[$fiche['cle']] ?? [];
        if (count($liste) === 1) {
            return $liste[0];
        }

        $places = array_values(array_filter($liste, fn (Agent $agent) => $agent->affectationActive !== null));

        return count($places) === 1 ? $places[0] : null;
    }

    private function norm(string $valeur): string
    {
        if (class_exists(\Normalizer::class)) {
            $decompose = \Normalizer::normalize($valeur, \Normalizer::FORM_D);
            if (is_string($decompose)) {
                $valeur = preg_replace('/\p{Mn}/u', '', $decompose) ?? $valeur;
            }
        }

        $valeur = mb_strtoupper($valeur);
        $valeur = preg_replace('/[^A-Z0-9]+/u', ' ', $valeur) ?? $valeur;

        return trim(preg_replace('/\s+/', ' ', $valeur) ?? $valeur);
    }
}
