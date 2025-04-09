#!/bin/bash

# Script d'installation des certificats SSL avec Certbot

# Vérification de Certbot
if ! command -v certbot &> /dev/null
then
    echo "Certbot n'est pas installé. Installation en cours..."
    apt-get update
    apt-get install -y certbot python3-certbot-nginx
fi

# Demande du nom de domaine
read -p "Entrez votre nom de domaine (exemple: votre-domaine.com): " DOMAIN_NAME

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

# Obtention du certificat Let's Encrypt
certbot --nginx -d $DOMAIN_NAME

echo "Configuration SSL terminée. Votre n8n est maintenant accessible via https://$DOMAIN_NAME" 