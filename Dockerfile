FROM n8nio/n8n:latest

# Définir les variables d'environnement
ENV N8N_PORT=5678
ENV NODE_ENV=production
ENV N8N_ENCRYPTION_KEY=votre_clef_de_chiffrement
ENV WEBHOOK_URL=https://votre-domaine.com
ENV DB_TYPE=sqlite
ENV DB_SQLITE_PATH=/home/node/.n8n/database.sqlite

# Exposer le port sur lequel n8n fonctionne
EXPOSE 5678

# Copier éventuels fichiers de configuration
COPY ./config/custom-extensions.json /home/node/.n8n/

# Démarrer n8n
CMD ["n8n", "start"] 