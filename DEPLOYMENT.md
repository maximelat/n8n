# Guide de déploiement automatique de n8n sur OVH

Ce guide explique comment configurer le déploiement automatique de n8n sur votre serveur OVH à partir de GitHub.

## Prérequis

- Un compte GitHub
- Un serveur OVH avec accès SSH et FTP
- Docker et Docker Compose installés sur votre serveur OVH

## Étape 1 : Configurer les secrets GitHub

Dans votre dépôt GitHub, allez dans **Settings > Secrets and variables > Actions** et ajoutez les secrets suivants :

1. `FTP_USERNAME` : Votre nom d'utilisateur FTP OVH
2. `FTP_PASSWORD` : Votre mot de passe FTP OVH 

## Étape 2 : Pousser le code sur GitHub

1. Clonez ce dépôt localement
2. Poussez-le vers votre dépôt GitHub
3. Le workflow GitHub Actions démarrera automatiquement

## Étape 3 : Installation manuelle après déploiement FTP

Après que GitHub Actions ait déployé les fichiers via FTP, vous devez vous connecter à votre serveur pour finaliser l'installation :

1. Connectez-vous à votre serveur OVH via SSH :
   ```bash
   ssh your-username@latry.consulting
   ```

2. Naviguez vers le répertoire de déploiement :
   ```bash
   cd /www/projet/n8n/
   ```

3. Rendez les scripts exécutables et lancez l'installation :
   ```bash
   chmod 755 *.sh
   ./init-after-deploy.sh
   ```

4. Si nécessaire, configurez Nginx :
   ```bash
   sudo ./setup-ssl.sh
   ```

Le workflow GitHub Actions effectuera les opérations suivantes :

1. Déployer tous les fichiers vers `/www/projet/n8n/` via FTP
2. Vous devrez ensuite exécuter manuellement les scripts d'installation

Une fois ces étapes terminées, n8n devrait être accessible à l'adresse :

```
https://latry.consulting/projet/n8n
```

## Résolution des problèmes

### Erreur SSH

Si vous rencontrez des erreurs avec SSH, vérifiez que :

1. Votre clé SSH privée est correctement configurée comme secret GitHub
2. Votre utilisateur SSH a les permissions nécessaires sur le serveur
3. Le fichier known_hosts est correctement configuré

### Erreur Docker

Si Docker ne démarre pas, connectez-vous au serveur et vérifiez :

```bash
ssh your-username@latry.consulting
cd /www/projet/n8n/
docker-compose ps
docker-compose logs
```

### Erreur Nginx

Si vous ne pouvez pas accéder à n8n via l'URL mais que Docker fonctionne, vérifiez la configuration Nginx :

```bash
sudo nginx -t
sudo systemctl status nginx
```

## Maintenance

Pour mettre à jour n8n, il suffit de pousser les modifications sur GitHub. Le workflow déploiera automatiquement les changements et redémarrera n8n si nécessaire. 