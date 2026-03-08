<?php
// --- PARAMÈTRES DE CONNEXION À LA BASE DE DONNÉES ---
include('config.php');

// --- FONCTION DE CONNEXION ---
function connect_db($servername, $username, $password, $dbname) {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Erreur de connexion à la base de données : " . mysqli_connect_error()); 
    }
    return $conn;
}

/**
 * Récupère la liste des quiz uniques (ID et Nom) depuis la table quiz.
 */
function get_all_quizzes($conn) {
    $quizzes = [];
    // Nous sélectionnons les ID de test distincts et utilisons le premier nom trouvé pour ce test.
    $sql = "SELECT DISTINCT `id_test`, COALESCE(NULLIF(`name_quiz`, ''), CONCAT('Quiz #', `id_test`)) AS name 
            FROM `quiz` 
            ORDER BY `id_test` ASC";
    $result = mysqli_query($conn, $sql);
    
    if ($result) { 
        while ($row = mysqli_fetch_assoc($result)) { 
            // Stocke l'ID et le nom dans un format simple
            $quizzes[] = ['id' => $row['id_test'], 'name' => $row['name']];
        } 
    }
    return $quizzes;
}


/**
 * Réinitialise la base de données pour un quiz spécifique.
 * @param mysqli_connection $conn La connexion à la base de données.
 * @param int $quiz_id L'ID du quiz à réinitialiser.
 * @return bool True en cas de succès, False sinon.
 */
function reset_db($conn, $quiz_id) {
    if (!$conn) {
        return false;
    }

    // Vide les réponses en cours et stockées (suppression générale pour l'instant)
    mysqli_query($conn, "DELETE FROM `current_answer`");
    mysqli_query($conn, "DELETE FROM `store_answer`");

    // Réinitialise l'état des questions UNIQUEMENT pour le quiz sélectionné
    $sql_quiz = "UPDATE `quiz` SET `done_question` = 0 WHERE `id_test` = ?";
    
    $stmt = $conn->prepare($sql_quiz);
    $stmt->bind_param("i", $quiz_id);
    $success = $stmt->execute();
    $stmt->close();
    
    // Réinitialise les scores des utilisateurs
    mysqli_query($conn, "UPDATE `user` SET `point_user` = 0");

    return $success; 
}


    $conn = connect_db($servername, $username, $password, $dbname);
    $all_quizzes = get_all_quizzes($conn);
    
    // Si la liste est vide, on force un ID par défaut (1)
    if (empty($all_quizzes)) {
        $all_quizzes = [['id' => 1, 'name' => 'Quiz par défaut (Vide)']];
        $selected_quiz_id = 1;
        $current_quiz_name = 'Quiz par défaut (Vide)';
    } else {
        // Récupère l'ID du quiz depuis l'URL (GET) ou par défaut le premier de la liste
        $selected_quiz_id = isset($_REQUEST['quiz_id']) ? (int)$_REQUEST['quiz_id'] : $all_quizzes[0]['id'];
        
        // Trouve le nom du quiz correspondant
        $quiz_index = array_search($selected_quiz_id, array_column($all_quizzes, 'id'));
        $current_quiz_name = ($quiz_index !== false) ? $all_quizzes[$quiz_index]['name'] : 'Quiz Inconnu';
    }
    $sql = "UPDATE `variable` SET `value_var`= ". $selected_quiz_id . " WHERE `id_var` = 1;";
    mysqli_query($conn, $sql);

    $status_message = ""; 

    // Requête pour trouver la prochaine question non faite dans le quiz sélectionné
    $sql = "SELECT q.`id_question_quiz` FROM `quiz` q WHERE q.`id_test` = ? AND q.`done_question` = 0 ORDER BY q.`order_quiz` ASC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_quiz_id);
    $stmt->execute();
    $result_next = $stmt->get_result();
    $row_next = $result_next->fetch_assoc();
    $stmt->close();
    
    $next_question = isset($row_next["id_question_quiz"]) ? $row_next["id_question_quiz"] : null;


    // --- GESTION DE LA SOUMISSION DU FORMULAIRE (RESET DB) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'reset_db') {
        
        $action_quiz_id = (int)$_POST['quiz_id_action'];

        if (reset_db($conn, $action_quiz_id)) {
            $status_message = "✅ Quiz **" . $current_quiz_name . "** réinitialisé avec succès.";
            
            // Re-requête pour la prochaine question après reset
            $stmt = $conn->prepare("SELECT q.`id_question_quiz` FROM `quiz` q WHERE q.`id_test` = ? AND q.`done_question` = 0 ORDER BY q.`order_quiz` ASC LIMIT 1");
            $stmt->bind_param("i", $action_quiz_id);
            $stmt->execute();
            $result_next = $stmt->get_result();
            $row_next = $result_next->fetch_assoc();
            $stmt->close();
            $next_question = isset($row_next["id_question_quiz"]) ? $row_next["id_question_quiz"] : null;
        } else {
            $status_message = "❌ Erreur lors de la réinitialisation du quiz #$action_quiz_id.";
        }
    }

    mysqli_close($conn);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Dashboard - Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body> 
<div id="app" class="min-h-screen p-4 sm:p-8 flex justify-center items-start bg-gray-50">
    <div class="w-full max-w-4xl container-card bg-white rounded-xl shadow-lg p-6 sm:p-8">
        
        <header class="mb-6 border-b pb-4">
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">🚀 Tableau de Bord du Quiz</h1>
        </header>

        <section class="mb-6 p-4 border rounded-lg shadow-sm bg-blue-50">
            <h2 class="text-xl font-semibold text-blue-800 mb-3">Sélectionner un Quiz</h2>
            <form method="GET" action="welcome.php" class="flex items-center gap-4">
                <label for="quiz_id" class="text-gray-700 font-medium">Quiz Actif :</label>
                <select name="quiz_id" id="quiz_id" onchange="this.form.submit()" class="flex-grow p-2 border border-blue-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($all_quizzes as $quiz): ?>
                        <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz['id'] == $selected_quiz_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($quiz['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <p class="mt-2 text-sm text-blue-600">Quiz actuellement sélectionné pour jouer et gérer : **<?php echo htmlspecialchars($current_quiz_name) . " (ID: " . $selected_quiz_id . ")"; ?>**</p>
        </section>

        <?php if (!empty($status_message)): ?>
            <div class="p-3 mb-4 text-sm font-medium text-green-800 bg-green-100 rounded-lg" role="alert">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <section class="mb-8 p-4 border rounded-lg shadow-sm">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Démarrer le Quiz : <?php echo htmlspecialchars($current_quiz_name); ?></h2>
            
            <a href="<?php 
                $target_question = isset($next_question) ? $next_question : 1;
                echo "quiz.php?id=" . $target_question . "&quiz_id=" . $selected_quiz_id; 
            ?>" class="inline-flex items-center px-6 py-3 bg-teal-600 text-white text-lg font-bold rounded-lg shadow hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500 focus:ring-opacity-50 transition duration-150 ease-in-out" role="button" aria-label="Start or Next Quiz">
                <?php echo isset($next_question) ? "Continuer le Quiz (Question #$next_question)" : "Démarrer le Quiz (Toutes faites)"; ?>
            </a>
        </section>

        <section class="mb-8 p-4 border rounded-lg shadow-sm bg-gray-50">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">🛠️ Outils d'Administration</h2>

            <div class="mb-6">
                <h3 class="text-xl font-medium text-red-700 mb-2">Réinitialisation du Quiz : <?php echo htmlspecialchars($current_quiz_name); ?></h3>
                <p class="text-gray-600 mb-3">**ATTENTION :** Ceci effacera toutes les réponses et réinitialisera l'état du quiz **<?php echo htmlspecialchars($current_quiz_name); ?>**.</p>
                <form method="POST" action="welcome.php?quiz_id=<?php echo $selected_quiz_id; ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir réinitialiser le QUIZ <?php echo htmlspecialchars($current_quiz_name); ?> ?');">
                    <input type="hidden" name="action" value="reset_db">
                    <input type="hidden" name="quiz_id_action" value="<?php echo $selected_quiz_id; ?>">
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition duration-150 ease-in-out">
                        🚨 Réinitialiser le Quiz Actif
                    </button>
                </form>
            </div>

            <hr class="my-6">

            <h3 class="text-xl font-medium text-gray-700 mb-3">Gestion du Contenu</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <a href="manage_users.php" class="block p-4 bg-blue-100 text-blue-800 rounded-lg shadow hover:bg-blue-200 transition">
                    <span class="font-semibold text-lg">👥 Gérer les Joueurs</span>
                </a>
                
                <a href="add_question.php" class="block p-4 bg-purple-100 text-purple-800 rounded-lg shadow hover:bg-purple-200 transition">
                    <span class="font-semibold text-lg">➕ Ajouter une Question</span>
                </a>
                
                <a href="manage_quiz.php?quiz_id=<?php echo $selected_quiz_id; ?>" class="block p-4 bg-yellow-100 text-yellow-800 rounded-lg shadow hover:bg-yellow-200 transition">
                    <span class="font-semibold text-lg">📝 Gérer le Quiz</span>
                    <p class="text-sm">Gérer les questions de **<?php echo htmlspecialchars($current_quiz_name); ?>**</p>
                </a>
            </div>
        </section>

    </div>
</div>

</body>
</html>