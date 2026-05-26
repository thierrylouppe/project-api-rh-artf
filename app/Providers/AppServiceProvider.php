<?php

namespace App\Providers;

use App\Interfaces\AdministrationInterface;
use App\Interfaces\AuditLogInterface;
use App\Interfaces\BureauInterface;
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
use App\Interfaces\TypeRecrutementInterface;
use App\Interfaces\UserInterface;
use App\Repositories\AdministrationRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\BureauRepository;
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
use App\Repositories\TypeRecrutementRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    private array $repositoryBindings = [
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
        TypeRecrutementInterface::class   => TypeRecrutementRepository::class,
        TypeAbsenceInterface::class       => TypeAbsenceRepository::class,
        TypeCongeInterface::class         => TypeCongeRepository::class,
        MotifAdministratifInterface::class => MotifAdministratifRepository::class,
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
