# Déploiement de n8n sur OVH

Ce dépôt contient les fichiers nécessaires pour déployer n8n sur un serveur OVH via GitHub Actions.

## Déploiement rapide (automatique)

Pour déployer automatiquement n8n sur votre serveur OVH, suivez les instructions dans [DEPLOYMENT.md](DEPLOYMENT.md).

Ce déploiement automatique effectuera toutes les étapes nécessaires via GitHub Actions :
1. Transfert des fichiers via FTP
2. Configuration des clés de chiffrement 
3. Préparation des fichiers d'installation

## Installation via l'interface web

Une fois les fichiers déployés, accédez à l'URL suivante pour terminer l'installation via le navigateur :

```
https://latry.consulting/projet/n8n/web-installer.php
```

Le mot de passe par défaut est : `n8n-install-password`

Cette interface vous permettra de :
1. Vérifier les prérequis du serveur
2. Installer n8n avec Docker
3. Configurer Nginx

## Configuration

Avant de déployer, vous devez configurer les variables d'environnement suivantes dans le Dockerfile et docker-compose.yml :

- `N8N_ENCRYPTION_KEY` : Une clé de chiffrement unique pour sécuriser vos données (générée automatiquement lors de l'installation)
- `WEBHOOK_URL` : L'URL de votre instance n8n (configurée par défaut à https://latry.consulting/projet/n8n)

## Secrets GitHub nécessaires

Pour que le déploiement automatique fonctionne, vous devez configurer les secrets suivants dans votre dépôt GitHub :

- `FTP_USERNAME` : Votre nom d'utilisateur FTP OVH
- `FTP_PASSWORD` : Votre mot de passe FTP OVH

## Installation manuelle sur le serveur

Après le déploiement FTP via GitHub Actions, connectez-vous à votre serveur OVH via SSH et exécutez les commandes suivantes :

```bash
cd /www/projet/n8n
chmod 755 *.sh
./init-after-deploy.sh
```

Pour des instructions détaillées, consultez [INSTALLATION_OVH.md](INSTALLATION_OVH.md).

## Sauvegarde

Les données de n8n sont stockées dans le volume Docker `n8n_data`. Pour sauvegarder vos données, vous pouvez utiliser le script de sauvegarde fourni :

```bash
./backup.sh
```

## Restauration

Pour restaurer une sauvegarde, utilisez la commande suivante :

```bash
./restore.sh /chemin/vers/n8n_backup.tar.gz /chemin/vers/n8n_data.tar.gz
```

## Accès à n8n

Une fois déployé, n8n sera accessible à l'adresse :

```
https://latry.consulting/projet/n8n
``` 