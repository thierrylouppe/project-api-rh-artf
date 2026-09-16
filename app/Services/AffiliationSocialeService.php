<?php

namespace App\Services;

use App\Enums\StatutAffiliation;
use App\Enums\TypeOrganismeSocial;
use App\Interfaces\AffiliationSocialeInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\OrganismeSocialInterface;
use App\Models\AffiliationSociale;
use App\Models\Agent;
use App\Models\OrganismeSocial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property AffiliationSocialeInterface $repository */
class AffiliationSocialeService extends BaseService
{
    public function __construct(
        AffiliationSocialeInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly OrganismeSocialInterface $organismeRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function agentsSansAffiliationCnss(): Collection
    {
        return $this->repository->agentsSansAffiliationCnss();
    }

    protected function beforeCreate(array $data): array
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $this->assertAgentModifiable($agent);

        $organisme = $this->organismeRepository->findById((int) $data['organisme_id']);
        abort_unless(
            $organisme instanceof OrganismeSocial && $organisme->actif,
            422,
            'Impossible d\'affilier un agent à un organisme inactif.'
        );

        $this->assertPasDeDoublonActif(
            (int) $data['agent_id'],
            (int) $data['organisme_id'],
            $data['statut'] ?? StatutAffiliation::ACTIVE->value,
        );

        $this->assertDates($data);

        $data['created_by'] = Auth::id();
        $data['numero_affiliation'] = $this->numeroAffiliation($data, $agent, $organisme);

        return $data;
    }

    protected function afterCreate($model): AffiliationSociale
    {
        $this->synchroniserNumeroCnss($model);

        return $model->load(['agent:id,matricule,nom,prenom,numero_cnss,statut', 'organisme']);
    }

    public function assurerAffiliationCnss(int $agentId, string $numero): AffiliationSociale
    {
        $organisme = $this->organismeRepository->findByCode('CNSS');
        abort_if(
            $organisme === null,
            422,
            'Organisme CNSS introuvable. Vérifiez le référentiel des organismes sociaux.'
        );

        $existante = $this->repository->findActiveForAgentAndOrganisme($agentId, (int) $organisme->id);
        if ($existante !== null) {
            $this->synchroniserNumeroCnss($existante);

            return $existante;
        }

        return $this->create([
            'agent_id'           => $agentId,
            'organisme_id'       => $organisme->id,
            'numero_affiliation' => $numero,
            'date_debut'         => now()->toDateString(),
            'statut'             => StatutAffiliation::ACTIVE->value,
        ]);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $affiliation = $this->repository->findById($id);
        $agent = $this->agentRepository->findById($affiliation->agent_id);
        $this->assertAgentModifiable($agent);

        $organismeId = (int) ($data['organisme_id'] ?? $affiliation->organisme_id);
        $organisme = $this->organismeRepository->findById($organismeId);

        if (isset($data['organisme_id']) && $organisme instanceof OrganismeSocial && ! $organisme->actif) {
            abort(422, 'Impossible d\'affilier un agent à un organisme inactif.');
        }

        $this->assertPasDeDoublonActif(
            (int) $affiliation->agent_id,
            $organismeId,
            $data['statut'] ?? $affiliation->statut?->value ?? StatutAffiliation::ACTIVE->value,
            $id,
        );

        $this->assertDates(array_merge([
            'date_debut' => $affiliation->date_debut?->format('Y-m-d'),
            'date_fin' => $affiliation->date_fin?->format('Y-m-d'),
        ], $data));

        return $data;
    }

    protected function afterUpdate($model): AffiliationSociale
    {
        $this->synchroniserNumeroCnss($model);

        return $model->load(['agent:id,matricule,nom,prenom,numero_cnss,statut', 'organisme']);
    }

    public function delete(int $id): bool
    {
        $affiliation = $this->repository->findById($id);
        $this->assertAgentModifiable($this->agentRepository->findById($affiliation->agent_id));

        return $this->repository->delete($id);
    }

    public function findById(int $id): AffiliationSociale
    {
        return $this->repository->findById($id)
            ->load(['agent:id,matricule,nom,prenom,numero_cnss,statut', 'organisme']);
    }

    private function assertAgentModifiable(Agent $agent): void
    {
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');
    }

    private function assertPasDeDoublonActif(int $agentId, int $organismeId, string $statut, ?int $excludeId = null): void
    {
        if ($statut !== StatutAffiliation::ACTIVE->value) {
            return;
        }

        abort_if(
            $this->repository->findActiveForAgentAndOrganisme($agentId, $organismeId, $excludeId) !== null,
            422,
            'Cet agent a déjà une affiliation active pour cet organisme.'
        );
    }

    private function assertDates(array $data): void
    {
        if (! isset($data['date_debut'], $data['date_fin']) || $data['date_fin'] === null) {
            return;
        }

        abort_if(
            $data['date_fin'] < $data['date_debut'],
            422,
            'La date de fin doit être postérieure ou égale à la date de début.'
        );
    }

    private function numeroAffiliation(array $data, Agent $agent, OrganismeSocial $organisme): string
    {
        $numero = trim((string) ($data['numero_affiliation'] ?? ''));

        if ($numero !== '') {
            return $numero;
        }

        if ($organisme->type === TypeOrganismeSocial::CNSS && filled($agent->numero_cnss)) {
            return (string) $agent->numero_cnss;
        }

        abort(422, 'Le numéro d\'affiliation est obligatoire.');
    }

    private function synchroniserNumeroCnss(AffiliationSociale $affiliation): void
    {
        $affiliation->loadMissing('organisme');

        if ($affiliation->organisme?->type !== TypeOrganismeSocial::CNSS) {
            return;
        }

        if (! $affiliation->estActive()) {
            return;
        }

        $this->agentRepository->update($affiliation->agent_id, [
            'numero_cnss' => $affiliation->numero_affiliation,
        ]);
    }
}
