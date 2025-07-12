<?php
// --- Configuration pour le DÉBOGAGE (À SUPPRIMER EN PRODUCTION) ---
// Ces lignes vont afficher toutes les erreurs directement sur la page.
// UNE FOIS LE PROBLÈME RÉSOLU ET LE SERVICE STABLE, DÉSACTIVEZ-LES OU SUPPRIMEZ-LES.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN DE LA CONFIGURATION DE DÉBOGAGE ---

// RETIRER CETTE LIGNE ! Un header 500 ne doit être envoyé que s'il y a une erreur RÉELLE et non voulue.
// header('HTTP/1.1 500 Internal Server Error');

// Désactive l'affichage des erreurs pour les fonctions sensibles si des erreurs se produisent APRES ce point,
// mais les lignes ci-dessus (pour le débogage) priment.
@ini_set('html_errors','0');
@ini_set('display_errors','0'); // Ceci sera ignoré si display_errors est déjà à 1
@ini_set('display_startup_errors','0'); // Ceci sera ignoré si display_startup_errors est déjà à 1
@ini_set('log_errors','0'); // Utile si vous ne voulez pas logguer les erreurs en plus de les afficher.

// Vérifie si la requête est une requête GET et si le paramètre 'token' est présent.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    // Décode le token Base64 de l'URL.
    // Utiliser rawurldecode si le jeton n'est pas urlencodé avec urlencode() mais avec rawurlencode()
    // Dans notre index.php corrigé, nous utilisons urlencode(), donc urldecode est correct.
    $encoded_token_param = $_GET['token'];
    $decoded_token_base64 = urldecode($encoded_token_param);
    $token = base64_decode($decoded_token_base64);

    // --- Amélioration du parsing du token ---
    // Le token original est UserAgent + IP + Date (e.g., Mozilla/5.0...10.223.218.242025:Jul:Sat).
    // Cette méthode est plus robuste que les strpos() basés sur des positions fixes.
    // Elle cherche les motifs regex pour extraire l'IP et la date.
    $user_agent = 'N/A';
    $remote_addr = 'N/A';
    $current_date = 'N/A';

    // Recherche de l'année (e.g., 2025) comme point de repère pour la date.
    // L'expression régulière cherche une séquence de 4 chiffres, puis 3 lettres, puis 3 lettres (ex: 2025:Jul:Sat)
    // et capture sa position (PREG_OFFSET_CAPTURE).
    if (preg_match('/(\d{4}:\w{3}:\w{3})/', $token, $matches, PREG_OFFSET_CAPTURE)) {
        $date_str_with_year = $matches[1][0]; // Ex: 2025:Jul:Sat
        $date_offset = $matches[1][1];

        $current_date = $date_str_with_year;

        // L'adresse IP est supposée se trouver avant la date.
        // On prend la partie du token avant la date pour y chercher l'IP et l'User-Agent.
        $potential_ip_date_part = substr($token, 0, $date_offset);

        // Recherche d'une adresse IP (ex: 192.168.1.1 ou 10.0.0.1).
        // L'expression régulière cherche quatre groupes de 1 à 3 chiffres séparés par des points.
        if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $potential_ip_date_part, $ip_matches, PREG_OFFSET_CAPTURE)) {
            $remote_addr = $ip_matches[1][0]; // L'IP trouvée
            // L'User-Agent est tout ce qui précède l'adresse IP dans cette partie.
            $user_agent = substr($potential_ip_date_part, 0, $ip_matches[1][1]);
        } else {
            // Si aucune IP n'est trouvée, l'intégralité de la partie est considérée comme l'User-Agent.
            $user_agent = $potential_ip_date_part;
        }
    } else {
        // Si le motif de date (ex: 2025) n'est pas trouvé du tout, tout le token est considéré comme l'User-Agent.
        $user_agent = $token;
    }

    // Configuration des informations Telegram.
    // CES VARIABLES D'ENVIRONNEMENT DOIVENT ÊTRE DÉFINIES SUR LE SERVICE RENDER.
    $telegramBotToken = getenv('TELEGRAM_TOKEN');
    $telegramChatID = getenv('TELEGRAM_CHAT_ID');

    // Construire le message à envoyer à Telegram.
    // Utilisation de l'opérateur de coalescence null (??) pour s'assurer qu'il y a toujours une valeur par défaut.
    $message = "Nouvelle connexion (via nkl-log.php):\n"
             . "User-Agent: " . ($user_agent ?: 'N/A') . "\n"
             . "IP: " . ($remote_addr ?: 'N/A') . "\n"
             . "Date: " . ($current_date ?: 'N/A') . "\n"
             . "Token brut reçu: " . ($token ?: 'N/A'); // Utile pour déboguer le parsing du token

    // Vérifier si le token et le chat ID Telegram sont définis et non vides.
    if (!empty($telegramBotToken) && !empty($telegramChatID)) {
        $telegramApiUrl = "https://api.telegram.org/bot{$telegramBotToken}/sendMessage";

        $data = [
            'chat_id' => $telegramChatID,
            'text' => $message,
            'disable_notification' => true, // Optionnel: ne pas notifier l'utilisateur
            'parse_mode' => 'HTML', // Permet l'utilisation de balises HTML dans le message si désiré
        ];

        // Initialiser cURL pour envoyer la requête à l'API Telegram.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramApiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        // Utiliser http_build_query() pour encoder correctement les données POST d'un tableau associatif.
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt(CURLOPT_RETURNTRANSFER, true); // Retourne la réponse au lieu de l'afficher
        curl_setopt(CURLOPT_TIMEOUT, 10); // Timeout de 10 secondes pour éviter de bloquer indéfiniment

        $response = curl_exec($ch); // Exécute la requête cURL

        // Vérifier les erreurs cURL.
        if (curl_errno($ch)) {
            // En cas d'erreur cURL, écrire dans les logs du serveur.
            error_log('[nkl-log.php] Erreur cURL lors de l\'envoi à Telegram : ' . curl_error($ch));
        }

        curl_close($ch); // Ferme la session cURL.

        // Décoder la réponse JSON de Telegram pour voir si le message a été envoyé avec succès.
        $jsonResponse = json_decode($response, true);
        if ($jsonResponse && $jsonResponse['ok'] === false) {
            error_log('[nkl-log.php] Erreur API Telegram : ' . ($jsonResponse['description'] ?? 'Description inconnue') . ' (Réponse brute: ' . $response . ')');
        } else if ($jsonResponse && $jsonResponse['ok'] === true) {
            error_log('[nkl-log.php] Message Telegram envoyé avec succès.');
        } else {
            error_log('[nkl-log.php] Réponse Telegram inattendue ou non-JSON: ' . $response);
        }
    } else {
        // Ce message sera dans les logs du serveur si les variables d'environnement sont manquantes.
        error_log('[nkl-log.php] Variables TELEGRAM_TOKEN ou TELEGRAM_CHAT_ID non définies ou vides.');
    }
} else {
    // Si la requête n'est pas GET ou le token est absent.
    // Enregistre les détails de la requête non autorisée pour débogage.
    error_log('[nkl-log.php] Accès non autorisé ou paramètre "token" manquant. Requête reçue: ' . json_encode($_SERVER));
}

// Toujours renvoyer une réponse HTTP 200 OK si le script a traité la requête.
// Cela indique au client que la requête a été reçue et traitée (même si l'envoi à Telegram a échoué).
header('HTTP/1.1 200 OK');
echo "Log traité. Les informations ont été envoyées (ou tentées d'être envoyées) à Telegram.";
?>