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

    // Requête préparée pour l'insertion
    $sql = "SELECT * FROM `current_answer` WHERE 1;" ;
    $result = mysqli_query($conn, $sql);


if (mysqli_num_rows($result) > 0) {
  // output data of each row
  while($row = mysqli_fetch_assoc($result)) {
	echo "id: " . $row["id_c_answer"]. " - User: " . $row["id_c_user"]. " - Answer: " . $row["answer_c_answer"]. " - Time: " . $row["time_c_answer"] . "<br>";
  }
} else {
  echo "0 results";
}


mysqli_close($conn);
?>
