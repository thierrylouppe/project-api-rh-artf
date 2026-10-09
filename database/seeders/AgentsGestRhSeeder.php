<?php

namespace Database\Seeders;

use App\Enums\StatutAffectation;
use App\Enums\StatutSalaireAgent;
use App\Enums\TypeChangementSalaireAgent;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\CompteIntegration;
use App\Models\ContactUrgence;
use App\Models\Diplome;
use App\Models\Direction;
use App\Models\Echelon;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\InformationsPersonnelle;
use App\Models\InformationsProfessionnelle;
use App\Models\Salaire;
use App\Models\SalaireAgent;
use App\Models\Service;
use App\Models\SituationFamiliale;
use App\Models\TypeIntegration;
use App\Models\User;
use App\Services\SalaireService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Intègre les agents réels du dump gestRHdb (01/07/2024).
 *
 * L'organigramme seedé n'est pas modifié. Un agent n'est affecté que sur un
 * bureau, un service ou une direction déjà présent. Les fiches de démonstration
 * (matricules ARFT- et STG-) sont retirées avant l'import.
 *
 * Prérequis : Localite, Administration, Direction, Service, Bureau, Grade,
 * Categorie, Echelon, Classegrillesalariale, Diplome, Fonction, TypeIntegration, User.
 */
class AgentsGestRhSeeder extends Seeder
{
    private const DUMP = 'doc/gestRHdb_010724.sql';

    /** @var array<string, string> sigle dump du diplôme → sigle du référentiel actuel */
    private const DIPLOMES = [
        'CEPE' => 'CEPE',
        'CAP' => 'CAP',
        'BEPC' => 'BEPC',
        'BET' => 'BET',
        'BEP' => 'BEP',
        'BAC' => 'BAC',
        'DUT' => 'DUT',
        'BTS' => 'BTS',
        'BENAM' => 'BENAM',
        'LICENCE' => 'LIC',
        'BACHELOR' => 'BACH',
        'MAITRISE' => 'MAIT',
        'DEA' => 'DEA',
        'MASTER' => 'MST',
        'DESS' => 'DESS',
        'DSENAM' => 'DSENAM',
        'MBA' => 'MBA',
        'DUTS' => 'DSTS',
        'DOCTORAT' => 'DOC',
    ];

    /** @var array<int, array{0: string, 1: string}> classe dump → [catégorie, grade] */
    private const CLASSES = [
        1 => ['Classe I', 'Personnel de service'],
        2 => ['Classe II', 'Personnel de service spécialisé'],
        3 => ['Classe III', 'Commis'],
        4 => ['Classe IV', 'Commis Principal'],
        5 => ['Classe V', 'Contrôleur'],
        6 => ['Classe VI', 'Contrôleur Principal'],
        7 => ['Classe VII', 'Vérificateur'],
        8 => ['Classe VIII', 'Inspecteur'],
        9 => ['Classe IX', 'Inspecteur Principal'],
        10 => ['Classe X', 'Hors Classe'],
    ];

    /** @var array<int, string> */
    private const FONCTIONS = [
        1 => 'Agent',
        2 => 'Chef de bureau',
        3 => 'Chef de service',
        4 => 'Directeur Central',
        5 => 'Directeur Général',
    ];

    public function run(): void
    {
        $path = base_path(self::DUMP);
        if (! is_file($path)) {
            throw new RuntimeException('Dump introuvable : '.self::DUMP);
        }

        $sql = (string) file_get_contents($path);
        $dump = $this->chargerDump($sql);
        $refs = $this->chargerReferentiels();

        if (Salaire::query()->doesntExist()) {
            app(SalaireService::class)->generateGrille();
        }

        $stats = [
            'retires' => 0,
            'crees' => 0,
            'deja' => 0,
            'exclus' => 0,
            'affectes' => 0,
            'sans_affectation' => 0,
            'salaires' => 0,
            'diplomes' => 0,
            'comptes' => 0,
        ];

        DB::transaction(function () use ($dump, $refs, &$stats) {
            $stats['retires'] = $this->retirerAgentsFictifs();

            foreach ($dump['users'] as $user) {
                $prenom = $this->texte($user[2] ?? null);
                if ($prenom === null) {
                    $stats['exclus']++;
                    continue;
                }

                $ancienId = (int) $user[0];
                $fiche = $this->fiche($user, $ancienId, $dump, $refs);
                $agent = $this->enregistrerAgent($fiche, $stats);

                $this->enregistrerVie($agent, $fiche);
                if ($fiche['diplome_id'] !== null) {
                    $stats['diplomes']++;
                }
                if ($this->enregistrerAffectation($agent, $fiche, $refs['admin_id'])) {
                    $stats['affectes']++;
                } else {
                    $stats['sans_affectation']++;
                }
                if ($this->enregistrerSalaire($agent, $fiche)) {
                    $stats['salaires']++;
                }
            }

            $stats['comptes'] = $this->creerComptesConnexion();
        });

        $this->command?->info(sprintf(
            'gestRHdb : %d agents créés, %d déjà présents, %d exclus (prénom absent), %d fictifs retirés.',
            $stats['crees'],
            $stats['deja'],
            $stats['exclus'],
            $stats['retires'],
        ));
        $this->command?->info(sprintf(
            'Affectations %d, sans affectation %d, salaires %d, diplômes %d, comptes %d.',
            $stats['affectes'],
            $stats['sans_affectation'],
            $stats['salaires'],
            $stats['diplomes'],
            $stats['comptes'],
        ));

        $createdBy = User::query()->where('email', 'admin@artf.cg')->value('id');
        if ($createdBy) {
            $bilan = app(\App\Services\RepriseHierarchieGestRhService::class)->appliquer((int) $createdBy);
            $this->command?->info(sprintf(
                'Hiérarchie : %d nominations, %d liens N+1, %d structures ambiguës.',
                $bilan['nominations_creees'],
                $bilan['liens_mis_a_jour'],
                count($bilan['ambigus']),
            ));
        }
    }

    /**
     * Placements courants du dump, une fiche par identité.
     * En cas de doublon, la fiche qui a une structure est retenue.
     *
     * @return list<array{
     *     ancien_id: int,
     *     cle: string,
     *     matricule: string|null,
     *     nom: string,
     *     prenom: string,
     *     date_naissance: string|null,
     *     fonction: string,
     *     placement: array{type: class-string, id: int}|null,
     *     conflit: bool
     * }>
     */
    public function extrairePlacements(): array
    {
        $path = base_path(self::DUMP);
        if (! is_file($path)) {
            throw new RuntimeException('Dump introuvable : '.self::DUMP);
        }

        $dump = $this->chargerDump((string) file_get_contents($path));
        $refs = $this->chargerReferentiels();
        $parCle = [];

        foreach ($dump['users'] as $user) {
            $prenom = $this->texte($user[2] ?? null);
            $nom = $this->texte($user[1] ?? null);
            if ($prenom === null || $nom === null) {
                continue;
            }

            $ancienId = (int) $user[0];
            $date = $this->date($this->texte($user[5] ?? null));
            $cle = $this->norm($nom).'|'.$this->norm($prenom).'|'.($date ?? '');
            $matricule = $this->recrutement($dump['recrutements'][$ancienId] ?? []);
            $ligne = [
                'ancien_id' => $ancienId,
                'cle' => $cle,
                'matricule' => $matricule !== null ? mb_strtoupper(trim($matricule)) : null,
                'nom' => $nom,
                'prenom' => $prenom,
                'date_naissance' => $date,
                'fonction' => self::FONCTIONS[(int) ($user[13] ?? 0)] ?? 'Agent',
                'placement' => $this->placement($user, $dump, $refs),
                'conflit' => false,
            ];

            if (! isset($parCle[$cle])) {
                $parCle[$cle] = $ligne;
                continue;
            }

            $ancien = $parCle[$cle];
            if ($ancien['placement'] === null && $ligne['placement'] !== null) {
                $parCle[$cle] = $ligne;
                continue;
            }

            if ($ancien['placement'] !== null && $ligne['placement'] !== null
                && ($ancien['placement']['type'] !== $ligne['placement']['type']
                    || $ancien['placement']['id'] !== $ligne['placement']['id']
                    || $ancien['fonction'] !== $ligne['fonction'])) {
                $parCle[$cle]['conflit'] = true;
            }
        }

        return array_values($parCle);
    }

    private function retirerAgentsFictifs(): int
    {
        $ids = Agent::query()
            ->where(function ($query) {
                $query->where('matricule', 'like', 'ARFT-%')
                    ->orWhere('matricule', 'like', 'STG-%');
            })
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        DB::table('prises_de_service')
            ->where(function ($query) use ($ids) {
                $query->whereIn('agent_id', $ids)->orWhereIn('responsable_id', $ids);
            })
            ->delete();

        DB::table('paie_lot_lignes')->whereIn('agent_id', $ids)->delete();
        DB::table('nominations')->whereIn('agent_id', $ids)->update(['nomination_precedente_id' => null]);
        DB::table('dossiers_integration')->whereIn('agent_id', $ids)->delete();

        $retires = Agent::query()->whereIn('id', $ids)->delete();

        User::query()
            ->whereNotNull('agent_id')
            ->whereDoesntHave('agent')
            ->each(function (User $user) {
                $user->syncRoles([]);
                $user->update([
                    'agent_id' => null,
                    'bureau_id' => null,
                    'is_active' => false,
                    'password' => bin2hex(random_bytes(16)),
                ]);
            });

        return $retires;
    }

    /**
     * @param  array<string, list<list<string|null>>>  $dump
     * @param  array<string, mixed>  $refs
     * @return array<string, mixed>
     */
    private function fiche(array $user, int $ancienId, array $dump, array $refs): array
    {
        $recrutement = $this->recrutement($dump['recrutements'][$ancienId] ?? []);
        $engagement = $this->engagement($dump['textes'][$ancienId] ?? []);
        $diplome = $this->diplome($dump['diplomes_agent'][$ancienId] ?? [], $dump['diplomes'], $refs['diplomes']);
        $placement = $this->placement($user, $dump, $refs);

        $fonctionNom = self::FONCTIONS[(int) ($user[13] ?? 0)] ?? 'Agent';
        $classe = $engagement['classe'] ?? null;
        $echelon = $engagement['echelon'] ?? null;

        return [
            'matricule' => $recrutement,
            'nom' => $this->texte($user[1]) ?? '—',
            'prenom' => $this->texte($user[2]),
            'date_naissance' => $this->texte($user[5]),
            'lieu_naissance' => $this->tronquer($this->texte($user[6]), 255),
            'genre' => $this->texte($user[4]) === 'F' ? 'F' : 'M',
            'telephone' => $this->tronquer($this->texte($user[9]), 30),
            'email' => $this->email($this->texte($user[7])),
            'adresse' => $this->tronquer($this->texte($user[8]), 255),
            'situation' => $this->texte($user[11]),
            'contact_nom' => $this->texte($user[17]),
            'contact_prenom' => $this->texte($user[18]),
            'contact_telephone' => $this->tronquer($this->texte($user[20]), 30),
            'fonction_id' => $refs['fonctions'][$fonctionNom] ?? null,
            'categorie_id' => $classe ? ($refs['categories'][$classe[0]] ?? null) : null,
            'grade_id' => $classe ? ($refs['grades'][$classe[1]] ?? null) : null,
            'echelon_id' => $echelon ? ($refs['echelons'][$echelon] ?? null) : null,
            'echelon' => $echelon,
            'classe_nom' => $classe[0] ?? null,
            'grade_nom' => $classe[1] ?? null,
            'date_decision' => $engagement['date'] ?? $this->date($this->texte($user[24])),
            'diplome_id' => $diplome['id'],
            'filiere' => $diplome['filiere'],
            'placement' => $placement,
            'type_integration_id' => $refs['type_integration_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $fiche
     */
    private function enregistrerAgent(array $fiche, array &$stats): Agent
    {
        $attributs = [
            'nom' => $fiche['nom'],
            'prenom' => $fiche['prenom'],
            'date_naissance' => $fiche['date_naissance'],
            'lieu_naissance' => $fiche['lieu_naissance'],
            'nationalite' => 'Congolaise',
            'genre' => $fiche['genre'],
            'telephone' => $fiche['telephone'],
            'email_personnel' => $fiche['email'],
            'fonction_id' => $fiche['fonction_id'],
            'grade_id' => $fiche['grade_id'],
            'categorie_id' => $fiche['categorie_id'],
            'echelon_id' => $fiche['echelon_id'],
            'type_integration_id' => $fiche['type_integration_id'],
            'date_prise_service' => $fiche['date_decision'],
            'statut' => 'actif',
        ];

        if ($fiche['matricule'] !== null) {
            $agent = Agent::query()->where('matricule', $fiche['matricule'])->first();
            if ($agent) {
                $stats['deja']++;

                return $agent;
            }

            // Une fiche déjà présente sans matricule peut correspondre à cette
            // ligne complète du dump. La réutiliser évite de créer un second
            // agent et conserve son compte utilisateur / son identifiant.
            $agent = $this->trouverParIdentite($fiche, sansMatricule: true);
            if ($agent) {
                $agent->fill($attributs + ['matricule' => $fiche['matricule']])->save();
                $stats['deja']++;

                return $agent;
            }

            $stats['crees']++;

            return Agent::query()->create($attributs + ['matricule' => $fiche['matricule']]);
        }

        // Le dump peut contenir les deux variantes d'une même personne dans
        // n'importe quel ordre. Une ligne sans matricule doit aussi retrouver
        // une fiche complète déjà importée, sans effacer son matricule.
        $agent = $this->trouverParIdentite($fiche);

        if ($agent) {
            $stats['deja']++;

            return $agent;
        }

        $stats['crees']++;

        return Agent::query()->create($attributs + ['matricule' => null]);
    }

    /**
     * Recherche une fiche correspondant à l'identité du dump.
     *
     * @param  array<string, mixed>  $fiche
     */
    private function trouverParIdentite(array $fiche, bool $sansMatricule = false): ?Agent
    {
        $query = Agent::query()
            ->whereRaw('LOWER(TRIM(nom)) = ?', [mb_strtolower(trim($fiche['nom']))])
            ->whereRaw('LOWER(TRIM(prenom)) = ?', [mb_strtolower(trim($fiche['prenom']))]);

        if ($fiche['date_naissance'] === null) {
            $query->whereNull('date_naissance');
        } else {
            $query->whereDate('date_naissance', $fiche['date_naissance']);
        }

        if ($sansMatricule) {
            $query->whereNull('matricule');
        }

        return $query->orderByRaw('matricule IS NOT NULL')->orderBy('id')->first();
    }

    /**
     * @param  array<string, mixed>  $fiche
     */
    private function enregistrerVie(Agent $agent, array $fiche): void
    {
        if ($fiche['adresse'] !== null) {
            InformationsPersonnelle::query()->firstOrCreate(
                ['agent_id' => $agent->id],
                ['adresse' => $fiche['adresse'], 'pays' => 'Congo'],
            );
        }

        if ($fiche['situation'] !== null) {
            SituationFamiliale::query()->firstOrCreate(
                ['agent_id' => $agent->id],
                ['statut_matrimonial' => $fiche['situation'], 'nb_enfants' => 0],
            );
        }

        if ($fiche['contact_nom'] !== null && $fiche['contact_telephone'] !== null) {
            ContactUrgence::query()->firstOrCreate(
                ['agent_id' => $agent->id, 'telephone' => $fiche['contact_telephone']],
                [
                    'nom' => $fiche['contact_nom'],
                    'prenom' => $fiche['contact_prenom'] ?? '—',
                ],
            );
        }

        if ($fiche['diplome_id'] !== null) {
            InformationsProfessionnelle::query()->firstOrCreate(
                ['agent_id' => $agent->id],
                [
                    'diplome_id' => $fiche['diplome_id'],
                    'specialite' => $fiche['filiere'],
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $fiche
     */
    private function enregistrerAffectation(Agent $agent, array $fiche, int $adminId): bool
    {
        $placement = $fiche['placement'];
        if ($placement === null) {
            return false;
        }

        Affectation::query()->firstOrCreate(
            [
                'agent_id' => $agent->id,
                'structurable_type' => $placement['type'],
                'structurable_id' => $placement['id'],
                'statut' => StatutAffectation::ACTIVE->value,
            ],
            [
                'date_affectation' => $fiche['date_decision'] ?? '2023-10-04',
                'motif' => 'Reprise du dossier gestRHdb du 01/07/2024',
                'created_by' => $adminId,
            ],
        );

        return true;
    }

    /**
     * @param  array<string, mixed>  $fiche
     */
    private function enregistrerSalaire(Agent $agent, array $fiche): bool
    {
        if ($fiche['classe_nom'] === null || $fiche['echelon'] === null) {
            return false;
        }

        if (SalaireAgent::query()->where('agent_id', $agent->id)->where('statut', StatutSalaireAgent::ACTIF->value)->exists()) {
            return false;
        }

        $classe = Classegrillesalariale::query()
            ->where('categorie_id', $fiche['categorie_id'])
            ->where('grade_id', $fiche['grade_id'])
            ->first();

        if ($classe === null) {
            return false;
        }

        $ligne = Salaire::query()
            ->where('classegrillesalariale_id', $classe->id)
            ->where('echelon', $fiche['echelon'])
            ->first();

        if ($ligne === null) {
            return false;
        }

        SalaireAgent::query()->create([
            'agent_id' => $agent->id,
            'salaire_id' => $ligne->id,
            'classegrillesalariale_id' => $classe->id,
            'echelon' => $fiche['echelon'],
            'montant_base' => $ligne->salaire,
            'date_debut' => $fiche['date_decision'] ?? '2023-10-04',
            'statut' => StatutSalaireAgent::ACTIF,
            'type_changement' => TypeChangementSalaireAgent::INITIAL,
            'motif' => 'Reprise de la décision d\'engagement gestRHdb',
        ]);

        return true;
    }

    private function creerComptesConnexion(): int
    {
        $reserves = [
            'admin@artf.cg',
            'rh@artf.cg',
            'dg@artf.cg',
            'directeur@artf.cg',
            'chef-service@artf.cg',
            'chef-bureau@artf.cg',
            'agent@artf.cg',
        ];

        $pris = User::query()->pluck('email')->map(fn (string $email) => mb_strtolower($email))->all();
        $pris = array_fill_keys($pris, true);
        $crees = 0;

        $agents = Agent::query()
            ->whereDoesntHave('compte')
            ->where(function ($query) {
                $query->whereNull('matricule')
                    ->orWhere(function ($matricule) {
                        $matricule->where('matricule', 'not like', 'ARFT-%')
                            ->where('matricule', 'not like', 'STG-%');
                    });
            })
            ->orderBy('id')
            ->get();

        foreach ($agents as $agent) {
            $email = $this->emailConnexion($agent, $pris, $reserves);

            $user = User::query()->create([
                'name' => trim($agent->prenom.' '.$agent->nom),
                'email' => $email,
                'password' => 'password',
                'agent_id' => $agent->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            CompteIntegration::query()->create([
                'agent_id' => $agent->id,
                'user_id' => $user->id,
                'login' => $email,
                'email_professionnel' => $email,
                'badge_numero' => $agent->matricule,
                'mot_de_passe_provisoire_envoye' => false,
                'date_creation' => now(),
            ]);

            if ($agent->email_professionnel === null) {
                $agent->update(['email_professionnel' => $email]);
            }

            SyncAgentRolesSeeder::attribuer($user->fresh());
            $crees++;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $crees;
    }

    /**
     * @param  array<string, true>  $pris
     * @param  list<string>  $reserves
     */
    private function emailConnexion(Agent $agent, array &$pris, array $reserves): string
    {
        $personnel = $agent->email_personnel ? mb_strtolower($agent->email_personnel) : null;
        $candidats = [];

        if ($personnel && ! in_array($personnel, $reserves, true)) {
            $candidats[] = $personnel;
        }

        $candidats[] = $this->emailDepuisIdentite($agent);

        foreach ($candidats as $candidat) {
            if (! isset($pris[$candidat])) {
                $pris[$candidat] = true;

                return $candidat;
            }
        }

        $base = $this->emailDepuisIdentite($agent);
        $local = strstr($base, '@', true) ?: 'agent'.$agent->id;
        $suffixe = $agent->matricule ? $this->slug($agent->matricule) : (string) $agent->id;
        $email = $local.'.'.$suffixe.'@artf.cg';
        $indice = 2;

        while (isset($pris[$email])) {
            $email = $local.'.'.$suffixe.'.'.$indice.'@artf.cg';
            $indice++;
        }

        $pris[$email] = true;

        return $email;
    }

    private function emailDepuisIdentite(Agent $agent): string
    {
        $local = $this->slug($agent->prenom).'.'.$this->slug($agent->nom);

        if ($local === '.') {
            $local = 'agent'.$agent->id;
        }

        return $local.'@artf.cg';
    }

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

    /**
     * @param  list<string|null>  $user
     * @param  array<string, mixed>  $dump
     * @param  array<string, mixed>  $refs
     * @return array{type: class-string, id: int}|null
     */
    private function placement(array $user, array $dump, array $refs): ?array
    {
        $type = $this->texte($user[15] ?? null);
        $id = $this->texte($user[16] ?? null);
        if ($type === null || $id === null) {
            return null;
        }

        $id = (int) $id;

        if (str_contains($type, 'Bureau')) {
            $bureau = $dump['bureaus'][$id] ?? null;
            if ($bureau === null) {
                return null;
            }

            $service = $dump['services'][$bureau['service_id']] ?? null;
            $sigleService = $service['sigle'] ?? null;
            $courant = $refs['bureaux_sigle'][$bureau['sigle']] ?? null;
            $parNom = $refs['bureaux_nom'][$this->norm($bureau['nom'])] ?? null;

            if ($courant && $this->norm($courant['nom']) === $this->norm($bureau['nom']) && $courant['service'] === $sigleService) {
                return ['type' => Bureau::class, 'id' => $courant['id']];
            }

            if ($parNom) {
                return ['type' => Bureau::class, 'id' => $parNom['id']];
            }

            if ($sigleService && isset($refs['services'][$sigleService])) {
                return ['type' => Service::class, 'id' => $refs['services'][$sigleService]['id']];
            }

            return null;
        }

        if (str_contains($type, 'Service')) {
            $service = $dump['services'][$id] ?? null;
            if ($service === null) {
                return null;
            }

            $courant = $refs['services'][$service['sigle']] ?? null;
            $direction = $dump['directions'][$service['direction_id']]['sigle'] ?? null;

            if ($courant && $this->norm($courant['nom']) === $this->norm($service['nom']) && $courant['direction'] === $direction) {
                return ['type' => Service::class, 'id' => $courant['id']];
            }

            if ($direction && isset($refs['directions'][$direction])) {
                return ['type' => Direction::class, 'id' => $refs['directions'][$direction]];
            }

            return null;
        }

        if (str_contains($type, 'Direction')) {
            $direction = $dump['directions'][$id] ?? null;
            if ($direction && isset($refs['directions'][$direction['sigle']])) {
                return ['type' => Direction::class, 'id' => $refs['directions'][$direction['sigle']]];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function chargerReferentiels(): array
    {
        $admin = User::query()->where('email', 'admin@artf.cg')->first();
        if ($admin === null) {
            throw new RuntimeException('Utilisateur admin@artf.cg introuvable. Lancer UserSeeder avant cet import.');
        }

        $type = TypeIntegration::query()->where('nom', 'Recrutement externe')->first();
        if ($type === null) {
            throw new RuntimeException('Type d\'intégration « Recrutement externe » introuvable.');
        }

        $directions = Direction::query()->pluck('id', 'sigle')->all();
        $services = [];
        foreach (Service::query()->with('direction')->get() as $service) {
            $services[$service->sigle] = [
                'id' => $service->id,
                'nom' => $service->nom,
                'direction' => $service->direction?->sigle,
            ];
        }

        $bureauxSigle = [];
        $bureauxNom = [];
        foreach (Bureau::query()->with('service')->get() as $bureau) {
            $ligne = [
                'id' => $bureau->id,
                'nom' => $bureau->nom,
                'service' => $bureau->service?->sigle,
            ];
            $bureauxSigle[$bureau->sigle] = $ligne;
            $bureauxNom[$this->norm($bureau->nom)] = $ligne;
        }

        return [
            'admin_id' => $admin->id,
            'type_integration_id' => $type->id,
            'directions' => $directions,
            'services' => $services,
            'bureaux_sigle' => $bureauxSigle,
            'bureaux_nom' => $bureauxNom,
            'fonctions' => Fonction::query()->pluck('id', 'nom')->all(),
            'categories' => Categorie::query()->pluck('id', 'nom')->all(),
            'grades' => Grade::query()->pluck('id', 'nom')->all(),
            'echelons' => Echelon::query()->pluck('id', 'numero')->all(),
            'diplomes' => Diplome::query()->pluck('id', 'sigle')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function chargerDump(string $sql): array
    {
        $directions = [];
        foreach ($this->lignes($sql, 'directions') as $row) {
            $directions[(int) $row[0]] = [
                'nom' => $this->texte($row[1]) ?? '',
                'sigle' => $this->texte($row[2]) ?? '',
            ];
        }

        $services = [];
        foreach ($this->lignes($sql, 'services') as $row) {
            $services[(int) $row[0]] = [
                'nom' => $this->texte($row[1]) ?? '',
                'sigle' => $this->texte($row[2]) ?? '',
                'direction_id' => (int) $row[3],
            ];
        }

        $bureaus = [];
        foreach ($this->lignes($sql, 'bureaus') as $row) {
            $bureaus[(int) $row[0]] = [
                'nom' => $this->texte($row[1]) ?? '',
                'sigle' => $this->texte($row[2]) ?? '',
                'service_id' => (int) $row[3],
            ];
        }

        $diplomes = [];
        foreach ($this->lignes($sql, 'diplomes') as $row) {
            $diplomes[(int) $row[0]] = [
                'nom' => $this->texte($row[1]) ?? '',
                'classe' => (int) $row[3],
            ];
        }

        $recrutements = [];
        foreach ($this->lignes($sql, 'recrutements') as $row) {
            $recrutements[(int) $row[9]][] = $row;
        }

        $textes = [];
        foreach ($this->lignes($sql, 'textes') as $row) {
            $textes[(int) $row[12]][] = $row;
        }

        $diplomesAgent = [];
        foreach ($this->lignes($sql, 'diplome_user') as $row) {
            $diplomesAgent[(int) $row[8]][] = $row;
        }

        return [
            'users' => $this->lignes($sql, 'users'),
            'directions' => $directions,
            'services' => $services,
            'bureaus' => $bureaus,
            'diplomes' => $diplomes,
            'recrutements' => $recrutements,
            'textes' => $textes,
            'diplomes_agent' => $diplomesAgent,
        ];
    }

    /**
     * @param  list<list<string|null>>  $lignes
     */
    private function recrutement(array $lignes): ?string
    {
        if ($lignes === []) {
            return null;
        }

        $valides = array_values(array_filter($lignes, fn (array $row) => ($row[8] ?? null) === '1' && $this->texte($row[1]) !== null));
        $choisi = ($valides !== [] ? $valides : $lignes)[array_key_last($valides !== [] ? $valides : $lignes)];
        $matricule = $this->texte($choisi[1] ?? null);

        return $matricule !== null && strcasecmp($matricule, 'null') !== 0 ? $matricule : null;
    }

    /**
     * @param  list<list<string|null>>  $lignes
     * @param  array<string, int>  $echelons
     * @return array{classe: array{0: string, 1: string}|null, echelon: int|null, echelon_id: int|null, date: string|null}
     */
    private function engagement(array $lignes): array
    {
        $vide = ['classe' => null, 'echelon' => null, 'echelon_id' => null, 'date' => null];
        if ($lignes === []) {
            return $vide;
        }

        usort($lignes, fn (array $a, array $b) => strcmp((string) $this->texte($a[2]), (string) $this->texte($b[2])));
        $row = $lignes[array_key_last($lignes)];
        $classeId = (int) ($row[10] ?? 0);
        $echelon = (int) ($row[11] ?? 0);

        return [
            'classe' => self::CLASSES[$classeId] ?? null,
            'echelon' => $echelon > 0 ? $echelon : null,
            'echelon_id' => null,
            'date' => $this->date($this->texte($row[2])),
        ];
    }

    /**
     * @param  list<list<string|null>>  $liens
     * @param  array<int, array{nom: string, classe: int}>  $catalogue
     * @param  array<string, int>  $diplomesActuels
     * @return array{id: int|null, filiere: string|null}
     */
    private function diplome(array $liens, array $catalogue, array $diplomesActuels): array
    {
        $candidats = [];
        foreach ($liens as $lien) {
            $meta = $catalogue[(int) ($lien[7] ?? 0)] ?? null;
            if ($meta === null || $meta['classe'] < 3) {
                continue;
            }
            $candidats[] = ['meta' => $meta, 'filiere' => $this->tronquer($this->texte($lien[4] ?? null), 255)];
        }

        if ($candidats === []) {
            return ['id' => null, 'filiere' => null];
        }

        usort($candidats, fn (array $a, array $b) => $a['meta']['classe'] <=> $b['meta']['classe']);
        $retenu = $candidats[array_key_last($candidats)];
        $sigle = self::DIPLOMES[mb_strtoupper($retenu['meta']['nom'])] ?? null;

        return [
            'id' => $sigle ? ($diplomesActuels[$sigle] ?? null) : null,
            'filiere' => $retenu['filiere'],
        ];
    }

    /**
     * @return list<list<string|null>>
     */
    private function lignes(string $sql, string $table): array
    {
        $prefix = "INSERT INTO `{$table}` VALUES ";
        foreach (explode("\n", $sql) as $line) {
            if (! str_starts_with($line, $prefix)) {
                continue;
            }

            return $this->tuples(rtrim(substr($line, strlen($prefix)), ";\r"));
        }

        return [];
    }

    /**
     * @return list<list<string|null>>
     */
    private function tuples(string $values): array
    {
        $rows = [];
        $depth = 0;
        $inString = false;
        $escape = false;
        $start = null;
        $length = strlen($values);

        for ($i = 0; $i < $length; $i++) {
            $char = $values[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === "'") {
                    $inString = false;
                }
                continue;
            }
            if ($char === "'") {
                $inString = true;
            } elseif ($char === '(') {
                if ($depth === 0) {
                    $start = $i + 1;
                }
                $depth++;
            } elseif ($char === ')') {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $rows[] = $this->champs(substr($values, $start, $i - $start));
                    $start = null;
                }
            }
        }

        return $rows;
    }

    /**
     * @return list<string|null>
     */
    private function champs(string $tuple): array
    {
        $fields = [];
        $current = '';
        $inString = false;
        $escape = false;
        $length = strlen($tuple);

        for ($i = 0; $i < $length; $i++) {
            $char = $tuple[$i];
            if ($inString) {
                $current .= $char;
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === "'") {
                    $inString = false;
                }
                continue;
            }
            if ($char === "'") {
                $inString = true;
                $current .= $char;
            } elseif ($char === ',') {
                $fields[] = $this->valeur(trim($current));
                $current = '';
            } else {
                $current .= $char;
            }
        }

        $fields[] = $this->valeur(trim($current));

        return $fields;
    }

    private function valeur(string $brut): ?string
    {
        if ($brut === 'NULL') {
            return null;
        }

        if (str_starts_with($brut, "'") && str_ends_with($brut, "'")) {
            return str_replace(["\\'", '\\\\'], ["'", '\\'], substr($brut, 1, -1));
        }

        return $brut;
    }

    private function texte(?string $valeur): ?string
    {
        if ($valeur === null) {
            return null;
        }

        $valeur = trim($valeur);
        if ($valeur === '' || strcasecmp($valeur, 'null') === 0) {
            return null;
        }

        return $valeur;
    }

    private function email(?string $valeur): ?string
    {
        if ($valeur === null || filter_var($valeur, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $valeur;
    }

    private function date(?string $valeur): ?string
    {
        if ($valeur === null || ! preg_match('/^\d{4}-\d{2}-\d{2}/', $valeur)) {
            return null;
        }

        return substr($valeur, 0, 10);
    }

    private function tronquer(?string $valeur, int $taille): ?string
    {
        if ($valeur === null) {
            return null;
        }

        return mb_substr($valeur, 0, $taille);
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
