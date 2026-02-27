# Deploy Scripts

## Requirements on server
- /var/www/ump/shared/.env exists
- /var/www/ump/shared/storage exists and writable
- git, composer, php installed
- current symlink points to a release
- queue worker managed by supervisor/systemd

## Deploy
REPO_URL="git@github.com:ORG/REPO.git" BRANCH=main ./deploy.sh deploy

## Rollback
./deploy.sh rollback

## Notes
- Script uses a lock file to prevent concurrent deploys.
- Keeps last 5 releases by default.
- Runs `php artisan deploy:check` before switching.
