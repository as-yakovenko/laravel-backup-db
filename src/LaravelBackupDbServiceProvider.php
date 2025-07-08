<?php

namespace Yakovenko\LaravelBackupDb;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Yakovenko\LaravelBackupDb\Commands\DatabaseBackupCommand;

class LaravelBackupDbServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom( __DIR__ . '/config/backup-db.php', 'backup-db' );
    }

    public function boot()
    {
        if ( $this->app->runningInConsole() ) {
            $this->commands([
                DatabaseBackupCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/config/backup-db.php' => config_path( 'backup-db.php' ),
            ], 'config' );
        }

        // Auto schedule if enabled
        if ( config( 'backup-db.auto_schedule', true ) ) {
            $this->app->booted( function () {
                $schedule = $this->app->make( Schedule::class );
                $schedule->command('yas:backup --auto')->daily();
            });
        }
    }
}
