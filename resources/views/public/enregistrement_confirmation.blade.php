<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enregistrement confirmé – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto py-16 px-4 text-center">
        <div class="bg-white rounded-2xl shadow-sm p-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check text-3xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Enregistrement effectué !</h1>
            <p class="text-gray-600 mb-6">Votre enfant est maintenant enregistré. Veuillez vous rendre à l'établissement pour finaliser son inscription.</p>
            <a href="{{ route('home') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-full font-semibold hover:bg-indigo-700 transition">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>