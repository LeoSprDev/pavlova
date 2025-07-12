#!/bin/bash
# Script de vérification prérequis Budget Workflow Pavlova

LOG_FILE="/tmp/pavlova_check.log"
> "$LOG_FILE"

log(){ echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"; }

check_os(){
  log "Détection du système d'exploitation..."
  if [ -e /etc/os-release ]; then
    . /etc/os-release
    OS="$ID"; VER="$VERSION_ID"; PRETTY="$PRETTY_NAME"
  else
    log "Fichier /etc/os-release introuvable"; return 1
  fi
  case "$OS" in
    ubuntu|debian|centos|rhel)
      log "OS détecté: $PRETTY";;
    *)
      log "OS non supporté: $PRETTY"; return 1;;
  esac
}

check_permissions(){
  log "Vérification des permissions..."
  if sudo -n true 2>/dev/null; then
    log "Droits sudo: OK"
  else
    log "Droits sudo requis"; return 1
  fi
  if [ -w /var/www ]; then
    log "Écriture /var/www: OK"
  else
    log "Pas d'écriture /var/www"; return 1
  fi
}

check_software(){
  log "Vérification des logiciels..."
  local missing=0
  for bin in php nginx git; do
    if command -v $bin >/dev/null 2>&1; then
      log "$bin présent"
    else
      log "$bin manquant"; missing=1
    fi
  done
  [ $missing -eq 0 ] || MISSING=true
}

check_resources(){
  log "Vérification des ressources..."
  RAM=$(free -m | awk '/^Mem:/ {print $2}')
  DISK=$(df --output=avail / | tail -1)
  CPU=$(nproc)
  log "RAM disponible: ${RAM}MB"
  log "Disque disponible: ${DISK}KB"
  log "CPU cores: $CPU"
  [ "$RAM" -ge 2000 ] || log "RAM insuffisante"
  [ "$DISK" -ge 10485760 ] || log "Espace disque insuffisant"
}

generate_report(){
  log "Génération rapport..."
  if [ "$MISSING" = true ]; then
    log "Prérequis manquants"
    exit 1
  else
    log "Tous les prérequis sont présents"
  fi
}

check_os && check_permissions && check_software && check_resources && generate_report
