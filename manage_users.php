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

// --- CRUD Operations ---

// 1. Add User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $new_name = trim($_POST['new_name']);
    if (!empty($new_name)) {
        // Use prepared statements for security
        $stmt = $conn->prepare("INSERT INTO `user` (`name_user`) VALUES (?)");
        $stmt->bind_param("s", $new_name);
        if ($stmt->execute()) {
            $status_message = "✅ Joueur **" . htmlspecialchars($new_name) . "** ajouté.";
        } else {
            $status_message = "❌ Erreur lors de l'ajout du joueur : " . $conn->error;
        }
        $stmt->close();
    } else {
        $status_message = "Veuillez entrer un nom valide.";
    }
}

// 2. Update User Name (Assuming a simple modal/form handles this on the front end)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $user_id = (int)$_POST['user_id'];
    $new_name = trim($_POST['new_name']);
    
    if ($user_id > 0 && !empty($new_name)) {
        $stmt = $conn->prepare("UPDATE `user` SET `name_user` = ? WHERE `id_user` = ?");
        $stmt->bind_param("si", $new_name, $user_id);
        if ($stmt->execute()) {
            $status_message = "✅ Nom du joueur (ID: $user_id) mis à jour en **" . htmlspecialchars($new_name) . "**.";
        } else {
            $status_message = "❌ Erreur lors de la mise à jour : " . $conn->error;
        }
        $stmt->close();
    } else {
        $status_message = "Données de mise à jour invalides.";
    }
}

// 3. Delete User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_user') {
    $user_id = (int)$_POST['user_id'];
    
    // IMPORTANT: Check for foreign key constraints! 
    // You must delete related entries in `current_answer` and `store_answer` first.
    
    // Step A: Delete related answers (required due to foreign keys)
    $conn->query("DELETE FROM `current_answer` WHERE `id_c_user` = $user_id");
    $conn->query("DELETE FROM `store_answer` WHERE `id_s_user` = $user_id");

    // Step B: Delete the user
    if ($user_id > 0) {
        $stmt = $conn->prepare("DELETE FROM `user` WHERE `id_user` = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $status_message = "✅ Joueur (ID: $user_id) supprimé.";
        } else {
            $status_message = "❌ Erreur lors de la suppression : " . $conn->error;
        }
        $stmt->close();
    }
}

// --- Fetch all users (Read) ---
$users = [];
$result = mysqli_query($conn, "SELECT `id_user`, `name_user`, `point_user` FROM `user` ORDER BY `id_user` ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Joueurs - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body> 
<div class="min-h-screen p-4 sm:p-8 flex justify-center items-start bg-gray-50">
    <div class="w-full max-w-4xl bg-white rounded-xl shadow-lg p-6 sm:p-8">
        
        <header class="mb-6 border-b pb-4 flex justify-between items-center">
            <h1 class="text-3xl font-extrabold text-gray-800">👥 Gestion des Joueurs</h1>
            <a href="welcome.php" class="text-sm text-teal-600 hover:text-teal-800 font-medium">← Retour au Dashboard</a>
        </header>

        <?php if (!empty($status_message)): ?>
            <div class="p-3 mb-4 text-sm font-medium text-blue-800 bg-blue-100 rounded-lg" role="alert">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>

        <section class="mb-8 p-4 border rounded-lg shadow-sm bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700 mb-3">➕ Ajouter un Nouveau Joueur</h2>
            <form method="POST" action="manage_users.php" class="flex gap-4">
                <input type="hidden" name="action" value="add_user">
                <input type="text" name="new_name" placeholder="Nom du nouveau joueur" required class="flex-grow p-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white font-medium rounded-lg shadow hover:bg-green-700 transition">Ajouter</button>
            </form>
        </section>

        <section class="mb-8 p-4 border rounded-lg shadow-sm">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Liste des Joueurs Actuels</h2>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom du Joueur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['id_user']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" action="manage_users.php" class="flex items-center gap-2">
                                        <input type="hidden" name="action" value="update_user">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id_user']); ?>">
                                        <input type="text" name="new_name" value="<?php echo htmlspecialchars($user['name_user']); ?>" required class="p-1 border border-gray-300 rounded-md text-sm">
                                        <button type="submit" class="text-teal-600 hover:text-teal-900 text-sm font-medium transition">Renommer</button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['point_user']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" action="manage_users.php" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer le joueur ID <?php echo $user['id_user']; ?> ? Toutes leurs réponses seront aussi supprimées.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id_user']); ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium transition">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Aucun joueur trouvé.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </div>
</div>
</body>
</html>