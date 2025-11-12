<?php
// --- PARAMÈTRES ---
include('config.php');

// If GET request, show simple HTML with two boutons.
// If POST (or JSON body), respond JSON and forward to ESP32.

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Affiche l'interface avec deux boutons
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Test ESP32 - START / STOP</title>
        <style>
            body{font-family:Arial,Helvetica,sans-serif;padding:20px}
            button{padding:10px 20px;margin:8px;font-size:16px}
            #result{white-space:pre-wrap;border:1px solid #ddd;padding:10px;margin-top:12px}
        </style>
    </head>
    <body>
        <h1>Test ESP32</h1>
        <p>Cliquer pour envoyer la commande au device :</p>
        <button id="start">START</button>
        <button id="stop">STOP</button>
        <div id="result"></div>

        <script>
        async function send(cmd) {
            const resEl = document.getElementById('result');
            resEl.textContent = 'Envoi de la commande ' + cmd + ' ...';
            try {
                const r = await fetch(location.href, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({command: cmd})
                });
                const json = await r.json();
                resEl.textContent = JSON.stringify(json, null, 2);
            } catch (e) {
                resEl.textContent = 'Erreur réseau: ' + e;
            }
        }
        document.getElementById('start').addEventListener('click', ()=>send('START'));
        document.getElementById('stop').addEventListener('click', ()=>send('STOP'));
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Pour les requêtes POST -> renvoyer JSON
header('Content-Type: application/json');

// Récupère la commande depuis $_POST ou depuis un corps JSON
$raw = file_get_contents('php://input');
$command = null;

if (!empty($_POST['command'])) {
    $command = strtoupper(trim($_POST['command']));
} elseif ($raw) {
    $dec = json_decode($raw, true);
    if (is_array($dec)) {
        if (!empty($dec['command'])) $command = strtoupper(trim($dec['command']));
        elseif (!empty($dec['cmd'])) $command = strtoupper(trim($dec['cmd']));
    }
}

// Valeur par défaut si aucune fournie (optionnel)
if ($command === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Aucune commande fournie.']);
    exit;
}

if ($command === 'START' || $command === 'STOP') {
    // --- 1. PARAMÈTRES DE L'ESP32 ---
    // Définissez le chemin de l'API sur l'ESP32
    $esp32_endpoint = "http://$esp32_ip/command";

    // --- 3. PRÉPARATION DE LA REQUÊTE VERS L'ESP32 ---
    $payload_to_esp32 = json_encode([
        'cmd' => $command,
    ]);

    // --- 4. ENVOI DE LA REQUÊTE CURL À L'ESP32 ---
    $ch = curl_init($esp32_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_to_esp32);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    $response_from_esp32 = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // --- 5. GESTION DES RÉPONSES ---
    if ($http_code == 200 && $response_from_esp32 !== false) {
        echo json_encode(['status' => 'success', 'message' => 'Commande envoyée.', 'esp32_response' => $response_from_esp32]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Erreur de communication avec l'ESP32. Code HTTP: $http_code. Erreur cURL: $curl_error"]);
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Commande invalide reçue.']);
}