<!DOCTYPE html>
<html>
<head>
    <title>Nouveau message de contact</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px;">
        <h2 style="margin-top: 0;">Nouveau message de {{ $message->nom }}</h2>
        <p><strong>Email :</strong> {{ $message->email }}</p>
        <p><strong>Sujet :</strong> {{ $message->sujet }}</p>
        <p><strong>Message :</strong></p>
        <p>{{ $message->message }}</p>
    </div>
</body>
</html>