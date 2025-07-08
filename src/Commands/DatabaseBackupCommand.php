<?php

namespace Yakovenko\LaravelBackupDb\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'yas:backup {--run : Only create a backup}
        {--all : The delete backups and create a current one}
        {--auto : The create a current one and delete backups from the previous 15 days}
        {--d : Only delete backups from the previous 15 days}';

    protected $description = 'Backup database with automatic cleanup options';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $storageDisk    = config( 'backup-db.storage_disk', 'local' );
            $directoryName  = config( 'backup-db.storage_directory', 'backup' );
            $cleanupDays    = config( 'backup-db.cleanup_days', 15 );
            $logging        = config( 'backup-db.logging', true );

            if ( $this->option( 'run' ) === false ) {
                $files = Storage::disk( $storageDisk )->allFiles( $directoryName );

                foreach ( $files as $filePath ) {
                    $timestamp = Storage::disk( $storageDisk )->lastModified( $filePath );

                    if ( ( $this->option( 'd' ) || $this->option( 'auto' ) ) && $timestamp < now()->subDays( $cleanupDays )->getTimestamp() ) {
                        Storage::disk( $storageDisk)->delete( $filePath );
                        $this->info( "Deleted old backup: $filePath" );

                        if ( $logging ) {
                            Log::info( "Database backup: Deleted old backup file", ['file' => $filePath] );
                        }
                    }

                    if ( $this->option( 'all' ) ) {
                        Storage::disk( $storageDisk)->delete( $filePath );
                        $this->info( "Deleted backup (all): $filePath" );

                        if ( $logging ) {
                            Log::info( "Database backup: Deleted backup file (all option)", ['file' => $filePath] );
                        }
                    }
                }
            }

            if ( $this->option( 'd' ) === false ) {
                $filename  = "backup-" . Carbon::now()->format('Y-m-d') . ".gz";
                $full_path = storage_path() . "/app/" . $directoryName . "/" . $filename;

                if ( !Storage::disk( $storageDisk )->exists( $directoryName ) ) {
                    Storage::disk( $storageDisk )->makeDirectory( $directoryName );
                }

                $username = config( 'database.connections.mysql.username' );
                $password = config( 'database.connections.mysql.password' );
                $host     = config( 'database.connections.mysql.host ');
                $database = config( 'database.connections.mysql.database' );

                // Create temporary file for MySQL password (more secure)
                $tempFile = tempnam( sys_get_temp_dir(), 'mysql_backup_' );
                file_put_contents( $tempFile, "[client]\npassword={$password}\n" );

                $command = "mysqldump --defaults-file={$tempFile} --user={$username} --host={$host} {$database} | gzip > {$full_path}";

                $returnVar  = null;
                $output     = null;

                exec( $command, $output, $returnVar );

                // Clean up temporary file
                unlink( $tempFile );

                if ( $returnVar === 0 ) {
                    $this->info( "Backup created successfully: $filename" );

                    if ( $logging ) {
                        Log::info( "Database backup: Backup created successfully", [
                            'filename' => $filename,
                            'path'     => $full_path,
                            'database' => $database,
                        ]);
                    }
                } else {
                    $this->error( "Backup failed with exit code: $returnVar" );

                    if ( $logging ) {
                        Log::error( "Database backup: Backup failed", [
                            'exit_code' => $returnVar,
                            'output'    => $output,
                            'database'  => $database,
                        ]);
                    }
                }
            }

            return 0;

        } catch ( \Exception $e ) {
            $this->error( "Backup failed with exception: " . $e->getMessage() );

            if ( config( 'backup-db.logging', true ) ) {
                Log::error( "Database backup: Exception occurred", [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }

            return 1;
        }
    }
}
