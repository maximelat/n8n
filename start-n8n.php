<?php
// Script qui peut être exécuté par le cron d'OVH pour démarrer n8n
$logFile = '/home/latrycf/www/projet/n8n/n8n-start.log';

file_put_contents($logFile, date('Y-m-d H:i:s') . " - Tentative de démarrage de n8n\n", FILE_APPEND);

// Utiliser le chemin complet pour node et npx
$nodePath = '/usr/bin/node';
$npxPath = '/usr/bin/npx';

if (!file_exists($nodePath)) {
    file_put_contents($logFile, "Node.js non trouvé à l'emplacement: $nodePath\n", FILE_APPEND);
    // Essayer de trouver node
    exec('which node', $nodePathOutput);
    if (!empty($nodePathOutput)) {
        $nodePath = trim($nodePathOutput[0]);
        file_put_contents($logFile, "Node.js trouvé à: $nodePath\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "Node.js introuvable\n", FILE_APPEND);
        exit(1);
    }
}

// Chemin vers le répertoire n8n
$n8nDir = '/home/latrycf/www/projet/n8n';

// Vérifier si npx existe
exec('which npx', $npxPathOutput);
if (!empty($npxPathOutput)) {
    $npxPath = trim($npxPathOutput[0]);
    file_put_contents($logFile, "npx trouvé à: $npxPath\n", FILE_APPEND);
} else {
    file_put_contents($logFile, "npx introuvable, utilisation de npm\n", FILE_APPEND);
    // Essayer avec npm
    $command = "cd $n8nDir && $nodePath ./node_modules/.bin/n8n start > $n8nDir/n8n-output.log 2>&1 &";
}

// Commande de démarrage
if (!isset($command)) {
    $command = "cd $n8nDir && $npxPath n8n start > $n8nDir/n8n-output.log 2>&1 &";
}

// Vérifier si n8n est déjà en cours d'exécution
exec("ps aux | grep 'n8n start' | grep -v grep", $psOutput);
if (!empty($psOutput)) {
    file_put_contents($logFile, "n8n semble déjà en cours d'exécution :\n" . implode("\n", $psOutput) . "\n", FILE_APPEND);
    echo "n8n est déjà en cours d'exécution.";
    exit(0);
}

// Exécuter la commande
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

// Enregistrer le résultat
file_put_contents($logFile, "Commande exécutée: $command\n", FILE_APPEND);
file_put_contents($logFile, "Code de retour: $returnCode\n", FILE_APPEND);
file_put_contents($logFile, "Sortie: " . implode("\n", $output) . "\n\n", FILE_APPEND);

// Vérifier après 5 secondes si n8n est en cours d'exécution
sleep(5);
exec("ps aux | grep 'n8n start' | grep -v grep", $checkOutput);
if (!empty($checkOutput)) {
    file_put_contents($logFile, "n8n a été démarré avec succès :\n" . implode("\n", $checkOutput) . "\n", FILE_APPEND);
    echo "n8n a été démarré avec succès.";
} else {
    file_put_contents($logFile, "n8n n'a pas pu être démarré correctement.\n", FILE_APPEND);
    echo "Échec du démarrage de n8n. Consultez les logs pour plus de détails.";
}
?> 