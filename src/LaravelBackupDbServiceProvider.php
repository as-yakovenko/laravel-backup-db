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
                $schedule           = $this->app->make( Schedule::class );
                $scheduleTime       = config( 'backup-db.schedule_time', '00:15' );
                $scheduleFrequency  = config( 'backup-db.schedule_frequency', 'daily' );
                $scheduleDay        = config( 'backup-db.schedule_day', 1 );

                $command = $schedule->command( 'yas:backup --auto' )
                        ->withoutOverlapping()
                        ->runInBackground();

                // Set schedule based on frequency
                switch ( $scheduleFrequency ) {
                    case 'weekly':
                        $command->weeklyOn( $scheduleDay, $scheduleTime );
                        break;
                    case 'monthly':
                        $command->monthlyOn( $scheduleDay, $scheduleTime );
                        break;
                    case 'daily':
                    default:
                        $command->dailyAt( $scheduleTime );
                        break;
                }
            });
        }
    }
}
