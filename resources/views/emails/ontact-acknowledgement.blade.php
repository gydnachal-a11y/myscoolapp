@component('mail::message')
# Merci {{ $message->nom }}

Nous avons bien reçu votre message intitulé **« {{ $message->sujet }} »**.

Notre équipe vous répondra dans les meilleurs délais à cette adresse.

@component('mail::panel')
> {{ Str::limit($message->message, 200) }}
@endcomponent

Cordialement,  
L'équipe de l'établissement
@endcomponent