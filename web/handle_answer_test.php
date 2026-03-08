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
$id_user = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
$answer_answer = isset($_POST['answer_answer']) ? (int)$_POST['answer_answer'] : 0; 

$message = "";
$success = false;

if ($id_user === 0 || $answer_answer === 0) {
    $message = "Erreur : Les champs sont invalides ou manquants.";
} else {
    // --- TRAITEMENT ET ENREGISTREMENT DANS LA BASE DE DONNÉES ---
    $conn = connect_db($servername, $username, $password, $dbname);
	
	$time_answer = floor(microtime(true) * 1000);
    //echo $time_answer;
            //a supprimmer
                if($answer_answer=='11') $answer_answer='1';
                if($answer_answer=='22') $answer_answer='2';
                if($answer_answer=='33') $answer_answer='3';
                if($answer_answer=='44') $answer_answer='4';
            //
    // Requête préparée pour l'insertion sécurisée
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO current_answer (id_c_user, answer_c_answer, time_c_answer) 
         VALUES (?, ?, ?)"
    );
    	
	// Liaison des paramètres : 'iii' pour trois entiers
    mysqli_stmt_bind_param($stmt, "iii", 
        $id_user, 
        $answer_answer,
        $time_answer
    );

    if (mysqli_stmt_execute($stmt)) {
        $message = "🎉 **SUCCÈS !** La réponse de test a été insérée dans la table `answer`.";
        $success = true;
    } else {
        $message = "❌ **ÉCHEC !** Erreur lors de l'enregistrement : " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>
