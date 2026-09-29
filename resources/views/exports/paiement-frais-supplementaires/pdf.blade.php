<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Paiements des frais supplémentaires</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 30px;
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
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: #4f46e5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            border-radius: 10px;
        }

        .school-info h1 {
            font-size: 20px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .school-info p {
            margin: 1px 0;
            color: #475569;
            font-size: 12px;
        }

        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 20px 0 5px;
            color: #4f46e5;
        }

        .document-subtitle {
            text-align: center;
            font-size: 13px;
            color: #475569;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- En-tête de l'école --}}
    <div class="school-header">
        @if($siteSettings->site_logo)
            <div class="school-logo">
                <img src="{{ public_path('storage/' . $siteSettings->site_logo) }}" alt="{{ $siteSettings->site_name }}">
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

    <div class="document-title">Paiements des frais supplémentaires</div>
    <div class="document-subtitle">
        Total : {{ $paiements->count() }} paiement(s)
    </div>

    <table>
        <thead>
            <tr>
                <th>Élève</th>
                <th>Frais</th>
                <th class="text-right">Montant USD</th>
                <th class="text-right">Montant FC</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paiements as $p)
                <tr>
                    <td>{{ $p->eleve?->nom . ' ' . $p->eleve?->prenom }}</td>
                    <td>{{ $p->fraisSupplementaire?->libelle ?? '—' }}</td>
                    <td class="text-right">{{ number_format($p->montant_paye_usd, 0, ',', ' ') }} $</td>
                    <td class="text-right">{{ number_format($p->montant_paye_fc, 0, ',', ' ') }} FC</td>
                    <td>{{ $p->date_paiement->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">Aucun paiement trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - {{ $siteSettings->site_name ?? config('app.name') }}
    </div>

</body>
</html>