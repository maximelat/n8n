#!/bin/bash

# Script de restauration pour n8n

# Vérification des arguments
if [ $# -ne 2 ]; then
    echo "Usage: $0 <fichier_config_backup> <fichier_data_backup>"
    echo "Exemple: $0 /root/n8n-backups/n8n_backup_2023-10-01_12-00-00.tar.gz /root/n8n-backups/n8n_data_2023-10-01_12-00-00.tar.gz"
    exit 1
fi

CONFIG_BACKUP=$1
DATA_BACKUP=$2

# Vérification de l'existence des fichiers de sauvegarde
if [ ! -f "$CONFIG_BACKUP" ]; then
    echo "Le fichier de sauvegarde de configuration n'existe pas: $CONFIG_BACKUP"
    exit 1
fi

if [ ! -f "$DATA_BACKUP" ]; then
    echo "Le fichier de sauvegarde de données n'existe pas: $DATA_BACKUP"
    exit 1
fi

# Arrêt du conteneur n8n si en cours d'exécution
if docker ps | grep -q n8n; then
    echo "Arrêt du conteneur n8n..."
    docker-compose down
fi

# Restauration des fichiers de configuration
echo "Restauration des fichiers de configuration..."
TEMP_DIR=$(mktemp -d)
tar -xzf "$CONFIG_BACKUP" -C "$TEMP_DIR"

# Déplacement des fichiers restaurés au bon endroit
cp "$TEMP_DIR/docker-compose.yml" .
cp "$TEMP_DIR/.env" . 2>/dev/null || echo "Fichier .env non trouvé dans la sauvegarde, l'existant est conservé."
cp "$TEMP_DIR/Dockerfile" .
cp "$TEMP_DIR/nginx-config.conf" .

# Nettoyage du répertoire temporaire
rm -rf "$TEMP_DIR"

# Restauration des données n8n
echo "Restauration des données n8n..."
docker volume rm n8n_data 2>/dev/null || true
docker volume create n8n_data
docker run --rm -v n8n_data:/destination -v $(dirname "$DATA_BACKUP"):/backup ubuntu sh -c "tar -xzf /backup/$(basename "$DATA_BACKUP") -C /destination"

# Redémarrage de n8n
echo "Redémarrage de n8n..."
docker-compose up -d

echo "Restauration terminée. n8n a été redémarré avec les données restaurées." 