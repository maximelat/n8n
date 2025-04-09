<?php
// Script d'installation pour n8n

// Fonction pour détecter automatiquement le répertoire n8n
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

// Déterminer le répertoire n8n
$n8nDir = detectN8nDirectory();

// Vérifier si exec est disponible (requis pour l'installation)
$execEnabled = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

// Vérifier les versions de Node.js et NPM
$nodeVersion = '';
$npmVersion = '';
$nodeInstalled = false;
$npmInstalled = false;

if ($execEnabled) {
    // Vérifier Node.js
    exec('node -v 2>&1', $nodeOutput, $nodeReturn);
    if ($nodeReturn === 0 && !empty($nodeOutput)) {
        $nodeVersion = trim(str_replace('v', '', $nodeOutput[0]));
        $nodeInstalled = true;
    }
    
    // Vérifier NPM
    exec('npm -v 2>&1', $npmOutput, $npmReturn);
    if ($npmReturn === 0 && !empty($npmOutput)) {
        $npmVersion = trim($npmOutput[0]);
        $npmInstalled = true;
    }
}

// Déterminer si nous sommes sur un hébergement OVH
$isOVHHosting = (strpos($_SERVER['SERVER_SOFTWARE'] ?? '', 'OVH') !== false) || 
                (file_exists('/etc/ovhrc')) || 
                (preg_match('/ovh|kimsufi|soyoustart/i', gethostname() ?? ''));

// État de l'installation
$installationStatus = [
    'node_installed' => $nodeInstalled,
    'npm_installed' => $npmInstalled,
    'env_exists' => file_exists($n8nDir . '.env'),
    'can_execute' => $execEnabled,
    'step' => isset($_GET['step']) ? intval($_GET['step']) : 0
];

// Fonction pour installer n8n
function installN8n($n8nDir) {
    $output = [];
    $exitCode = 0;
    
    // Créer le fichier .env s'il n'existe pas
    if (!file_exists($n8nDir . '.env')) {
        $envSample = <<<EOT
# Environnement n8n
N8N_PORT=5678
N8N_PROTOCOL=http
N8N_HOST=localhost
N8N_PATH=/
N8N_LOG_LEVEL=info

# Base de données
DB_TYPE=sqlite
DB_PATH=~/.n8n/database.sqlite

# Sécurité
ENCRYPTION_KEY=
EOT;
        file_put_contents($n8nDir . '.env', $envSample);
    }
    
    // Installer n8n via npm
    exec('cd ' . escapeshellarg($n8nDir) . ' && npm install n8n', $output, $exitCode);
    
    return [
        'success' => ($exitCode === 0),
        'output' => implode("\n", $output)
    ];
}

// Fonction pour générer une clé d'encryption
function generateEncryptionKey() {
    return bin2hex(random_bytes(24));
}

// Fonction pour générer un fichier d'instructions
function generateInstructionsFile($n8nDir) {
    $encryptionKey = generateEncryptionKey();
    $instructionsContent = <<<EOT
INSTRUCTIONS D'INSTALLATION MANUELLE POUR N8N
=============================================

En raison des restrictions sur l'hébergement OVH, vous devez compléter l'installation manuellement via SSH.
Voici les différentes options d'installation :

OPTION 1: INSTALLATION AVEC NPM
------------------------------

1. Connectez-vous à votre serveur via SSH:
   ```
   ssh votre_utilisateur@latry.consulting
   ```

2. Naviguez vers le répertoire n8n:
   ```
   cd ~/www/projet/n8n/
   ```

3. Installez n8n via npm:
   ```
   npm install n8n
   ```

4. Pour démarrer n8n en mode développement:
   ```
   npx n8n start
   ```

5. Pour un déploiement en production, utilisez PM2:
   ```
   npm install -g pm2
   pm2 start "npx n8n start" --name="n8n"
   ```

6. Pour configurer n8n pour qu'il démarre automatiquement au redémarrage du serveur:
   ```
   pm2 save
   pm2 startup
   ```
   Suivez les instructions affichées par la commande pm2 startup.


OPTION 2: INSTALLATION AVEC DOCKER (RECOMMANDÉE)
-----------------------------------------------

Si Docker est disponible sur votre serveur OVH (VPS, Cloud, etc.), cette méthode est plus simple:

1. Connectez-vous à votre serveur via SSH:
   ```
   ssh votre_utilisateur@latry.consulting
   ```

2. Naviguez vers le répertoire n8n:
   ```
   cd ~/www/projet/n8n/
   ```

3. Créez un fichier docker-compose.yml avec le contenu suivant:
   ```yaml
   version: '3'
   
   services:
     n8n:
       image: n8nio/n8n
       restart: always
       ports:
         - "5678:5678"
       environment:
         - N8N_PROTOCOL=https
         - N8N_HOST=latry.consulting
         - N8N_PATH=/projet/n8n
         - N8N_PORT=5678
         - NODE_ENV=production
         - WEBHOOK_URL=https://latry.consulting/projet/n8n
         - ENCRYPTION_KEY={$encryptionKey}
       volumes:
         - n8n_data:/home/node/.n8n
   
   volumes:
     n8n_data:
       external: false
   ```

4. Lancez n8n avec Docker Compose:
   ```
   docker-compose up -d
   ```


COMMANDES UTILES
---------------

- Pour accéder à n8n depuis votre navigateur:
  https://latry.consulting/projet/n8n

- Pour arrêter n8n (avec PM2):
  ```
  pm2 stop n8n
  ```

- Pour redémarrer n8n (avec PM2):
  ```
  pm2 restart n8n
  ```

- Pour voir les logs (avec PM2):
  ```
  pm2 logs n8n
  ```

- Pour arrêter n8n (avec Docker):
  ```
  docker-compose down
  ```

- Pour redémarrer n8n (avec Docker):
  ```
  docker-compose restart
  ```

- Pour voir les logs (avec Docker):
  ```
  docker-compose logs -f
  ```


RÉSOLUTION DES PROBLÈMES
-----------------------

Si vous rencontrez des problèmes, vérifiez que:
- Node.js version 16.9.0 ou supérieure est installé (pour l'installation NPM)
- NPM version 7.0.0 ou supérieure est installé (pour l'installation NPM)
- Docker et Docker Compose sont installés (pour l'installation Docker)
- Le fichier .env contient bien une clé d'encryption valide
- Le port 5678 est disponible et n'est pas bloqué par un pare-feu
- Votre configuration Nginx permet l'accès au port 5678

EOT;

    $instructionsFile = $n8nDir . 'INSTRUCTIONS_N8N.txt';
    file_put_contents($instructionsFile, $instructionsContent);
    return $instructionsFile;
}

// Traitement des actions
$actionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_env':
            $encryptionKey = generateEncryptionKey();
            $dbType = $_POST['db_type'] ?? 'sqlite';
            $dbPath = '/home/latrycf/n8n/database.sqlite';
            
            if ($dbType === 'sqlite') {
                $dbConfig = "DB_TYPE=sqlite\nDB_PATH={$dbPath}";
            } else {
                $dbHost = $_POST['db_host'] ?? 'localhost';
                $dbPort = $_POST['db_port'] ?? '3306';
                $dbName = $_POST['db_name'] ?? 'n8n';
                $dbUser = $_POST['db_user'] ?? 'n8n';
                $dbPass = $_POST['db_pass'] ?? '';
                
                $dbConfig = "DB_TYPE={$dbType}\nDB_HOST={$dbHost}\nDB_PORT={$dbPort}\nDB_DATABASE={$dbName}\nDB_USER={$dbUser}\nDB_PASS={$dbPass}";
            }
            
            $port = $_POST['port'] ?? '5678';
            $protocol = $_POST['protocol'] ?? 'http';
            $host = $_POST['host'] ?? 'localhost';
            
            $envContent = <<<EOT
# Environnement n8n
N8N_PORT={$port}
N8N_PROTOCOL={$protocol}
N8N_HOST={$host}
N8N_PATH=/
N8N_LOG_LEVEL=info

# Base de données
{$dbConfig}

# Sécurité
ENCRYPTION_KEY={$encryptionKey}
EOT;
            
            $saveResult = file_put_contents($n8nDir . '.env', $envContent);
            $actionResult = [
                'success' => ($saveResult !== false),
                'message' => ($saveResult !== false) 
                    ? 'Fichier .env créé avec succès.' 
                    : 'Erreur lors de la création du fichier .env.'
            ];
            
            // Mettre à jour le statut
            $installationStatus['env_exists'] = file_exists($n8nDir . '.env');
            break;
            
        case 'install_n8n':
            if ($installationStatus['can_execute'] && $installationStatus['node_installed'] && $installationStatus['npm_installed']) {
                $installResult = installN8n($n8nDir);
                $actionResult = [
                    'success' => $installResult['success'],
                    'message' => $installResult['success'] 
                        ? 'n8n a été installé avec succès.' 
                        : 'Erreur lors de l\'installation de n8n. Détails: ' . $installResult['output']
                ];
            } else {
                $actionResult = [
                    'success' => false,
                    'message' => 'Impossible d\'installer n8n. Vérifiez que Node.js et NPM sont installés et que la fonction exec() est disponible.'
                ];
            }
            break;
            
        case 'generate_instructions':
            $instructionsFile = generateInstructionsFile($n8nDir);
            $actionResult = [
                'success' => file_exists($instructionsFile),
                'message' => file_exists($instructionsFile) 
                    ? 'Instructions générées avec succès dans ' . basename($instructionsFile) 
                    : 'Erreur lors de la génération des instructions.'
            ];
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
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            background-color: #f5f7fa;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #3498db;
            margin-top: 25px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        .btn-disabled:hover {
            background-color: #95a5a6;
        }
        .status {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-ok {
            background-color: #2ecc71;
        }
        .status-warning {
            background-color: #f39c12;
        }
        .status-error {
            background-color: #e74c3c;
        }
        .status-unknown {
            background-color: #95a5a6;
        }
        .status-message {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d5f5e3;
            color: #27ae60;
        }
        .alert-error {
            background-color: #fadbd8;
            color: #c0392b;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .steps {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
            color: #95a5a6;
        }
        .step.active {
            color: #3498db;
            font-weight: bold;
        }
        .step.completed {
            color: #2ecc71;
        }
        .step.completed:after {
            content: "✓";
            margin-left: 5px;
        }
        .tab {
            display: none;
        }
        .tab.active {
            display: block;
        }
        .tab-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }
        .code-block {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Installation de n8n</h1>
        
        <div class="steps">
            <div class="step <?php echo $installationStatus['step'] === 0 ? 'active' : ($installationStatus['step'] > 0 ? 'completed' : ''); ?>">1. Vérification prérequis</div>
            <div class="step <?php echo $installationStatus['step'] === 1 ? 'active' : ($installationStatus['step'] > 1 ? 'completed' : ''); ?>">2. Configuration</div>
            <div class="step <?php echo $installationStatus['step'] === 2 ? 'active' : ($installationStatus['step'] > 2 ? 'completed' : ''); ?>">3. Installation</div>
            <div class="step <?php echo $installationStatus['step'] === 3 ? 'active' : ''; ?>">4. Finalisation</div>
        </div>
        
        <?php if ($actionResult): ?>
        <div class="alert alert-<?php echo $actionResult['success'] ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($actionResult['message']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Étape 1: Vérification des prérequis -->
        <div class="tab <?php echo $installationStatus['step'] === 0 ? 'active' : ''; ?>">
            <h2>Vérification des prérequis</h2>
            
            <?php if ($isOVHHosting): ?>
            <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">⚠️ Hébergement OVH détecté</h3>
                <p>Attention : Votre site semble être hébergé sur OVH. En raison des restrictions sur l'hébergement OVH, l'installation complète automatique via PHP n'est pas possible.</p>
                <p>Vous devrez vous connecter à votre serveur via SSH et suivre les instructions générées pour compléter l'installation.</p>
            </div>
            <?php endif; ?>
            
            <div class="status-message">
                <span class="status status-<?php echo $installationStatus['node_installed'] ? 'ok' : 'error'; ?>"></span>
                <div>Node.js: <?php echo $nodeInstalled ? 'Installé (version ' . htmlspecialchars($nodeVersion) . ')' : 'Non installé'; ?></div>
            </div>
            
            <div class="status-message">
                <span class="status status-<?php echo $installationStatus['npm_installed'] ? 'ok' : 'error'; ?>"></span>
                <div>NPM: <?php echo $npmInstalled ? 'Installé (version ' . htmlspecialchars($npmVersion) . ')' : 'Non installé'; ?></div>
            </div>
            
            <div class="status-message">
                <span class="status status-<?php echo $installationStatus['can_execute'] ? 'ok' : 'error'; ?>"></span>
                <div>Fonction exec(): <?php echo $installationStatus['can_execute'] ? 'Disponible' : 'Non disponible'; ?></div>
            </div>
            
            <?php if (!$installationStatus['node_installed'] || !$installationStatus['npm_installed']): ?>
            <div class="alert alert-error">
                <p><strong>Node.js et NPM sont requis pour l'installation de n8n.</strong></p>
                <p>Instructions d'installation:</p>
                <ul>
                    <li>Sous Linux: <code>sudo apt-get install nodejs npm</code> (Debian/Ubuntu) ou <code>sudo yum install nodejs npm</code> (CentOS/RHEL)</li>
                    <li>Sous Windows/Mac: Téléchargez et installez depuis <a href="https://nodejs.org/" target="_blank">nodejs.org</a></li>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!$installationStatus['can_execute']): ?>
            <div class="alert alert-error">
                <p><strong>La fonction exec() n'est pas disponible sur votre serveur.</strong></p>
                <p>Cette fonction est nécessaire pour installer et exécuter n8n. Contactez votre hébergeur pour l'activer dans votre configuration PHP.</p>
            </div>
            <?php endif; ?>
            
            <div class="tab-buttons">
                <a href="index.php" class="btn">Retour</a>
                <a href="?step=1" class="btn <?php echo (!$installationStatus['node_installed'] || !$installationStatus['npm_installed'] || !$installationStatus['can_execute']) ? 'btn-disabled' : ''; ?>" <?php echo (!$installationStatus['node_installed'] || !$installationStatus['npm_installed'] || !$installationStatus['can_execute']) ? 'onclick="return false;"' : ''; ?>>Suivant</a>
            </div>
        </div>
        
        <!-- Étape 2: Configuration -->
        <div class="tab <?php echo $installationStatus['step'] === 1 ? 'active' : ''; ?>">
            <h2>Configuration</h2>
            
            <form method="post" action="?step=1">
                <input type="hidden" name="action" value="create_env">
                
                <h3>Configuration de base</h3>
                <div class="form-group">
                    <label for="port">Port</label>
                    <input type="text" id="port" name="port" class="form-control" value="5678" required>
                </div>
                
                <div class="form-group">
                    <label for="protocol">Protocole</label>
                    <select id="protocol" name="protocol" class="form-control">
                        <option value="http">HTTP</option>
                        <option value="https">HTTPS</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="host">Hôte</label>
                    <input type="text" id="host" name="host" class="form-control" value="localhost" required>
                </div>
                
                <h3>Configuration de la base de données</h3>
                <div class="form-group">
                    <label for="db_type">Type de base de données</label>
                    <select id="db_type" name="db_type" class="form-control" onchange="toggleDbFields()">
                        <option value="sqlite">SQLite (recommandé pour démarrer)</option>
                        <option value="mysqldb">MySQL</option>
                        <option value="postgresdb">PostgreSQL</option>
                    </select>
                </div>
                
                <div id="advanced_db_fields" style="display: none;">
                    <div class="form-group">
                        <label for="db_host">Hôte DB</label>
                        <input type="text" id="db_host" name="db_host" class="form-control" value="localhost">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_port">Port DB</label>
                        <input type="text" id="db_port" name="db_port" class="form-control" value="3306">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Nom de la base de données</label>
                        <input type="text" id="db_name" name="db_name" class="form-control" value="n8n">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Utilisateur DB</label>
                        <input type="text" id="db_user" name="db_user" class="form-control" value="n8n">
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Mot de passe DB</label>
                        <input type="password" id="db_pass" name="db_pass" class="form-control">
                    </div>
                </div>
                
                <div class="tab-buttons">
                    <a href="?step=0" class="btn">Précédent</a>
                    <button type="submit" class="btn">Enregistrer et continuer</button>
                </div>
            </form>
        </div>
        
        <!-- Étape 3: Installation -->
        <div class="tab <?php echo $installationStatus['step'] === 2 ? 'active' : ''; ?>">
            <h2>Installation de n8n</h2>
            
            <?php if (!$installationStatus['env_exists']): ?>
            <div class="alert alert-error">
                <p>Le fichier .env n'existe pas. Veuillez revenir à l'étape précédente pour le configurer.</p>
            </div>
            <?php else: ?>
            <p>Nous sommes prêts à installer n8n sur votre serveur. Cela peut prendre plusieurs minutes.</p>
            
            <form method="post" action="?step=2">
                <input type="hidden" name="action" value="install_n8n">
                <button type="submit" class="btn">Installer n8n</button>
            </form>
            <?php endif; ?>
            
            <div class="tab-buttons">
                <a href="?step=1" class="btn">Précédent</a>
                <a href="?step=3" class="btn">Passer cette étape</a>
            </div>
        </div>
        
        <!-- Étape 4: Finalisation -->
        <div class="tab <?php echo $installationStatus['step'] === 3 ? 'active' : ''; ?>">
            <h2>Installation complétée</h2>
            
            <?php if ($isOVHHosting): ?>
            <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; margin-bottom: 20px;">
                <h3 style="margin-top: 0;">⚠️ Instructions d'installation manuelle pour OVH</h3>
                <p>En raison des restrictions sur l'hébergement OVH, vous devez terminer l'installation manuellement via SSH:</p>
                
                <h4>Option 1: Installation directe avec NPM</h4>
                <ol>
                    <li>Connectez-vous à votre serveur via SSH:
                        <div class="code-block">ssh votre_utilisateur@latry.consulting</div>
                    </li>
                    <li>Naviguez vers le répertoire n8n:
                        <div class="code-block">cd ~/www/projet/n8n/</div>
                    </li>
                    <li>Installez n8n:
                        <div class="code-block">npm install n8n</div>
                    </li>
                    <li>Pour démarrer n8n en arrière-plan:
                        <div class="code-block">npm install -g pm2 && pm2 start "npx n8n start" --name="n8n"</div>
                    </li>
                </ol>
                
                <h4>Option 2: Installation avec Docker (recommandée si disponible)</h4>
                <p>Si Docker est disponible sur votre serveur OVH (VPS, Cloud, etc.), cette méthode est recommandée:</p>
                <ol>
                    <li>Connectez-vous à votre serveur via SSH</li>
                    <li>Naviguez vers le répertoire n8n</li>
                    <li>Créez un fichier docker-compose.yml (ou utilisez celui du dépôt):</li>
                    <div class="code-block">
version: '3'

services:
  n8n:
    image: n8nio/n8n
    restart: always
    ports:
      - "5678:5678"
    environment:
      - N8N_PROTOCOL=https
      - N8N_HOST=latry.consulting
      - N8N_PATH=/projet/n8n
      - N8N_PORT=5678
      - NODE_ENV=production
      - WEBHOOK_URL=https://latry.consulting/projet/n8n
      - ENCRYPTION_KEY=<?php echo generateEncryptionKey(); ?>
    volumes:
      - n8n_data:/home/node/.n8n

volumes:
  n8n_data:
    external: false
                    </div>
                    <li>Lancez n8n avec Docker Compose:
                        <div class="code-block">docker-compose up -d</div>
                    </li>
                </ol>
                
                <p>Une fois l'installation terminée, n8n sera accessible à l'adresse suivante:</p>
                <div class="code-block">https://latry.consulting/projet/n8n</div>
                
                <form method="post" action="?step=3">
                    <input type="hidden" name="action" value="generate_instructions">
                    <button type="submit" class="btn">Télécharger les instructions détaillées</button>
                </form>
                
                <?php if (file_exists($n8nDir . 'INSTRUCTIONS_N8N.txt')): ?>
                <p>Les instructions détaillées sont disponibles ici: <a href="INSTRUCTIONS_N8N.txt" download class="btn">Télécharger</a></p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p>Pour démarrer n8n, exécutez la commande suivante dans le terminal :</p>
            
            <div class="code-block">cd <?php echo htmlspecialchars($n8nDir); ?> && npx n8n start</div>
            
            <p>Pour un déploiement en production, vous pouvez utiliser :</p>
            
            <div class="code-block">cd <?php echo htmlspecialchars($n8nDir); ?> && npm install -g pm2 && pm2 start "npx n8n start" --name="n8n"</div>
            
            <p>Accédez à n8n en utilisant l'URL suivante :</p>
            
            <div class="code-block">http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>:5678</div>
            <?php endif; ?>
            
            <div class="tab-buttons">
                <a href="?step=2" class="btn">Précédent</a>
                <a href="index.php" class="btn">Terminer</a>
            </div>
        </div>
    </div>
    
    <script>
        function toggleDbFields() {
            var dbType = document.getElementById('db_type').value;
            var advancedFields = document.getElementById('advanced_db_fields');
            
            if (dbType === 'sqlite') {
                advancedFields.style.display = 'none';
            } else {
                advancedFields.style.display = 'block';
                
                // Ajuster le port par défaut en fonction du type de DB
                var portField = document.getElementById('db_port');
                if (dbType === 'postgresdb') {
                    portField.value = '5432';
                } else {
                    portField.value = '3306';
                }
            }
        }
    </script>
</body>
</html> 