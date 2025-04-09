#!/bin/bash

# Script de sauvegarde pour n8n

# Définition des variables
BACKUP_DIR="/root/n8n-backups"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="$BACKUP_DIR/n8n_backup_$DATE.tar.gz"

# Création du répertoire de sauvegarde s'il n'existe pas
mkdir -p $BACKUP_DIR

# Vérification que n8n fonctionne
if ! docker ps | grep -q n8n; then
    echo "Le conteneur n8n ne semble pas être en cours d'exécution."
    exit 1
fi

# Sauvegarde des données n8n
echo "Sauvegarde des données n8n..."
docker run --rm -v n8n_data:/source -v $BACKUP_DIR:/backup ubuntu tar czf /backup/n8n_data_$DATE.tar.gz -C /source .

# Sauvegarde des fichiers de configuration
echo "Sauvegarde des fichiers de configuration..."
tar -czf $BACKUP_FILE docker-compose.yml .env Dockerfile nginx-config.conf

echo "Sauvegarde terminée : $BACKUP_FILE"

# Conservation des 7 dernières sauvegardes uniquement
echo "Suppression des anciennes sauvegardes..."
ls -t $BACKUP_DIR/n8n_backup_*.tar.gz | tail -n +8 | xargs -r rm
ls -t $BACKUP_DIR/n8n_data_*.tar.gz | tail -n +8 | xargs -r rm

echo "Nettoyage terminé. Les 7 sauvegardes les plus récentes ont été conservées." 