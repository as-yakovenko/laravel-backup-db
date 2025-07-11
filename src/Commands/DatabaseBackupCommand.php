<?php

namespace Yakovenko\LaravelBackupDb\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'yas:backup {--run : Only create a backup}
        {--all : Delete all backups and create a current one}
        {--auto : Create a current one and delete backups older than N days}
        {--d : Only delete backups older than N days}';

    protected $description = 'Backup database with automatic cleanup options';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info( "▶️  Starting DatabaseBackupCommand" );

        try {
            $storageDisk    = config( 'backup-db.storage_disk', 'local' );
            $directoryName  = config( 'backup-db.storage_directory', 'backup' );
            $cleanupDays    = config( 'backup-db.cleanup_days', 15 );
            $logging        = config( 'backup-db.logging', true );

            $onlyRun     = $this->option( 'run' );
            $deleteOnly  = $this->option( 'd' );
            $autoCleanup = $this->option( 'auto' );
            $deleteAll   = $this->option( 'all' );

            $storage = Storage::disk( $storageDisk );

            if ( $onlyRun && ( $deleteOnly || $autoCleanup || $deleteAll ) ) {
                $this->error( '--run cannot be combined with --d, --auto or --all' );
                return 1;
            }

            if ( !$onlyRun ) {
                $this->deleteBackups( $storage, $directoryName, $cleanupDays, $deleteAll, $autoCleanup, $logging );
            }

            if ( !$deleteOnly ) {
                $this->createBackup( $storage, $directoryName, $logging );
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

    /**
     * Delete backups older than specified days or all backups if --all is specified.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $storage
     * @param string $directory
     * @param int $days
     * @param bool $deleteAll
     * @param bool $autoCleanup
     * @param bool $logging
     */
    protected function deleteBackups( $storage, string $directory, int $days, bool $deleteAll, bool $autoCleanup, bool $logging ): void
    {
        $files      = $storage->allFiles( $directory );
        $cutoffDate = now()->subDays( $days )->getTimestamp();

        foreach ( $files as $filePath ) {
            $timestamp = $storage->lastModified( $filePath );

            if ( $deleteAll || ( $autoCleanup && $timestamp < $cutoffDate ) ) {
                $storage->delete( $filePath );
                $this->info( "Deleted backup: $filePath" );

                if ( $logging ) {
                    Log::info( "Database backup: Deleted backup file", [ 'file' => $filePath ] );
                }
            }
        }
    }

    /**
     * Create a backup of the database and store it in the specified directory.
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $storage
     * @param string $directory
     * @param bool $logging
     */
    protected function createBackup( $storage, string $directory, bool $logging ): void
    {
        $physicalPath = storage_path() . "/app/" . $directory;

        if ( !file_exists( $physicalPath ) ) {
            mkdir( $physicalPath, 0755, true );
        }

        if ( ! $storage->exists( $directory ) ) {
            $storage->makeDirectory( $directory );
        }

        $now       = Carbon::now();
        $filename  = "backup-" . $now->format( 'Y-m-d_H-i-s' ) . ".gz";
        $full_path = $physicalPath . "/" . $filename;

        $username = config( 'database.connections.mysql.username' );
        $password = config( 'database.connections.mysql.password' );
        $host     = config( 'database.connections.mysql.host' );
        $database = config( 'database.connections.mysql.database' );
        $port     = config( 'database.connections.mysql.port', '3306' );

        $errorFile = tempnam( sys_get_temp_dir(), 'mysql_error_' );

        $command = "mysqldump --user=" . escapeshellarg( $username ) . " --password=" . escapeshellarg( $password ) . " --host=" . escapeshellarg( $host ) . " --port=" . escapeshellarg( $port ) . " " . escapeshellarg( $database ) . " 2>" . escapeshellarg( $errorFile ) . " | gzip > " . escapeshellarg( $full_path );

        $mysqldumpCheck = ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' )
            ? 'where mysqldump'
            : 'which mysqldump';

        exec( $mysqldumpCheck, $checkOutput, $checkResult );

        if ( $checkResult !== 0 ) {
            $this->error( 'mysqldump utility not found in system PATH.' );
            if ( $logging ) {
                Log::error( 'Database backup: mysqldump utility not found.' );
            }
            return;
        }

        exec( $command, $output, $returnVar );

        $errorOutput = file_exists( $errorFile ) ? file_get_contents( $errorFile ) : '';

        if ( file_exists( $errorFile ) ) {
            unlink( $errorFile );
        }

        $fileSize = file_exists( $full_path ) ? filesize( $full_path ) : 0;

        if ( !empty( $errorOutput ) ) {
            $this->warn( "mysqldump warning: " . trim( $errorOutput ) );
        }

        if ( $fileSize > 0 ) {
            $this->info( "Backup created successfully: $filename (size: " . number_format( $fileSize / 1024, 2 ) . " KB)" );

            if ( $logging ) {
                Log::info( "Database backup: Backup created successfully", [
                    'filename' => $filename,
                    'path'     => $full_path,
                    'size'     => $fileSize,
                    'database' => $database,
                ]);
            }
        } else {
            $this->error( "Backup failed - no file created" );

            if ( $logging ) {
                Log::error( "Database backup: No file created", [
                    'database' => $database,
                ]);
            }
        }
    }
}
