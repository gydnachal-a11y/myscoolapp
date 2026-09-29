<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des élèves</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== Réinitialisation et base ===== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            padding: 15px;
            background: #f8fafc;
        }

        .page-container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }

        /* ===== En-tête de l'école (Style Badge/Carte moderne) ===== */
        .school-header {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 20px;
            width: 100%;
            margin: 0 auto 25px auto;
            padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
            position: relative;
        }

        .school-logo { 
            flex-shrink: 0; 
        }

        .school-logo img {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.08);
            border: 2px solid #ffffff;
        }

        .logo-placeholder {
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .school-info {
            flex-grow: 1;
        }

        .school-info h1 {
            font-size: 20px;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 6px;
            letter-spacing: -0.4px;
            text-transform: uppercase;
        }

        .school-info p {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ===== Titres du document ===== */
        .document-header-box {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            margin-bottom: 25px;
        }

        .document-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1e3a8a;
            margin-bottom: 6px;
        }

        .document-subtitle {
            font-size: 11px;
            color: #475569;
            font-weight: 500;
        }

        .document-subtitle span {
            background: #e0e7ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            margin-top: 4px;
        }

        .total-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #dbeafe;
        }

        /* ===== Sections et Groupements ===== */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 25px 0 8px;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 4px solid #3b82f6;
            padding-left: 8px;
            page-break-after: avoid;
        }

        .session-title {
            font-size: 12px;
            font-weight: 700;
            margin: 15px 0 6px;
            color: #334155;
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 6px;
            page-break-after: avoid;
        }

        .salle-title {
            font-size: 11px;
            font-weight: 600;
            margin: 10px 0 4px 10px;
            color: #64748b;
            page-break-after: avoid;
        }

        /* ===== Tableaux élégants ===== */
        table {
            width: 100%;
            margin: 0 auto 20px auto;
            border-collapse: collapse;
            font-size: 11px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        th {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.6px;
            padding: 10px 12px;
            text-align: left;
            border: none;
        }

        td {
            padding: 9px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #334155;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:nth-child(even) { 
            background-color: #f8fafc; 
        }
        
        tbody tr:hover { 
            background-color: #f1f5f9; 
        }

        .col-numero {
            width: 40px;
            text-align: center;
            color: #94a3b8;
            font-weight: 600;
        }

        .badge-sexe {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-sexe.m { background: #eff6ff; color: #1d4ed8; }
        .badge-sexe.f { background: #fdf2f8; color: #db2777; }

        .empty {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-style: italic;
            background: #f8fafc;
        }

        /* ===== Pied de page ===== */
        .footer {
            width: 100%;
            margin: 30px auto 0;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }

        /* ===== Styles Impression / PDF ===== */
        @media print {
            body { 
                margin: 0; 
                padding: 0; 
                background: #ffffff;
                font-size: 10px;
            }
            .page-container {
                max-width: 100%;
                box-shadow: none;
                border: none;
                padding: 10px;
                border-radius: 0;
            }
            .school-header { border-bottom: 2px solid #cbd5e1; }
            .document-header-box { background: #f8fafc !important; -webkit-print-color-adjust: exact; }
            th { background: #1e3a8a !important; color: white !important; -webkit-print-color-adjust: exact; }
            .section-title, .session-title { page-break-after: avoid; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="page-container">

    <div class="school-header">
        <div class="school-logo">
            @if($siteSettings->site_logo)
                <img src="{{ asset('storage/' . $siteSettings->site_logo) }}" alt="{{ $siteSettings->site_name }}">
            @else
                <div class="logo-placeholder"><i class="fa-solid fa-graduation-cap"></i></div>
            @endif
        </div>
        <div class="school-info">
            <h1>{{ $siteSettings->site_name ?? config('app.name', 'Mon École') }}</h1>
            @if($siteSettings->site_address)<p><i class="fa-solid fa-location-dot text-indigo-600"></i> {{ $siteSettings->site_address }}</p>@endif
            @if($siteSettings->site_email)<p><i class="fa-solid fa-envelope text-indigo-600"></i> {{ $siteSettings->site_email }}</p>@endif
            @if($siteSettings->site_phone)<p><i class="fa-solid fa-phone text-indigo-600"></i> {{ $siteSettings->site_phone }}</p>@endif
        </div>
    </div>

    <div class="document-header-box">
        <div class="document-title">Liste officielle des élèves</div>
        <div class="document-subtitle">
            @if(request('salle'))
                Salle : <span>{{ \App\Models\SalleDeClasse::find(request('salle'))?->nom ?? '—' }}</span>
                @php $salleOption = \App\Models\SalleDeClasse::find(request('salle'))?->option; @endphp
                @if($salleOption) — Option : <span>{{ $salleOption->nom }}</span> @endif
            @elseif(request('option'))
                Option : <span>{{ \App\Models\Option::find(request('option'))?->nom ?? '—' }}</span>
            @elseif(request('section'))
                Section : <span>{{ \App\Models\Section::find(request('section'))?->nom ?? '—' }}</span>
            @elseif(request('session'))
                Session : <span>{{ \App\Models\Session::find(request('session'))?->nom ?? '—' }}</span>
            @else
                <span>Tous les élèves inscrits</span>
            @endif
        </div>
    </div>

    @php
        // Détection de la structure : groupée (Section -> Session -> Salle) ou simple
        $isGrouped = $inscriptions->isNotEmpty() && $inscriptions->first() instanceof \Illuminate\Support\Collection;

        $totalEleves = 0;
        if ($inscriptions instanceof \Illuminate\Support\Collection) {
            if ($isGrouped) {
                $totalEleves = $inscriptions->flatten(2)->count();
            } else {
                $totalEleves = $inscriptions->count();
            }
        }
    @endphp

    @if($totalEleves > 0)
        <div style="text-align: center;">
            <div class="total-badge">
                <i class="fa-solid fa-users"></i> <strong>{{ $totalEleves }}</strong> élève(s) enregistré(s)
            </div>
        </div>
    @endif

    @if(!$isGrouped)
        <table>
            <thead>
                <tr>
                    <th class="col-numero">#</th>
                    <th>Élève</th>
                    <th>Sexe</th>
                    <th>Âge</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inscriptions as $index => $ins)
                    @php
                        $eleve = $ins->eleve ?? null;
                        $sexeCode = strtolower($eleve->sexe ?? $eleve->sexe_libelle ?? '');
                        $isF = str_starts_with($sexeCode, 'f') || str_starts_with($sexeCode, 'm'); // juste pour la classe
                    @endphp
                    <tr>
                        <td class="col-numero">{{ $index + 1 }}</td>
                        <td style="font-weight: 600;">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : '—' }}</td>
                        <td>
                            @php $sexeLib = $eleve->sexe_libelle ?? '—'; @endphp
                            <span class="badge-sexe {{ str_starts_with(strtolower($sexeLib), 'f') ? 'f' : 'm' }}">
                                {{ $sexeLib }}
                            </span>
                        </td>
                        <td>{{ $eleve && $eleve->date_naissance ? $eleve->date_naissance->age . ' ans' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Aucun élève trouvé</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        @forelse($inscriptions as $sectionName => $sessions)
            <div class="section-title"><i class="fa-solid fa-layer-group"></i> {{ $sectionName }}</div>
            @foreach($sessions as $sessionName => $salles)
                <div class="session-title"><i class="fa-solid fa-calendar-days"></i> {{ $sessionName }}</div>
                @foreach($salles as $salleName => $inscriptionsParSalle)
                    @php
                        $premiere = $inscriptionsParSalle->first();
                        $option = $premiere && $premiere->salleDeClasse ? $premiere->salleDeClasse->option : null;
                    @endphp
                    <div class="salle-title">
                        <i class="fa-solid fa-door-open"></i> {{ $salleName }} @if($option) <span style="color: #3b82f6;">(Option : {{ $option->nom }})</span> @endif
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th class="col-numero">#</th>
                                <th>Élève</th>
                                <th>Sexe</th>
                                <th>Âge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inscriptionsParSalle as $index => $ins)
                                @php $eleve = $ins->eleve ?? null; @endphp
                                <tr>
                                    <td class="col-numero">{{ $index + 1 }}</td>
                                    <td style="font-weight: 600;">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : '—' }}</td>
                                    <td>
                                        @php $sexeLib = $eleve->sexe_libelle ?? '—'; @endphp
                                        <span class="badge-sexe {{ str_starts_with(strtolower($sexeLib), 'f') ? 'f' : 'm' }}">
                                            {{ $sexeLib }}
                                        </span>
                                    </td>
                                    <td>{{ $eleve && $eleve->date_naissance ? $eleve->date_naissance->age . ' ans' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @endforeach
        @empty
            <p class="empty">Aucun élève trouvé</p>
        @endforelse
    @endif

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $siteSettings->site_name ?? config('app.name') }}
    </div>

</div>

</body>
</html>