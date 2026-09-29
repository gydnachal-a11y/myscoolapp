<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de {{ $eleve->nom_complet }} - {{ $periode->nom }}</title>

    @php
        // ✅ Fallback : ne jamais crasher sur $siteSettings
        $siteSettings ??= (object) \App\Models\SiteSetting::getDefaults();

        $pourcentage ??= null;
        $logoPath = !empty($siteSettings->site_logo)
            ? public_path('storage/' . $siteSettings->site_logo)
            : null;
        $logoExists = $logoPath && file_exists($logoPath);
    @endphp

    <style>
        /* ========== RESET ========== */
        @page { margin: 25mm 15mm 20mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 12px;
            line-height: 1.5;
        }

        /* ========== EN-TÊTE ÉTABLISSEMENT ========== */
        .school-header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
        }
        .school-logo,
        .school-info { display: table-cell; vertical-align: middle; }

        .school-logo { width: 80px; }

        .school-logo img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: #4f46e5;
            color: #fff;
            text-align: center;
            line-height: 70px;
            font-size: 28px;
            font-weight: bold;
            border-radius: 8px;
        }

        .school-info h1 {
            font-size: 20px;
            color: #1e293b;
            margin: 0 0 4px;
        }
        .school-info p {
            margin: 1px 0;
            color: #475569;
            font-size: 11px;
        }

        /* ========== TITRES DOCUMENT ========== */
        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 15px 0 3px;
            color: #4f46e5;
        }

        .document-subtitle {
            text-align: center;
            font-size: 12px;
            color: #475569;
            margin-bottom: 15px;
        }

        /* ========== INFOS ÉLÈVE ========== */
        .info-eleve {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            font-size: 12px;
            background: #f8fafc;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .info-item { display: table-cell; }
        .info-eleve span { font-weight: bold; color: #334155; }

        /* ========== TABLEAU ========== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 7px 9px;
            text-align: left;
        }

        th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
        }

        td.center { text-align: center; }
        td.note   { text-align: center; font-weight: bold; color: #4f46e5; }

        tbody tr:nth-child(even) { background-color: #f8fafc; }

        /* ========== RÉSULTATS ========== */
        .resultats {
            display: table;
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            background: #eef2ff;
            border-radius: 6px;
            border: 1px solid #c7d2fe;
        }
        .resultat-item {
            display: table-cell;
            text-align: center;
            width: 50%;
        }
        .resultat-item .label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .resultat-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #4f46e5;
            margin-top: 2px;
        }
        .resultat-item .value small {
            font-size: 10px;
            color: #94a3b8;
            font-weight: normal;
        }

        /* ========== SIGNATURES ========== */
        .signatures {
            display: table;
            width: 100%;
            margin-top: 35px;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            font-size: 11px;
            color: #475569;
            padding: 0 15px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            margin-top: 35px;
            padding-top: 5px;
        }

        /* ========== FOOTER ========== */
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    {{-- ===== En-tête établissement ===== --}}
    <div class="school-header">
        <div class="school-logo">
            @if($logoExists)
                <img src="{{ $logoPath }}" alt="Logo {{ $siteSettings->site_name }}">
            @else
                <div class="logo-placeholder">
                    {{ strtoupper(substr($siteSettings->site_name ?? 'E', 0, 1)) }}
                </div>
            @endif
        </div>

        <div class="school-info">
            <h1>{{ $siteSettings->site_name ?? config('app.name', 'Mon École') }}</h1>
            @if(!empty($siteSettings->site_address))
                <p>{{ $siteSettings->site_address }}</p>
            @endif
            @if(!empty($siteSettings->site_email))
                <p>{{ $siteSettings->site_email }}</p>
            @endif
            @if(!empty($siteSettings->site_phone))
                <p>{{ $siteSettings->site_phone }}</p>
            @endif
        </div>
    </div>

    {{-- ===== Titres ===== --}}
    <h2 class="document-title">Bulletin de notes</h2>
    <p class="document-subtitle">
        Année scolaire : {{ $periode->anneeScolaire?->nom ?? $periode->anneeScolaire?->libelle ?? 'Non spécifiée' }}
    </p>

    {{-- ===== Infos élève ===== --}}
    <div class="info-eleve">
        <div class="info-item">
            <span>Élève :</span> {{ $eleve->nom_complet }}
        </div>
        <div class="info-item" style="text-align:right;">
            <span>Période :</span> {{ $periode->nom }}
        </div>
    </div>

    {{-- ===== Tableau des notes ===== --}}
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Cours</th>
                <th style="width: 15%; text-align:center;">Pondération</th>
                <th style="width: 15%; text-align:center;">Note / 20</th>
                <th style="width: 30%;">Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notes as $note)
                @php $coeff = $note->courSalle?->ponderation?->valeur ?? 1; @endphp
                <tr>
                    <td>{{ $note->courSalle?->cour?->nom ?? 'Cours supprimé' }}</td>
                    <td class="center">{{ $coeff }}</td>
                    <td class="note">
                        {{ $note->note !== null ? number_format($note->note, 2, ',', ' ') : '—' }}
                    </td>
                    <td>{{ $note->appreciation ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 15px;">
                        Aucune note disponible pour cette période.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== Résultats ===== --}}
    @if($moyenne !== null || $pourcentage !== null)
        <div class="resultats">
            @if($moyenne !== null)
                <div class="resultat-item">
                    <div class="label">Moyenne pondérée</div>
                    <div class="value">
                        {{ number_format($moyenne, 2, ',', ' ') }}<small> / 20</small>
                    </div>
                </div>
            @endif

            @if($pourcentage !== null)
                <div class="resultat-item">
                    <div class="label">Pourcentage</div>
                    <div class="value">
                        {{ number_format($pourcentage, 2, ',', ' ') }}<small> %</small>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ===== Signatures ===== --}}
    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line">Le titulaire</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Le directeur</div>
        </div>
    </div>

    {{-- ===== Footer ===== --}}
    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>

</body>
</html>