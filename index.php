<?php
// Interface web pour les outils n8n

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
$envPath = $n8nDir . '.env';

// Vérifier l'état de l'installation
$installationStatus = [
    'env_exists' => file_exists($envPath),
    'encryption_key' => false,
    'db_configured' => false
];

if ($installationStatus['env_exists'] && is_readable($envPath)) {
    $envContent = file_get_contents($envPath);
    
    // Vérifier si la clé d'encryption existe
    if (preg_match('/^ENCRYPTION_KEY=(.+)$/m', $envContent, $matches) && !empty($matches[1])) {
        $installationStatus['encryption_key'] = true;
    }
    
    // Vérifier si la base de données est configurée
    if (preg_match('/^DB_TYPE=(.+)$/m', $envContent)) {
        $installationStatus['db_configured'] = true;
    }
}

// Calculer le statut global
$installationComplete = $installationStatus['env_exists'] && 
                       $installationStatus['encryption_key'] && 
                       $installationStatus['db_configured'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outils n8n</title>
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
            margin-right: 10px;
            margin-bottom: 15px;
            transition: background 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #95a5a6;
        }
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        .btn-success {
            background-color: #2ecc71;
        }
        .btn-success:hover {
            background-color: #27ae60;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .status {
            display: inline-block;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-success {
            background-color: #2ecc71;
        }
        .status-warning {
            background-color: #f39c12;
        }
        .status-error {
            background-color: #e74c3c;
        }
        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Outils de gestion n8n</h1>
        
        <div class="card">
            <h2>État de l'installation</h2>
            
            <div class="status-item">
                <span class="status <?php echo $installationStatus['env_exists'] ? 'status-success' : 'status-error'; ?>"></span>
                Fichier .env : <?php echo $installationStatus['env_exists'] ? 'OK' : 'Non trouvé'; ?>
            </div>
            
            <div class="status-item">
                <span class="status <?php echo $installationStatus['encryption_key'] ? 'status-success' : 'status-warning'; ?>"></span>
                Clé d'encryption : <?php echo $installationStatus['encryption_key'] ? 'Configurée' : 'Non configurée'; ?>
            </div>
            
            <div class="status-item">
                <span class="status <?php echo $installationStatus['db_configured'] ? 'status-success' : 'status-warning'; ?>"></span>
                Configuration de la base de données : <?php echo $installationStatus['db_configured'] ? 'OK' : 'Non configurée'; ?>
            </div>
            
            <?php if (!$installationComplete): ?>
                <p><strong>L'installation n'est pas complète.</strong> Veuillez utiliser l'outil d'installation pour configurer n8n.</p>
                <a href="install.php" class="btn">Configurer n8n</a>
            <?php else: ?>
                <p><strong>Installation complète.</strong> n8n est configuré et prêt à être utilisé.</p>
                <a href="install.php" class="btn btn-secondary">Modifier la configuration</a>
            <?php endif; ?>
        </div>
        
        <h2>Outils disponibles</h2>
        
        <div>
            <a href="test-exec.php" class="btn">Test de compatibilité</a>
            <a href="install.php" class="btn">Installation et configuration</a>
            <?php if ($installationComplete): ?>
                <a href="<?php echo rtrim($n8nDir, '/'); ?>" class="btn btn-success">Accéder à n8n</a>
            <?php endif; ?>
        </div>
        
        <h2>Informations système</h2>
        <div class="card">
            <p><strong>Répertoire n8n :</strong> <?php echo htmlspecialchars($n8nDir); ?></p>
            <p><strong>Version PHP :</strong> <?php echo phpversion(); ?></p>
            <p><strong>Système d'exploitation :</strong> <?php echo php_uname('s') . ' ' . php_uname('r'); ?></p>
        </div>
    </div>
</body>
</html> 