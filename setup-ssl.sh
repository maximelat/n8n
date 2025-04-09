#!/bin/bash

# Script d'installation des certificats SSL avec Certbot

# Puisque latry.consulting a déjà HTTPS, nous allons simplement configurer Nginx

# Nom de domaine fixe
DOMAIN_NAME="latry.consulting"

# Vérification de Nginx
if ! command -v nginx &> /dev/null
then
    echo "Nginx n'est pas installé. Installation en cours..."
    apt-get update
    apt-get install -y nginx
fi

# Mise à jour du fichier de configuration Nginx
sed -i "s/votre-domaine.com/$DOMAIN_NAME/g" nginx-config.conf

# Copie du fichier de configuration Nginx
cp nginx-config.conf /etc/nginx/sites-available/$DOMAIN_NAME
ln -sf /etc/nginx/sites-available/$DOMAIN_NAME /etc/nginx/sites-enabled/

# Vérification de la syntaxe Nginx
nginx -t

# Redémarrage de Nginx
systemctl restart nginx

echo "Configuration Nginx terminée. Votre n8n est maintenant accessible via https://$DOMAIN_NAME/projet/n8n" 