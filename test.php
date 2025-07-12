<?php
// Active toutes les erreurs pour le débogage.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/dev/stderr');

echo "<h1>Hello from test.php!</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Optionnel: tente d'écrire dans le dossier data pour tester les permissions.
$test_log_file = __DIR__ . '/data/test_log.txt';
if (file_put_contents($test_log_file, "Test access: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND) !== false) {
    echo "<p>Successfully wrote to test_log.txt in data/ folder.</p>";
} else {
    echo "<p style='color: red;'>Failed to write to test_log.txt in data/ folder. Check permissions!</p>";
    error_log('[test.php] Failed to write to ' . $test_log_file);
}

?>