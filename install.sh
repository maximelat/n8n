#!/bin/bash

# Script d'installation de n8n sur serveur OVH

# Définir le chemin du répertoire n8n
N8N_DIR="$HOME/www/projet/n8n"
cd "$N8N_DIR"

# Vérification de Docker
if ! command -v docker &> /dev/null
then
    echo "Docker n'est pas installé. Installation en cours..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    usermod -aG docker $(whoami)
    echo "Docker a été installé. Veuillez vous déconnecter et vous reconnecter pour appliquer les changements de groupe."
    exit 1
fi

# Vérification de Docker Compose
if ! command -v docker-compose &> /dev/null
then
    echo "Docker Compose n'est pas installé. Installation en cours..."
    # Vérifier si nous avons téléchargé Docker Compose avec l'installateur PHP
    if [ -f "$N8N_DIR/docker-compose" ]; then
        echo "Utilisation du Docker Compose téléchargé..."
        sudo mv "$N8N_DIR/docker-compose" /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
    else
        curl -L "https://github.com/docker/compose/releases/download/v2.20.3/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        chmod +x /usr/local/bin/docker-compose
    fi
    echo "Docker Compose a été installé."
fi

# Copie du fichier .env.example vers .env s'il n'existe pas déjà
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Fichier .env créé."
fi

# Génération d'une clé de chiffrement aléatoire si nécessaire
if grep -q "votre_clef_de_chiffrement" .env; then
    ENCRYPTION_KEY=$(openssl rand -hex 24)
    sed -i "s/votre_clef_de_chiffrement/$ENCRYPTION_KEY/g" .env
fi

# URL prédéfinie pour n8n
WEBHOOK_URL="https://latry.consulting/projet/n8n"
sed -i "s|https://votre-domaine.com|$WEBHOOK_URL|g" .env 2>/dev/null || echo "URL déjà configurée."

# Démarrage de n8n
echo "Démarrage de n8n..."
docker-compose up -d

echo "n8n est maintenant installé et en cours d'exécution."
echo "Vous pouvez y accéder à l'adresse: $WEBHOOK_URL"
echo "Veuillez configurer votre serveur web avec le script setup-ssl.sh pour finaliser l'installation." 