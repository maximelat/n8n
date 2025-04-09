# Déploiement de n8n sur OVH

Ce dépôt contient les fichiers nécessaires pour déployer n8n sur un serveur OVH via GitHub Actions.

## Configuration

Avant de déployer, vous devez configurer les variables d'environnement suivantes dans le Dockerfile et docker-compose.yml :

- `N8N_ENCRYPTION_KEY` : Une clé de chiffrement unique pour sécuriser vos données
- `WEBHOOK_URL` : L'URL de votre instance n8n (par exemple, https://votre-domaine.com)

## Secrets GitHub

Pour que le déploiement FTP fonctionne, vous devez configurer les secrets suivants dans votre dépôt GitHub :

- `FTP_USERNAME` : Votre nom d'utilisateur FTP OVH
- `FTP_PASSWORD` : Votre mot de passe FTP OVH

## Installation sur le serveur

Après le déploiement FTP via GitHub Actions, connectez-vous à votre serveur OVH via SSH et exécutez les commandes suivantes :

```bash
cd /chemin/vers/votre/repertoire/n8n
docker-compose up -d
```

## Mise à jour

Pour mettre à jour n8n, il vous suffit de pousser les modifications sur la branche main de votre dépôt GitHub. Le workflow GitHub Actions déploiera automatiquement les fichiers sur votre serveur OVH via FTP.

## Sauvegarde

Les données de n8n sont stockées dans le volume Docker `n8n_data`. Pour sauvegarder vos données, vous pouvez utiliser la commande suivante :

```bash
docker run --rm -v n8n_data:/source -v /chemin/vers/sauvegarde:/backup ubuntu tar cvf /backup/n8n_backup.tar /source
```

## Restauration

Pour restaurer une sauvegarde, utilisez la commande suivante :

```bash
docker run --rm -v n8n_data:/destination -v /chemin/vers/sauvegarde:/backup ubuntu tar xvf /backup/n8n_backup.tar -C /
``` 