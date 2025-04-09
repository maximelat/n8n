# Installation de n8n sur OVH

Ce dépôt contient des scripts pour installer et exécuter n8n sur un hébergement OVH.

## Prérequis

- Un hébergement web OVH avec PHP 7.3+ et Node.js 16.9.0+
- La fonction `exec()` activée en PHP
- Accès FTP/SFTP à votre espace d'hébergement

## Installation

### 1. Vérification des prérequis

Accédez à `check-node.php` dans votre navigateur pour vérifier que toutes les dépendances sont installées. Ce script vous indiquera si votre environnement est prêt pour n8n.

```
https://votre-domaine.com/projet/n8n/check-node.php
```

Si Node.js ou NPM ne sont pas installés ou sont trop anciens, contactez le support OVH pour les faire mettre à jour.

### 2. Installation automatique

Accédez à `install-n8n.php` dans votre navigateur pour lancer l'installation automatique :

```
https://votre-domaine.com/projet/n8n/install-n8n.php
```

Ce script va :
- Vérifier vos prérequis
- Créer les fichiers de configuration nécessaires
- Installer n8n via NPM
- Générer des scripts de démarrage pour n8n

L'installation peut prendre plusieurs minutes. Une fois terminée, le script vous fournira des instructions pour configurer une tâche cron.

### 3. Configuration du Cron

Pour démarrer n8n automatiquement et le maintenir en fonctionnement :

1. Accédez à votre espace client OVH
2. Allez dans la section 'Hébergement' > 'Tâches planifiées'
3. Ajoutez une nouvelle tâche cron avec la commande :
   ```
   php /home/votre_utilisateur/www/projet/n8n/start-n8n.php
   ```
4. Configurez-la pour s'exécuter toutes les heures

## Utilisation

Accédez à n8n via :

```
https://votre-domaine.com/projet/n8n
```

## Fichiers inclus

- `check-node.php` : Vérifie la disponibilité des dépendances
- `install-n8n.php` : Script d'installation automatique
- `start-n8n.php` : Script de démarrage pour n8n (généré automatiquement)
- `.env` : Configuration de n8n (généré automatiquement)
- `.htaccess` : Configuration du serveur web pour la redirection vers n8n
- `package.json` : Définition des dépendances NPM

## Problèmes connus

- La gestion des processus en arrière-plan peut être limitée sur OVH
- Certains hébergements mutualisés OVH peuvent avoir des restrictions sur l'utilisation des ressources

Si n8n ne démarre pas correctement, vérifiez les fichiers de log :
- `install-n8n.log`
- `n8n-start.log`
- `n8n-output.log`

## Support

Pour toute question ou problème, contactez :
- Support OVH pour les questions liées à l'hébergement
- [Documentation officielle de n8n](https://docs.n8n.io/) pour les questions sur n8n 