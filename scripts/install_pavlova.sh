#!/bin/bash
# Installation automatisée Budget Workflow Pavlova
set -e
LOG_FILE="/tmp/pavlova_install.log"
> "$LOG_FILE"

log(){ echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"; }

update_system(){
  log "Mise à jour du système..."
  sudo apt-get update -y && sudo apt-get upgrade -y
  sudo apt-get install -y curl gnupg2 ca-certificates lsb-release unzip sudo
}

install_php(){
  log "Installation PHP..."
  sudo apt-get install -y software-properties-common
  sudo add-apt-repository -y ppa:ondrej/php || true
  sudo apt-get update -y
  sudo apt-get install -y php8.1 php8.1-fpm php8.1-cli php8.1-pgsql php8.1-sqlite3 php8.1-redis php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-zip php8.1-bcmath php8.1-soap php8.1-intl php8.1-readline php8.1-ldap
}

install_database(){
  log "Installation PostgreSQL..."
  sudo sh -c "echo 'deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main' > /etc/apt/sources.list.d/pgdg.list"
  curl -sSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
  sudo apt-get update -y
  if ! sudo apt-get install -y postgresql-16 postgresql-client-16; then
    log "Échec PostgreSQL, installation SQLite"
    sudo apt-get install -y sqlite3
    SQLITE_ONLY=true
  fi
}

install_webserver(){
  log "Installation Nginx..."
  sudo apt-get install -y nginx
}

install_tools(){
  log "Installation outils complémentaires..."
  sudo apt-get install -y redis-server git nodejs npm supervisor
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
}

validate_installation(){
  log "Validation services..."
  sudo systemctl enable --now php8.1-fpm || true
  sudo systemctl enable --now nginx || true
  sudo systemctl enable --now redis-server || true
  if [ -z "$SQLITE_ONLY" ]; then
    sudo systemctl enable --now postgresql || true
  fi
}

update_system
install_php
install_database
install_webserver
install_tools
validate_installation
log "Installation terminée."
