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
    'cleanup_days' => env( 'BACKUP_DB_CLEANUP_DAYS', 5 ),

    /*
    |--------------------------------------------------------------------------
    | Auto Schedule
    |--------------------------------------------------------------------------
    |
    | Enable automatic backup scheduling.
    | If enabled, backup will run automatically with --auto option.
    |
    */
    'auto_schedule' => env( 'BACKUP_DB_AUTO_SCHEDULE', true ),

    /*
    |--------------------------------------------------------------------------
    | Schedule Time
    |--------------------------------------------------------------------------
    |
    | Time when the backup should run (24-hour format).
    | Examples: '00:15', '02:30', '14:00'
    |
    */
    'schedule_time' => env( 'BACKUP_DB_SCHEDULE_TIME', '00:15' ),

    /*
    |--------------------------------------------------------------------------
    | Schedule Frequency
    |--------------------------------------------------------------------------
    |
    | How often the backup should run.
    | Options: 'daily', 'weekly', 'monthly'
    |
    */
    'schedule_frequency' => env( 'BACKUP_DB_SCHEDULE_FREQUENCY', 'daily' ),

    /*
    |--------------------------------------------------------------------------
    | Schedule Day (for weekly/monthly)
    |--------------------------------------------------------------------------
    |
    | For weekly: day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
    | For monthly: day of month (1-31)
    | Ignored for daily frequency.
    |
    */
    'schedule_day' => env( 'BACKUP_DB_SCHEDULE_DAY', 1 ),

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
