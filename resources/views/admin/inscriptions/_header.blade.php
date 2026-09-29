@php
    $logo = \App\Models\Setting::getValue('ecole_logo');
    $nom = \App\Models\Setting::getValue('ecole_nom', 'Mon École');
    $adresse = \App\Models\Setting::getValue('ecole_adresse');
    $tel = \App\Models\Setting::getValue('ecole_telephone');
    $email = \App\Models\Setting::getValue('ecole_email');
@endphp

<table style="width:100%; margin-bottom:25px; border-collapse:collapse;">
    <tr>
        <td style="width:80px; vertical-align:middle; padding-right:15px;">
            @if($logo)
                <img src="{{ public_path('storage/'.$logo) }}" style="height:70px; border-radius:8px;">
            @else
                <div style="width:70px; height:70px; background:linear-gradient(135deg, #667eea, #764ba2); border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:24px; font-weight:bold;">
                    {{ substr($nom, 0, 1) }}
                </div>
            @endif
        </td>
        <td style="vertical-align:middle;">
            <h1 style="margin:0; font-size:22px; color:#1e293b; font-weight:800; letter-spacing:-0.5px;">{{ $nom }}</h1>
            <p style="margin:4px 0 0; font-size:11px; color:#64748b; line-height:1.5;">
                @if($adresse){{ $adresse }}@endif
                @if($adresse && $tel) <span style="color:#cbd5e1;">|</span> @endif
                @if($tel)Tél : {{ $tel }}@endif
                @if(($adresse || $tel) && $email) <span style="color:#cbd5e1;">|</span> @endif
                @if($email)Email : {{ $email }}@endif
            </p>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="padding-top:10px;">
            <hr style="border:none; border-top:2px solid #667eea; margin:0;">
        </td>
    </tr>
</table>