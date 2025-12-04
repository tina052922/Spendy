# Includes Folder

This folder contains all PHP include files and shared utilities.

## Files

- **db.php** - Database connection configuration
- **activity_logger.php** - Activity logging functions
- **config.php** - Application configuration
- **google_oauth.php** - Google OAuth configuration

## Usage

All PHP files should include these files using:
```php
require_once __DIR__ . '/../includes/filename.php';
```

## Database Connection

The `db.php` file provides the `$conn` MySQLi connection object that should be used throughout the application.

## Activity Logging

The `activity_logger.php` file provides functions for logging user activities, including:
- `logDeposit()`
- `logWithdraw()`
- `logProfileUpdate()`
- And more...

