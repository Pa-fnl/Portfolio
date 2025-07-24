<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Accès interdit.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Système de limitation des envois par IP
 * 1 email maximum par minute par IP
 */
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $lockFile = __DIR__ . '/rate_limit_' . md5($ip) . '.lock';
    
    // Vérifier si un fichier de verrouillage existe
    if (file_exists($lockFile)) {
        $lastSent = filemtime($lockFile);
        $timeDiff = time() - $lastSent;
        
        // Si moins d'1 minute s'est écoulée
        if ($timeDiff < 60) {
            $remainingTime = 60 - $timeDiff;
            return [
                'allowed' => false, 
                'message' => "Veuillez patienter {$remainingTime} secondes avant d'envoyer un nouveau message."
            ];
        }
    }
    
    // Créer/mettre à jour le fichier de verrouillage
    touch($lockFile);
    
    // Nettoyer les anciens fichiers de verrouillage (plus de 24h)
    $files = glob(__DIR__ . '/rate_limit_*.lock');
    foreach ($files as $file) {
        if (time() - filemtime($file) > 86400) { // 24 heures
            unlink($file);
        }
    }
    
    return ['allowed' => true];
}

/**
 * Validation et nettoyage des données
 */
function validateAndSanitizeData() {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
        'radio' => $_POST['radio'] ?? '',
        'recaptcha_token' => $_POST['g-recaptcha-response'] ?? ''
    ];
    
    // Validation des champs requis
    $errors = [];
    if (empty($data['name'])) $errors[] = 'nom';
    if (empty($data['email'])) $errors[] = 'email';
    if (empty($data['phone'])) $errors[] = 'téléphone';
    if (empty($data['message'])) $errors[] = 'message';
    if (empty($data['recaptcha_token'])) $errors[] = 'vérification de sécurité';
    
    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => 'Champs manquants : ' . implode(', ', $errors)
        ];
    }
    
    // Validation de l'email
    $data['email'] = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$data['email']) {
        return [
            'valid' => false,
            'message' => 'Adresse email invalide'
        ];
    }
    
    // Nettoyage des données
    $data['name'] = htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8');
    $data['phone'] = htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8');
    $data['message'] = htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8');
    $data['radio'] = htmlspecialchars($data['radio'], ENT_QUOTES, 'UTF-8');
    
    // Limitation de taille
    if (strlen($data['message']) > 5000) {
        return [
            'valid' => false,
            'message' => 'Le message est trop long (maximum 5000 caractères)'
        ];
    }
    
    return ['valid' => true, 'data' => $data];
}

/**
 * Vérification reCAPTCHA v3
 */
function verifyRecaptcha($token) {
    $secret = RECAPTCHA_SECRET;
    $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
    
    $postData = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData,
            'timeout' => 10
        ]
    ]);

    $response = @file_get_contents($verifyURL, false, $context);
    if ($response === false) {
        return [
            'valid' => false,
            'message' => 'Impossible de vérifier le reCAPTCHA. Veuillez réessayer.'
        ];
    }

    $data = json_decode($response);
    if (!$data || !$data->success) {
        return [
            'valid' => false,
            'message' => 'Échec de la vérification de sécurité'
        ];
    }

    // Vérifier le score (pour reCAPTCHA v3)
    if (isset($data->score) && $data->score < 0.5) {
        return [
            'valid' => false,
            'message' => 'Vérification de sécurité échouée'
        ];
    }

    return ['valid' => true];
}

/**
 * Envoi de l'email via PHPMailer
 */
function sendEmail($data) {
    try {
        $mail = new PHPMailer(true);
        
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = 'ssl0.ovh.net';
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        
        // Timeout plus long pour éviter les erreurs
        $mail->Timeout = 60;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Configuration du message
        $mail->setFrom('SMTP_USERNAME', 'Site Web Paul Fenelon');
        $mail->addReplyTo($data['email'], $data['name']);
        $mail->addAddress('SMTP_USERNAME');

        $mail->isHTML(true);
        $mail->Subject = 'Nouveau message de ' . $data['name'];
        
        $contactPref = $data['radio'] ? 
            ($data['radio'] === 'par_mail' ? 'Par email' : 'Par téléphone') : 
            'Non spécifiée';
            
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px;'>
                <h2 style='color: #333;'>Nouveau message depuis votre site web</h2>
                <div style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>
                    <p><strong>Nom :</strong> {$data['name']}</p>
                    <p><strong>Email :</strong> {$data['email']}</p>
                    <p><strong>Téléphone :</strong> {$data['phone']}</p>
                    <p><strong>Préférence de contact :</strong> {$contactPref}</p>
                </div>
                <div style='margin-top: 20px;'>
                    <h3 style='color: #333;'>Message :</h3>
                    <div style='background: white; padding: 15px; border-left: 4px solid #007bff; border-radius: 3px;'>
                        " . nl2br($data['message']) . "
                    </div>
                </div>
                <div style='margin-top: 20px; font-size: 12px; color: #666;'>
                    <p>Message envoyé le " . date('d/m/Y à H:i:s') . "</p>
                    <p>IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'Inconnue') . "</p>
                </div>
            </div>
        ";

        $mail->send();
        return [
            'success' => true,
            'message' => 'Message envoyé avec succès ! Je vous répondrai dans les plus brefs délais.'
        ];
        
    } catch (Exception $e) {
        // Log l'erreur pour le debug (optionnel)
        error_log("Erreur envoi email: " . $e->getMessage());
        
        // Messages d'erreur plus user-friendly
        if (strpos($e->getMessage(), 'quota') !== false || strpos($e->getMessage(), '550') !== false) {
            return [
                'success' => false,
                'message' => 'Service temporairement indisponible. Veuillez réessayer dans quelques minutes ou me contacter directement à dev@paulfenelon.fr ou linkdIn'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erreur lors de l\'envoi. Veuillez réessayer ou me contacter directement à dev@paulfenelon.fr ou linkdIn'
        ];
    }
}

// === TRAITEMENT PRINCIPAL ===

try {
    // Vérification de la méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Vérification de la limitation de taux
    $rateLimit = checkRateLimit();
    if (!$rateLimit['allowed']) {
        echo json_encode(['success' => false, 'message' => $rateLimit['message']]);
        exit;
    }

    // Validation des données
    $validation = validateAndSanitizeData();
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'message' => $validation['message']]);
        exit;
    }

    $data = $validation['data'];

    // Vérification reCAPTCHA
    $recaptcha = verifyRecaptcha($data['recaptcha_token']);
    if (!$recaptcha['valid']) {
        echo json_encode(['success' => false, 'message' => $recaptcha['message']]);
        exit;
    }

    // Envoi de l'email
    $result = sendEmail($data);
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue. Veuillez réessayer.'
    ]);
}
?>
