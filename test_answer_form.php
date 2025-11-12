<!DOCTYPE html>
<html lang="fr"> 
<head>
    <meta charset="UTF-8">
    <title>Test d'Écriture dans la Table 'answer'</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 40px auto; padding: 20px; border: 1px solid #ccc; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { margin-top: 20px; padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    
    <h1>Tester l'Insertion dans 'answer'</h1>

    <form action="handle_answer_test.php" method="POST">
        
        <p>Entrez les valeurs pour simuler une réponse d'une remote:</p>
        
        <label for="id_user">ID Utilisateur (id_user) :</label>
        <input type="number" id="id_user" name="id_user" value="1" required>

        <label for="answer_answer">Réponse Choisie (answer_answer - Option 1 à 4, ou 0 si pas de réponse) :</label>
        <input type="number" id="answer_answer" name="answer_answer" value="2" required min="0" max="4">

        <button type="submit">Tester l'Insertion</button>

    </form>

</body>
</html>
