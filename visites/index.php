<?php
// --- CONFIGURATION EXPLICITE POUR LE DÉBOGAGE ---
// Ces lignes forcent l'affichage et la journalisation de toutes les erreurs PHP.
// Elles DOIVENT être au tout début du fichier pour être efficaces.
// UNE FOIS LE PROBLÈME RÉSOLU ET LE SERVICE STABLE, vous devrez remettre
// error_reporting(E_NONE) et display_errors à 0 pour la production,
// comme dans les versions précédentes que je vous ai fournies pour la production.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // Rapporte TOUTES les erreurs, avertissements, notices.
ini_set('log_errors', 1); // S'assure que les erreurs sont journalisées par PHP.
ini_set('error_log', '/dev/stderr'); // Envoie les logs PHP vers la sortie standard/erreur, visible dans les logs Render.
// --- FIN DE LA CONFIGURATION DE DÉBOGAGE ---

// La fonction session_destroy() ne devrait être appelée que si une session a été démarrée.
// Pour éviter un avertissement (même si error_reporting est à E_ALL, il peut être bon de l'éviter).
// Si vous n'utilisez pas du tout de sessions dans ce fichier, vous pouvez même supprimer ce bloc.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();


// --- Logique de journalisation de l'adresse IP ---
$remote_addr = getenv('REMOTE_ADDR'); // Récupère l'adresse IP du client

// Chemin correct pour le fichier de log honeypotbots.dat
// __DIR__ est le répertoire du fichier PHP actuel (ici, nickel_).
// Donc, '/data/honeypotbots.dat' est le bon chemin si 'data' est un sous-répertoire de la racine.
// Rappel : Pour une persistance fiable sur Render, utilisez un "Persistent Disk" et écrivez dans son chemin de montage.
$log_file_path = __DIR__ . '/data/honeypotbots.dat';

// Vérifie si l'adresse IP est disponible et tente d'écrire dans le fichier de log.
if ($remote_addr) {
    // Tente d'écrire. Si cela échoue, l'erreur sera maintenant affichée et logguée grâce aux ini_set ci-dessus.
    if (file_put_contents($log_file_path, $remote_addr . ',' . date('Y-m-d H:i:s') . "\n", FILE_APPEND) === false) {
        // Enregistre explicitement une erreur si l'écriture échoue.
        error_log('[index.php] ÉCHEC d\'écriture dans le fichier de log: ' . $log_file_path);
    }
} else {
    error_log('[index.php] Adresse IP distante non disponible (getenv(\'REMOTE_ADDR\') est vide ou non défini).');
}

// --- Renvoi de la réponse 403 Forbidden ---
// Envoie le header HTTP 403 Forbidden.
header('HTTP/1.0 403 Forbidden');

// Affiche le contenu HTML de la page d'erreur 403 et termine l'exécution du script.
// Le "die()" termine l'exécution et envoie le contenu.
die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have permission to access / on this server.</p></body></html>');

// Le exit() après die() n'est pas strictement nécessaire car die() termine déjà le script,
// mais il ne fait pas de mal et peut être laissé pour la clarté.
exit();
?>