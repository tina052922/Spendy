# API Folder

This folder contains all API endpoint files (PHP files that handle HTTP requests and return JSON responses).

## Structure

All API endpoints are located here, including:
- Authentication endpoints (login.php, signup.php, logout.php)
- Data retrieval endpoints (get_*.php)
- Data modification endpoints (add_*.php, update_*.php)
- Transaction endpoints (draw_save_money.php, auto_save_income.php)

## Usage

All API endpoints should be accessed using the path: `../api/filename.php` from HTML files in the `views/` folder.

## Database Connection

All API files use the database connection from `../includes/db.php`.

## Activity Logging

Activity logging is handled by `../includes/activity_logger.php`.

