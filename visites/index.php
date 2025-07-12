<?php
// Désactiver l'affichage des erreurs pour la production.
// Les erreurs PHP seront enregistrées dans les logs du serveur (Render).
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_NONE); // Ne signale aucune erreur à l'utilisateur.

// Important : Si vous utilisez session_destroy(), assurez-vous qu'une session a été démarrée
// avant, sinon cela peut générer un avertissement (silencieux ici à cause de error_reporting(E_NONE)).
// Si vous n'utilisez pas de sessions ailleurs, cette ligne peut être omise.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
} else if (session_status() === PHP_SESSION_NONE) {
    // Si aucune session n'est active, essayez de la démarrer puis de la détruire pour vider d'éventuels cookies de session.
    // Cependant, si le but est juste de ne pas avoir de session, le plus simple est de ne pas appeler session_start() du tout.
    // Pour l'instant, je retire cette logique si ce n'est pas nécessaire, pour la simplicité.
    // Si votre intention est de garantir qu'aucune session persistante n'existe, cette ligne suffit :
    // session_destroy(); // Ceci détruit la session si elle a été démarrée ailleurs.
}


// --- Logique de journalisation de l'adresse IP ---
$remote_addr = getenv('REMOTE_ADDR'); // Récupère l'adresse IP du client

// Chemin absolu vers le fichier de log honeypotbots.dat.
// __DIR__ est le répertoire du fichier PHP actuel (ici, nickel_).
// Donc, 'data/honeypotbots.dat' est correct si 'data' est un sous-répertoire de 'nickel_'.
// ATTENTION : Sur Render, pour une persistance fiable des données après un redéploiement
// ou redémarrage du service, vous DEVEZ utiliser un "Persistent Disk" et écrire
// dans le chemin de montage de ce disque (ex: /var/data/honeypotbots.dat).
// Si vous n'utilisez pas de disque persistant, ce fichier sera perdu.
$log_file_path = __DIR__ . '/data/honeypotbots.dat';

// Vérifie si l'adresse IP est disponible et tente d'écrire dans le fichier de log.
// Il est bon de vérifier si l'écriture a réussi pour le débogage (via les logs serveur).
if ($remote_addr) {
    if (file_put_contents($log_file_path, $remote_addr . ',', FILE_APPEND) === false) {
        error_log('[index.php] Impossible d\'écrire dans le fichier de log: ' . $log_file_path);
    }
} else {
    error_log('[index.php] Adresse IP distante non disponible (getenv(\'REMOTE_ADDR\') est vide).');
}

// --- Renvoi de la réponse 403 Forbidden ---
// Envoie le header HTTP 403 Forbidden.
header('HTTP/1.0 403 Forbidden');

// Affiche le contenu HTML de la page d'erreur 403 et termine l'exécution du script.
die('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have permission to access / on this server.</p></body></html>');

// Le exit() après die() n'est pas strictement nécessaire car die() termine déjà le script,
// mais il ne fait pas de mal et peut être laissé pour la clarté.
exit();
?>