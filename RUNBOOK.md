# UMP Mentorship Portal - Ops Runbook

## Critical URLs
- Health: GET /api/v1/health
- Reports: GET /api/v1/admin/reports/summary (auth required)

## Expected Health Output
- success=true, db_ok=true, app_time present

## Common Checks
### 1) API down
- Check web server (nginx/apache) status
- Check php-fpm status
- Check application logs: storage/logs/laravel.log

### 2) DB errors
- Verify MySQL service running
- Verify DB credentials in .env
- Run "select 1" connectivity check

### 3) Queue stuck (emails/reminders not sending)
- Check queue worker process running
- Restart supervisor service for queue worker
- Run: php artisan queue:restart
- Check failed jobs: php artisan queue:failed

### 4) Scheduler not running (reminders/backups)
- Ensure cron exists:
  * * * * * php /var/www/.../artisan schedule:run >> /dev/null 2>&1

### 5) Sentry verification
- Confirm DSN set
- Trigger a test 500 in staging only (never production)

## Standard Restart Commands
- php artisan optimize:clear
- php artisan config:cache
- php artisan route:cache
- php artisan event:cache
- php artisan queue:restart

## Deployment Checklist
- APP_DEBUG=false
- DB migrated
- cache warmed
- queue worker running
- scheduler configured
- health endpoint returns db_ok true
