#!/bin/bash

# Script d'initialisation automatique après déploiement
echo "=== INITIALISATION DE N8N SUR OVH ==="
echo "Début de l'initialisation post-déploiement..."

# Rendre tous les scripts exécutables
echo "Mise à jour des permissions des scripts..."
chmod 755 *.sh || { echo "ERREUR: Impossible de modifier les permissions des scripts."; exit 1; }

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "ERREUR: Docker n'est pas installé sur ce serveur."
    echo "Pour installer Docker, exécutez:"
    echo "curl -fsSL https://get.docker.com -o get-docker.sh && sh get-docker.sh"
    exit 1
fi

# Vérifier que Docker Compose est installé
if ! command -v docker-compose &> /dev/null; then
    echo "ERREUR: Docker Compose n'est pas installé sur ce serveur."
    echo "Pour installer Docker Compose, exécutez:"
    echo "curl -L \"https://github.com/docker/compose/releases/download/v2.20.3/docker-compose-\$(uname -s)-\$(uname -m)\" -o /usr/local/bin/docker-compose && chmod +x /usr/local/bin/docker-compose"
    exit 1
fi

# Exécuter le script d'installation
echo "Exécution du script d'installation..."
./install.sh || { echo "ERREUR: L'installation a échoué."; exit 1; }

# Configurer Nginx si autorisé
if [ "$(id -u)" -eq 0 ]; then
    echo "Configuration de Nginx..."
    ./setup-ssl.sh || { echo "ERREUR: La configuration de Nginx a échoué."; }
else
    echo "ATTENTION: Vous n'êtes pas root. Pour configurer Nginx, exécutez:"
    echo "sudo ./setup-ssl.sh"
fi

echo ""
echo "=== INSTALLATION TERMINÉE ==="
echo "n8n devrait être accessible à l'adresse: https://latry.consulting/projet/n8n"
echo ""
echo "Pour vérifier le statut de n8n, exécutez:"
echo "docker ps | grep n8n"
echo ""
echo "Pour voir les logs de n8n, exécutez:"
echo "docker logs -f n8n" 