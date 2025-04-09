<?php
// Script d'installation web pour n8n
session_start();

// Protection par mot de passe - à modifier
$password = "n8n-install-password";

// Vérifier le mot de passe
$authorized = false;
if (isset($_POST['password']) && $_POST['password'] === $password) {
    $authorized = true;
    $_SESSION['authorized'] = true;
} elseif (isset($_SESSION['authorized']) && $_SESSION['authorized'] === true) {
    $authorized = true;
}

// Fonction pour exécuter une commande shell
function execCommand($command, $workingDir = null) {
    $output = [];
    $returnVar = 0;
    
    $currentDir = getcwd();
    if ($workingDir) {
        chdir($workingDir);
    }
    
    exec($command . " 2>&1", $output, $returnVar);
    
    if ($workingDir) {
        chdir($currentDir);
    }
    
    return [
        'output' => $output,
        'returnVar' => $returnVar
    ];
}

// Fonction pour détecter le répertoire n8n
function detectN8nDirectory() {
    $possiblePaths = [
        "/home/latrycf/www/projet/n8n/",  // Chemin absolu
        "/www/projet/n8n/",              // Chemin FTP
        getcwd() . "/"                   // Répertoire courant
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_dir($path)) {
            return $path;
        }
    }
    
    // Si aucun chemin n'est trouvé, utilisez le répertoire courant
    return getcwd() . "/";
}

// Détection du chemin n8n
$n8nDir = detectN8nDirectory();

// Actions d'installation
$installationLog = [];
$error = false;

if ($authorized && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'check_requirements':
            // Vérifier les prérequis
            $installationLog[] = "Vérification des prérequis...";
            
            // Vérifier Docker
            $dockerResult = execCommand("which docker");
            if ($dockerResult['returnVar'] !== 0) {
                $installationLog[] = "❌ Docker n'est pas installé.";
                $error = true;
            } else {
                $installationLog[] = "✅ Docker est installé.";
            }
            
            // Vérifier Docker Compose
            $dockerComposeResult = execCommand("which docker-compose");
            if ($dockerComposeResult['returnVar'] !== 0) {
                $installationLog[] = "❌ Docker Compose n'est pas installé.";
                $error = true;
            } else {
                $installationLog[] = "✅ Docker Compose est installé.";
            }
            
            // Vérifier les permissions
            $permissionsResult = execCommand("chmod 755 {$n8nDir}*.sh");
            if ($permissionsResult['returnVar'] !== 0) {
                $installationLog[] = "❌ Impossible de changer les permissions des scripts.";
                $error = true;
            } else {
                $installationLog[] = "✅ Permissions des scripts modifiées avec succès.";
            }
            
            break;
            
        case 'start_installation':
            // Démarrer l'installation
            $installationLog[] = "Démarrage de l'installation...";
            $installationLog[] = "Utilisation du répertoire: {$n8nDir}";
            
            // Exécuter le script d'installation
            $installResult = execCommand("{$n8nDir}install.sh");
            $installationLog[] = "Résultat de l'installation:";
            foreach ($installResult['output'] as $line) {
                $installationLog[] = $line;
            }
            
            if ($installResult['returnVar'] !== 0) {
                $installationLog[] = "❌ L'installation a échoué.";
                $error = true;
            } else {
                $installationLog[] = "✅ Installation réussie.";
            }
            
            break;
            
        case 'setup_nginx':
            // Configurer Nginx
            $installationLog[] = "Configuration de Nginx...";
            $installationLog[] = "Utilisation du répertoire: {$n8nDir}";
            
            // Exécuter le script de configuration Nginx
            $nginxResult = execCommand("sudo {$n8nDir}setup-ssl.sh");
            $installationLog[] = "Résultat de la configuration Nginx:";
            foreach ($nginxResult['output'] as $line) {
                $installationLog[] = $line;
            }
            
            if ($nginxResult['returnVar'] !== 0) {
                $installationLog[] = "❌ La configuration de Nginx a échoué.";
                $error = true;
            } else {
                $installationLog[] = "✅ Configuration de Nginx réussie.";
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
    <title>Installation de n8n</title>
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
        <h1>Installation de n8n sur OVH</h1>
        
        <?php if (!$authorized): ?>
        <div class="step">
            <h2>Authentification</h2>
            <form method="post" action="web-installer.php">
                <p>Veuillez entrer le mot de passe d'installation:</p>
                <input type="password" name="password" required>
                <button type="submit" class="button">Connexion</button>
            </form>
        </div>
        <?php else: ?>
        
        <div class="step">
            <h2>Étape 1: Vérification des prérequis</h2>
            <p>Vérifiez que votre serveur dispose des prérequis nécessaires pour installer n8n.</p>
            <form method="post" action="web-installer.php">
                <input type="hidden" name="action" value="check_requirements">
                <button type="submit" class="button">Vérifier les prérequis</button>
            </form>
        </div>
        
        <div class="step">
            <h2>Étape 2: Installation de n8n</h2>
            <p>Installez n8n avec Docker et Docker Compose.</p>
            <form method="post" action="web-installer.php">
                <input type="hidden" name="action" value="start_installation">
                <button type="submit" class="button">Installer n8n</button>
            </form>
        </div>
        
        <div class="step">
            <h2>Étape 3: Configuration de Nginx</h2>
            <p>Configurez Nginx pour accéder à n8n via votre domaine.</p>
            <form method="post" action="web-installer.php">
                <input type="hidden" name="action" value="setup_nginx">
                <button type="submit" class="button">Configurer Nginx</button>
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
            <h2>Accès à n8n</h2>
            <p>Une fois l'installation terminée, vous pourrez accéder à n8n à l'adresse suivante:</p>
            <p><a href="https://latry.consulting/projet/n8n" target="_blank">https://latry.consulting/projet/n8n</a></p>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html> 