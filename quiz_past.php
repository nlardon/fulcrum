<?php
// --- PARAMÈTRES DE CONNEXION À LA BASE DE DONNÉES ---
include('config.php');

// Définir le chemin du script actuel pour la soumission du formulaire
$self_script = htmlspecialchars($_SERVER['PHP_SELF']);

// --- FONCTION DE CONNEXION ---
function connect_db($servername, $username, $password, $dbname) {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Erreur de connexion à la base de données."); 
    }
    return $conn;
}

$start_time_answer = floor(microtime(true) * 1000);
function setTime() {
        $start_time_answer = floor(microtime(true) * 1000);
    }

// --- 2. RÉCUPÉRATION DE LA LISTE DES QUESTIONS POUR LE MENU ---
$conn = connect_db($servername, $username, $password, $dbname);

$sql_list = "SELECT id_question, name_question FROM question ORDER BY id_question ASC";
$result_list = mysqli_query($conn, $sql_list);
$questions_list = [];

if ($result_list) {
    while ($row = mysqli_fetch_assoc($result_list)) {
        $questions_list[] = $row;
    }
}

// --- 3. RÉCUPÉRATION DE LA QUESTION À AFFICHER ---
// Récupère l'ID via GET.
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 
               (!empty($questions_list) ? $questions_list[0]['id_question'] : null);

$question = null;

if ($selected_id) {
    // Requête préparée pour la sécurité (contre les injections SQL)
    $stmt = mysqli_prepare($conn, "SELECT 
                                    id_question, 
                                    text_question, 
                                    1_question, 
                                    2_question, 
                                    3_question, 
                                    4_question,
                                    time_question,
                                    point_question,
                                    image_question 
                                  FROM question 
                                  WHERE id_question = ?");
    
    mysqli_stmt_bind_param($stmt, "i", $selected_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $question = mysqli_fetch_assoc($result);
    }
    
    mysqli_stmt_close($stmt);
}

mysqli_close($conn); // Fermeture définitive de la connexion
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quiz - Sélection de Question</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; }
        .question-text { font-size: 1.2em; font-weight: bold; margin-bottom: 20px; }
        .answer-option { margin-bottom: 10px; }
        label { display: block; padding: 8px; border: 1px solid #eee; cursor: pointer; border-radius: 4px; }
        label:hover { background-color: #f5f5f5; }
        input[type="radio"] { margin-right: 10px; }
        #timer-display { color: #d9534f; font-size: 1.8em; }
        #start-button { padding: 10px 20px; font-size: 1.1em; cursor: pointer; }
        .max-height-image {
            max-height: 300px; /* Set the maximum height */
            width: auto;       /* Allow the width to adjust proportionally */
            display: block;    /* Optional: helps with layout */
            border: 1px solid #ccc; /* Optional: adds a border for visibility */
        }
    </style>
</head>
<body>

    <h1>Quiz - Choix de la Question</h1>

    <form id="selectForm" action="<?php echo $self_script; ?>" method="GET">
        <label for="question_select">Choisir une question :</label>
        <select name="id" id="question_select" onchange="this.form.submit()">
            <option value="">-- Sélectionner une question --</option>
            <?php foreach ($questions_list as $q): ?>
                <option 
                    value="<?php echo $q['id_question']; ?>"
                    <?php if ($q['id_question'] == $selected_id) echo 'selected'; ?>
                >
                    <?php echo htmlspecialchars($q['id_question'] . ' - ' . $q['name_question']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <hr>

    <?php if ($question): ?>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 id="timer-display">Prêt à Démarrer ?</h2> 
            
            <input type="hidden" id="time-limit" value="<?php echo (int)$question['time_question']; ?>">
          
            <button id="start-button" onclick="sendStartCommand();">Démarrer la Question</button>
        </div>
        
        <form action="traitement_reponse.php" method="POST" id="quiz-form">
            
            <input type="hidden" name="id_question" value="<?php echo $question['id_question']; ?>">
            <input type="hidden" name="start_time_answer" value="<?php echo $start_time_answer; ?>">

            <div class="question-text">
                <?php echo htmlspecialchars($question['text_question']); ?>
            </div>
            <div class="question-image">
                <img src="<?php echo htmlspecialchars($question['image_question']); ?>" alt="A descriptive name for the image" class="max-height-image">
            </div>

            <fieldset id="answer-options-fieldset" disabled> 
                <div class="answer-options">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="answer-option">
                            <label for="rep_<?php echo $i; ?>">
                                <input 
                                    type="radio" 
                                    id="rep_<?php echo $i; ?>" 
                                    name="reponse_choisie" 
                                    value="<?php echo $i; ?>" 
                                    required
                                >
                                <?php echo htmlspecialchars($question[$i . '_question']); ?>
                            </label>
                        </div>
                    <?php endfor; ?>
                </div>
            </fieldset>

            <button type="submit" id="submit-button" disabled>Valider les réponses</button>

        </form>

    <?php else: ?>
        <p>Veuillez sélectionner une question dans le menu déroulant ci-dessus. 👆</p>
    <?php endif; ?>

<script>
    // Vérifie si le champ time-limit existe avant de tenter d'accéder à ses propriétés
    const timeLimitInput = document.getElementById('time-limit');

    // Récupère la durée maximale en secondes, ou 10 secondes par défaut si non défini
    let timeLeft = parseInt(timeLimitInput ? timeLimitInput.value : 10); 
    
    const timerDisplay = document.getElementById('timer-display');
    const startButton = document.getElementById('start-button');
    const quizForm = document.getElementById('quiz-form');
    const answerFieldset = document.getElementById('answer-options-fieldset');
    const submitButton = document.getElementById('submit-button');

    let timerInterval;

    /**
     * Met à jour l'affichage du timer
     */
    function updateDisplay() {
        if (timerDisplay) {
            timerDisplay.textContent = `Temps restant : ${timeLeft} secondes`;
        }


    }

        
    
    function sendStartCommand() {
        // 1. Désactiver le bouton et l'afficher comme 'En cours...'
        startButton.disabled = true;
        startButton.textContent = "Démarrage en cours...";

        // 2. Envoyer la requête POST
        fetch('relais_esp32.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                command: 'START'
                    })
        })
        .then(response => response.json())
        .then(data => {
            if (true) { ////////// data.status === 'success' remettre ça en condition
                console.log("Commande START envoyée avec succès.");
                // 3. Si la commande est réussie, lancer le timer local
                startTimer(); 
            } else {
                console.error("Erreur lors de la commande START:", data.message);
                startButton.textContent = "Erreur !";
                startButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
            startButton.textContent = "Erreur réseau !";
            startButton.disabled = false;
        });

        setTime();

    }

    function sendStopCommand() {
        // 1. Envoyer la requête POST
        fetch('relais_esp32.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                command: 'STOP'
                    })
        })
        .then(response => response.json())
        .then(data => {
            if (true) { ////////// data.status === 'success' remettre ça en condition
                console.log("Commande STOP envoyée avec succès.");
            } else {
                console.error("Erreur lors de la commande STOP:", data.message);
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
        });
    }
    /**
     * Démarre le compte à rebours
     */
    function startTimer() {
        // Le quiz ne démarre que si le bouton et le formulaire existent
        if (!startButton || !answerFieldset || !submitButton) return; 
        
        startButton.disabled = true;
        answerFieldset.disabled = false;
        submitButton.disabled = false;
    
        updateDisplay(); 
        
        timerInterval = setInterval(() => {
            timeLeft--;
            updateDisplay();

            if (timeLeft <= 0) {
                clearInterval(timerInterval); 
                endTimer(false); // Temps écoulé
            }
        }, 1000);
    }

    /**
     * Gère la fin du timer (expiré ou validation manuelle)
     * @param {boolean} submitted - True si l'utilisateur a soumis manuellement.
     */
    function endTimer(submitted) {
        clearInterval(timerInterval); 
        
        // Désactiver toutes les interactions
        answerFieldset.disabled = true;
        //submitButton.disabled = true;
        
        if (submitted) {
            sendStopCommand();
            timerDisplay.textContent = "Réponse validée !";
        } else {
            // Le temps est écoulé
            sendStopCommand();
            timerDisplay.textContent = "TEMPS ÉCOULÉ ! Soumission...";
            // Soumission automatique si le temps est écoulé
            //if (quizForm) {
            //    quizForm.submit(); 
            //}
        }
    }

    // Intercepter la soumission manuelle du formulaire pour arrêter le timer
    if (quizForm) {
        quizForm.addEventListener('submit', () => {
            endTimer(true); 
        });
    }

    // Initialiser l'affichage si une question est chargée
    if (timeLimitInput) {
        updateDisplay();
    }
</script>
</body>
</html>
