<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement - {{ $salle->nom }} - {{ $periode->nom }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            background: white;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        /* En-tête de l'établissement */
        .school-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
        }

        .school-logo {
            flex-shrink: 0;
        }

        .school-logo img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border-radius: 10px;
        }

        .school-info h1 {
            font-size: 22px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .school-info p {
            font-size: 13px;
            color: #475569;
            margin: 1px 0;
        }

        .document-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 20px 0 5px;
            color: #4f46e5;
        }

        .document-subtitle {
            text-align: center;
            font-size: 14px;
            color: #475569;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .rang {
            font-weight: bold;
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        @media print {
            body {
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- En-tête de l'école --}}
    <div class="school-header">
        @if($siteSettings->site_logo)
            <div class="school-logo">
                <img src="{{ asset('storage/' . $siteSettings->site_logo) }}" alt="{{ $siteSettings->site_name }}">
            </div>
        @else
            <div class="logo-placeholder">
                <i class="fa-solid fa-school"></i>
            </div>
        @endif
        <div class="school-info">
            <h1>{{ $siteSettings->site_name ?? config('app.name', 'Mon École') }}</h1>
            @if($siteSettings->site_address)
                <p>{{ $siteSettings->site_address }}</p>
            @endif
            @if($siteSettings->site_email)
                <p>{{ $siteSettings->site_email }}</p>
            @endif
            @if($siteSettings->site_phone)
                <p>{{ $siteSettings->site_phone }}</p>
            @endif
        </div>
    </div>

    <div class="document-title">Classement des élèves</div>
    <div class="document-subtitle">
        Salle : {{ $salle->nom }} - Période : {{ $periode->nom }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Élève</th>
                <th>Moyenne /20</th>
                <th>Pourcentage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classement as $item)
                <tr>
                    <td class="rang">{{ $item['rang'] }}</td>
                    <td>{{ $item['eleve']->nom_complet }}</td>
                    <td>{{ $item['moyenne'] !== null ? number_format($item['moyenne'], 2) : '—' }}</td>
                    <td>{{ $item['pourcentage'] !== null ? $item['pourcentage'] . '%' : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">Aucun élève trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer no-print">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>

    {{-- FontAwesome pour l'icône de l'école si pas de logo --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>
</html>