<?php

namespace App\Console;


use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\ParseLawsCommand::class,
        \App\Console\Commands\SaveLawsCommand::class,
        \App\Console\Commands\TranslateLawsCommand::class,
        \App\Console\Commands\VacanciesScraping\ParseInfostudCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
