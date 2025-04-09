<?php
// Script d'installation PHP pour n8n (sans dépendre des commandes système)
session_start();

// Protection par mot de passe
$password = "n8n-install-password";

// Vérifier le mot de passe
$authorized = false;
if (isset($_POST['password']) && $_POST['password'] === $password) {
    $authorized = true;
    $_SESSION['authorized'] = true;
} elseif (isset($_SESSION['authorized']) && $_SESSION['authorized'] === true) {
    $authorized = true;
}

// Log pour les actions
$installationLog = [];
$error = false;

// Fonction pour générer une clé de chiffrement aléatoire
function generateRandomKey($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Fonction pour remplacer le contenu dans un fichier
function replaceInFile($filePath, $search, $replace) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }
    
    $newContent = str_replace($search, $replace, $content);
    return file_put_contents($filePath, $newContent) !== false;
}

// Actions d'installation
if ($authorized && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'prepare_files':
            $installationLog[] = "Préparation des fichiers d'installation...";
            
            // Définir le chemin du répertoire n8n
            $n8nDir = "/www/projet/n8n/";
            
            // Vérifier que le répertoire n8n existe
            if (!is_dir($n8nDir)) {
                $installationLog[] = "❌ Le répertoire n8n n'existe pas.";
                $error = true;
                break;
            }
            
            // Générer une clé de chiffrement aléatoire
            $encryptionKey = generateRandomKey();
            $installationLog[] = "✅ Clé de chiffrement générée.";
            
            // Mettre à jour les fichiers avec la clé générée
            $files = [
                $n8nDir . '.env.example',
                $n8nDir . 'Dockerfile',
                $n8nDir . 'docker-compose.yml'
            ];
            
            foreach ($files as $file) {
                if (replaceInFile($file, 'votre_clef_de_chiffrement', $encryptionKey)) {
                    $installationLog[] = "✅ Clé de chiffrement mise à jour dans " . basename($file);
                } else {
                    $installationLog[] = "❌ Impossible de mettre à jour la clé dans " . basename($file);
                    $error = true;
                }
            }
            
            // Créer un fichier .env à partir du template
            if (copy($n8nDir . '.env.example', $n8nDir . '.env')) {
                $installationLog[] = "✅ Fichier .env créé.";
            } else {
                $installationLog[] = "❌ Impossible de créer le fichier .env.";
                $error = true;
            }
            
            // Donner les permissions d'exécution aux scripts shell
            $scripts = glob($n8nDir . "*.sh");
            foreach ($scripts as $script) {
                if (chmod($script, 0755)) {
                    $installationLog[] = "✅ Permissions d'exécution accordées à " . basename($script);
                } else {
                    $installationLog[] = "❌ Impossible de changer les permissions de " . basename($script);
                    $error = true;
                }
            }
            
            break;
            
        case 'download_compose':
            $installationLog[] = "Téléchargement de docker-compose...";
            
            // URL pour télécharger la dernière version de Docker Compose
            $url = "https://github.com/docker/compose/releases/download/v2.20.3/docker-compose-linux-x86_64";
            $destinationPath = "/www/projet/n8n/docker-compose";
            
            // Télécharger Docker Compose
            if (file_put_contents($destinationPath, file_get_contents($url)) !== false) {
                chmod($destinationPath, 0755);
                $installationLog[] = "✅ Docker Compose téléchargé avec succès.";
            } else {
                $installationLog[] = "❌ Impossible de télécharger Docker Compose.";
                $error = true;
            }
            
            break;
            
        case 'create_instructions':
            $installationLog[] = "Création des instructions d'installation manuelle...";
            
            $instructionsFile = "/www/projet/n8n/INSTRUCTIONS.txt";
            $instructions = <<<EOT
INSTRUCTIONS D'INSTALLATION MANUELLE POUR N8N
=============================================

Étant donné que l'installation automatique via PHP n'est pas possible en raison des restrictions sur les fonctions exec() et shell_exec(),
voici les étapes à suivre pour installer manuellement n8n sur votre serveur OVH.

1. Connectez-vous à votre serveur via SSH:
   ```
   ssh votre-utilisateur@latry.consulting
   ```

2. Naviguez vers le répertoire n8n:
   ```
   cd /www/projet/n8n/
   ```

3. Assurez-vous que les scripts sont exécutables:
   ```
   chmod 755 *.sh
   ```

4. Exécutez le script d'installation:
   ```
   ./install.sh
   ```

5. Configurez Nginx:
   ```
   sudo ./setup-ssl.sh
   ```

6. Une fois l'installation terminée, n8n sera accessible à l'adresse:
   https://latry.consulting/projet/n8n

REMARQUES IMPORTANTES:
- Docker et Docker Compose doivent être installés sur votre serveur
- Vous devez avoir les droits sudo pour configurer Nginx
- Les fichiers ont déjà été préparés avec une clé de chiffrement aléatoire

EOT;

            if (file_put_contents($instructionsFile, $instructions)) {
                $installationLog[] = "✅ Instructions d'installation créées dans INSTRUCTIONS.txt";
                $installationLog[] = "⚠️ L'exécution automatique n'est pas disponible. Veuillez suivre les instructions manuelles.";
            } else {
                $installationLog[] = "❌ Impossible de créer le fichier d'instructions.";
                $error = true;
            }
            
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation PHP de n8n</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .step {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .step h2 {
            margin-top: 0;
            font-size: 18px;
        }
        .log {
            background-color: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-top: 10px;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="password"] {
            padding: 8px;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Installation PHP de n8n sur OVH</h1>
        
        <?php if (!$authorized): ?>
        <div class="step">
            <h2>Authentification</h2>
            <form method="post" action="php-installer.php">
                <p>Veuillez entrer le mot de passe d'installation:</p>
                <input type="password" name="password" required>
                <button type="submit" class="button">Connexion</button>
            </form>
        </div>
        <?php else: ?>
        
        <div class="step">
            <h2>Étape 1: Préparation des fichiers</h2>
            <p>Cette étape prépare les fichiers de configuration avec une clé de chiffrement aléatoire et donne les permissions d'exécution aux scripts.</p>
            <form method="post" action="php-installer.php">
                <input type="hidden" name="action" value="prepare_files">
                <button type="submit" class="button">Préparer les fichiers</button>
            </form>
        </div>
        
        <div class="step">
            <h2>Étape 2: Téléchargement de Docker Compose (Optionnel)</h2>
            <p>Si Docker Compose n'est pas installé sur votre serveur, vous pouvez le télécharger ici.</p>
            <form method="post" action="php-installer.php">
                <input type="hidden" name="action" value="download_compose">
                <button type="submit" class="button">Télécharger Docker Compose</button>
            </form>
        </div>
        
        <div class="step">
            <h2>Étape 3: Créer les instructions d'installation manuelle</h2>
            <p>Cette étape crée un fichier contenant les instructions pour installer manuellement n8n via SSH.</p>
            <form method="post" action="php-installer.php">
                <input type="hidden" name="action" value="create_instructions">
                <button type="submit" class="button">Créer les instructions</button>
            </form>
        </div>
        
        <?php if (!empty($installationLog)): ?>
        <div class="step">
            <h2>Résultat de l'opération</h2>
            <div class="log">
                <?php foreach ($installationLog as $line): ?>
                    <?php echo htmlspecialchars($line) . "\n"; ?>
                <?php endforeach; ?>
            </div>
            
            <?php if ($error): ?>
                <p class="error">L'opération a échoué. Veuillez vérifier les erreurs ci-dessus.</p>
            <?php else: ?>
                <p class="success">L'opération a réussi!</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="step">
            <h2>Note importante</h2>
            <p class="warning">⚠️ En raison des restrictions sur l'hébergement OVH, l'installation complète automatique n'est pas possible via PHP.</p>
            <p>Vous devrez vous connecter à votre serveur via SSH et suivre les instructions générées pour compléter l'installation.</p>
            <p>Une fois l'installation terminée, n8n sera accessible à l'adresse suivante:</p>
            <p><a href="https://latry.consulting/projet/n8n" target="_blank">https://latry.consulting/projet/n8n</a></p>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html> 