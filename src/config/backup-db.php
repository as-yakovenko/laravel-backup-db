<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Database Configuration
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Auto Cleanup Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep backups before automatic cleanup.
    | Older backups will be deleted when using --auto or --d options.
    |
    */
    'cleanup_days' => env( 'BACKUP_DB_CLEANUP_DAYS', 15 ),

    /*
    |--------------------------------------------------------------------------
    | Auto Schedule
    |--------------------------------------------------------------------------
    |
    | Enable automatic daily backup scheduling.
    | If enabled, backup will run daily with --auto option.
    |
    */
    'auto_schedule' => env( 'BACKUP_DB_AUTO_SCHEDULE', true ),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk where backups will be stored.
    |
    */
    'storage_disk' => env( 'BACKUP_DB_STORAGE_DISK', 'local' ),

    /*
    |--------------------------------------------------------------------------
    | Storage Directory
    |--------------------------------------------------------------------------
    |
    | The directory within the storage disk where backups will be stored.
    |
    */
    'storage_directory' => env( 'BACKUP_DB_STORAGE_DIRECTORY', 'backup' ),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of backup operations.
    |
    */
    'logging' => env( 'BACKUP_DB_LOGGING', true ),
];
