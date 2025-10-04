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
        {--auto : Delete backups older than N days and create a current one}
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

        // Load configuration
        $storageDisk   = config( 'backup-db.storage_disk', 'local' );
        $directoryName = config( 'backup-db.storage_directory', 'backup' );
        $cleanupDays   = config( 'backup-db.cleanup_days', 15 );
        $logging       = config( 'backup-db.logging', true );

        try {

            // Process cleanup if not --run only
            if ( $this->option( 'run' ) === false ) {
                $this->processCleanup( $storageDisk, $directoryName, $cleanupDays, $logging );
            }

            // Create backup if not --d only
            if ( $this->option( 'd' ) === false ) {
                $this->createBackup( $storageDisk, $directoryName, $logging );
            }

            $this->info( "✅ DatabaseBackupCommand completed successfully" );

            return 0;

        } catch ( \Exception $e ) {

            $this->error( "❌ DatabaseBackupCommand failed: " . $e->getMessage() );

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
     * Process cleanup of old backup files
     *
     * @param string $storageDisk
     * @param string $directoryName
     * @param int $cleanupDays
     * @param bool $logging
     */
    protected function processCleanup( string $storageDisk, string $directoryName, int $cleanupDays, bool $logging ): void
    {
        $this->info( "🔄 Processing cleanup..." );

        $physicalPath = $this->getPhysicalPath( $storageDisk, $directoryName );

        if ( !file_exists( $physicalPath ) ) {
            $this->info( "Directory does not exist: $physicalPath" );
            return;
        }

        $physicalFiles = scandir( $physicalPath );
        $backupFiles   = array_filter( $physicalFiles, function( $file ) {
            return $file !== '.' && $file !== '..' && strpos( $file, 'backup-' ) === 0;
        });

        if ( empty( $backupFiles ) ) {
            $this->info( "No backup files found for cleanup" );
            return;
        }

        $this->info( "Found " . count( $backupFiles ) . " backup files" );
        $deletedCount = 0;

        foreach ( $backupFiles as $filename ) {
            $filePath = $physicalPath . "/" . $filename;
            $timestamp = filemtime( $filePath );

            if ( $this->shouldDeleteFile( $timestamp, $cleanupDays ) ) {
                unlink( $filePath );
                $this->info( "✅ Deleted: $filename" );
                $deletedCount++;

                if ( $logging ) {
                    Log::info( "Database backup: Deleted backup file", [ 'file' => $filename ] );
                }
            }
        }

        $this->info( "Cleanup completed: $deletedCount files deleted" );
    }

    /**
     * Create a new backup
     *
     * @param string $storageDisk
     * @param string $directoryName
     * @param bool $logging
     */
    protected function createBackup( string $storageDisk, string $directoryName, bool $logging ): void
    {
        $this->info( "💾 Creating backup..." );

        $filename     = "backup-" . Carbon::now()->format('Y-m-d_H-i-s') . ".gz";
        $physicalPath = $this->getPhysicalPath( $storageDisk, $directoryName );
        $full_path    = $physicalPath . "/" . $filename;

        // Ensure directory exists
        $this->ensureDirectoryExists( $physicalPath, $storageDisk, $directoryName );

        // Execute mysqldump
        $command = $this->buildMysqldumpCommand( $full_path );
        exec( $command, $output, $returnVar );

        // Check result
        $fileSize = file_exists( $full_path ) ? filesize( $full_path ) : 0;

        if ( $fileSize > 0 ) {
            $this->info( "✅ Backup created: $filename (" . number_format( $fileSize / 1024, 2 ) . " KB)" );

            if ( $logging ) {
                Log::info( "Database backup: Backup created successfully", [
                    'filename' => $filename,
                    'path'     => $full_path,
                    'size'     => $fileSize,
                    'database' => config( 'database.connections.mysql.database' ),
                ]);
            }
        } else {
            $this->error( "❌ Backup failed - no file created" );

            if ( $logging ) {
                Log::error( "Database backup: No file created", [
                    'database' => config( 'database.connections.mysql.database' )
                ]);
            }
        }
    }

    /**
     * Determine if a file should be deleted based on its timestamp
     *
     * @param int $timestamp
     * @param int $cleanupDays
     * @return bool
     */
    protected function shouldDeleteFile( int $timestamp, int $cleanupDays ): bool
    {
        if ( $this->option( 'all' ) ) {
            return true;
        }

        if ( $this->option( 'd' ) || $this->option( 'auto' ) ) {
            return $timestamp < now()->subDays( $cleanupDays )->getTimestamp();
        }

        return false;
    }

    /**
     * Ensure the backup directory exists
     *
     * @param string $physicalPath
     * @param string $storageDisk
     * @param string $directoryName
     */
    protected function ensureDirectoryExists( string $physicalPath, string $storageDisk, string $directoryName ): void
    {
        if ( !file_exists( $physicalPath ) ) {
            mkdir( $physicalPath, 0755, true );
        }

        if ( !Storage::disk( $storageDisk )->exists( $directoryName ) ) {
            Storage::disk( $storageDisk )->makeDirectory( $directoryName );
        }
    }

    /**
     * Build mysqldump command
     *
     * @param string $outputPath
     * @return string
     */
    protected function buildMysqldumpCommand( string $outputPath ): string
    {
        $username = config( 'database.connections.mysql.username' );
        $password = config( 'database.connections.mysql.password' );
        $host     = config( 'database.connections.mysql.host' );
        $database = config( 'database.connections.mysql.database' );
        $port     = config( 'database.connections.mysql.port', '3306' );

        return "mysqldump --user=" . escapeshellarg( $username ) .
               " --password=" . escapeshellarg( $password ) .
               " --host=" . escapeshellarg( $host ) .
               " --port=" . escapeshellarg( $port ) .
               " --single-transaction " .
               escapeshellarg( $database ) .
               " | gzip > " . escapeshellarg( $outputPath );
    }

    /**
     * Get the physical path to the directory depending on the disk setting
     *
     * @param string $storageDisk
     * @param string $directoryName
     * @return string
     */
    protected function getPhysicalPath( string $storageDisk, string $directoryName ): string
    {
        $diskConfig = config( "filesystems.disks.{$storageDisk}" );

        // For all disks, use root from the configuration
        if ( isset( $diskConfig['root'] ) ) {
            return rtrim( $diskConfig['root'], '/' ) . '/' . $directoryName;
        }

        // Fallback to local
        return storage_path( "app/{$directoryName}" );
    }
}
