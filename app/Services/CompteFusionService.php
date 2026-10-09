<?php

namespace App\Services;

use App\Interfaces\CompteFusionInterface;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fusion des comptes utilisateurs en double.
 *
 * ## D'où viennent les doublons
 *
 * 1. Un ancien compte `prenom.nom@artf.cg` a perdu sa fiche agent : l'agent de
 *    démonstration (`ARFT-…`) a été supprimé par AgentsGestRhSeeder, le compte
 *    a seulement été désactivé (`users.agent_id` n'a pas de clé étrangère).
 *    L'import a ensuite créé pour la vraie fiche un second compte, suffixé :
 *    `prenom.nom.<matricule>@artf.cg`.
 * 2. Un agent qui avait déjà un compte mais pas de fiche d'intégration a reçu
 *    un second compte rattaché à la **même** fiche.
 *
 * ## Règles
 *
 * - On ne supprime jamais d'historique : toutes les références au compte absorbé
 *   (clés étrangères, colonnes polymorphes, rôles) sont repointées vers le compte
 *   conservé, puis seul le compte absorbé disparaît.
 * - Le compte conservé est toujours celui qui a la fiche agent.
 * - Il récupère l'adresse simple de l'absorbé quand c'est l'adresse canonique de
 *   l'agent (`prenom.nom@artf.cg`) ou que la sienne n'en est que la variante
 *   suffixée. Son mot de passe ne change pas.
 * - Une correspondance qui n'est pas univoque n'est jamais fusionnée d'office :
 *   elle est signalée « ambiguë » et se force à la main (`--paire`).
 * - Les comptes système de UserSeeder ne sont jamais touchés.
 */
class CompteFusionService
{
    public const COMPTES_SYSTEME = [
        'admin@artf.cg',
        'rh@artf.cg',
        'dg@artf.cg',
        'directeur@artf.cg',
        'chef-service@artf.cg',
        'chef-bureau@artf.cg',
        'agent@artf.cg',
    ];

    private const DOMAINE = 'artf.cg';

    /** Force des critères : le plus fort l'emporte quand plusieurs s'appliquent. */
    private const CRITERES = ['email' => 3, 'identite' => 2, 'nom' => 1];

    /** @var array<string, int>|null nombre d'agents par clé d'identité */
    private ?array $homonymes = null;

    public function __construct(private readonly CompteFusionInterface $comptes) {}

    // ─── Bilan ───────────────────────────────────────────────────────────────

    /**
     * Ce que la fusion ferait, sans rien écrire.
     *
     * @return array{
     *     paires: list<array{absorbe: User, conserve: User, critere: string}>,
     *     ambigues: list<array{compte: User, candidats: list<User>, motif: string}>,
     *     sans_correspondant: list<User>
     * }
     */
    public function bilan(): array
    {
        $avecFiche = $this->comptes->comptesAvecFiche(self::COMPTES_SYSTEME);
        $paires = [];

        // 1. Plusieurs comptes sur la même fiche agent.
        $absorbesMemeFiche = [];
        foreach ($avecFiche->groupBy('agent_id')->filter(fn (Collection $g) => $g->count() > 1) as $agentId => $groupe) {
            $conserve = $this->choisirConserve($groupe, (int) $agentId);
            foreach ($groupe as $compte) {
                if ($compte->id !== $conserve->id) {
                    $paires[] = ['absorbe' => $compte, 'conserve' => $conserve, 'critere' => 'meme_fiche'];
                    $absorbesMemeFiche[$compte->id] = true;
                }
            }
        }

        // 2. Comptes sans fiche → compte avec fiche de la même personne.
        $cibles = $avecFiche->reject(fn (User $u) => isset($absorbesMemeFiche[$u->id]))->values();
        $candidatsParOrphelin = [];
        $orphelinsParCible = [];

        foreach ($this->comptes->comptesSansFiche(self::COMPTES_SYSTEME) as $orphelin) {
            $candidats = [];
            foreach ($cibles as $cible) {
                $critere = $this->critere($orphelin, $cible);
                if ($critere !== null) {
                    $candidats[] = ['cible' => $cible, 'critere' => $critere];
                    $orphelinsParCible[$cible->id][] = $orphelin->id;
                }
            }
            $candidatsParOrphelin[$orphelin->id] = ['compte' => $orphelin, 'candidats' => $candidats];
        }

        $ambigues = [];
        $sansCorrespondant = [];

        foreach ($candidatsParOrphelin as ['compte' => $orphelin, 'candidats' => $candidats]) {
            if ($candidats === []) {
                $sansCorrespondant[] = $orphelin;

                continue;
            }

            if (count($candidats) > 1) {
                $ambigues[] = [
                    'compte' => $orphelin,
                    'candidats' => array_column($candidats, 'cible'),
                    'motif' => 'plusieurs comptes avec fiche correspondent',
                ];

                continue;
            }

            $cible = $candidats[0]['cible'];
            if (count($orphelinsParCible[$cible->id]) > 1) {
                $ambigues[] = [
                    'compte' => $orphelin,
                    'candidats' => [$cible],
                    'motif' => 'ce compte avec fiche correspond à plusieurs comptes sans fiche',
                ];

                continue;
            }

            $paires[] = ['absorbe' => $orphelin, 'conserve' => $cible, 'critere' => $candidats[0]['critere']];
        }

        return [
            'paires' => $paires,
            'ambigues' => $ambigues,
            'sans_correspondant' => $sansCorrespondant,
        ];
    }

    // ─── Fusion ──────────────────────────────────────────────────────────────

    /**
     * Fusionne `$absorbeId` dans `$conserveId`, en une transaction.
     *
     * @return array{
     *     absorbe_id: int, conserve_id: int,
     *     email_absorbe: string, email_avant: string, email_apres: string,
     *     roles_ajoutes: list<string>, lignes: array<string, int>
     * }
     *
     * @throws InvalidArgumentException si la paire n'est pas fusionnable
     */
    public function fusionner(int $absorbeId, int $conserveId): array
    {
        $resultat = DB::transaction(function () use ($absorbeId, $conserveId) {
            [$absorbe, $conserve] = $this->verifierPaire($absorbeId, $conserveId);

            $emailAbsorbe = $absorbe->email;
            $emailAvant = $conserve->email;
            $emailApres = $this->adresseFinale($absorbe, $conserve);

            if ($emailApres !== $emailAvant && $this->comptes->adresseIntegrationPrise($emailApres, $conserve->id)) {
                throw new InvalidArgumentException("L'adresse {$emailApres} est déjà utilisée par une autre fiche d'intégration.");
            }

            $lignes = $this->repointerReferences($absorbe->id, $conserve->id);

            $rolesAjoutes = $absorbe->getRoleNames()->diff($conserve->getRoleNames())->values()->all();
            if ($rolesAjoutes !== []) {
                $conserve->assignRole($rolesAjoutes);
            }
            $permissions = $absorbe->getDirectPermissions();
            if ($permissions->isNotEmpty()) {
                $conserve->givePermissionTo($permissions);
            }

            $this->comptes->revoquerAcces($absorbe->id);
            $absorbe->delete();

            if ($emailApres !== $emailAvant) {
                $conserve->update(['email' => $emailApres]);
                $this->comptes->renommerAdresse($conserve->id, $conserve->agent_id, $emailAvant, $emailApres);
            }

            return [
                'absorbe_id' => $absorbe->id,
                'conserve_id' => $conserve->id,
                'email_absorbe' => $emailAbsorbe,
                'email_avant' => $emailAvant,
                'email_apres' => $emailApres,
                'roles_ajoutes' => $rolesAjoutes,
                'lignes' => $lignes,
            ];
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Log::info('Fusion de comptes utilisateurs', $resultat);

        return $resultat;
    }

    // ─── Purge des comptes sans personne derrière ────────────────────────────

    /**
     * Retire un compte sans fiche, désactivé et sans correspondant : en pratique
     * un compte d'agent de démonstration neutralisé par l'import. Il est supprimé
     * s'il n'a aucun historique ; sinon il reste en base, désactivé, pour ne pas
     * perdre la trace de ce qu'il a fait.
     *
     * @return 'supprime'|'conserve'
     *
     * @throws InvalidArgumentException si le compte n'est pas purgeable
     */
    public function purger(int $userId): string
    {
        return DB::transaction(function () use ($userId) {
            $compte = $this->comptes->trouver($userId);

            if ($compte === null) {
                throw new InvalidArgumentException('Compte introuvable.');
            }
            if (in_array(mb_strtolower($compte->email), self::COMPTES_SYSTEME, true)) {
                throw new InvalidArgumentException("Le compte système {$compte->email} n'est jamais purgé.");
            }
            if ($compte->agent !== null) {
                throw new InvalidArgumentException("Le compte {$compte->email} a une fiche agent.");
            }
            if ($compte->is_active) {
                throw new InvalidArgumentException("Le compte {$compte->email} est actif : à vérifier à la main.");
            }

            $this->comptes->revoquerAcces($compte->id);

            if ($this->comptes->nombreReferences($compte->id) > 0) {
                Log::info('Purge de compte : conservé (historique)', ['id' => $compte->id, 'email' => $compte->email]);

                return 'conserve';
            }

            $this->comptes->supprimerNotifications($compte->id);
            $compte->delete();
            Log::info('Purge de compte : supprimé', ['id' => $compte->id, 'email' => $compte->email]);

            return 'supprime';
        });
    }

    // ─── Prévention (import des agents) ──────────────────────────────────────

    /**
     * Compte existant, sans fiche, qui appartient sans ambiguïté à cet agent.
     * Utilisé par l'import pour rattacher un compte au lieu d'en créer un second.
     */
    public function compteOrphelinPour(Agent $agent): ?User
    {
        $cles = $this->clesAgent($agent);
        foreach ($cles as $cle) {
            if (($this->homonymes()[$cle] ?? 0) > 1) {
                return null;
            }
        }

        $identite = $this->localIdentite($agent);
        $candidats = $this->comptes->comptesSansFiche(self::COMPTES_SYSTEME)
            ->filter(function (User $compte) use ($cles, $identite) {
                [$local, $domaine] = $this->decouper($compte->email);

                return ($identite !== null && $domaine === self::DOMAINE && $local === $identite)
                    || in_array($this->norm($compte->name), $cles, true);
            })
            ->values();

        return $candidats->count() === 1 ? $candidats->first() : null;
    }

    // ─── Règles internes ─────────────────────────────────────────────────────

    /** @return array{0: User, 1: User} */
    private function verifierPaire(int $absorbeId, int $conserveId): array
    {
        if ($absorbeId === $conserveId) {
            throw new InvalidArgumentException('Un compte ne peut pas être fusionné avec lui-même.');
        }

        $absorbe = $this->comptes->trouver($absorbeId);
        $conserve = $this->comptes->trouver($conserveId);

        if ($absorbe === null || $conserve === null) {
            throw new InvalidArgumentException('Compte introuvable.');
        }

        foreach ([$absorbe, $conserve] as $compte) {
            if (in_array(mb_strtolower($compte->email), self::COMPTES_SYSTEME, true)) {
                throw new InvalidArgumentException("Le compte système {$compte->email} n'est jamais fusionné.");
            }
        }

        if ($conserve->agent === null) {
            throw new InvalidArgumentException("Le compte conservé ({$conserve->email}) doit avoir une fiche agent.");
        }

        if ($absorbe->agent !== null && $absorbe->agent_id !== $conserve->agent_id) {
            throw new InvalidArgumentException("Le compte absorbé ({$absorbe->email}) est rattaché à une autre fiche agent.");
        }

        return [$absorbe, $conserve];
    }

    /** @return array<string, int> lignes repointées par « table.colonne » */
    private function repointerReferences(int $ancien, int $nouveau): array
    {
        $lignes = [];

        foreach ($this->comptes->colonnesUtilisateur() as ['table' => $table, 'colonne' => $colonne]) {
            $n = $this->comptes->repointer($table, $colonne, $ancien, $nouveau);
            if ($n > 0) {
                $lignes["{$table}.{$colonne}"] = $n;
            }
        }

        foreach ($this->comptes->colonnesPolymorphes() as ['table' => $table, 'type' => $type, 'id' => $id]) {
            $n = $this->comptes->repointerPolymorphe($table, $type, $id, $ancien, $nouveau);
            if ($n > 0) {
                $lignes["{$table}.{$id}"] = ($lignes["{$table}.{$id}"] ?? 0) + $n;
            }
        }

        return $lignes;
    }

    /**
     * Compte conservé quand plusieurs comptes partagent une fiche : celui de la
     * fiche d'intégration, sinon le premier actif, sinon le plus ancien.
     *
     * @param  Collection<int, User>  $groupe
     */
    private function choisirConserve(Collection $groupe, int $agentId): User
    {
        $integration = $this->comptes->compteIntegrationUserId($agentId);

        return $groupe->firstWhere('id', $integration)
            ?? $groupe->firstWhere('is_active', true)
            ?? $groupe->sortBy('id')->first();
    }

    /** Critère le plus fort qui relie un compte sans fiche à un compte avec fiche. */
    private function critere(User $orphelin, User $cible): ?string
    {
        $agent = $cible->agent;
        $trouves = [];

        if ($this->estVarianteSuffixee($cible->email, $orphelin->email, $agent)) {
            $trouves[] = 'email';
        }

        [$local, $domaine] = $this->decouper($orphelin->email);
        $identite = $this->localIdentite($agent);
        if ($identite !== null && $domaine === self::DOMAINE && $local === $identite) {
            $trouves[] = 'identite';
        }

        $nom = $this->norm($orphelin->name);
        if ($nom !== '' && (in_array($nom, $this->clesAgent($agent), true) || $nom === $this->norm($cible->name))) {
            $trouves[] = 'nom';
        }

        if ($trouves === []) {
            return null;
        }

        usort($trouves, fn (string $a, string $b) => self::CRITERES[$b] <=> self::CRITERES[$a]);

        return $trouves[0];
    }

    /** Adresse que garde le compte conservé après fusion. */
    private function adresseFinale(User $absorbe, User $conserve): string
    {
        $agent = $conserve->agent;
        $identite = $this->localIdentite($agent);
        [$localAbsorbe, $domaineAbsorbe] = $this->decouper($absorbe->email);
        [$localConserve, $domaineConserve] = $this->decouper($conserve->email);

        $absorbeCanonique = $identite !== null && $domaineAbsorbe === self::DOMAINE && $localAbsorbe === $identite;
        $conserveCanonique = $identite !== null && $domaineConserve === self::DOMAINE && $localConserve === $identite;

        if ($absorbeCanonique && ! $conserveCanonique) {
            return mb_strtolower($absorbe->email);
        }

        if ($this->estVarianteSuffixee($conserve->email, $absorbe->email, $agent)) {
            return mb_strtolower($absorbe->email);
        }

        return $conserve->email;
    }

    /**
     * `$suffixee` est-elle `$simple` + `.<matricule>` ou `.<id agent>` (+ `.<n>`),
     * comme les fabrique AgentsGestRhSeeder::emailConnexion ? On exige le suffixe
     * exact : `jean.ba@` ne doit pas capter `jean.ba.ndongo@`.
     */
    private function estVarianteSuffixee(string $suffixee, string $simple, ?Agent $agent): bool
    {
        if ($agent === null) {
            return false;
        }

        [$localLong, $domaineLong] = $this->decouper($suffixee);
        [$localCourt, $domaineCourt] = $this->decouper($simple);

        if ($localCourt === '' || $domaineLong !== $domaineCourt || ! str_starts_with($localLong, $localCourt.'.')) {
            return false;
        }

        $reste = substr($localLong, strlen($localCourt) + 1);
        $suffixes = array_filter([
            $agent->matricule ? $this->slug($agent->matricule) : null,
            (string) $agent->id,
        ]);

        foreach ($suffixes as $suffixe) {
            if (preg_match('/^'.preg_quote($suffixe, '/').'(\.\d+)?$/', $reste) === 1) {
                return true;
            }
        }

        return false;
    }

    /** Partie locale canonique `prenom.nom`, comme AgentsGestRhSeeder::emailDepuisIdentite. */
    private function localIdentite(?Agent $agent): ?string
    {
        if ($agent === null) {
            return null;
        }

        $local = $this->slug((string) $agent->prenom).'.'.$this->slug((string) $agent->nom);

        return $local === '.' ? null : $local;
    }

    /** @return list<string> */
    private function clesAgent(?Agent $agent): array
    {
        if ($agent === null) {
            return [];
        }

        return array_values(array_unique(array_filter([
            $this->norm($agent->prenom.' '.$agent->nom),
            $this->norm($agent->nom.' '.$agent->prenom),
        ])));
    }

    /** @return array<string, int> */
    private function homonymes(): array
    {
        if ($this->homonymes !== null) {
            return $this->homonymes;
        }

        $compte = [];
        foreach ($this->comptes->identitesAgents() as $agent) {
            foreach ($this->clesAgent($agent) as $cle) {
                $compte[$cle] = ($compte[$cle] ?? 0) + 1;
            }
        }

        return $this->homonymes = $compte;
    }

    /** @return array{0: string, 1: string} partie locale, domaine (minuscules) */
    private function decouper(string $email): array
    {
        $email = mb_strtolower(trim($email));
        $position = strrpos($email, '@');

        return $position === false ? [$email, ''] : [substr($email, 0, $position), substr($email, $position + 1)];
    }

    /** Même normalisation que AgentsGestRhSeeder::slug. */
    private function slug(string $valeur): string
    {
        $valeur = strtr(mb_strtolower($valeur), [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'û' => 'u', 'ù' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);
        $valeur = preg_replace('/[^a-z0-9]+/', '.', $valeur) ?? $valeur;

        return trim($valeur, '.');
    }

    /** Majuscules, sans accents, ponctuation et espaces réduits (comme AgentsGestRhSeeder::norm). */
    private function norm(?string $valeur): string
    {
        $valeur = (string) $valeur;
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
