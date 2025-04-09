<?php
echo "<h1>Vérification de l'environnement pour n8n</h1>";

// Fonction pour vérifier si une commande existe
function commandExists($command) {
    $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $returnVal = shell_exec("$whereIsCommand $command");
    return !empty($returnVal);
}

// Tableau pour stocker les informations
$info = [
    'exec_enabled' => function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))),
    'node_installed' => false,
    'node_version' => '',
    'node_path' => '',
    'npm_installed' => false,
    'npm_version' => '',
    'npm_path' => '',
    'n8n_installed' => false,
    'n8n_path' => ''
];

// Vérifier si exec est disponible
if ($info['exec_enabled']) {
    echo "<p style='color:green'>✓ Fonction exec() : Disponible</p>";
    
    // Vérifier Node.js
    $nodeCheck = shell_exec('which node 2>&1');
    if (!empty($nodeCheck)) {
        $info['node_installed'] = true;
        $info['node_path'] = trim($nodeCheck);
        
        // Vérifier la version de Node.js
        $nodeVersion = shell_exec('node -v 2>&1');
        if (!empty($nodeVersion)) {
            $info['node_version'] = trim($nodeVersion);
            echo "<p style='color:green'>✓ Node.js: Version " . htmlspecialchars($info['node_version']) . " trouvée à " . htmlspecialchars($info['node_path']) . "</p>";
        } else {
            echo "<p style='color:orange'>⚠ Node.js trouvé mais impossible de déterminer la version</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Node.js: Non trouvé</p>";
    }
    
    // Vérifier NPM
    $npmCheck = shell_exec('which npm 2>&1');
    if (!empty($npmCheck)) {
        $info['npm_installed'] = true;
        $info['npm_path'] = trim($npmCheck);
        
        // Vérifier la version de NPM
        $npmVersion = shell_exec('npm -v 2>&1');
        if (!empty($npmVersion)) {
            $info['npm_version'] = trim($npmVersion);
            echo "<p style='color:green'>✓ NPM: Version " . htmlspecialchars($info['npm_version']) . " trouvée à " . htmlspecialchars($info['npm_path']) . "</p>";
        } else {
            echo "<p style='color:orange'>⚠ NPM trouvé mais impossible de déterminer la version</p>";
        }
    } else {
        echo "<p style='color:red'>✗ NPM: Non trouvé</p>";
    }
    
    // Vérifier n8n
    $n8nDir = realpath(__DIR__);
    $n8nModulePath = "$n8nDir/node_modules/n8n";
    if (is_dir($n8nModulePath)) {
        $info['n8n_installed'] = true;
        $info['n8n_path'] = $n8nModulePath;
        echo "<p style='color:green'>✓ n8n: Installé dans " . htmlspecialchars($info['n8n_path']) . "</p>";
        
        // Essayer de trouver la version de n8n
        $packageJsonPath = "$n8nModulePath/package.json";
        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageJson['version'])) {
                echo "<p style='color:green'>✓ Version de n8n: " . htmlspecialchars($packageJson['version']) . "</p>";
            }
        }
    } else {
        echo "<p style='color:red'>✗ n8n: Non installé dans $n8nModulePath</p>";
    }
    
    // Vérifier les processus en cours d'exécution
    echo "<h2>Processus Node.js en cours d'exécution</h2>";
    $processes = shell_exec("ps aux | grep node | grep -v grep");
    if (!empty($processes)) {
        echo "<pre>" . htmlspecialchars($processes) . "</pre>";
    } else {
        echo "<p>Aucun processus Node.js en cours d'exécution</p>";
    }
} else {
    echo "<p style='color:red'>✗ Fonction exec() : Non disponible</p>";
    echo "<p>Sans la fonction exec(), il n'est pas possible de vérifier ou d'exécuter Node.js.</p>";
}

// Afficher le chemin de travail
$currentDir = getcwd();
echo "<h2>Informations sur l'environnement</h2>";
echo "<p>Répertoire courant : " . htmlspecialchars($currentDir) . "</p>";
echo "<p>Répertoire du script : " . htmlspecialchars(__DIR__) . "</p>";

// Vérifier le fichier .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "<p style='color:green'>✓ Fichier .env trouvé</p>";
    
    // Afficher le contenu du fichier .env (en masquant les informations sensibles)
    $envContent = file_get_contents($envFile);
    $envContent = preg_replace('/(ENCRYPTION_KEY|PASSWORD|SECRET)=([^\n]*)/', '$1=********', $envContent);
    echo "<pre>" . htmlspecialchars($envContent) . "</pre>";
} else {
    echo "<p style='color:red'>✗ Fichier .env non trouvé</p>";
}

// Afficher l'espace disque disponible
$freeSpace = disk_free_space("/");
$totalSpace = disk_total_space("/");
$usedSpace = $totalSpace - $freeSpace;
$percentUsed = round(($usedSpace / $totalSpace) * 100, 2);

echo "<h2>Espace disque</h2>";
echo "<p>Espace total : " . round($totalSpace / 1024 / 1024 / 1024, 2) . " GB</p>";
echo "<p>Espace utilisé : " . round($usedSpace / 1024 / 1024 / 1024, 2) . " GB ($percentUsed%)</p>";
echo "<p>Espace libre : " . round($freeSpace / 1024 / 1024 / 1024, 2) . " GB</p>";

// Afficher les variables PHP importantes
echo "<h2>Configuration PHP</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Paramètre</th><th>Valeur</th></tr>";
echo "<tr><td>Version PHP</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>max_execution_time</td><td>" . ini_get('max_execution_time') . " secondes</td></tr>";
echo "<tr><td>memory_limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
echo "<tr><td>upload_max_filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td>post_max_size</td><td>" . ini_get('post_max_size') . "</td></tr>";
echo "</table>";

// Instructions pour installer n8n
echo "<h2>Instructions pour installer n8n</h2>";

if (!$info['node_installed'] || !$info['npm_installed']) {
    echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Prérequis manquants</h3>";
    echo "<p>Vous devez d'abord installer Node.js et NPM. Sur un hébergement mutualisé OVH, cela peut être problématique.</p>";
    echo "<p>Options :</p>";
    echo "<ol>";
    echo "<li>Contactez OVH pour activer Node.js et NPM sur votre hébergement</li>";
    echo "<li>Passez à un hébergement VPS ou dédié qui vous donne un accès complet</li>";
    echo "<li>Utilisez Docker si disponible sur votre hébergement</li>";
    echo "</ol>";
    echo "</div>";
} else if (!$info['n8n_installed']) {
    echo "<div style='background-color: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Installation de n8n</h3>";
    echo "<p>Pour installer n8n, vous devez exécuter la commande suivante dans le répertoire du projet :</p>";
    echo "<pre>npm install n8n</pre>";
    echo "<p>Si vous n'avez pas d'accès SSH, créez une tâche Cron dans votre espace client OVH avec :</p>";
    echo "<pre>cd " . htmlspecialchars(__DIR__) . " && npm install n8n</pre>";
    echo "</div>";
} else {
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h3>n8n est installé</h3>";
    echo "<p>Pour démarrer n8n, utilisez le script start-n8n.php ou exécutez :</p>";
    echo "<pre>cd " . htmlspecialchars(__DIR__) . " && npx n8n start</pre>";
    echo "<p>Vous pouvez configurer une tâche Cron dans votre espace client OVH pour démarrer n8n automatiquement.</p>";
    echo "</div>";
}
?> 