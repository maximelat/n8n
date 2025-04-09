<?php
// Page d'accueil pour n8n
$n8nDir = realpath(__DIR__);

// Vérifier si n8n est installé
$n8nInstalled = is_dir($n8nDir . '/node_modules/n8n');

// Vérifier si le fichier .env existe
$envExists = file_exists($n8nDir . '/.env');

// Vérifier si n8n est en cours d'exécution (si exec est disponible)
$n8nRunning = false;
$execEnabled = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))));

if ($execEnabled) {
    exec("ps aux | grep 'n8n start' | grep -v grep", $psOutput);
    $n8nRunning = !empty($psOutput);
}

// Fonction pour formater les dates de fichiers
function formatFileTime($file) {
    if (file_exists($file)) {
        return date('Y-m-d H:i:s', filemtime($file));
    }
    return 'N/A';
}

// Récupérer les informations sur les fichiers de log
$logs = [
    'Installation' => [
        'path' => $n8nDir . '/install-n8n.log',
        'exists' => file_exists($n8nDir . '/install-n8n.log'),
        'updated' => formatFileTime($n8nDir . '/install-n8n.log')
    ],
    'Démarrage' => [
        'path' => $n8nDir . '/n8n-start.log',
        'exists' => file_exists($n8nDir . '/n8n-start.log'),
        'updated' => formatFileTime($n8nDir . '/n8n-start.log')
    ],
    'Sortie n8n' => [
        'path' => $n8nDir . '/n8n-output.log',
        'exists' => file_exists($n8nDir . '/n8n-output.log'),
        'updated' => formatFileTime($n8nDir . '/n8n-output.log')
    ]
];

// Dernières lignes des logs
$logContent = [];
foreach ($logs as $name => $log) {
    if ($log['exists']) {
        $content = file_get_contents($log['path']);
        // Obtenir les 10 dernières lignes
        $lines = explode("\n", $content);
        $lastLines = array_slice($lines, -10);
        $logContent[$name] = implode("\n", $lastLines);
    }
}

// Statut global
$status = 'Inconnu';
$statusClass = 'status-unknown';

if (!$n8nInstalled) {
    $status = 'Non installé';
    $statusClass = 'status-error';
} elseif ($n8nRunning) {
    $status = 'En cours d'exécution';
    $statusClass = 'status-ok';
} else {
    $status = 'Installé mais arrêté';
    $statusClass = 'status-warning';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>n8n - Tableau de bord</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            max-width: 1200px;
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
        .status-container {
            display: flex;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
            border-left: 5px solid #ddd;
        }
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .status-ok {
            background-color: #2ecc71;
            border-left-color: #2ecc71;
        }
        .status-warning {
            background-color: #f39c12;
            border-left-color: #f39c12;
        }
        .status-error {
            background-color: #e74c3c;
            border-left-color: #e74c3c;
        }
        .status-unknown {
            background-color: #95a5a6;
            border-left-color: #95a5a6;
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
        .btn-warning {
            background-color: #f39c12;
        }
        .btn-warning:hover {
            background-color: #e67e22;
        }
        .btn-danger {
            background-color: #e74c3c;
        }
        .btn-danger:hover {
            background-color: #c0392b;
        }
        .btn-success {
            background-color: #2ecc71;
        }
        .btn-success:hover {
            background-color: #27ae60;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px;
        }
        .log-container {
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .log-content {
            height: 200px;
            overflow-y: auto;
            background-color: #252525;
            color: #f8f8f8;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>n8n - Tableau de bord</h1>
        
        <div class="status-container <?php echo $statusClass; ?>">
            <div class="status-indicator <?php echo $statusClass; ?>"></div>
            <div>
                <h2 style="margin: 0;">État actuel : <?php echo $status; ?></h2>
                <p>
                    <?php if ($n8nInstalled && $envExists): ?>
                        n8n est <?php echo $n8nRunning ? 'en cours d\'exécution' : 'arrêté'; ?>.
                        <?php if (!$n8nRunning): ?>
                            Utilisez le bouton ci-dessous pour le démarrer.
                        <?php endif; ?>
                    <?php elseif (!$n8nInstalled): ?>
                        n8n n'est pas encore installé. Utilisez le script d'installation pour configurer n8n.
                    <?php elseif (!$envExists): ?>
                        Le fichier de configuration .env est manquant. Veuillez exécuter le script d'installation.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Installation</h2>
                <p>Vérifiez les prérequis et installez n8n sur votre serveur.</p>
                <a href="check-node.php" class="btn">Vérifier les prérequis</a>
                <a href="install-n8n.php" class="btn">Installer n8n</a>
            </div>
            
            <div class="card">
                <h2>Gestion</h2>
                <p>Gérez l'instance n8n en cours d'exécution.</p>
                <?php if ($n8nInstalled): ?>
                    <a href="start-n8n.php" class="btn btn-success">Démarrer n8n</a>
                    <?php if ($n8nRunning): ?>
                        <form method="post" action="stop-n8n.php" style="display: inline;">
                            <button type="submit" class="btn btn-danger">Arrêter n8n</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="#" class="btn btn-disabled" style="opacity: 0.5; cursor: not-allowed;">Démarrer n8n</a>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Accès</h2>
                <p>Accédez à l'interface n8n ou à la documentation.</p>
                <a href="/projet/n8n/" class="btn" target="_blank">Ouvrir n8n</a>
                <a href="https://docs.n8n.io/" class="btn" target="_blank">Documentation</a>
            </div>
        </div>
        
        <h2>Journaux système</h2>
        <table>
            <tr>
                <th>Fichier de log</th>
                <th>Dernière mise à jour</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($logs as $name => $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td><?php echo $log['exists'] ? htmlspecialchars($log['updated']) : 'Non disponible'; ?></td>
                <td>
                    <?php if ($log['exists']): ?>
                    <button onclick="showLog('<?php echo $name; ?>')" class="btn">Voir</button>
                    <?php else: ?>
                    <span>Aucun log disponible</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php foreach ($logs as $name => $log): ?>
            <?php if ($log['exists']): ?>
            <div id="log-<?php echo str_replace(' ', '-', strtolower($name)); ?>" class="log-container" style="display: none;">
                <h3><?php echo htmlspecialchars($name); ?> - Dernières lignes</h3>
                <div class="log-content"><?php echo htmlspecialchars($logContent[$name] ?? 'Log vide'); ?></div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script>
        function showLog(name) {
            // Cacher tous les logs
            document.querySelectorAll('.log-container').forEach(el => {
                el.style.display = 'none';
            });
            
            // Afficher le log sélectionné
            const logId = 'log-' + name.toLowerCase().replace(' ', '-');
            document.getElementById(logId).style.display = 'block';
        }
    </script>
</body>
</html> 