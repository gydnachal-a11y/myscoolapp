<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Bulletin de {{ $eleve->nom_complet }} - {{ $periode->nom }}</title>

    @php
        // ✅ Fallback : si le View Composer ne s'est pas exécuté, on récupère
        //    les settings directement. Le `??=` garantit qu'on ne réécrase
        //    pas une valeur déjà définie par le composer.
        $siteSettings ??= (object) \App\Models\SiteSetting::getDefaults();

        // ✅ Guard : au cas où le contrôleur n'a pas passé $pourcentage
        $pourcentage ??= null;

        // ✅ Guard : normalisation du numéro de téléphone pour le lien tel:
        $telHref = $siteSettings->site_phone
            ? 'tel:' . preg_replace('/\s+/', '', $siteSettings->site_phone)
            : null;
    @endphp

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </noscript>

    <style>
        /* ========== RESET ========== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        /* ========== PAGE ========== */
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            background: #fff;
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== EN-TÊTE ÉTABLISSEMENT ========== */
        .school-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
        }

        .school-logo { flex-shrink: 0; }

        .school-logo img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
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
            letter-spacing: -0.3px;
        }

        .school-info p {
            font-size: 13px;
            color: #475569;
            margin: 1px 0;
        }

        /* ========== TITRES DOCUMENT ========== */
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

        /* ========== INFOS ÉLÈVE ========== */
        .info-eleve {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 15px;
            background: #f8fafc;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .info-eleve span { font-weight: bold; }

        /* ========== TABLEAU ========== */
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
            vertical-align: middle;
        }

        th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: #334155;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        td.note     { text-align: center; font-weight: 600; }
        td.pondere  { text-align: center; color: #64748b; }

        tbody tr:nth-child(even) { background-color: #f8fafc; }

        /* ========== MOYENNE ========== */
        .resultats {
            display: flex;
            justify-content: flex-end;
            gap: 2rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .resultat-item {
            text-align: right;
            font-size: 15px;
        }

        .resultat-item .label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resultat-item .value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #4f46e5;
        }

        /* ========== SIGNATURES ========== */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            gap: 20px;
        }

        .signature-block {
            width: 45%;
            text-align: center;
            font-size: 12px;
            color: #475569;
        }

        .signature-line {
            border-top: 1px solid #94a3b8;
            margin-top: 50px;
            padding-top: 5px;
        }

        /* ========== FOOTER ========== */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        /* ========== IMPRESSION ========== */
        @media print {
            body {
                padding: 0;
                max-width: none;
            }

            .no-print { display: none !important; }

            .school-header { page-break-after: avoid; }
            table           { page-break-inside: auto; }
            tr              { page-break-inside: avoid; page-break-after: auto; }
            thead           { display: table-header-group; }
            .signatures     { page-break-inside: avoid; }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- ===== En-tête établissement ===== --}}
    <header class="school-header">
        @if(!empty($siteSettings->site_logo))
            <div class="school-logo">
                <img src="{{ asset('storage/' . $siteSettings->site_logo) }}"
                     alt="Logo {{ $siteSettings->site_name }}">
            </div>
        @else
            <div class="logo-placeholder" aria-hidden="true">
                <i class="fa-solid fa-school"></i>
            </div>
        @endif

        <div class="school-info">
            <h1>{{ $siteSettings->site_name ?? config('app.name', 'Mon École') }}</h1>

            @if(!empty($siteSettings->site_address))
                <p>{{ $siteSettings->site_address }}</p>
            @endif

            @if(!empty($siteSettings->site_email))
                <p>{{ $siteSettings->site_email }}</p>
            @endif

            @if(!empty($siteSettings->site_phone))
                <p>
                    @if($telHref)
                        <a href="{{ $telHref }}" style="color: inherit; text-decoration: none;">
                            {{ $siteSettings->site_phone }}
                        </a>
                    @else
                        {{ $siteSettings->site_phone }}
                    @endif
                </p>
            @endif
        </div>
    </header>

    {{-- ===== Titre document ===== --}}
    <h2 class="document-title">Bulletin de notes</h2>
    <p class="document-subtitle">
        Année scolaire : {{ $periode->anneeScolaire?->nom ?? 'Non spécifiée' }}
    </p>

    {{-- ===== Infos élève ===== --}}
    <div class="info-eleve">
        <p><span>Élève :</span> {{ $eleve->nom_complet }}</p>
        <p><span>Période :</span> {{ $periode->nom }}</p>
    </div>

    {{-- ===== Tableau des notes ===== --}}
    <table>
        <thead>
            <tr>
                <th>Cours</th>
                <th style="text-align:center;">Pondération</th>
                <th style="text-align:center;">Note / 20</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notes as $note)
                @php
                    $coeff = $note->courSalle?->ponderation?->valeur ?? 1;
                @endphp
                <tr>
                    <td>{{ $note->courSalle?->cour?->nom ?? 'Cours supprimé' }}</td>
                    <td class="pondere">{{ $coeff }}</td>
                    <td class="note">
                        {{ $note->note !== null ? number_format($note->note, 2, ',', ' ') : '—' }}
                    </td>
                    <td>{{ $note->appreciation ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 1.5rem;">
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
                    <div class="value">{{ number_format($moyenne, 2, ',', ' ') }} / 20</div>
                </div>
            @endif

            @if($pourcentage !== null)
                <div class="resultat-item">
                    <div class="label">Pourcentage</div>
                    <div class="value">{{ number_format($pourcentage, 2, ',', ' ') }} %</div>
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
    <footer class="footer no-print">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </footer>
</body>
</html>