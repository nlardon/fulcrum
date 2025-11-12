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

    $id_question = 1;
    $message = "";
    $success = false;
    $conn = connect_db($servername, $username, $password, $dbname);

    // Requête préparée pour l'insertion
    $sql = "SELECT * FROM `current_answer` WHERE 1;" ;
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        echo "current answer -> id: " . $row["id_c_answer"]. " - User: " . $row["id_c_user"]. " - Answer: " . $row["answer_c_answer"]. " - Time: " . $row["time_c_answer"] . "<br>";
    }
    } else {
    echo "0 results";
    }


    //ecrire fonction calcul des points
    $sql = "SELECT `correct_question`, `point_question` FROM `question` WHERE `id_question` = " . $id_question . ";" ;
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        $true_answer = $row["correct_question"];
        $point = $row["point_question"];
    }
    } else {
    echo "0 results";
    }

    echo $true_answer;
    echo $point;

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
        echo "0 results";
    }

    $nb_user = 0;
    $sql = "SELECT * FROM `user` WHERE 1;" ;
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
    // output data of each row
    while($row = mysqli_fetch_assoc($result)) {
        $nb_user = $nb_user + 1;
    }
    } else {
    echo "0 results";
    }
    //echo $nb_user;
    $user = 1;
    while($user <= $nb_user ) {
            $sql = "SELECT * FROM `current_answer` WHERE `id_c_user` = " . $user . ";" ;
            $result = mysqli_query($conn, $sql);
            $row = mysqli_fetch_assoc($result);

            if($row!=NULL){
                $stmt = mysqli_prepare($conn, 
                "INSERT INTO store_answer (id_s_user, id_s_question, answer_s_answer, time_s_answer, points_s_answer) 
                VALUES (?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "iiiii", 
                $user, 
                $id_question,
                $row["answer_c_answer"],
                $row["time_c_answer"],
                $row["points_c_answer"]
                );

            }else{
                $stmt = mysqli_prepare($conn, 
                "INSERT INTO store_answer (id_s_user, id_s_question) 
                VALUES (?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "ii", 
                $user, 
                $id_question
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
?>
