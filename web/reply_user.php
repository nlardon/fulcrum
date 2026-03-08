<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test d'Écriture dans la Table 'answer'</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Les styles personnalisés ne sont plus nécessaires avec Tailwind, 
           mais on peut garder la configuration de base du corps si besoin */
        body { 
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 text-center">Tester l'Insertion dans 'answer'</h1>

        <form action="handle_answer_test.php" method="POST" id="answerForm">
            
            <p class="mb-4 text-sm text-gray-600">Entrez les valeurs pour simuler une réponse d'une remote:</p>
            
            <label for="id_user" class="block text-sm font-medium text-gray-700">ID Utilisateur (id_user) :</label>
            <input type="number" id="id_user" name="id_user" value="1" required 
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">

            <input type="hidden" id="answer_answer" name="answer_answer" value="0">

            <div class="mt-8">
                <label class="block text-sm font-medium text-gray-700 mb-3">Choisissez une Réponse :</label>
                <div class="grid grid-cols-2 gap-4">
                    
                    <button type="button" data-answer="1"
                            class="answer-button w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        Réponse 1
                    </button>
                    
                    <button type="button" data-answer="2"
                            class="answer-button w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        Réponse 2
                    </button>
                    
                    <button type="button" data-answer="3"
                            class="answer-button w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-800 bg-yellow-400 hover:bg-yellow-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition duration-150 ease-in-out">
                        Réponse 3
                    </button>
                    
                    <button type="button" data-answer="4"
                            class="answer-button w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                        Réponse 4
                    </button>
                    
                </div>
            </div>
            
            <button type="submit" id="submitButton"
                    class="w-full mt-6 py-3 px-4 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
                    disabled>
                Tester l'Insertion (Sélectionnez une réponse)
            </button>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const answerButtons = document.querySelectorAll('.answer-button');
            const answerInput = document.getElementById('answer_answer');
            const submitButton = document.getElementById('submitButton');
            const form = document.getElementById('answerForm');
            
            let selectedAnswer = null;

            answerButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    // Empêche la soumission du formulaire
                    e.preventDefault(); 
                    
                    // Réinitialise la sélection visuelle
                    answerButtons.forEach(btn => {
                        btn.classList.remove('ring-4', 'ring-offset-2', 'ring-opacity-75');
                        btn.classList.remove('ring-blue-300', 'ring-green-300', 'ring-yellow-300', 'ring-red-300');
                        btn.classList.add('shadow-sm');
                    });
                    
                    // Récupère la valeur de la réponse
                    selectedAnswer = button.getAttribute('data-answer');
                    answerInput.value = selectedAnswer;

                    // Applique l'effet de sélection visuelle
                    const colorMap = {
                        '1': 'ring-blue-300',
                        '2': 'ring-green-300',
                        '3': 'ring-yellow-300',
                        '4': 'ring-red-300'
                    };

                    button.classList.add('ring-4', 'ring-offset-2', 'ring-opacity-75', colorMap[selectedAnswer]);
                    button.classList.remove('shadow-sm');

                    // Active le bouton de soumission et met à jour son texte
                    submitButton.disabled = false;
                    submitButton.textContent = `Tester l'Insertion (Réponse ${selectedAnswer} sélectionnée)`;
                });
            });

            // Gérer la soumission du formulaire (juste pour être sûr que le script ne bloque pas la soumission)
            form.addEventListener('submit', (e) => {
                if (!selectedAnswer) {
                    alert("Veuillez sélectionner une réponse avant de soumettre.");
                    e.preventDefault();
                }
            });
        });
    </script>

</body>
</html>
