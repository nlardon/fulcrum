<?php
header('Content-Type: application/json');
// --- PARAMÈTRES ---
include('config.php');

// --- 1. PARAMÈTRES DE L'ESP32 ---
// Définissez le chemin de l'API sur l'ESP32
$esp32_endpoint = "http://$esp32_ip/command"; 

// --- 2. RÉCUPÉRATION DE LA COMMANDE DU NAVIGATEUR ---
// Lire le corps JSON envoyé par JavaScript (fetch)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$command = $data['command'] ?? 'NO_COMMAND';

if ($command === 'START' || $command === 'STOP' ) {
    // --- 3. PRÉPARATION DE LA REQUÊTE VERS L'ESP32 ---
    
    // Le contenu que l'ESP32 recevra (peut être un JSON simple)
    $payload_to_esp32 = json_encode([
        'cmd' => $command,
    ]);
    
    // --- 4. ENVOI DE LA REQUÊTE CURL À L'ESP32 ---
    $ch = curl_init($esp32_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_to_esp32);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    // Définir un timeout court pour ne pas bloquer le navigateur si l'ESP32 est hors ligne
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); 

    $response_from_esp32 = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // --- 5. GESTION DES RÉPONSES ---
    if ($http_code == 200 && $response_from_esp32 !== false) {
        echo json_encode(['status' => 'success', 'message' => 'Commande envoyée.']);
    } else {
        // En cas d'échec de la connexion ou de l'ESP32
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Erreur de communication avec l'ESP32. Code HTTP: $http_code. Erreur cURL: $curl_error"]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Commande invalide reçue.']);
}