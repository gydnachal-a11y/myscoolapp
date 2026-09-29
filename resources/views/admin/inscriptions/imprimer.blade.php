<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    @include('admin.inscriptions._header')
    <title>Impression - Liste des inscriptions</title>
    <script>
        window.onload = function() { window.print(); };
    </script>
    <style>
        /* ==== Styles généraux ==== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: #1e293b;
            font-size: 12px;
            padding: 20px;
            background: #fff;
        }

        /* ==== En-tête du document ==== */
        .doc-header {
            margin-bottom: 20px;
        }
        .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: -0.3px;
        }
        .doc-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-meta {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* ==== Tableau ==== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .data-table thead th {
            background: #1e293b;
            color: #fff;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            text-align: left;
            border: none;
        }
        .data-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
            color: #334155;
        }
        .data-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Colonnes numériques */
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }

        /* ==== Pied de page ==== */
        .doc-footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }

        /* ==== Impression ==== */
        @media print {
            body {
                padding: 10px;
                font-size: 11px;
            }
            .no-print {
                display: none !important;
            }
            .data-table thead th {
                background: #e2e8f0 !important;
                color: #1e293b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .data-table tbody tr:nth-child(even) {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- En-tête de l'école -->
    <div class="doc-header">
        @include('admin.inscriptions._header')
    </div>

    <!-- Titre du document -->
    <div>
        <h1 class="doc-title">Liste des inscriptions</h1>
        <p class="doc-subtitle">État des inscriptions par élève, classe et frais</p>
        <p class="doc-meta">Imprimé le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

    <!-- Tableau des inscriptions -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:25%;">Élève</th>
                <th style="width:15%;">Année</th>
                <th style="width:20%;">Salle de classe</th>
                <th class="text-right" style="width:12%;">Frais inscription</th>
                <th class="text-right" style="width:12%;">Frais annuel</th>
                <th class="text-center" style="width:16%;">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inscriptions as $ins)
                <tr>
                    <td><strong>{{ $ins->eleve->nom }} {{ $ins->eleve->prenom }}</strong></td>
                    <td>{{ $ins->anneeScolaire->libelle }}</td>
                    <td>{{ $ins->salleDeClasse->nom }}</td>
                    <td class="text-right">{{ number_format($ins->frais_inscription_final, 0, ',', ' ') }} $</td>
                    <td class="text-right">{{ number_format($ins->frais_annuel_final, 0, ',', ' ') }} $</td>
                    <td class="text-center">{{ $ins->date_inscription->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">
                        Aucune inscription trouvée.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pied de page -->
    <div class="doc-footer">
        Document généré automatiquement par {{ config('app.name', 'MyscoolApp') }} — Page 1/1
    </div>
</body>
</html>