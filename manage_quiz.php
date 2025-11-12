<?php
// Include configuration and connection functions
include('config.php');

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
    $sql = "SELECT DISTINCT `id_test`, COALESCE(NULLIF(`name_quiz`, ''), CONCAT('Quiz #', `id_test`)) AS name 
            FROM `quiz` 
            ORDER BY `id_test` ASC";
    $result = mysqli_query($conn, $sql);
    
    if ($result) { 
        while ($row = mysqli_fetch_assoc($result)) { 
            $quizzes[] = ['id' => $row['id_test'], 'name' => $row['name']];
        } 
    }
    return $quizzes;
}


$conn = connect_db($servername, $username, $password, $dbname);
$status_message = "";
$all_quizzes = get_all_quizzes($conn);


// Si la liste est vide, on force un ID par défaut (1)
if (empty($all_quizzes)) {
    $all_quizzes = [['id' => 1, 'name' => 'Quiz par défaut (Vide)']];
    $ID_TEST = 1;
    $current_quiz_name = 'Quiz par défaut (Vide)';
} else {
    // Récupère l'ID du quiz depuis l'URL (GET/POST) ou par défaut le premier de la liste
    $ID_TEST = isset($_REQUEST['quiz_id']) ? (int)$_REQUEST['quiz_id'] : $all_quizzes[0]['id'];
    
    // Trouve le nom du quiz correspondant
    $quiz_index = array_search($ID_TEST, array_column($all_quizzes, 'id'));
    $current_quiz_name = ($quiz_index !== false) ? $all_quizzes[$quiz_index]['name'] : 'Quiz Inconnu';
}

// --- CRUD Operations sur 'quiz' table ---

// 1. Add Question to Quiz (avec ordre initial)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_to_quiz') {
    $question_id = (int)$_POST['question_id'];
    $order_value = (int)$_POST['order_value'];
    $quiz_id_action = (int)$_POST['quiz_id_action'];
    $quiz_name_action = $_POST['quiz_name_action']; // Nouveau champ

    // Prevent duplicates
    $check = $conn->query("SELECT COUNT(*) FROM `quiz` WHERE `id_question_quiz` = $question_id AND `id_test` = $quiz_id_action");
    $count = $check->fetch_row()[0];

    if ($count == 0 && $question_id > 0) {
        // Insertion avec la colonne 'name_quiz'
        $stmt = $conn->prepare("INSERT INTO `quiz` (`id_test`, `id_question_quiz`, `name_quiz`, `order_quiz`, `done_question`) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("iisi", $quiz_id_action, $question_id, $quiz_name_action, $order_value);
        if ($stmt->execute()) {
            $status_message = "✅ Question (ID: $question_id) ajoutée à **$quiz_name_action** avec l'ordre $order_value.";
        } else {
            $status_message = "❌ Erreur lors de l'ajout : " . $conn->error;
        }
        $stmt->close();
    } else if ($count > 0) {
         $status_message = "⚠️ Question (ID: $question_id) est déjà dans le quiz #$quiz_id_action.";
    }
}

// 2. Remove Question from Quiz (identique)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'remove_from_quiz') {
    $question_id = (int)$_POST['question_id'];
    $quiz_id_action = (int)$_POST['quiz_id_action'];
    
    if ($question_id > 0) {
        $stmt = $conn->prepare("DELETE FROM `quiz` WHERE `id_question_quiz` = ? AND `id_test` = ?");
        $stmt->bind_param("ii", $question_id, $quiz_id_action);
        if ($stmt->execute()) {
            $status_message = "✅ Question (ID: $question_id) retirée du quiz #$quiz_id_action.";
        } else {
            $status_message = "❌ Erreur lors du retrait : " . $conn->error;
        }
        $stmt->close();
    }
}

// 3. Update Question Order (identique)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_order') {
    $question_id = (int)$_POST['question_id'];
    $new_order = (int)$_POST['new_order'];
    $quiz_id_action = (int)$_POST['quiz_id_action'];
    
    if ($question_id > 0 && $new_order >= 0) {
        $stmt = $conn->prepare("UPDATE `quiz` SET `order_quiz` = ? WHERE `id_question_quiz` = ? AND `id_test` = ?");
        $stmt->bind_param("iii", $new_order, $question_id, $quiz_id_action);
        if ($stmt->execute()) {
            $status_message = "✅ Ordre de la question (ID: $question_id) du quiz #$quiz_id_action mis à jour à $new_order.";
        } else {
            $status_message = "❌ Erreur lors de la mise à jour de l'ordre : " . $conn->error;
        }
        $stmt->close();
    }
}

// 4. Update Quiz Name (Nouveau)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_quiz_name') {
    $new_name = trim($_POST['new_quiz_name']);
    $quiz_id_action = (int)$_POST['quiz_id_action'];
    
    if (!empty($new_name) && $quiz_id_action > 0) {
        // Mise à jour de la colonne `name_quiz` pour TOUTES les lignes qui ont cet `id_test`
        $stmt = $conn->prepare("UPDATE `quiz` SET `name_quiz` = ? WHERE `id_test` = ?");
        $stmt->bind_param("si", $new_name, $quiz_id_action);
        if ($stmt->execute()) {
            $status_message = "✅ Nom du Quiz #$quiz_id_action mis à jour à **" . htmlspecialchars($new_name) . "**.";
            $current_quiz_name = $new_name; // Mise à jour immédiate
        } else {
            $status_message = "❌ Erreur lors de la mise à jour du nom : " . $conn->error;
        }
        $stmt->close();
    }
}


// --- Fetch Data (Utilise $ID_TEST) ---

// 1. Fetch all questions (from question table)
$all_questions = [];
$result_q = mysqli_query($conn, "SELECT `id_question`, `name_question`, `text_question` FROM `question` ORDER BY `id_question` ASC");
if ($result_q) {
    while ($row = mysqli_fetch_assoc($result_q)) {
        $all_questions[$row['id_question']] = $row;
        $all_questions[$row['id_question']]['in_quiz'] = false;
        $all_questions[$row['id_question']]['done_status'] = false;
        $all_questions[$row['id_question']]['order_quiz'] = 999;
    }
}

// 2. Fetch questions currently in the selected quiz ($ID_TEST)
$result_quiz = mysqli_query($conn, "SELECT `id_question_quiz`, `done_question`, `order_quiz` FROM `quiz` WHERE `id_test` = $ID_TEST ORDER BY `order_quiz` ASC");
if ($result_quiz) {
    while ($row = mysqli_fetch_assoc($result_quiz)) {
        $q_id = $row['id_question_quiz'];
        if (isset($all_questions[$q_id])) {
            $all_questions[$q_id]['in_quiz'] = true;
            $all_questions[$q_id]['done_status'] = ($row['done_question'] == 1);
            $all_questions[$q_id]['order_quiz'] = $row['order_quiz'];
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer le Quiz - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body> 
<div class="min-h-screen p-4 sm:p-8 flex justify-center items-start bg-gray-50">
    <div class="w-full max-w-6xl bg-white rounded-xl shadow-lg p-6 sm:p-8">
        
        <header class="mb-6 border-b pb-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-800">📝 Gestion des Questions du Quiz</h1>
            <a href="welcome.php?quiz_id=<?php echo $ID_TEST; ?>" class="text-sm text-teal-600 hover:text-teal-800 font-medium">← Retour au Dashboard</a>
        </header>

        <?php if (!empty($status_message)): ?>
            <div class="p-3 mb-4 text-sm font-medium <?php echo strpos($status_message, '❌') !== false ? 'text-red-800 bg-red-100' : (strpos($status_message, '⚠️') !== false ? 'text-yellow-800 bg-yellow-100' : 'text-green-800 bg-green-100'); ?> rounded-lg" role="alert">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <section class="mb-6 p-4 border rounded-lg shadow-sm bg-blue-50">
            <h2 class="text-xl font-semibold text-blue-800 mb-3">Quiz Actif : <?php echo htmlspecialchars($current_quiz_name); ?> (ID: <?php echo $ID_TEST; ?>)</h2>
            <div class="space-y-4">
                <form method="GET" action="manage_quiz.php" class="flex items-center gap-4">
                    <label for="quiz_id" class="text-gray-700 font-medium">Changer de Quiz :</label>
                    <select name="quiz_id" id="quiz_id" onchange="this.form.submit()" class="flex-grow p-2 border border-blue-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($all_quizzes as $quiz): ?>
                            <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz['id'] == $ID_TEST) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($quiz['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <form method="POST" action="manage_quiz.php?quiz_id=<?php echo $ID_TEST; ?>" class="flex items-center gap-4">
                    <input type="hidden" name="action" value="update_quiz_name">
                    <input type="hidden" name="quiz_id_action" value="<?php echo $ID_TEST; ?>">
                    <label for="new_quiz_name" class="text-gray-700 font-medium">Renommer :</label>
                    <input type="text" name="new_quiz_name" id="new_quiz_name" value="<?php echo htmlspecialchars($current_quiz_name); ?>" required class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg shadow hover:bg-indigo-700 transition">Renommer</button>
                </form>
            </div>
        </section>

        <section class="p-4 border rounded-lg shadow-sm">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Questions dans le Quiz : <?php echo htmlspecialchars($current_quiz_name); ?></h2>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Q.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom Question</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut Quiz</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fait</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    // Triez le tableau pour afficher les questions INCLUSES en premier et par `order_quiz`
                    usort($all_questions, function($a, $b) {
                        if ($a['in_quiz'] && !$b['in_quiz']) return -1;
                        if (!$a['in_quiz'] && $b['in_quiz']) return 1;
                        if ($a['in_quiz'] && $b['in_quiz']) {
                            return $a['order_quiz'] <=> $b['order_quiz'];
                        }
                        return $a['id_question'] <=> $b['id_question'];
                    });

                    if (!empty($all_questions)): 
                        foreach ($all_questions as $q): 
                            $bg_color = $q['in_quiz'] ? 'bg-indigo-50/50' : 'bg-white';
                    ?>
                            <tr class="<?php echo $bg_color; ?>">
                                <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($q['id_question']); ?></td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($q['name_question']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($q['in_quiz']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">INCLUS</span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">EXCLU</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($q['in_quiz']): ?>
                                        <form method="POST" action="manage_quiz.php?quiz_id=<?php echo $ID_TEST; ?>" class="flex items-center gap-2">
                                            <input type="hidden" name="action" value="update_order">
                                            <input type="hidden" name="question_id" value="<?php echo htmlspecialchars($q['id_question']); ?>">
                                            <input type="hidden" name="quiz_id_action" value="<?php echo $ID_TEST; ?>">
                                            <input type="number" name="new_order" value="<?php echo htmlspecialchars($q['order_quiz']); ?>" min="0" class="w-16 p-1 border border-gray-300 rounded-md text-sm text-center">
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm font-medium transition">OK</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($q['done_status']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">OUI</span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">NON</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($q['in_quiz']): ?>
                                        <form method="POST" action="manage_quiz.php?quiz_id=<?php echo $ID_TEST; ?>" class="inline-block" onsubmit="return confirm('Retirer la question ID <?php echo $q['id_question']; ?> du quiz <?php echo htmlspecialchars($current_quiz_name); ?> ?');">
                                            <input type="hidden" name="action" value="remove_from_quiz">
                                            <input type="hidden" name="question_id" value="<?php echo htmlspecialchars($q['id_question']); ?>">
                                            <input type="hidden" name="quiz_id_action" value="<?php echo $ID_TEST; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition font-medium">Retirer</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="manage_quiz.php?quiz_id=<?php echo $ID_TEST; ?>" class="flex items-center gap-2">
                                            <input type="hidden" name="action" value="add_to_quiz">
                                            <input type="hidden" name="question_id" value="<?php echo htmlspecialchars($q['id_question']); ?>">
                                            <input type="hidden" name="quiz_id_action" value="<?php echo $ID_TEST; ?>">
                                            <input type="hidden" name="quiz_name_action" value="<?php echo htmlspecialchars($current_quiz_name); ?>">
                                            <input type="number" name="order_value" placeholder="Ordre" value="<?php echo count(array_filter($all_questions, fn($item) => $item['in_quiz'])) + 1; ?>" min="0" class="w-16 p-1 border border-gray-300 rounded-md text-sm text-center">
                                            <button type="submit" class="text-green-600 hover:text-green-900 transition font-medium">Ajouter</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Aucune question disponible.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </div>
</div>
</body>
</html>