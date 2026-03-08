<?php
// --- PARAMÈTRES DE CONNEXION À LA BASE DE DONNÉES ---
include('config.php');

$conn = null;
// Définir le chemin du script actuel pour la soumission du formulaire
$self_script = htmlspecialchars($_SERVER['PHP_SELF']);

// Définir le chemin du script actuel pour la soumission du formulaire
$self_script = htmlspecialchars($_SERVER['PHP_SELF']);

// --- FONCTION DE CONNEXION ---
function connect_db($servername, $username, $password, $dbname) {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        // En production, vous voudriez logger l'erreur plutôt que de l'afficher
        die("Erreur de connexion à la base de données."); 
    }
    return $conn;
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
// La connexion reste ouverte pour la requête suivante si nécessaire

// --- 3. RÉCUPÉRATION DE LA QUESTION À AFFICHER ---
// Récupère l'ID via GET. Par défaut (si rien n'est sélectionné), on prend le premier ID de la liste
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 
               (!empty($questions_list) ? $questions_list[0]['id_question'] : null);

$question = null;

if ($selected_id) {
    // Utilisation d'une REQUÊTE PRÉPARÉE pour la sécurité
    $stmt = mysqli_prepare($conn, "SELECT 
                                    id_question, 
                                    text_question, 
                                    1_question, 
                                    2_question, 
                                    3_question, 
                                    4_question
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
        /* Styles CSS ici */
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; }
        .question-text { font-size: 1.2em; font-weight: bold; margin-bottom: 20px; }
        .answer-option { margin-bottom: 10px; }
        label { display: block; padding: 8px; border: 1px solid #eee; cursor: pointer; border-radius: 4px; }
        label:hover { background-color: #f5f5f5; }
        input[type="radio"] { margin-right: 10px; }
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
        
        <form action="traitement_reponse.php" method="POST">
            
            <input type="hidden" name="id_question" value="<?php echo $question['id_question']; ?>">

            <div class="question-text">
                <?php echo htmlspecialchars($question['text_question']); ?>
            </div>

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

            <button type="submit">Valider ma réponse</button>

        </form>

    <?php else: ?>
        <p>Aucune question sélectionnée ou trouvée. Veuillez faire un choix dans le menu ci-dessus.</p>
    <?php endif; ?>

</body>
</html>
