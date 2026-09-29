<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des élèves</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            margin: 30px;
            color: #1e293b;
        }

        /* ===== En-tête de l'établissement (centré) ===== */
        .school-header {
            width: 70%;
            margin: 0 auto 20px auto;
            border-collapse: collapse;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
        }

        .school-logo {
            width: 80px;
            vertical-align: middle;
            text-align: center;
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
            display: inline-block;
            text-align: center;
            line-height: 70px;
            font-size: 28px;
            border-radius: 10px;
        }

        .school-info {
            vertical-align: middle;
            padding-left: 15px;
            text-align: left;
        }

        .school-info h1 {
            font-size: 20px;
            color: #111827;
            margin-bottom: 4px;
        }

        .school-info p {
            margin: 1px 0;
            color: #475569;
            font-size: 12px;
        }

        /* ===== Titre principal ===== */
        h1 {
            text-align: center;
            font-size: 18px;
            margin: 20px 0 5px;
            color: #1e293b;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 20px;
            font-style: italic;
        }

        .count {
            text-align: center;
            font-size: 13px;
            color: #475569;
            margin-bottom: 15px;
        }

        /* ===== Tableau de données centré ===== */
        table.data {
            width: 80%;
            margin: 0 auto;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }

        table.data td {
            padding: 8px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 12px;
        }

        table.data tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .empty {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
        }

        /* ===== Pied de page ===== */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>

    {{-- En-tête de l'école centré --}}
    <table class="school-header">
        <tr>
            <td class="school-logo">
                @if($siteSettings->site_logo)
                    <img src="{{ public_path('storage/' . $siteSettings->site_logo) }}" alt="{{ $siteSettings->site_name }}">
                @else
                    <span class="logo-placeholder"><i class="fa-solid fa-school"></i></span>
                @endif
            </td>
            <td class="school-info">
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
            </td>
        </tr>
    </table>

    <h1>Liste des élèves</h1>

    <div class="subtitle">
        @if(request('salle'))
            Salle : {{ \App\Models\SalleDeClasse::find(request('salle'))?->nom ?? '—' }}
        @endif
        @if(request('option'))
            Option : {{ \App\Models\Option::find(request('option'))?->nom ?? '—' }}
        @endif
        @if(request('section'))
            Section : {{ \App\Models\Section::find(request('section'))?->nom ?? '—' }}
        @endif
        @if(request('session'))
            Session : {{ \App\Models\Session::find(request('session'))?->nom ?? '—' }}
        @endif
        @if(request('recherche'))
            Recherche : "{{ request('recherche') }}"
        @endif
        @if(!request('salle') && !request('option') && !request('section') && !request('session') && !request('recherche'))
            Tous les élèves inscrits
        @endif
    </div>

    <div class="count">
        Total : {{ $inscriptions->count() }} élève(s)
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Élève</th>
                <th>Sexe</th>
                <th>Âge</th>
                <th>Année</th>
                <th>Salle</th>
                <th>Option</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscriptions as $ins)
                <tr>
                    <td>{{ $ins->eleve->nom }} {{ $ins->eleve->prenom }}</td>
                    <td>{{ $ins->eleve->sexe_libelle }}</td>
                    <td>{{ $ins->eleve->date_naissance ? $ins->eleve->date_naissance->age . ' ans' : '—' }}</td>
                    <td>{{ $ins->anneeScolaire->libelle ?? '—' }}</td>
                    <td>{{ $ins->salleDeClasse->nom ?? '—' }}</td>
                    <td>{{ $ins->salleDeClasse->option->nom ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">Aucun élève trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - {{ $siteSettings->site_name ?? config('app.name') }}
    </div>

</body>
</html>