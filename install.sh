#!/bin/bash

# Script d'installation de n8n sur serveur OVH

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
    curl -L "https://github.com/docker/compose/releases/download/v2.20.3/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    echo "Docker Compose a été installé."
fi

# Copie du fichier .env.example vers .env s'il n'existe pas déjà
if [ ! -f .env ]; then
    cp .env.example .env
    echo "Fichier .env créé. Veuillez le modifier avec vos valeurs."
    echo "Exécutez nano .env pour éditer le fichier."
fi

# Génération d'une clé de chiffrement aléatoire
ENCRYPTION_KEY=$(openssl rand -hex 24)
sed -i "s/votre_clef_de_chiffrement/$ENCRYPTION_KEY/g" .env

# Demande pour l'URL du webhook
read -p "Entrez l'URL de votre domaine (exemple: https://votre-domaine.com): " WEBHOOK_URL
sed -i "s|https://votre-domaine.com|$WEBHOOK_URL|g" .env

# Démarrage de n8n
echo "Démarrage de n8n..."
docker-compose up -d

echo "n8n est maintenant installé et en cours d'exécution."
echo "Vous pouvez y accéder à l'adresse: $WEBHOOK_URL:5678"
echo "N'oubliez pas de configurer votre serveur web pour rediriger les requêtes vers le port 5678." 