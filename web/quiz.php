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

// Le timer démarre au chargement de la page, on récupère l'heure de début en PHP.
$start_time_answer = floor(microtime(true) * 1000); 

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
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Styles personnalisés qui ne peuvent pas être facilement faits avec des utilitaires Tailwind */
        
        /* Conteneur principal */
        body { 
            /*max-width: 1200px; */
            margin: 40px auto; 
            padding: 20px; 
            border: 1px solid #ccc;
            text-align: center;
        }

        /* Masquer le radio bouton par défaut */
        input[type="radio"] { 
            position: absolute; 
            opacity: 0; 
            pointer-events: none;
        }

        /* Feedback visuel lorsque le bouton radio est coché */
        input[type="radio"]:checked + label {
            /* Utilisation d'une ombre pour simuler une bordure de sélection */
            box-shadow: 0 0 0 4px #000; 
            transform: translateY(-2px);
        }
        
        /* Assure que les boîtes de réponse sont de hauteur égale et prennent toute la place */
        .answer-label { 
            display: block; 
            height: 100%;
            transition: all 0.15s ease-in-out; 
            /* Forcer le texte en blanc par défaut sur les couleurs sombres de Tailwind */
            color: white; 
        }
        
        /* Exception pour la couleur jaune pour avoir un meilleur contraste */
        .box-3 {
             color: #1f2937; /* Texte gris foncé */
        }
        
        .max-height-image {
            max-height: 300px;
            width: auto;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body onload="checkQuestionAndStart()" class="font-sans"> <h1 class="text-2xl font-bold mb-4">Quiz - Choix de la Question</h1>

    <form id="selectForm" action="<?php echo $self_script; ?>" method="GET" class="mb-4">
        <label for="question_select" class="block mb-2 font-medium">Choisir une question :</label>
        <select name="id" id="question_select" onchange="this.form.submit()" class="p-2 border border-gray-300 rounded-md w-full">
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

    <hr class="my-6">

    <?php if ($question): ?>
        
        <div class="text-center mb-6">
            <h2 id="timer-display" class="text-red-600 text-3xl font-extrabold">Initialisation du Quiz...</h2> 
            
            <input type="hidden" id="time-limit" value="<?php echo (int)$question['time_question']; ?>">
        </div>
        
        <form action="traitement_reponse.php" method="POST" id="quiz-form">
            
            <input type="hidden" name="id_question" value="<?php echo $question['id_question']; ?>">
            <input type="hidden" name="start_time_answer" value="<?php echo $start_time_answer; ?>">

            <div class="question-text text-4xl font-bold mb-5">
                <?php echo htmlspecialchars($question['text_question']); ?>
            </div>
            <div class="question-image mb-5 text-center">
                <img src="<?php echo htmlspecialchars($question['image_question']); ?>" alt="Image de la question" class="max-height-image mx-auto rounded-lg">
            </div>

            <fieldset id="answer-options-fieldset" disabled> 
                <div class="answer-options flex flex-wrap gap-4">
                    
                    <div class="answer-option-pair text-4xl flex gap-4 w-full">
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                            <div class="answer-option flex-1 min-w-0">
                                <input 
                                    type="radio" 
                                    id="rep_<?php echo $i; ?>" 
                                    name="reponse_choisie" 
                                    value="<?php echo $i; ?>" 
                                    required
                                >
                                <?php
                                    $color_class = '';
                                    switch ($i) {
                                        case 1: $color_class = 'bg-blue-500 border-blue-700 hover:bg-blue-600 box-1'; break;
                                        case 2: $color_class = 'bg-green-500 border-green-700 hover:bg-green-600 box-2'; break;
                                    }
                                ?>
                                <label 
                                    for="rep_<?php echo $i; ?>" 
                                    class="answer-label <?= $color_class ?> p-4 rounded-lg border-2 cursor-pointer font-semibold shadow-md transform hover:scale-[1.01]"
                                >
                                    <?php echo htmlspecialchars($question[$i . '_question']); ?>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="answer-option-pair text-4xl flex gap-4 w-full">
                        <?php for ($i = 3; $i <= 4; $i++): ?>
                            <div class="answer-option flex-1 min-w-0">
                                <input 
                                    type="radio" 
                                    id="rep_<?php echo $i; ?>" 
                                    name="reponse_choisie" 
                                    value="<?php echo $i; ?>" 
                                    required
                                >
                                <?php
                                    $color_class = '';
                                    switch ($i) {
                                        case 3: $color_class = 'bg-yellow-400 border-yellow-600 hover:bg-yellow-500 box-3'; break;
                                        case 4: $color_class = 'bg-red-500 border-red-700 hover:bg-red-600 box-4'; break;
                                    }
                                ?>
                                <label 
                                    for="rep_<?php echo $i; ?>" 
                                    class="answer-label <?= $color_class ?> p-4 rounded-lg border-2 cursor-pointer font-semibold shadow-md transform hover:scale-[1.01]"
                                >
                                    <?php echo htmlspecialchars($question[$i . '_question']); ?>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </fieldset>

            <button type="submit" id="submit-button" disabled class="mt-6 w-full py-3 bg-gray-500 text-white font-bold rounded-lg shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                Valider les réponses
            </button>

        </form>

    <?php else: ?>
        <p class="text-center text-lg mt-4">Veuillez sélectionner une question dans le menu déroulant ci-dessus. 👆</p>
    <?php endif; ?>

<script>
    // Le code JavaScript reste inchangé car il gère la logique (timer, fetch), pas le style.
    
    // Vérifie si le champ time-limit existe avant de tenter d'accéder à ses propriétés
    const timeLimitInput = document.getElementById('time-limit');

    // Récupère la durée maximale en secondes, ou 10 secondes par défaut si non défini
    let timeLeft = parseInt(timeLimitInput ? timeLimitInput.value : 10); 
    
    const timerDisplay = document.getElementById('timer-display');
    const quizForm = document.getElementById('quiz-form');
    const answerFieldset = document.getElementById('answer-options-fieldset');
    const submitButton = document.getElementById('submit-button');

    let timerInterval;

    /**
     * Vérifie si une question est chargée et démarre le processus
     */
    function checkQuestionAndStart() {
        if (timeLimitInput) {
            sendStartCommand();
        }
    }


    /**
     * Met à jour l'affichage du timer
     */
    function updateDisplay() {
        if (timerDisplay) {
            timerDisplay.textContent = `Temps restant : ${timeLeft} secondes`;
        }
    }

    // Le contenu de sendStartCommand est réutilisé pour l'auto-démarrage
    function sendStartCommand() {
        if (timerDisplay) {
            timerDisplay.textContent = "Démarrage du Quiz...";
        }

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
                console.log("Commande START envoyée avec succès. Lancement du timer.");
                // 3. Si la commande est réussie, lancer le timer local
                startTimer(); 
            } else {
                console.error("Erreur lors de la commande START:", data.message);
                if (timerDisplay) {
                     timerDisplay.textContent = "Erreur de démarrage !";
                }
            }
        })
        .catch(error => {
            console.error('Erreur réseau:', error);
            if (timerDisplay) {
                 timerDisplay.textContent = "Erreur réseau au démarrage !";
            }
        });
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
        // Le quiz ne démarre que si le formulaire existe
        if (!answerFieldset || !submitButton) return; 
        
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
        
        if (submitted) {
            sendStopCommand();
            timerDisplay.textContent = "Réponse validée !";
        } else {
            // Le temps est écoulé
            sendStopCommand();
            timerDisplay.textContent = "TEMPS ÉCOULÉ ! Soumission...";
            // Soumission automatique si le temps est écoulé (décommenter si nécessaire)
            // if (quizForm) {
            //     quizForm.submit(); 
            // }
        }
    }

    // Intercepter la soumission manuelle du formulaire pour arrêter le timer
    if (quizForm) {
        quizForm.addEventListener('submit', () => {
            endTimer(true); 
        });
    }
</script>
</body>
</html>
