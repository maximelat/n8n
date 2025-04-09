# Guide d'installation de n8n sur OVH

Ce guide vous explique comment déployer n8n sur un serveur OVH en utilisant Docker, GitHub Actions et Nginx.

## Prérequis

- Un serveur OVH (VPS, dédié ou mutualisé) avec accès SSH
- Un nom de domaine configuré pour pointer vers votre serveur
- Accès FTP à votre serveur OVH
- Un compte GitHub pour héberger votre code

## Étape 1 : Configurer votre dépôt GitHub

1. Créez un nouveau dépôt GitHub à partir de ce code source
2. Configurez les secrets GitHub suivants :
   - `FTP_USERNAME` : Votre nom d'utilisateur FTP OVH
   - `FTP_PASSWORD` : Votre mot de passe FTP OVH

## Étape 2 : Déployer le code sur OVH via GitHub Actions

Lorsque vous poussez du code sur la branche `main`, GitHub Actions déploiera automatiquement les fichiers sur votre serveur OVH via FTP, dans le répertoire configuré dans le workflow (par défaut, `/www/projet/n8n/`).

## Étape 3 : Configuration initiale sur le serveur OVH

1. Connectez-vous à votre serveur OVH via SSH :
   ```bash
   ssh user@votre-serveur-ovh
   ```

2. Naviguez vers le répertoire de déploiement :
   ```bash
   cd /www/projet/n8n/
   ```

3. Rendez les scripts exécutables :
   ```bash
   chmod +x install.sh setup-ssl.sh backup.sh restore.sh
   ```

4. Exécutez le script d'installation :
   ```bash
   ./install.sh
   ```
   Ce script vérifiera l'installation de Docker et Docker Compose, créera un fichier `.env` avec une clé de chiffrement aléatoire et vous demandera l'URL de votre domaine pour configurer n8n.

## Étape 4 : Configuration SSL avec Let's Encrypt

1. Exécutez le script de configuration SSL :
   ```bash
   sudo ./setup-ssl.sh
   ```
   Ce script installera Nginx et Certbot si nécessaire, puis configurera votre nom de domaine et obtiendra un certificat SSL Let's Encrypt.

## Étape 5 : Vérification

1. Accédez à votre instance n8n via votre navigateur :
   ```
   https://votre-domaine.com
   ```

2. Vous devriez voir l'interface n8n et pouvoir créer un compte administrateur.

## Maintenance

### Sauvegardes régulières

Pour sauvegarder régulièrement votre instance n8n, vous pouvez ajouter le script de sauvegarde au cron :

```bash
# Éditer la crontab
crontab -e

# Ajouter cette ligne pour une sauvegarde quotidienne à 2h du matin
0 2 * * * /www/projet/n8n/backup.sh
```

### Restauration

Pour restaurer à partir d'une sauvegarde :

```bash
./restore.sh /chemin/vers/n8n_backup_DATE.tar.gz /chemin/vers/n8n_data_DATE.tar.gz
```

### Mise à jour de n8n

Pour mettre à jour n8n vers la dernière version :

1. Modifiez le `docker-compose.yml` pour spécifier la dernière version de n8n
2. Poussez les modifications sur GitHub
3. GitHub Actions déploiera automatiquement les fichiers mis à jour sur votre serveur OVH
4. Connectez-vous à votre serveur et redémarrez n8n :
   ```bash
   cd /www/projet/n8n/
   docker-compose down
   docker-compose up -d
   ```

## Dépannage

### Logs Docker

Pour consulter les logs de n8n :

```bash
docker logs -f n8n
```

### Problèmes de connexion à n8n

Si vous ne pouvez pas accéder à n8n après l'installation :

1. Vérifiez que le conteneur Docker est en cours d'exécution :
   ```bash
   docker ps | grep n8n
   ```

2. Vérifiez les logs Nginx :
   ```bash
   tail -f /var/log/nginx/error.log
   ```

3. Vérifiez que le port 5678 est ouvert dans le pare-feu :
   ```bash
   sudo iptables -L | grep 5678
   ``` 