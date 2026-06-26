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
use App\Interfaces\NominationInterface;
use App\Interfaces\PriseDeServiceInterface;
use App\Interfaces\RemiseMaterielInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Interfaces\ClassegrillesalarialeInterface;
use App\Interfaces\ParametregrileInterface;
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
use App\Repositories\NominationRepository;
use App\Repositories\PriseDeServiceRepository;
use App\Repositories\RemiseMaterielRepository;
use App\Repositories\ValidationWorkflowRepository;
use App\Repositories\ClassegrillesalarialeRepository;
use App\Repositories\ParametregrileRepository;
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
        NominationInterface::class             => NominationRepository::class,
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
        // Module Grille Salariale
        ClassegrillesalarialeInterface::class => ClassegrillesalarialeRepository::class,
        ParametregrileInterface::class        => ParametregrileRepository::class,
        SalaireInterface::class               => SalaireRepository::class,
        // Module 1.3 — Administration système
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
