<?php

namespace App\Providers;

use App\Interfaces\CircuitValidationInterface;
use App\Interfaces\ActeAdministratifInterface;
use App\Interfaces\AffectationInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\CompteIntegrationInterface;
use App\Interfaces\ContratInterface;
use App\Interfaces\DocumentDossierInterface;
use App\Interfaces\DossierIntegrationInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\LotAffectationInterface;
use App\Interfaces\LotNominationInterface;
use App\Interfaces\NotificationInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\PriseDeServiceInterface;
use App\Interfaces\RemiseMaterielInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Interfaces\ClassegrillesalarialeInterface;
use App\Interfaces\ParametregrileInterface;
use App\Interfaces\SalaireAgentInterface;
use App\Interfaces\SalaireInterface;
use App\Interfaces\AdministrationInterface;
use App\Interfaces\AuditLogInterface;
use App\Interfaces\BureauInterface;
use App\Interfaces\ConventionStageInterface;
use App\Interfaces\CategorieInterface;
use App\Interfaces\DirectionInterface;
use App\Interfaces\DiplomeInterface;
use App\Interfaces\EchelonInterface;
use App\Interfaces\FonctionInterface;
use App\Interfaces\GradeInterface;
use App\Interfaces\LocaliteInterface;
use App\Interfaces\MotifAdministratifInterface;
use App\Interfaces\ParametreApplicationInterface;
use App\Interfaces\PermissionInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\ServiceInterface;
use App\Interfaces\AbsenceInterface;
use App\Interfaces\CongeSoldeInterface;
use App\Interfaces\ContactUrgenceInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Interfaces\DocumentAgentInterface;
use App\Interfaces\InformationsPersonnelleInterface;
use App\Interfaces\InformationsProfessionnelleInterface;
use App\Interfaces\JourFerieInterface;
use App\Interfaces\RegleAcquisitionCongeInterface;
use App\Interfaces\SituationFamilialeInterface;
use App\Interfaces\TypeAbsenceInterface;
use App\Interfaces\TypeCongeInterface;
use App\Interfaces\TypeContratInterface;
use App\Interfaces\TypeDocumentInterface;
use App\Interfaces\TypeIntegrationInterface;
use App\Interfaces\UserInterface;
use App\Repositories\CircuitValidationRepository;
use App\Repositories\ActeAdministratifRepository;
use App\Repositories\AffectationRepository;
use App\Repositories\AgentRepository;
use App\Repositories\CompteIntegrationRepository;
use App\Repositories\ContratRepository;
use App\Repositories\DocumentDossierRepository;
use App\Repositories\DossierIntegrationRepository;
use App\Repositories\HistoriqueIntegrationRepository;
use App\Repositories\LotAffectationRepository;
use App\Repositories\LotNominationRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\NominationRepository;
use App\Repositories\PriseDeServiceRepository;
use App\Repositories\RemiseMaterielRepository;
use App\Repositories\ValidationWorkflowRepository;
use App\Repositories\ClassegrillesalarialeRepository;
use App\Repositories\ParametregrileRepository;
use App\Repositories\SalaireAgentRepository;
use App\Repositories\SalaireRepository;
use App\Repositories\AdministrationRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\BureauRepository;
use App\Repositories\ConventionStageRepository;
use App\Repositories\CategorieRepository;
use App\Repositories\DirectionRepository;
use App\Repositories\DiplomeRepository;
use App\Repositories\EchelonRepository;
use App\Repositories\FonctionRepository;
use App\Repositories\GradeRepository;
use App\Repositories\LocaliteRepository;
use App\Repositories\MotifAdministratifRepository;
use App\Repositories\ParametreApplicationRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\AbsenceRepository;
use App\Repositories\CongeSoldeRepository;
use App\Repositories\ContactUrgenceRepository;
use App\Repositories\DemandeCongeRepository;
use App\Repositories\DocumentAgentRepository;
use App\Repositories\InformationsPersonnelleRepository;
use App\Repositories\InformationsProfessionnelleRepository;
use App\Repositories\JourFerieRepository;
use App\Repositories\RegleAcquisitionCongeRepository;
use App\Repositories\SituationFamilialeRepository;
use App\Repositories\TypeAbsenceRepository;
use App\Repositories\TypeCongeRepository;
use App\Repositories\TypeContratRepository;
use App\Repositories\TypeDocumentRepository;
use App\Repositories\TypeIntegrationRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private array $repositoryBindings = [
        // Module Stage
        ConventionStageInterface::class        => ConventionStageRepository::class,
        // Circuit de validation configurable
        CircuitValidationInterface::class      => CircuitValidationRepository::class,
        // Module 2 — Intégration Administrative
        AgentInterface::class                  => AgentRepository::class,
        DossierIntegrationInterface::class     => DossierIntegrationRepository::class,
        DocumentDossierInterface::class        => DocumentDossierRepository::class,
        ValidationWorkflowInterface::class     => ValidationWorkflowRepository::class,
        ActeAdministratifInterface::class      => ActeAdministratifRepository::class,
        ContratInterface::class                => ContratRepository::class,
        AffectationInterface::class            => AffectationRepository::class,
        LotAffectationInterface::class         => LotAffectationRepository::class,
        NominationInterface::class             => NominationRepository::class,
        LotNominationInterface::class          => LotNominationRepository::class,
        CompteIntegrationInterface::class      => CompteIntegrationRepository::class,
        RemiseMaterielInterface::class         => RemiseMaterielRepository::class,
        PriseDeServiceInterface::class         => PriseDeServiceRepository::class,
        HistoriqueIntegrationInterface::class  => HistoriqueIntegrationRepository::class,
        // Module 1.1 — Structure organisationnelle
        LocaliteInterface::class          => LocaliteRepository::class,
        AdministrationInterface::class    => AdministrationRepository::class,
        DirectionInterface::class         => DirectionRepository::class,
        ServiceInterface::class           => ServiceRepository::class,
        BureauInterface::class            => BureauRepository::class,
        // Module 1.2 — Référentiels RH
        DiplomeInterface::class           => DiplomeRepository::class,
        GradeInterface::class             => GradeRepository::class,
        CategorieInterface::class         => CategorieRepository::class,
        EchelonInterface::class           => EchelonRepository::class,
        FonctionInterface::class          => FonctionRepository::class,
        TypeContratInterface::class       => TypeContratRepository::class,
        TypeDocumentInterface::class      => TypeDocumentRepository::class,
        TypeIntegrationInterface::class   => TypeIntegrationRepository::class,
        TypeAbsenceInterface::class       => TypeAbsenceRepository::class,
        TypeCongeInterface::class         => TypeCongeRepository::class,
        MotifAdministratifInterface::class => MotifAdministratifRepository::class,
        // Module Congés & absences
        JourFerieInterface::class             => JourFerieRepository::class,
        RegleAcquisitionCongeInterface::class => RegleAcquisitionCongeRepository::class,
        CongeSoldeInterface::class            => CongeSoldeRepository::class,
        DemandeCongeInterface::class          => DemandeCongeRepository::class,
        AbsenceInterface::class               => AbsenceRepository::class,
        // Module dossier agent (vie courante)
        InformationsPersonnelleInterface::class     => InformationsPersonnelleRepository::class,
        InformationsProfessionnelleInterface::class => InformationsProfessionnelleRepository::class,
        ContactUrgenceInterface::class              => ContactUrgenceRepository::class,
        SituationFamilialeInterface::class          => SituationFamilialeRepository::class,
        DocumentAgentInterface::class               => DocumentAgentRepository::class,
        // Module Grille Salariale
        ClassegrillesalarialeInterface::class => ClassegrillesalarialeRepository::class,
        ParametregrileInterface::class        => ParametregrileRepository::class,
        SalaireInterface::class               => SalaireRepository::class,
        SalaireAgentInterface::class          => SalaireAgentRepository::class,
        // Module 1.3 — Administration système
        NotificationInterface::class        => NotificationRepository::class,
        UserInterface::class                => UserRepository::class,
        RoleInterface::class                => RoleRepository::class,
        PermissionInterface::class          => PermissionRepository::class,
        AuditLogInterface::class            => AuditLogRepository::class,
        ParametreApplicationInterface::class => ParametreApplicationRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositoryBindings as $interface => $repository) {
            $this->app->bind($interface, $repository);
        }
    }

    public function boot(): void
    {
        //
    }
}
