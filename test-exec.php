<?php
// Script de test pour vérifier les permissions d'exécution

// Informations sur PHP
echo "<h1>Test d'exécution et d'environnement PHP</h1>";
echo "<h2>Informations PHP</h2>";
echo "<p>Version PHP: " . phpversion() . "</p>";
echo "<p>Extensions chargées: " . implode(", ", get_loaded_extensions()) . "</p>";
echo "<p>Fonction exec() disponible: " . (function_exists('exec') ? 'Oui' : 'Non') . "</p>";
echo "<p>Fonction shell_exec() disponible: " . (function_exists('shell_exec') ? 'Oui' : 'Non') . "</p>";

// Test d'exécution de commandes
echo "<h2>Test d'exécution</h2>";

// Créer un fichier de test
$testFile = __DIR__ . '/test-script.sh';
$testContent = "#!/bin/bash\necho 'Le script fonctionne!'";
file_put_contents($testFile, $testContent);
chmod($testFile, 0755);

echo "<p>Création du fichier test-script.sh: " . (file_exists($testFile) ? 'OK' : 'Échec') . "</p>";
echo "<p>Permissions: " . substr(sprintf('%o', fileperms($testFile)), -4) . "</p>";

// Tester l'exécution avec exec()
if (function_exists('exec')) {
    $output = array();
    $return_var = 0;
    exec($testFile . " 2>&1", $output, $return_var);
    echo "<p>Test exec(): Code de retour: $return_var</p>";
    echo "<p>Test exec(): Sortie: " . implode("<br>", $output) . "</p>";
} else {
    echo "<p>Test exec(): Non disponible</p>";
}

// Tester l'exécution avec shell_exec()
if (function_exists('shell_exec')) {
    $output = shell_exec($testFile . " 2>&1");
    echo "<p>Test shell_exec(): Sortie: " . nl2br($output) . "</p>";
} else {
    echo "<p>Test shell_exec(): Non disponible</p>";
}

// Diagnostiquer le répertoire d'installation de n8n
echo "<h2>Diagnostic du répertoire n8n</h2>";
$n8nDir = "/www/projet/n8n/";
echo "<p>Répertoire n8n existe: " . (is_dir($n8nDir) ? 'Oui' : 'Non') . "</p>";

if (is_dir($n8nDir)) {
    $files = scandir($n8nDir);
    echo "<p>Fichiers dans le répertoire n8n: " . implode(", ", array_diff($files, array('.', '..'))) . "</p>";
    
    // Tester les permissions des scripts
    $scripts = glob($n8nDir . "*.sh");
    echo "<p>Scripts trouvés: " . implode(", ", array_map('basename', $scripts)) . "</p>";
    
    foreach ($scripts as $script) {
        echo "<p>Script: " . basename($script) . " - Permissions: " . substr(sprintf('%o', fileperms($script)), -4) . "</p>";
        
        // Tester l'exécution du script
        if (function_exists('exec')) {
            $output = array();
            $return_var = 0;
            exec("cd $n8nDir && ls -la " . basename($script) . " 2>&1", $output, $return_var);
            echo "<p>Test exec pour " . basename($script) . ": Code de retour: $return_var</p>";
            echo "<p>Détails: " . implode("<br>", $output) . "</p>";
        }
    }
}

// Information sur l'utilisateur
echo "<h2>Information sur l'utilisateur</h2>";
echo "<p>Utilisateur PHP: " . exec('whoami') . "</p>";
echo "<p>Répertoire courant: " . getcwd() . "</p>";
echo "<p>UID: " . getmyuid() . ", GID: " . getmygid() . "</p>";

// Supprimer le fichier de test
if (file_exists($testFile)) {
    unlink($testFile);
    echo "<p>Suppression du fichier test-script.sh: OK</p>";
}
?> 