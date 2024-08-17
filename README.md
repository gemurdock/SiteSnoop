# SiteSnoop
Watch websites for changes.

# Run local setup via docker
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
cd ./site-snoop
sail up
(If sail not installed 'php artisan sail:install')
(If not migrated yet) sail artisan migrate

# Run tests, but exclude external requests
php artisan test --exclude-group=external_resource

# Run test, with xdbug, for only one test
php -dxdebug.remote_autostart artisan test --filter JSONQueryTest

# If connecting remotely to the database while using Sail, you may need to add the following to the .env file
# This is the port for postgres
FORWARD_DB_PORT=5432
