<?php
// --- PARAMÈTRES DE CONNEXION À LA BASE DE DONNÉES ---
include('config.php');

// --- FONCTION DE CONNEXION ---
function connect_db($servername, $username, $password, $dbname) {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Erreur de connexion à la base de données."); 
    }
    return $conn;
}

    $message = "";
    $success = false;
    
    $conn = connect_db($servername, $username, $password, $dbname);

    //fonction recupere le numero de quiz
    $sql = "SELECT * FROM `variable` WHERE `id_var` = 1 LIMIT 1;" ;
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        $quiz_id = $row["value_var"];
    }
    } else {
    //echo "0 results";
    }

    //echo $quiz_id;

    // Requête préparée pour l'insertion
    $sql = "SELECT user.id_user, user.name_user, SUM(store_answer.points_s_answer) AS total_points FROM `user` LEFT JOIN `store_answer` ON store_answer.id_s_user = user.id_user GROUP BY user.id_user, user.name_user ORDER BY total_points DESC;" ;
    $result = mysqli_query($conn, $sql);
    $rows = [];
    if ($result) {
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    }

    // Requête préparée pour l'insertion
    $sql_delete = "DELETE FROM `current_answer` WHERE 1;" ;
    mysqli_query($conn, $sql_delete);

    // Requête pour trouver la prochaine question non faite dans le quiz sélectionné
    $sql = "SELECT q.`id_question_quiz` FROM `quiz` q WHERE q.`id_test` = ? AND q.`done_question` = 0 ORDER BY q.`order_quiz` ASC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result_next = $stmt->get_result();
    $row_next = $result_next->fetch_assoc();
    $stmt->close();
    
    $next_question = isset($row_next["id_question_quiz"]) ? $row_next["id_question_quiz"] : null;

    mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results Dashboard (PHP)</title>
    <!-- Tailwind CSS loaded via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for better aesthetics */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc;
        }
        .container-card {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        th a {
             /* Remove the default link underline */
             text-decoration: none;
        }
    </style>
</head>
<body>
 
<div id="app" class="min-h-screen p-4 sm:p-8 flex justify-center items-start">
    <div class="w-full max-w-6xl container-card bg-white rounded-xl p-6 sm:p-8">
        <!-- Header -->
        <header class="mb-6 border-b pb-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight"><?php if(isset($next_question)) echo "Classement"; else echo "Classement final"?></h1>
            </div>
            <div class="mt-1">
                <a href="<?php if(isset($next_question)) echo "quiz.php?id=" . $next_question . "&quiz_id=" . $quiz_id; else echo "welcome.php"?>" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg shadow hover:bg-teal-700 focus:outline-none" role="button" aria-label="Next">Next</a>
            </div>
            </header>
        
        <!-- Podium (top 3) -->
        <div class="mb-6 flex items-end justify-center gap-6">
            <?php
                $first = $rows[0] ?? null;
                $second = $rows[1] ?? null;
                $third = $rows[2] ?? null;
            ?>

            <!-- Second place -->
            <div class="flex flex-col items-center">
                <div class="w-24 h-32 bg-teal-100 rounded-t-lg flex items-end justify-center shadow">
                    <div class="text-sm font-semibold text-teal-700 mb-2"><?php echo $second ? htmlspecialchars($second['total_points']) : '-'; ?></div>
                </div>
                <div class="bg-teal-50 px-3 py-1 rounded-b-md text-center text-sm w-24 truncate"><?php echo $second ? htmlspecialchars($second['name_user']) : '—'; ?></div>
                <div class="mt-2 text-sm text-gray-500">2</div>
            </div>

            <!-- First place -->
            <div class="flex flex-col items-center">
                <div class="w-28 h-40 bg-yellow-100 rounded-t-lg flex items-end justify-center shadow-lg">
                    <div class="text-lg font-bold text-yellow-800 mb-3"><?php echo $first ? htmlspecialchars($first['total_points']) : '-'; ?></div>
                </div>
                <div class="bg-yellow-50 px-4 py-1 rounded-b-md text-center text-sm w-28 truncate"><?php echo $first ? htmlspecialchars($first['name_user']) : '—'; ?></div>
                <div class="mt-2 text-sm text-gray-500">1</div>
            </div>

            <!-- Third place -->
            <div class="flex flex-col items-center">
                <div class="w-20 h-24 bg-amber-100 rounded-t-lg flex items-end justify-center shadow">
                    <div class="text-sm font-semibold text-amber-800 mb-1"><?php echo $third ? htmlspecialchars($third['total_points']) : '-'; ?></div>
                </div>
                <div class="bg-amber-50 px-3 py-1 rounded-b-md text-center text-sm w-20 truncate"><?php echo $third ? htmlspecialchars($third['name_user']) : '—'; ?></div>
                <div class="mt-2 text-sm text-gray-500">3</div>
            </div>
        </div>
        
        <!-- Results Table Container -->
        <div id="results-container" class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-teal-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">
                            <span>User ID</span>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">
                            <span>Name</span>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">
                            <span>Points</span>
                        </th>    
                    </tr>
                </thead>
                <tbody id="results-table-body" class="bg-white divide-y divide-gray-200">
                    <?php 
                    // Loop through the results and output HTML rows
                    foreach ($rows as $row) {
                        $points = $row['total_points'];
                        // Couleur du badge Points
                        $pointsClass = $points > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';

                        echo '<tr class="hover:bg-gray-50 transition duration-150 ease-in-out">';
                        
                        // User ID
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-teal-600">' . htmlspecialchars($row['id_user']) . '</td>';
                        
                        // User Name
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['name_user']) . '</td>';
                        
                        // Points
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center">';
                        echo '<span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium ' . $pointsClass . '">' . htmlspecialchars($row['total_points']) . '</span>';
                        echo '</td>';
                        
                        echo '</tr>';



                    }
                    
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>
