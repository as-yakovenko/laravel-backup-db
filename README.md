# Laravel Database Backup

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yakovenko/laravel-backup-db.svg?style=flat-square)](https://packagist.org/packages/yakovenko/laravel-backup-db)
[![Total Downloads](https://img.shields.io/packagist/dt/yakovenko/laravel-backup-db.svg?style=flat-square)](https://packagist.org/packages/yakovenko/laravel-backup-db)
[![License](https://img.shields.io/packagist/l/yakovenko/laravel-backup-db.svg?style=flat-square)](https://opensource.org/licenses/MIT)

`yakovenko/laravel-backup-db` - Simple Laravel database backup command with automatic cleanup options, secure MySQL connection, comprehensive logging, and configurable storage support.

## Installation

### Requirements

- PHP     : ^8.0
- Laravel : ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0
- MySQL database
- `mysqldump` command available in system PATH

### Install the Package

You can install the package via Composer:

```bash
composer require yakovenko/laravel-backup-db
```

## Configuration

Publish the config file:
```bash
php artisan vendor:publish --provider="Yakovenko\LaravelBackupDb\LaravelBackupDbServiceProvider" --tag=config
```

This will create `config/backup-db.php` with the following options:

```php
return [
    // Number of days to keep backups
    'cleanup_days' => env('BACKUP_DB_CLEANUP_DAYS', 15),
    
    // Enable automatic daily backup scheduling
    'auto_schedule' => env('BACKUP_DB_AUTO_SCHEDULE', true),
    
    // Storage disk for backups
    'storage_disk' => env('BACKUP_DB_STORAGE_DISK', 'local'),
    
    // Storage directory for backups
    'storage_directory' => env('BACKUP_DB_STORAGE_DIRECTORY', 'backup'),
    
    // Enable logging
    'logging' => env('BACKUP_DB_LOGGING', true),
];
```

## Automatic Scheduling

The package automatically schedules daily backups when `auto_schedule` is enabled in config. No need to manually add to `Kernel.php`!

## Usage

### Create backup only
```bash
php artisan yas:backup --run
```

### Create backup and delete old backups (older than configured days)
```bash
php artisan yas:backup --auto
```

### Delete all backups and create new one
```bash
php artisan yas:backup --all
```

### Delete only old backups (older than configured days)
```bash
php artisan yas:backup --d
```

### Author

- **Alexander Yakovenko** - [GitHub](https://github.com/as-yakovenko) - [Email](mailto:paffen.web@gmail.com)

## License

MIT
