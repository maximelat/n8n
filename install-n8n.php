<?php
/**
 * Script d'installation de n8n pour OVH
 * Ce script peut être appelé via une tâche cron pour installer n8n sans SSH
 */

// Chemin vers le répertoire n8n
$n8nDir = realpath(__DIR__);
$logFile = $n8nDir . '/install-n8n.log';

// Fonction pour logger les informations
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
    echo "$message<br>";
}

// Fonction pour exécuter une commande
function execCommand($command) {
    global $logFile;
    logMessage("Exécution de la commande : $command");
    
    $output = [];
    $returnCode = 0;
    exec($command . " 2>&1", $output, $returnCode);
    
    $outputStr = implode("\n", $output);
    logMessage("Code retour : $returnCode");
    logMessage("Sortie : $outputStr");
    
    return [
        'code' => $returnCode,
        'output' => $outputStr
    ];
}

// Vérifier si exec est disponible
if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
    logMessage("ERREUR : La fonction exec() n'est pas disponible. Impossible d'installer n8n.");
    exit(1);
}

// Vérifier si Node.js est installé
$nodeCheck = execCommand("which node");
if ($nodeCheck['code'] !== 0) {
    logMessage("ERREUR : Node.js n'est pas installé ou n'est pas dans le PATH.");
    exit(1);
}

// Vérifier la version de Node.js
$nodeVersion = execCommand("node -v");
if ($nodeVersion['code'] === 0) {
    preg_match('/v(\d+)\.(\d+)\.(\d+)/', $nodeVersion['output'], $matches);
    if (isset($matches[1]) && intval($matches[1]) < 16) {
        logMessage("ERREUR : Version de Node.js incompatible. n8n nécessite Node.js v16.9.0 ou supérieure.");
        exit(1);
    }
} else {
    logMessage("AVERTISSEMENT : Impossible de déterminer la version de Node.js.");
}

// Vérifier si NPM est installé
$npmCheck = execCommand("which npm");
if ($npmCheck['code'] !== 0) {
    logMessage("ERREUR : NPM n'est pas installé ou n'est pas dans le PATH.");
    exit(1);
}

// Création du fichier package.json s'il n'existe pas
$packageJsonPath = $n8nDir . '/package.json';
if (!file_exists($packageJsonPath)) {
    logMessage("Création du fichier package.json");
    $packageJson = [
        "name" => "n8n-instance",
        "version" => "1.0.0",
        "description" => "Instance n8n pour latry.consulting",
        "main" => "index.js",
        "scripts" => [
            "start" => "n8n start",
            "install-n8n" => "npm install n8n",
            "update" => "npm update n8n"
        ],
        "dependencies" => [
            "n8n" => "^1.2.0"
        ],
        "engines" => [
            "node" => ">=16.9.0"
        ],
        "private" => true
    ];
    
    file_put_contents($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Création du fichier .env s'il n'existe pas
$envPath = $n8nDir . '/.env';
if (!file_exists($envPath)) {
    logMessage("Création du fichier .env");
    $envContent = <<<EOT
# Environnement n8n
N8N_PORT=5678
N8N_PROTOCOL=https
N8N_HOST=latry.consulting
N8N_PATH=/projet/n8n
N8N_LOG_LEVEL=info

# Base de données
DB_TYPE=sqlite
DB_PATH=/home/latrycf/www/projet/n8n/database.sqlite

# Sécurité
ENCRYPTION_KEY=
EOT;

    // Générer une clé d'encryption
    $encryptionKey = bin2hex(random_bytes(24));
    $envContent = str_replace('ENCRYPTION_KEY=', 'ENCRYPTION_KEY=' . $encryptionKey, $envContent);
    
    file_put_contents($envPath, $envContent);
}

// Installation de n8n via npm
logMessage("Installation de n8n en cours. Cela peut prendre plusieurs minutes...");
$result = execCommand("cd $n8nDir && npm install n8n --no-fund --no-audit");

if ($result['code'] === 0) {
    logMessage("n8n a été installé avec succès !");
    
    // Vérifier l'installation
    $n8nModulePath = "$n8nDir/node_modules/n8n";
    if (is_dir($n8nModulePath)) {
        logMessage("Modules n8n trouvés dans $n8nModulePath");
        
        // Essayer de trouver la version de n8n
        $packageJsonPath = "$n8nModulePath/package.json";
        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                logMessage("Version de n8n installée : " . $packageJson['version']);
            }
        }
    } else {
        logMessage("AVERTISSEMENT : Les modules n8n n'ont pas été trouvés dans le répertoire attendu.");
    }
    
    // Créer le fichier start-n8n.php s'il n'existe pas
    $startScriptPath = "$n8nDir/start-n8n.php";
    if (!file_exists($startScriptPath)) {
        logMessage("Création du script de démarrage start-n8n.php");
        
        $startScriptContent = <<<EOT
<?php
// Script qui peut être exécuté par le cron d'OVH pour démarrer n8n
\$logFile = __DIR__ . '/n8n-start.log';

file_put_contents(\$logFile, date('Y-m-d H:i:s') . " - Tentative de démarrage de n8n\\n", FILE_APPEND);

// Utiliser le chemin complet pour node et npx
\$nodePath = '/usr/bin/node';
\$npxPath = '/usr/bin/npx';

if (!file_exists(\$nodePath)) {
    file_put_contents(\$logFile, "Node.js non trouvé à l'emplacement: \$nodePath\\n", FILE_APPEND);
    // Essayer de trouver node
    exec('which node', \$nodePathOutput);
    if (!empty(\$nodePathOutput)) {
        \$nodePath = trim(\$nodePathOutput[0]);
        file_put_contents(\$logFile, "Node.js trouvé à: \$nodePath\\n", FILE_APPEND);
    } else {
        file_put_contents(\$logFile, "Node.js introuvable\\n", FILE_APPEND);
        exit(1);
    }
}

// Chemin vers le répertoire n8n
\$n8nDir = __DIR__;

// Vérifier si npx existe
exec('which npx', \$npxPathOutput);
if (!empty(\$npxPathOutput)) {
    \$npxPath = trim(\$npxPathOutput[0]);
    file_put_contents(\$logFile, "npx trouvé à: \$npxPath\\n", FILE_APPEND);
} else {
    file_put_contents(\$logFile, "npx introuvable, utilisation du chemin node_modules\\n", FILE_APPEND);
    // Essayer avec le chemin direct
    \$command = "cd \$n8nDir && \$nodePath ./node_modules/.bin/n8n start > \$n8nDir/n8n-output.log 2>&1 &";
}

// Commande de démarrage
if (!isset(\$command)) {
    \$command = "cd \$n8nDir && \$npxPath n8n start > \$n8nDir/n8n-output.log 2>&1 &";
}

// Vérifier si n8n est déjà en cours d'exécution
exec("ps aux | grep 'n8n start' | grep -v grep", \$psOutput);
if (!empty(\$psOutput)) {
    file_put_contents(\$logFile, "n8n semble déjà en cours d'exécution :\\n" . implode("\\n", \$psOutput) . "\\n", FILE_APPEND);
    echo "n8n est déjà en cours d'exécution.";
    exit(0);
}

// Exécuter la commande
\$output = [];
\$returnCode = 0;
exec(\$command, \$output, \$returnCode);

// Enregistrer le résultat
file_put_contents(\$logFile, "Commande exécutée: \$command\\n", FILE_APPEND);
file_put_contents(\$logFile, "Code de retour: \$returnCode\\n", FILE_APPEND);
file_put_contents(\$logFile, "Sortie: " . implode("\\n", \$output) . "\\n\\n", FILE_APPEND);

echo "Tentative de démarrage de n8n. Consultez les logs pour plus de détails.";
EOT;
        
        file_put_contents($startScriptPath, $startScriptContent);
    }
    
    // Instructions pour configurer le cron
    logMessage("INSTRUCTIONS POUR CONFIGURER LE CRON:");
    logMessage("1. Accédez à votre espace client OVH");
    logMessage("2. Allez dans la section 'Hébergement' > 'Tâches planifiées'");
    logMessage("3. Ajoutez une nouvelle tâche cron avec la commande:");
    logMessage("   php " . $n8nDir . "/start-n8n.php");
    logMessage("4. Configurez-la pour s'exécuter toutes les heures (ou selon vos besoins)");
    logMessage("");
    logMessage("Accédez à n8n via : https://latry.consulting/projet/n8n");
} else {
    logMessage("ERREUR: L'installation de n8n a échoué. Vérifiez les détails de l'erreur ci-dessus.");
}
?> 