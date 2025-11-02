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

    // --- RÉCUPÉRATION ET ASSAINISSEMENT DES DONNÉES POST ---
    $id_question = isset($_POST['id_question']) ? (int)$_POST['id_question'] : 0;
    //echo $id_question;
    $start_time_answer = isset($_POST['start_time_answer']) ? (int)$_POST['start_time_answer'] : 0;
    $end_time_answer = floor(microtime(true) * 1000); 

    //echo $start_time_answer;
    $message = "";
    $success = false;

    $conn = connect_db($servername, $username, $password, $dbname);

    //fonction recupere les points a gagner
    $sql = "SELECT `correct_question`, `point_question` FROM `question` WHERE `id_question` = " . $id_question . ";" ;
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        $true_answer = $row["correct_question"];
        $point = $row["point_question"];
    }
    } else {
    //echo "0 results";
    }
    //echo $true_answer;
    //echo $point;

    //fonction calcul les points en triant reponse juste + temps
    $sql = "SELECT * FROM `current_answer` INNER JOIN `question` ON `current_answer`.`answer_c_answer` = `question`.`correct_question` WHERE `question`.`id_question` = " . $id_question . " ORDER BY `current_answer`.`time_c_answer` ASC;";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        $sql = "UPDATE `current_answer` SET `points_c_answer` = " . $point . " WHERE `current_answer`.`id_c_user` = ". $row["id_c_user"] .";";
        mysqli_query($conn, $sql);
        $point = $point - 1;
    }
    } else {
    //echo "0 results";
    }

    // fonction copy les donnees "current" dans "store" en ajoutant l'id question, points et temps
    $nb_user = 0;
    $sql = "SELECT * FROM `user` WHERE 1;" ;
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        $nb_user = $nb_user + 1;
    }
    } else {
    //echo "0 results";
    }
    //echo $nb_user;
    $user = 1;
    while($user <= $nb_user ) {
            $sql = "SELECT * FROM `current_answer` WHERE `id_c_user` = " . $user . ";" ;
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);
            
            if($row!=NULL){
                $stmt = mysqli_prepare($conn, 
                "INSERT INTO store_answer (id_s_user, id_s_question, answer_s_answer, time_s_answer, points_s_answer, start_time_s_answer	) 
                VALUES (?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "iiiiii", 
                $user, 
                $id_question,
                $row["answer_c_answer"],
                $row["time_c_answer"],
                $row["points_c_answer"],
                $start_time_answer
                );

            }else{
                $stmt = mysqli_prepare($conn, 
                "INSERT INTO store_answer (id_s_user, id_s_question, time_s_answer, start_time_s_answer) 
                VALUES (?, ?, ? ,?)"
                );
                mysqli_stmt_bind_param($stmt, "iiii", 
                $user, 
                $id_question,
                $end_time_answer,
                $start_time_answer
                );
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "🎉 **SUCCÈS !** La réponse de test a été insérée dans la table `answer`.";
                $success = true;
            } else {
                $message = "❌ **ÉCHEC !** Erreur lors de l'enregistrement : " . mysqli_error($conn);
            }


            mysqli_stmt_close($stmt);
            $user = $user + 1;           
    }
    
    mysqli_close($conn);

    //// Gestion de l'affichage
    $conn = connect_db($servername, $username, $password, $dbname);
    // Requête préparée pour l'insertion
    $sql = "SELECT store_answer.id_s_user, user.name_user, store_answer.answer_s_answer, store_answer.time_s_answer, store_answer.points_s_answer, store_answer.start_time_s_answer FROM `store_answer` LEFT JOIN `user` ON `user`.`id_user` = `store_answer`.`id_s_user` WHERE `id_s_question` = " . $id_question . " ORDER BY store_answer.points_s_answer DESC , store_answer.time_s_answer ASC;" ;
    //$sql = "SELECT * FROM `store_answer` WHERE `id_s_question` = " . $id_question . " ;" ;
    $result = mysqli_query($conn, $sql);

    // --- 0. Calculate summary counts for the bar chart (NEW LOGIC) ---
    $responseCounts = [
    '0' => 0, // NA
    '1' => 0, // Bleu
    '2' => 0, // Vert
    '3' => 0, // Jaune
    '4' => 0, // Rouge
    ];

    foreach ($result as $row) {
        $answer = $row['answer_s_answer'];
        if (isset($responseCounts[$answer])) {
            $responseCounts[$answer]++;
        }
    }
    /*echo $responseCounts[0];
    echo $responseCounts[1];
    echo $responseCounts[2];
    echo $responseCounts[3];
    echo $responseCounts[4];*/

    $maxCount = max($responseCounts);
    $maxCount = $maxCount > 0 ? $maxCount : 1; // Ensure max is at least 1 for division
    $maxAns = array_sum($responseCounts);
    $sumAns = $responseCounts[1]+$responseCounts[2]+$responseCounts[3]+$responseCounts[4];

    
    // Requête préparée pour l'insertion
    $sql = "SELECT * FROM `question` WHERE `id_question` = " . $id_question . ";" ;
    $result_q = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        while($row = mysqli_fetch_assoc($result_q)) {
            $question_name = $row["name_question"];
            $question_text = $row["text_question"];
            $ans_green = $row["1_question"];
            $ans_blue = $row["2_question"];
            $ans_yellow = $row["3_question"];
            $ans_red = $row["4_question"];
            $ans_correct = $row["correct_question"];
            if($ans_correct == 1) $ans_correct_text = $ans_green;
            elseif($ans_correct == 2) $ans_correct_text = $ans_blue;
            elseif($ans_correct == 3) $ans_correct_text = $ans_yellow;
            else $ans_correct_text = $ans_red;
        }        
    } else {
        echo "0 results";
    }

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
    // passe la question en "done" dans la table quiz
    $sql = "UPDATE `quiz` SET `done_question`= 1 WHERE `id_question_quiz` = " . $id_question . " AND  `id_test` = " . $quiz_id . " ;" ;
    mysqli_query($conn, $sql);
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
        <header class="mb-8 border-b pb-4 flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Synthèse des Réponses Utilisateurs</h1>
                <p class="text-gray-500 mt-1">Affichage du décompte des réponses (1, 2, 3, 4) sous forme de graphique à barres.</p>
            </div>
            <div class="mt-1">
                <a href="ranking.php" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg shadow hover:bg-teal-700 focus:outline-none" role="button" aria-label="Next to Ranking">Next to Ranking</a>
            </div>
        </header>

        <!-- --- NOUVEAU GRAPHIQUE À BARRES DE RÉSUMÉ --- -->
        <div class="mb-10 p-5 bg-gray-50 rounded-xl border border-gray-200">
            <h2 class="text-l font-semibold text-gray-700 mb-6">Répartition des Réponses (Total: <?php echo $sumAns; ?> / <?php echo $maxAns; ?>)</h2>
            <h2 class="text-l font-semibold text-gray-700 mb-6">Question : <?php echo $question_name . " : " . $question_text; ?> </h2>
            <h2 class="text-xl font-semibold text-gray-700 mb-6">Réponse : <?php echo $ans_correct_text; ?> </h2>
            
            <div class="flex items-end justify-around h-64 border-b border-gray-300 pb-2">
                <?php
                // Configuration pour les barres
                $chartData = [
                    '1' => ['label' => $ans_blue, 'color' => 'bg-blue-500', 'text' => 'text-blue-700'],
                    '2' => ['label' => $ans_green, 'color' => 'bg-green-500', 'text' => 'text-green-700'],
                    '3' => ['label' => $ans_yellow, 'color' => 'bg-yellow-400', 'text' => 'text-yellow-700'],
                    '4' => ['label' => $ans_red, 'color' => 'bg-red-500', 'text' => 'text-red-700'],
                ];

                foreach ($chartData as $key => $data) {
                    $count = $responseCounts[$key];
                    // Calcule la hauteur en pourcentage, laisse 10% d'espace pour la lisibilité
                    $heightPercent = ($count / $maxCount) * 90; 
                    $heightPercent = max(2, $heightPercent); // Hauteur minimale

                    echo '<div class="flex flex-col items-center justify-end h-full w-1/4 px-2">';
                    
                    // Conteneur de la Barre (FIX: Added h-full here)
                    echo '<div class="w-full h-full flex flex-col items-center justify-end">';
                    // Compteur
                    echo '<span class="text-xs font-bold text-gray-700 mb-1">' . $count . '</span>';
                    // Barre
                    echo '<div class="w-2/3 rounded-t-lg shadow-md transition-all duration-700 ease-out ' . $data['color'] . '" style="height: ' . $heightPercent . '%;"></div>'; 
                    echo '</div>';

                    // Libellé
                    echo '<div class="text-sm font-medium mt-3 whitespace-nowrap text-center ' . $data['text'] . '">' . $data['label'] . '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <!-- --- FIN GRAPHIQUE À BARRES DE RÉSUMÉ --- -->
        
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
                            <span>Choix de Réponse</span>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">
                            <span>Time</span>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">
                            <span>Points</span>
                        </th>  
                       
                    </tr>
                </thead>
                <tbody id="results-table-body" class="bg-white divide-y divide-gray-200">
                    <?php 
                    // Loop through the results and output HTML rows
                    foreach ($result as $row) {
                        $points = $row['points_s_answer'];
                        // Couleur du badge Points
                        $pointsClass = $points > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                        // Couleur du badge Choix de Réponse
                        $answerColorClass = '';
                        switch ($row['answer_s_answer']) {
                            case '0': $answerColorClass = 'bg-gray-100 text-black-800'; break;
                            case '1': $answerColorClass = 'bg-blue-100 text-blue-800'; break;
                            case '2': $answerColorClass = 'bg-green-100 text-green-800'; break;
                            case '3': $answerColorClass = 'bg-yellow-100 text-yellow-800'; break;
                            case '4': $answerColorClass = 'bg-red-100 text-red-800'; break;
                            default: $answerColorClass = 'bg-gray-100 text-gray-800'; break;
                        }

                        echo '<tr class="hover:bg-gray-50 transition duration-150 ease-in-out">';
                        
                        // User ID
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-teal-600">' . htmlspecialchars($row['id_s_user']) . '</td>';
                        
                        // User Name
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['name_user']) . '</td>';
                        
                        // Choix de Réponse (Answer Text) - affiché comme un badge
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center">';
                        echo '<span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium ' . $answerColorClass . '">';
                        echo 'Choix ' . htmlspecialchars($row['answer_s_answer']);
                        echo '</span>';
                        echo '</td>';
                        
                        // Time
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['time_s_answer'] - $row['start_time_s_answer'] ) . '</td>';
                        
                        // Points
                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center">';
                        echo '<span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium ' . $pointsClass . '">' . htmlspecialchars($row['points_s_answer']) . '</span>';
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

