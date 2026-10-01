<?php

use App\Jobs\PositionConventionnelleEcheanceJob;
use App\Jobs\AppliquerEffetsMiseAPiedJob;
use App\Jobs\ContratDelai30JoursJob;
use App\Jobs\ContratEssaiEnFinDateJob;
use App\Jobs\ConventionStageEnFinDateJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ConventionStageEnFinDateJob)
    ->weekdays()
    ->at('08:00')
    ->withoutOverlapping();

Schedule::job(new AppliquerEffetsMiseAPiedJob)
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::job(new ContratEssaiEnFinDateJob)
    ->weekdays()
    ->at('08:00')
    ->withoutOverlapping();

Schedule::job(new ContratDelai30JoursJob)
    ->weekdays()
    ->at('08:00')
    ->withoutOverlapping();

Schedule::job(new PositionConventionnelleEcheanceJob)
    ->weekdays()
    ->at('08:00')
    ->withoutOverlapping();
