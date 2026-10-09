<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CompteFusionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Fusionne les comptes utilisateurs en double (voir CompteFusionService).
 *
 *   php artisan comptes:fusionner                      bilan seul, aucune écriture
 *   php artisan comptes:fusionner --appliquer          fusionne les paires sûres
 *   php artisan comptes:fusionner --appliquer --purger retire aussi les comptes sans personne derrière
 *   php artisan comptes:fusionner --paire=12:345       force une paire (absorbé:conservé)
 *   php artisan comptes:fusionner --json               bilan JSON
 */
class FusionnerComptesCommand extends Command
{
    protected $signature = 'comptes:fusionner
        {--appliquer : Effectue les fusions (sinon simple bilan)}
        {--paire=* : Paire forcée ABSORBE:CONSERVE (ids utilisateurs), répétable}
        {--purger : Retire aussi les comptes sans fiche, désactivés et sans correspondant : supprimés si aucun historique}
        {--json : Affiche le bilan au format JSON}';

    protected $description = 'Fusionne les comptes en double : le compte sans fiche est absorbé par celui de la fiche agent, historique conservé';

    private const LIBELLES = [
        'email' => 'adresse suffixée par le matricule',
        'identite' => 'adresse prenom.nom de la fiche',
        'nom' => 'même nom',
        'meme_fiche' => 'même fiche agent',
    ];

    public function handle(CompteFusionService $service): int
    {
        $forcees = $this->pairesForcees();
        if ($forcees === null) {
            return self::FAILURE;
        }

        if ($forcees !== []) {
            return $this->appliquer($service, $forcees, json: (bool) $this->option('json'));
        }

        $bilan = $service->bilan();
        $paires = array_map(fn (array $p) => [$p['absorbe']->id, $p['conserve']->id], $bilan['paires']);
        $aPurger = $this->option('purger')
            ? array_values(array_map(fn (User $u) => $u->id, array_filter($bilan['sans_correspondant'], fn (User $u) => ! $u->is_active)))
            : [];

        if ($this->option('json')) {
            $sortie = $this->bilanJson($bilan);
            if ($this->option('appliquer')) {
                $sortie['resultats'] = $this->executer($service, $paires);
                $sortie['purge'] = $this->purger($service, $aPurger);
            }
            $this->line(json_encode($sortie, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->afficherBilan($bilan);

        if (! $this->option('appliquer')) {
            $this->newLine();
            if ($this->option('purger')) {
                $this->line(count($aPurger).' compte(s) désactivé(s) sans correspondant seraient retirés par --purger.');
            }
            $this->warn('Simple bilan : rien n\'a été modifié. Relancer avec --appliquer pour fusionner les paires ci-dessus.');

            return self::SUCCESS;
        }

        $code = $this->appliquer($service, $paires, json: false);

        if ($aPurger !== []) {
            $purge = $this->purger($service, $aPurger);
            $supprimes = count(array_filter($purge, fn (array $r) => ($r['resultat'] ?? null) === 'supprime'));
            $conserves = count(array_filter($purge, fn (array $r) => ($r['resultat'] ?? null) === 'conserve'));
            $echecs = array_filter($purge, fn (array $r) => isset($r['erreur']));
            $this->info("Purge : {$supprimes} compte(s) supprimé(s), {$conserves} gardé(s) désactivé(s) car cités dans l'historique.");
            foreach ($echecs as $r) {
                $this->error("Purge impossible pour #{$r['id']} : {$r['erreur']}");
            }
            if ($echecs !== []) {
                $code = self::FAILURE;
            }
        }

        return $code;
    }

    /**
     * @param  list<int>  $ids
     * @return list<array{id: int, resultat?: string, erreur?: string}>
     */
    private function purger(CompteFusionService $service, array $ids): array
    {
        $resultats = [];
        foreach ($ids as $id) {
            try {
                $resultats[] = ['id' => $id, 'resultat' => $service->purger($id)];
            } catch (Throwable $e) {
                $resultats[] = ['id' => $id, 'erreur' => $e->getMessage()];
            }
        }

        return $resultats;
    }

    /**
     * @param  list<array{0: int, 1: int}>  $paires
     */
    private function appliquer(CompteFusionService $service, array $paires, bool $json): int
    {
        $resultats = $this->executer($service, $paires);

        if ($json) {
            $this->line(json_encode(['resultats' => $resultats], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->newLine();
            foreach ($resultats as $r) {
                if ($r['ok']) {
                    $this->info(sprintf(
                        'Fusionné : #%d %s → #%d, adresse finale %s (%d ligne(s) d\'historique repointée(s)).',
                        $r['absorbe_id'], $r['email_absorbe'], $r['conserve_id'], $r['email_apres'], array_sum($r['lignes']),
                    ));
                } else {
                    $this->error(sprintf('Échec : #%d → #%d : %s', $r['absorbe_id'], $r['conserve_id'], $r['erreur']));
                }
            }
            $ok = count(array_filter($resultats, fn (array $r) => $r['ok']));
            $this->newLine();
            $this->info("$ok fusion(s) effectuée(s), ".(count($resultats) - $ok).' échec(s). Mots de passe des comptes conservés inchangés.');
        }

        return collect($resultats)->every(fn (array $r) => $r['ok']) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Une transaction par paire : un échec n'annule que sa paire.
     *
     * @param  list<array{0: int, 1: int}>  $paires
     * @return list<array<string, mixed>>
     */
    private function executer(CompteFusionService $service, array $paires): array
    {
        $resultats = [];
        foreach ($paires as [$absorbe, $conserve]) {
            try {
                $resultats[] = ['ok' => true] + $service->fusionner($absorbe, $conserve);
            } catch (Throwable $e) {
                $resultats[] = ['ok' => false, 'absorbe_id' => $absorbe, 'conserve_id' => $conserve, 'erreur' => $e->getMessage()];
            }
        }

        return $resultats;
    }

    /** @return list<array{0: int, 1: int}>|null null si une option --paire est mal formée */
    private function pairesForcees(): ?array
    {
        $paires = [];
        foreach ((array) $this->option('paire') as $valeur) {
            if (preg_match('/^(\d+):(\d+)$/', (string) $valeur, $m) !== 1) {
                $this->error("Option --paire invalide : « {$valeur} ». Format attendu : ID_ABSORBE:ID_CONSERVE.");

                return null;
            }
            $paires[] = [(int) $m[1], (int) $m[2]];
        }

        if ($paires !== [] && ! $this->option('appliquer')) {
            $this->warn('--paire sans --appliquer : rien n\'est écrit. Ajouter --appliquer pour fusionner.');
            foreach ($paires as [$a, $c]) {
                $this->line(" - #{$a} sera absorbé par #{$c}");
            }

            return [];
        }

        return $paires;
    }

    private function afficherBilan(array $bilan): void
    {
        $this->info(count($bilan['paires']).' paire(s) à fusionner :');
        if ($bilan['paires'] !== []) {
            $this->table(
                ['Absorbé', 'E-mail absorbé', 'Conservé', 'E-mail conservé', 'Agent', 'Critère'],
                array_map(fn (array $p) => [
                    $p['absorbe']->id,
                    $p['absorbe']->email,
                    $p['conserve']->id,
                    $p['conserve']->email,
                    $this->agent($p['conserve']),
                    self::LIBELLES[$p['critere']] ?? $p['critere'],
                ], $bilan['paires']),
            );
        }

        $this->newLine();
        $this->warn(count($bilan['ambigues']).' cas ambigu(s), non fusionné(s) : à trancher avec --paire=ABSORBE:CONSERVE.');
        foreach ($bilan['ambigues'] as $cas) {
            $this->line(sprintf(' - #%d %s (%s) ; candidats : %s', $cas['compte']->id, $cas['compte']->email, $cas['motif'],
                implode(', ', array_map(fn (User $u) => "#{$u->id} {$u->email}", $cas['candidats']))));
        }

        $this->newLine();
        $this->line(count($bilan['sans_correspondant']).' compte(s) sans fiche ni correspondant (à vérifier à la main) :');
        foreach ($bilan['sans_correspondant'] as $compte) {
            $this->line(sprintf(' - #%d %s %s', $compte->id, $compte->email, $compte->is_active ? '' : '(désactivé)'));
        }
    }

    /** @return array<string, mixed> */
    private function bilanJson(array $bilan): array
    {
        $compte = fn (User $u) => ['id' => $u->id, 'email' => $u->email, 'name' => $u->name, 'actif' => (bool) $u->is_active];

        return [
            'paires' => array_map(fn (array $p) => [
                'absorbe' => $compte($p['absorbe']),
                'conserve' => $compte($p['conserve']) + ['agent' => $this->agent($p['conserve'])],
                'critere' => $p['critere'],
            ], $bilan['paires']),
            'ambigues' => array_map(fn (array $cas) => [
                'compte' => $compte($cas['compte']),
                'motif' => $cas['motif'],
                'candidats' => array_map($compte, $cas['candidats']),
            ], $bilan['ambigues']),
            'sans_correspondant' => array_map($compte, $bilan['sans_correspondant']),
        ];
    }

    private function agent(User $compte): string
    {
        $agent = $compte->agent;

        return $agent ? trim("{$agent->nom} {$agent->prenom}").($agent->matricule ? " ({$agent->matricule})" : '') : '—';
    }
}
