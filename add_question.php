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

$conn = connect_db($servername, $username, $password, $dbname);
$status_message = "";

// --- Handle Form Submission (Add Question) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_question') {
    
    // Sanitize and validate inputs
    $name = trim($_POST['name_question']);
    $text = trim($_POST['text_question']);
    $option1 = trim($_POST['1_question']);
    $option2 = trim($_POST['2_question']);
    $option3 = trim($_POST['3_question']);
    $option4 = trim($_POST['4_question']);
    $correct = (int)$_POST['correct_question'];
    $time = (int)$_POST['time_question'];
    $point = (int)$_POST['point_question'];
    $image = trim($_POST['image_question']); // Note: You'd need file upload logic for proper image handling

    // Basic validation
    if (empty($name) || empty($text) || empty($option1) || $correct < 1 || $correct > 4 || $time <= 0 || $point <= 0) {
        $status_message = "❌ Erreur : Veuillez remplir tous les champs obligatoires et vérifier les valeurs numériques/de réponse.";
    } else {
        // Use prepared statements for security
        $sql = "INSERT INTO `question` (`name_question`, `text_question`, `1_question`, `2_question`, `3_question`, `4_question`, `correct_question`, `time_question`, `point_question`, `image_question`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters (s for string, i for integer)
        $stmt->bind_param("ssssssiids", $name, $text, $option1, $option2, $option3, $option4, $correct, $time, $point, $image);
        
        if ($stmt->execute()) {
            $last_id = $stmt->insert_id;
            $status_message = "✅ Question (ID: $last_id) **" . htmlspecialchars($name) . "** ajoutée avec succès.";
            
            // Optionally: Clear POST data to prevent form resubmission on refresh
            $_POST = array(); 
        } else {
            $status_message = "❌ Erreur lors de l'ajout de la question : " . $conn->error;
        }
        $stmt->close();
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une Question - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body> 
<div class="min-h-screen p-4 sm:p-8 flex justify-center items-start bg-gray-50">
    <div class="w-full max-w-4xl bg-white rounded-xl shadow-lg p-6 sm:p-8">
        
        <header class="mb-6 border-b pb-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-800">📝 Ajouter une Nouvelle Question</h1>
            <a href="welcome.php" class="text-sm text-teal-600 hover:text-teal-800 font-medium">← Retour au Dashboard</a>
        </header>

        <?php if (!empty($status_message)): ?>
            <div class="p-3 mb-4 text-sm font-medium <?php echo strpos($status_message, '❌') !== false ? 'text-red-800 bg-red-100' : 'text-green-800 bg-green-100'; ?> rounded-lg" role="alert">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <section class="p-4 border rounded-lg shadow-sm bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Détails de la Question</h2>
            
            <form method="POST" action="add_question.php" class="space-y-6">
                <input type="hidden" name="action" value="add_question">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name_question" class="block text-sm font-medium text-gray-700">Nom Interne de la Question (ex: Ezra's life 1)</label>
                        <input type="text" name="name_question" id="name_question" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="image_question" class="block text-sm font-medium text-gray-700">Chemin de l'Image (ex: img/ezra.jpg)</label>
                        <input type="text" name="image_question" id="image_question" value="img/default.jpg" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                <div>
                    <label for="text_question" class="block text-sm font-medium text-gray-700">Texte Complet de la Question</label>
                    <textarea name="text_question" id="text_question" rows="3" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500"></textarea>
                </div>

                <h3 class="text-lg font-medium text-gray-700 mt-6">Options de Réponse</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="1_question" class="block text-sm font-medium text-gray-700">Option 1</label>
                        <input type="text" name="1_question" id="1_question" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="2_question" class="block text-sm font-medium text-gray-700">Option 2 (peut être vide)</label>
                        <input type="text" name="2_question" id="2_question" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="3_question" class="block text-sm font-medium text-gray-700">Option 3 (peut être vide)</label>
                        <input type="text" name="3_question" id="3_question" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="4_question" class="block text-sm font-medium text-gray-700">Option 4 (peut être vide)</label>
                        <input type="text" name="4_question" id="4_question" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>

                <h3 class="text-lg font-medium text-gray-700 mt-6">Paramètres</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="correct_question" class="block text-sm font-medium text-gray-700">Réponse Correcte (1, 2, 3 ou 4)</label>
                        <input type="number" name="correct_question" id="correct_question" min="1" max="4" value="1" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="time_question" class="block text-sm font-medium text-gray-700">Temps (Secondes)</label>
                        <input type="number" name="time_question" id="time_question" min="1" value="30" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label for="point_question" class="block text-sm font-medium text-gray-700">Points</label>
                        <input type="number" name="point_question" id="point_question" min="1" value="100" required class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>

                <div class="pt-4 border-t mt-6">
                    <button type="submit" class="w-full px-4 py-2 bg-teal-600 text-white font-bold rounded-lg shadow hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                        Enregistrer la Question
                    </button>
                </div>
            </form>
        </section>

    </div>
</div>
</body>
</html>