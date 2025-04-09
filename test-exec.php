<?php
// Script de test pour vérifier la compatibilité du serveur avec n8n

// Liste des exigences pour n8n
$requirements = [
    'php_version' => [
        'name' => 'Version PHP',
        'min' => '7.4.0',
        'recommended' => '8.1.0',
        'current' => phpversion(),
        'status' => version_compare(phpversion(), '7.4.0', '>=') ? 'ok' : 'error',
        'message' => 'n8n nécessite PHP 7.4 ou supérieur'
    ],
    'node_js' => [
        'name' => 'Node.js',
        'min' => '16.9.0',
        'recommended' => '18.0.0',
        'current' => '',
        'status' => 'unknown',
        'message' => 'n8n nécessite Node.js 16.9.0 ou supérieur'
    ],
    'npm' => [
        'name' => 'NPM',
        'min' => '7.0.0',
        'recommended' => '9.0.0',
        'current' => '',
        'status' => 'unknown',
        'message' => 'n8n nécessite NPM 7.0.0 ou supérieur'
    ],
    'extensions' => [
        'name' => 'Extensions PHP',
        'required' => ['curl', 'xml', 'mbstring', 'pdo'],
        'optional' => ['gd', 'bcmath', 'zip'],
        'missing_required' => [],
        'missing_optional' => [],
        'status' => 'unknown',
        'message' => 'Certaines extensions PHP requises sont manquantes'
    ],
    'disk_space' => [
        'name' => 'Espace disque',
        'min' => 500 * 1024 * 1024, // 500 MB
        'recommended' => 1024 * 1024 * 1024, // 1 GB
        'current' => 0,
        'status' => 'unknown',
        'message' => 'n8n nécessite au moins 500 MB d\'espace disque disponible'
    ],
    'exec_function' => [
        'name' => 'Fonction exec()',
        'required' => true,
        'enabled' => function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))),
        'status' => 'unknown',
        'message' => 'La fonction exec() est désactivée. Certaines fonctionnalités de n8n peuvent ne pas fonctionner correctement.'
    ]
];

// Vérifier la version de Node.js
if ($requirements['exec_function']['enabled']) {
    exec('node -v 2>&1', $node_output, $node_return);
    if ($node_return === 0 && !empty($node_output)) {
        $node_version = trim(str_replace('v', '', $node_output[0]));
        $requirements['node_js']['current'] = $node_version;
        $requirements['node_js']['status'] = version_compare($node_version, '16.9.0', '>=') ? 'ok' : 'error';
    } else {
        $requirements['node_js']['status'] = 'error';
        $requirements['node_js']['current'] = 'Non installé';
    }
    
    // Vérifier la version de NPM
    exec('npm -v 2>&1', $npm_output, $npm_return);
    if ($npm_return === 0 && !empty($npm_output)) {
        $npm_version = trim($npm_output[0]);
        $requirements['npm']['current'] = $npm_version;
        $requirements['npm']['status'] = version_compare($npm_version, '7.0.0', '>=') ? 'ok' : 'error';
    } else {
        $requirements['npm']['status'] = 'error';
        $requirements['npm']['current'] = 'Non installé';
    }
} else {
    $requirements['exec_function']['status'] = 'error';
    $requirements['node_js']['status'] = 'unknown';
    $requirements['node_js']['current'] = 'Impossible de vérifier (exec désactivée)';
    $requirements['npm']['status'] = 'unknown';
    $requirements['npm']['current'] = 'Impossible de vérifier (exec désactivée)';
}

// Vérifier les extensions PHP
foreach ($requirements['extensions']['required'] as $ext) {
    if (!extension_loaded($ext)) {
        $requirements['extensions']['missing_required'][] = $ext;
    }
}

foreach ($requirements['extensions']['optional'] as $ext) {
    if (!extension_loaded($ext)) {
        $requirements['extensions']['missing_optional'][] = $ext;
    }
}

$requirements['extensions']['status'] = empty($requirements['extensions']['missing_required']) ? 'ok' : 'error';

// Vérifier l'espace disque disponible
if (function_exists('disk_free_space')) {
    $free_space = @disk_free_space('.');
    if ($free_space !== false) {
        $requirements['disk_space']['current'] = $free_space;
        $requirements['disk_space']['status'] = ($free_space >= $requirements['disk_space']['min']) ? 'ok' : 'error';
    } else {
        $requirements['disk_space']['status'] = 'unknown';
        $requirements['disk_space']['current'] = 'Impossible de vérifier';
    }
} else {
    $requirements['disk_space']['status'] = 'unknown';
    $requirements['disk_space']['current'] = 'Fonction non disponible';
}

// Formater la taille en unités lisibles
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Calculer le statut global
$global_status = 'ok';
foreach ($requirements as $req) {
    if (isset($req['status']) && $req['status'] === 'error') {
        $global_status = 'error';
        break;
    }
}

// Formatage des versions
$formatVersion = function($version) {
    return $version ? $version : 'Non détecté';
};

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de compatibilité n8n</title>
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
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .requirements-table th, .requirements-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .requirements-table th {
            background-color: #f2f2f2;
        }
        .requirements-table tr:nth-child(even) {
            background-color: #f9f9f9;
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
        }
        .summary {
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .summary-ok {
            background-color: #d5f5e3;
            color: #27ae60;
        }
        .summary-error {
            background-color: #fadbd8;
            color: #c0392b;
        }
        .extension-list {
            list-style-type: none;
            padding-left: 0;
        }
        .extension-list li {
            margin-bottom: 5px;
        }
        .extension-list li:before {
            content: "•";
            margin-right: 5px;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test de compatibilité n8n</h1>
        
        <div class="summary summary-<?php echo $global_status; ?>">
            <?php if ($global_status === 'ok'): ?>
                ✅ Votre serveur semble compatible avec n8n !
            <?php else: ?>
                ⚠️ Certaines exigences ne sont pas satisfaites. Veuillez corriger les problèmes ci-dessous.
            <?php endif; ?>
        </div>
        
        <table class="requirements-table">
            <thead>
                <tr>
                    <th>Exigence</th>
                    <th>Nécessaire</th>
                    <th>Recommandé</th>
                    <th>Actuel</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <!-- PHP Version -->
                <tr>
                    <td><?php echo $requirements['php_version']['name']; ?></td>
                    <td><?php echo $requirements['php_version']['min']; ?></td>
                    <td><?php echo $requirements['php_version']['recommended']; ?></td>
                    <td><?php echo $formatVersion($requirements['php_version']['current']); ?></td>
                    <td>
                        <div class="status-message">
                            <span class="status status-<?php echo $requirements['php_version']['status']; ?>"></span>
                            <?php if ($requirements['php_version']['status'] !== 'ok'): ?>
                                <?php echo $requirements['php_version']['message']; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- Node.js -->
                <tr>
                    <td><?php echo $requirements['node_js']['name']; ?></td>
                    <td><?php echo $requirements['node_js']['min']; ?></td>
                    <td><?php echo $requirements['node_js']['recommended']; ?></td>
                    <td><?php echo $formatVersion($requirements['node_js']['current']); ?></td>
                    <td>
                        <div class="status-message">
                            <span class="status status-<?php echo $requirements['node_js']['status']; ?>"></span>
                            <?php if ($requirements['node_js']['status'] !== 'ok'): ?>
                                <?php echo $requirements['node_js']['message']; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- NPM -->
                <tr>
                    <td><?php echo $requirements['npm']['name']; ?></td>
                    <td><?php echo $requirements['npm']['min']; ?></td>
                    <td><?php echo $requirements['npm']['recommended']; ?></td>
                    <td><?php echo $formatVersion($requirements['npm']['current']); ?></td>
                    <td>
                        <div class="status-message">
                            <span class="status status-<?php echo $requirements['npm']['status']; ?>"></span>
                            <?php if ($requirements['npm']['status'] !== 'ok'): ?>
                                <?php echo $requirements['npm']['message']; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- Extensions PHP -->
                <tr>
                    <td><?php echo $requirements['extensions']['name']; ?></td>
                    <td colspan="2">
                        <strong>Requises :</strong>
                        <ul class="extension-list">
                            <?php foreach ($requirements['extensions']['required'] as $ext): ?>
                                <li><?php echo $ext; ?> <?php echo in_array($ext, $requirements['extensions']['missing_required']) ? '❌' : '✅'; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <strong>Optionnelles :</strong>
                        <ul class="extension-list">
                            <?php foreach ($requirements['extensions']['optional'] as $ext): ?>
                                <li><?php echo $ext; ?> <?php echo in_array($ext, $requirements['extensions']['missing_optional']) ? '⚠️' : '✅'; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <?php if (empty($requirements['extensions']['missing_required']) && empty($requirements['extensions']['missing_optional'])): ?>
                            Toutes les extensions sont installées
                        <?php elseif (empty($requirements['extensions']['missing_required'])): ?>
                            Extensions requises OK, certaines extensions optionnelles manquantes
                        <?php else: ?>
                            Extensions requises manquantes
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="status-message">
                            <span class="status status-<?php echo $requirements['extensions']['status']; ?>"></span>
                            <?php if ($requirements['extensions']['status'] !== 'ok'): ?>
                                <?php echo $requirements['extensions']['message']; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- Espace disque -->
                <tr>
                    <td><?php echo $requirements['disk_space']['name']; ?></td>
                    <td><?php echo formatSize($requirements['disk_space']['min']); ?></td>
                    <td><?php echo formatSize($requirements['disk_space']['recommended']); ?></td>
                    <td>
                        <?php 
                        if (is_numeric($requirements['disk_space']['current'])) {
                            echo formatSize($requirements['disk_space']['current']);
                        } else {
                            echo $requirements['disk_space']['current'];
                        }
                        ?>
                    </td>
                    <td>
                        <div class="status-message">
                            <span class="status status-<?php echo $requirements['disk_space']['status']; ?>"></span>
                            <?php if ($requirements['disk_space']['status'] !== 'ok'): ?>
                                <?php echo $requirements['disk_space']['message']; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                
                <!-- Fonction exec -->
                <tr>
                    <td><?php echo $requirements['exec_function']['name']; ?></td>
                    <td colspan="2">Activée</td>
                    <td><?php echo $requirements['exec_function']['enabled'] ? 'Activée' : 'Désactivée'; ?></td>
                    <td>
                        <div class="status-message">
                            <span class="status status-<?php echo $requirements['exec_function']['enabled'] ? 'ok' : 'error'; ?>"></span>
                            <?php if (!$requirements['exec_function']['enabled']): ?>
                                <?php echo $requirements['exec_function']['message']; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2>Informations système supplémentaires</h2>
        <table class="requirements-table">
            <tr>
                <td><strong>Serveur Web</strong></td>
                <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu'; ?></td>
            </tr>
            <tr>
                <td><strong>OS du serveur</strong></td>
                <td><?php echo php_uname(); ?></td>
            </tr>
            <tr>
                <td><strong>Limite de mémoire PHP</strong></td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <td><strong>Limite de temps d'exécution</strong></td>
                <td><?php echo ini_get('max_execution_time'); ?> secondes</td>
            </tr>
            <tr>
                <td><strong>Taille maximale d'upload</strong></td>
                <td><?php echo ini_get('upload_max_filesize'); ?></td>
            </tr>
        </table>
        
        <a href="index.php" class="btn">Retour à l'accueil</a>
    </div>
</body>
</html> 