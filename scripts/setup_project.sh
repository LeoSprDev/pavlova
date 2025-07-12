#!/bin/bash
# Configuration projet après installation système
set -e
LOG_FILE="/tmp/pavlova_setup.log"
> "$LOG_FILE"

log(){ echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"; }

clone_project(){
  log "Clonage du projet..."
  if [ ! -d /var/www/pavlova ]; then
    sudo git clone https://github.com/LeoSprDev/pavlova /var/www/pavlova
  fi
  sudo chown -R $USER:www-data /var/www/pavlova
  cd /var/www/pavlova
}

install_dependencies(){
  log "Installation dépendances PHP..."
  composer install --no-interaction --optimize-autoloader
  log "Installation dépendances Node..."
  [ -f package.json ] && npm install --production
}

configure_laravel(){
  log "Configuration Laravel..."
  cp .env.example .env
  php artisan key:generate
  sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
  sed -i "s/DB_DATABASE=.*/DB_DATABASE=pavlova_budget/" .env
  sed -i "s/DB_USERNAME=.*/DB_USERNAME=pavlova_user/" .env
  DB_PASS=$(openssl rand -base64 12)
  sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
}

setup_database(){
  log "Configuration base de données..."
  if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='pavlova_user'" | grep -q 1; then
    log "Utilisateur PostgreSQL déjà créé"
  else
    sudo -u postgres psql -c "CREATE USER pavlova_user WITH PASSWORD '$DB_PASS';"
  fi
  sudo -u postgres psql -c "CREATE DATABASE pavlova_budget OWNER pavlova_user" || true
  php artisan migrate --force
  php artisan db:seed --force
}

configure_server(){
  log "Configuration Nginx..."
  sudo tee /etc/nginx/sites-available/pavlova >/dev/null <<NGINX
server {
    listen 80;
    server_name _;
    root /var/www/pavlova/public;
    index index.php index.html;
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }
}
NGINX
  sudo ln -sf /etc/nginx/sites-available/pavlova /etc/nginx/sites-enabled/pavlova
  sudo nginx -t && sudo systemctl reload nginx || true
  sudo chown -R www-data:www-data storage bootstrap/cache
}

final_tests(){
  log "Tests finaux..."
  php artisan --version && log "Laravel OK"
}

clone_project
install_dependencies
configure_laravel
setup_database
configure_server
final_tests
log "Setup terminé."
