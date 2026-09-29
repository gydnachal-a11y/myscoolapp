<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des paiements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @php
        $settings = $siteSettings ?? \App\Models\SiteSetting::getSettings();
        $fmtUsd = fn ($v) => number_format((float) $v, 2, ',', ' ');
        $fmtFc  = fn ($v) => number_format((float) $v, 0, ',', ' ');

        $statutMeta = [
            'paye'    => ['label' => 'Payé',    'class' => 'solde'],
            'partiel' => ['label' => 'Partiel', 'class' => 'partiel'],
            'impaye'  => ['label' => 'Impayé',  'class' => 'impaye'],
            'surpaye' => ['label' => 'Surpayé', 'class' => 'solde'],
        ];

        $totalPaiements = $paiements instanceof \Illuminate\Support\Collection
            ? $paiements->count()
            : count($paiements);
    @endphp

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            padding: 15px;
            background: #f8fafc;
        }

        .page-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }

        /* HEADER */
        .school-header {
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 25px; padding-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        .school-logo img {
            width: 75px; height: 75px; object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.08);
            border: 2px solid #ffffff;
        }
        .logo-placeholder {
            width: 75px; height: 75px;
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; border-radius: 12px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }
        .school-info { flex-grow: 1; }
        .school-info h1 {
            font-size: 20px; font-weight: 800; color: #065f46;
            margin-bottom: 6px; letter-spacing: -0.4px;
            text-transform: uppercase;
        }
        .school-info p {
            font-size: 11px; color: #64748b;
            margin: 2px 0; display: flex;
            align-items: center; gap: 6px;
        }

        /* DOCUMENT HEADER */
        .document-header-box {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border: 1px solid #d1fae5;
            border-radius: 12px;
            padding: 18px; text-align: center;
            margin-bottom: 25px;
        }
        .document-title {
            font-size: 16px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #065f46; margin-bottom: 6px;
        }
        .total-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: #e6f4ea; color: #047857;
            padding: 6px 14px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
            border: 1px solid #a7f3d0;
        }

        /* TABLE */
        table {
            width: 100%; border-collapse: collapse;
            font-size: 11px; background: #fff;
            border-radius: 8px; overflow: hidden;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        th {
            background: #065f46; color: #fff;
            font-weight: 600; text-transform: uppercase;
            font-size: 9.5px; letter-spacing: 0.5px;
            padding: 10px 8px; text-align: left;
            border: none;
        }
        td {
            padding: 9px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle; color: #334155;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #f1f5f9; }

        .col-numero { width: 35px; text-align: center; color: #94a3b8; font-weight: 600; }
        .text-right { text-align: right; }

        .badge-statut {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
        }
        .badge-statut.solde    { background: #d1fae5; color: #065f46; }
        .badge-statut.partiel  { background: #fef3c7; color: #b45309; }
        .badge-statut.impaye   { background: #fee2e2; color: #b91c1c; }

        .montant-usd { font-weight: 700; color: #047857; }
        .montant-fc  { font-weight: 600; color: #475569; }

        .empty {
            text-align: center; padding: 25px;
            color: #94a3b8; font-style: italic;
            background: #f8fafc;
        }

        /* FOOTER */
        .footer {
            margin-top: 30px; text-align: center;
            font-size: 10px; color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }

        /* PRINT */
        @media print {
            body {
                margin: 0; padding: 0;
                background: #fff; font-size: 10px;
            }
            .page-container {
                max-width: 100%;
                box-shadow: none; border: none;
                padding: 10px; border-radius: 0;
            }
            .school-header { border-bottom: 2px solid #cbd5e1; }
            .document-header-box { background: #f8fafc !important; -webkit-print-color-adjust: exact; }
            th {
                background: #065f46 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
            }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="page-container">

    {{-- En-tête école --}}
    <header class="school-header">
        <div class="school-logo">
            @if(!empty($settings->site_logo))
                <img src="{{ asset('storage/' . $settings->site_logo) }}" alt="{{ $settings->site_name }}">
            @else
                <div class="logo-placeholder">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            @endif
        </div>
        <div class="school-info">
            <h1>{{ $settings->site_name ?? config('app.name', 'Mon École') }}</h1>
            @if(!empty($settings->site_address))
                <p><i class="fa-solid fa-location-dot"></i> {{ $settings->site_address }}</p>
            @endif
            @if(!empty($settings->site_email))
                <p><i class="fa-solid fa-envelope"></i> {{ $settings->site_email }}</p>
            @endif
            @if(!empty($settings->site_phone))
                <p><i class="fa-solid fa-phone"></i> {{ $settings->site_phone }}</p>
            @endif
        </div>
    </header>

    <div class="document-header-box">
        <div class="document-title">Journal et liste des paiements</div>
    </div>

    @if($totalPaiements > 0)
        <div style="text-align: center;">
            <div class="total-badge">
                <i class="fa-solid fa-cash-register"></i>
                <strong>{{ $totalPaiements }}</strong> enregistrement(s) trouvé(s)
            </div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="col-numero">#</th>
                <th>Élève</th>
                <th>Salle</th>
                <th>Mode</th>
                <th>Période</th>
                <th class="text-right">Payé ($)</th>
                <th class="text-right">Payé (FC)</th>
                <th class="text-right">Rest. ($)</th>
                <th class="text-right">Rest. (FC)</th>
                <th style="text-align:center;">Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paiements as $index => $p)
                @php
                    $meta = $statutMeta[$p->statut ?? ''] ?? ['label' => $p->statut ?? '—', 'class' => 'solde'];
                @endphp
                <tr>
                    <td class="col-numero">{{ $index + 1 }}</td>
                    <td style="font-weight: 600;">{{ $p->eleve?->nom_complet ?? '—' }}</td>
                    <td>{{ $p->salleClasse?->nom ?? '—' }}</td>
                    <td>{{ ucfirst($p->type_periode ?? '—') }}</td>
                    <td>{{ $p->periode ?? '—' }}</td>
                    <td class="text-right montant-usd">{{ $fmtUsd($p->montant_paye_usd ?? 0) }} $</td>
                    <td class="text-right montant-fc">{{ $fmtFc($p->montant_paye_fc ?? 0) }}</td>
                    <td class="text-right" style="color:#64748b;">{{ $fmtUsd($p->montant_restant_usd ?? 0) }} $</td>
                    <td class="text-right" style="color:#64748b;">{{ $fmtFc($p->montant_restant_fc ?? 0) }}</td>
                    <td style="text-align:center;">
                        <span class="badge-statut {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                    </td>
                    <td>{{ $p->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty">Aucun paiement trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $settings->site_name ?? config('app.name') }}
    </footer>
</div>
</body>
</html>