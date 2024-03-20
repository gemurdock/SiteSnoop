# SiteSnoop
Watch websites for changes.

# Run local setup via docker
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
cd ./site-snoop
sail up
