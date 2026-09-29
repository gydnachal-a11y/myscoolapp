@extends('layouts.admin')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title">Tableau de bord <strong>financier</strong></h1>
            <p class="index-subtitle">Recettes par salle de classe – {{ $anneeSelectionnee->libelle ?? 'Année sélectionnée' }}</p>
        </div>
        <div class="header-actions">
            <div class="taux-display">
                <span>Taux actuel : <strong>1 $ = {{ number_format($taux, 0) }} FC</strong></span>
                <a href="{{ route('admin.taux.edit') }}" class="taux-link">
                    <i class="fa-solid fa-pen"></i> Modifier
                </a>
            </div>
        </div>
    </div>

    {{-- Filtre année --}}
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="filter-field">
                <label class="filter-label">Année scolaire</label>
                <select name="annee_scolaire_id" class="filter-select" onchange="this.form.submit()">
                    @foreach($annees as $an)
                        <option value="{{ $an->id }}" {{ $anneeId == $an->id ? 'selected' : '' }}>
                            {{ $an->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
        </form>
    </div>

    {{-- Comparaison des effectifs (NOUVEAU) --}}
    @if(isset($comparaisonGlobale))
    <div class="comparison-card">
        <div class="comparison-header">
            <h3><i class="fa-solid fa-people-group"></i> Effectifs attendus vs inscrits</h3>
            <span class="comparison-badge {{ $comparaisonGlobale['niveau'] }}">
                {{ ucfirst($comparaisonGlobale['niveau']) }}
            </span>
        </div>
        <div class="comparison-grid">
            <div class="comparison-item">
                <span class="comparison-label">Effectif attendu</span>
                <span class="comparison-value">{{ $comparaisonGlobale['effectif_attendu'] }}</span>
            </div>
            <div class="comparison-item">
                <span class="comparison-label">Effectif inscrit</span>
                <span class="comparison-value">{{ $comparaisonGlobale['effectif_inscrit'] }}</span>
            </div>
            <div class="comparison-item">
                <span class="comparison-label">Écart</span>
                <span class="comparison-value {{ $comparaisonGlobale['ecart'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $comparaisonGlobale['ecart'] >= 0 ? '+' : '' }}{{ $comparaisonGlobale['ecart'] }}
                </span>
            </div>
            <div class="comparison-item">
                <span class="comparison-label">Taux de remplissage</span>
                <span class="comparison-value">{{ $comparaisonGlobale['pourcentage'] }}%</span>
            </div>
        </div>
        <div class="progress-wrapper">
            <div class="progress-bar-bg">
                <div class="progress-bar-fill"
                     style="width: {{ min($comparaisonGlobale['pourcentage'], 100) }}%; background: {{ $comparaisonGlobale['pourcentage'] < 70 ? '#ef4444' : ($comparaisonGlobale['pourcentage'] <= 90 ? '#f59e0b' : '#22c55e') }};">
                </div>
            </div>
            <p class="progress-message {{ $comparaisonGlobale['couleur'] }}">
                <i class="fa-solid fa-circle-info"></i>
                {{ $comparaisonGlobale['message'] }}
            </p>
        </div>
    </div>
    @endif

    {{-- Cartes globales --}}
    <div class="stats-grid">
        <div class="stat-card border-indigo">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <p class="stat-label">Élèves inscrits</p>
                <p class="stat-value">{{ $totaux['totalInscriptions'] }}</p>
                <p class="stat-sub">{{ $statsParSalle->count() }} salles actives</p>
            </div>
        </div>
        <div class="stat-card border-green">
            <div class="stat-icon"><i class="fa-solid fa-money-bill"></i></div>
            <div>
                <p class="stat-label">Total frais inscription</p>
                <p class="stat-value">{{ number_format($totaux['totalFraisInscription'], 0) }} $</p>
                <p class="stat-sub">≈ {{ number_format($totaux['totalFraisInscription'] * $taux, 0) }} FC</p>
            </div>
        </div>
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="fa-solid fa-wallet"></i></div>
            <div>
                <p class="stat-label">Total frais annuels</p>
                <p class="stat-value">{{ number_format($totaux['totalFraisAnnuel'], 0) }} $</p>
                <p class="stat-sub">≈ {{ number_format($totaux['totalFraisAnnuel'] * $taux, 0) }} FC</p>
            </div>
        </div>
        <div class="stat-card border-amber">
            <div class="stat-icon"><i class="fa-solid fa-calculator"></i></div>
            <div>
                <p class="stat-label">Total général</p>
                <p class="stat-value">{{ number_format($totaux['totalFrais'], 0) }} $</p>
                <p class="stat-sub">≈ {{ number_format($totaux['totalFrais'] * $taux, 0) }} FC</p>
            </div>
        </div>
    </div>

    {{-- Graphiques --}}
    <div class="charts-grid">
        <div class="chart-card">
            <h3 class="chart-title"><i class="fa-solid fa-chart-bar"></i> Frais d'inscription par salle ($)</h3>
            <canvas id="fraisInscriptionChart"></canvas>
        </div>
        <div class="chart-card">
            <h3 class="chart-title"><i class="fa-solid fa-chart-pie"></i> Répartition des élèves</h3>
            <canvas id="repartitionChart"></canvas>
        </div>
    </div>

    {{-- Tableau détaillé avec indicateurs de performance --}}
    <div class="table-card">
        <div class="table-header">
            <h3 class="table-title"><i class="fa-solid fa-list"></i> Détails par salle</h3>
            <span class="table-subtitle">{{ $statsParSalle->count() }} salles · {{ $totaux['totalInscriptions'] }} élèves</span>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Salle</th>
                        <th>Section</th>
                        <th>Élèves</th>
                        <th>Capacité</th>
                        <th>Taux rempl.</th>
                        <th>Frais Inscription ($)</th>
                        <th>Frais Annuel ($)</th>
                        <th>Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statsParSalle as $stat)
                    <tr>
                        <td data-label="Salle">
                            <span class="cell-primary">{{ $stat->nom }}</span>
                            @if(isset($stat->option) && $stat->option)
                                <span class="option-badge">{{ $stat->option->nom }}</span>
                            @endif
                        </td>
                        <td data-label="Section">{{ $stat->section->nom ?? '—' }}</td>
                        <td data-label="Élèves">{{ $stat->nb_inscriptions }}</td>
                        <td data-label="Capacité">{{ $stat->capacite_max }}</td>
                        <td data-label="Taux rempl.">
                            <span class="taux-badge {{ $stat->indicateur_couleur ?? 'gray' }}">
                                {{ $stat->taux_remplissage ?? 0 }}%
                            </span>
                        </td>
                        <td data-label="Frais Inscription ($)">{{ number_format($stat->total_frais_inscription ?? 0, 0) }}</td>
                        <td data-label="Frais Annuel ($)">{{ number_format($stat->total_frais_annuel ?? 0, 0) }}</td>
                        <td data-label="Total ($)">
                            <strong>{{ number_format(($stat->total_frais_inscription ?? 0) + ($stat->total_frais_annuel ?? 0), 0) }}</strong>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty-cell">Aucune donnée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    /* ==== Styles premium ==== */
    .index-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .index-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s ease forwards;
        opacity: 0;
    }
    .index-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .header-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    .taux-display {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: white;
        padding: 0.6rem 1rem;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        font-size: 0.9rem;
    }
    .taux-display strong { color: #1e293b; }
    .taux-link {
        color: #667eea;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.2s;
    }
    .taux-link:hover { color: #4f46e5; }

    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.25rem;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.2s ease forwards;
        opacity: 0;
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1rem;
    }
    .filter-field {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        flex: 1;
        min-width: 200px;
    }
    .filter-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }
    .filter-select {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.95rem;
        color: #1e293b;
        transition: border-color 0.3s;
        outline: none;
    }
    .filter-select:focus {
        border-color: #667eea;
        background: white;
    }
    .btn-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.7rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        border: none;
        background: #1e293b;
        color: white;
    }
    .btn-filter:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }

    /* ==== Comparaison des effectifs (NOUVEAU) ==== */
    .comparison-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.5rem;
        margin-bottom: 2rem;
        animation: cardIn 0.6s 0.15s ease forwards;
        opacity: 0;
    }
    .comparison-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    .comparison-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .comparison-header h3 i { color: #667eea; }
    .comparison-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .comparison-badge.inférieur { background: #fee2e2; color: #dc2626; }
    .comparison-badge.moyen { background: #fef3c7; color: #d97706; }
    .comparison-badge.supérieur { background: #dcfce7; color: #16a34a; }

    .comparison-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .comparison-item {
        display: flex;
        flex-direction: column;
    }
    .comparison-label {
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .comparison-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0.25rem;
    }
    .text-green-600 { color: #16a34a; }
    .text-red-600 { color: #dc2626; }

    .progress-wrapper {
        margin-top: 0.5rem;
    }
    .progress-bar-bg {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 1s ease;
    }
    .progress-message {
        margin-top: 0.5rem;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .progress-message i { font-size: 0.8rem; }
    .progress-message.text-green-600 { color: #16a34a; }
    .progress-message.text-amber-600 { color: #d97706; }
    .progress-message.text-red-600 { color: #dc2626; }

    @keyframes cardIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
    }
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        border-left: 4px solid;
        animation: cardIn 0.6s ease forwards;
        opacity: 0;
    }
    .stat-card:nth-child(1) { animation-delay: 0.25s; }
    .stat-card:nth-child(2) { animation-delay: 0.35s; }
    .stat-card:nth-child(3) { animation-delay: 0.45s; }
    .stat-card:nth-child(4) { animation-delay: 0.55s; }
    .border-indigo { border-left-color: #667eea; }
    .border-green { border-left-color: #22c55e; }
    .border-purple { border-left-color: #a855f7; }
    .border-amber { border-left-color: #f59e0b; }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .border-indigo .stat-icon { background: #eef2ff; color: #667eea; }
    .border-green .stat-icon { background: #f0fdf4; color: #22c55e; }
    .border-purple .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-amber .stat-icon { background: #fffbeb; color: #f59e0b; }
    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        font-weight: 600;
    }
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0.25rem;
    }
    .stat-sub {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 1024px) {
        .charts-grid { grid-template-columns: 1fr 1fr; }
    }
    .chart-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        animation: cardIn 0.6s ease forwards;
        opacity: 0;
    }
    .chart-card:nth-child(1) { animation-delay: 0.65s; }
    .chart-card:nth-child(2) { animation-delay: 0.75s; }
    .chart-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chart-title i { color: #667eea; }

    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        animation: cardIn 0.6s 0.85s ease forwards;
        opacity: 0;
    }
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .table-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .table-title i { color: #667eea; }
    .table-subtitle {
        font-size: 0.8rem;
        color: #94a3b8;
    }
    .table-wrapper { overflow-x: auto; }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        color: #475569;
    }
    .data-table thead th {
        text-align: left;
        padding: 0.9rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .data-table tbody td {
        padding: 0.9rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .cell-primary { font-weight: 600; color: #1e293b; }
    .option-badge {
        display: inline-block;
        font-size: 0.6rem;
        padding: 0.15rem 0.5rem;
        border-radius: 10px;
        background: #e0e7ff;
        color: #4f46e5;
        margin-left: 0.4rem;
        font-weight: 500;
    }
    .taux-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .taux-badge.green { background: #dcfce7; color: #16a34a; }
    .taux-badge.amber { background: #fef3c7; color: #d97706; }
    .taux-badge.red { background: #fee2e2; color: #dc2626; }
    .taux-badge.gray { background: #f1f5f9; color: #64748b; }

    .empty-cell {
        text-align: center;
        padding: 3rem;
        color: #94a3b8;
    }

    /* ==== Mobile ==== */
    @media (max-width: 640px) {
        .stats-grid { grid-template-columns: 1fr; }
        .comparison-grid { grid-template-columns: 1fr 1fr; }
        .table-card {
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }
        .data-table,
        .data-table tbody,
        .data-table tr,
        .data-table td {
            display: block;
            width: 100%;
        }
        .data-table thead { display: none; }
        .data-table tr {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.25rem;
            padding: 1rem;
        }
        .data-table td {
            border: none;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 100px;
        }
    }

    @media (max-width: 768px) {
        .index-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .filter-form {
            flex-direction: column;
        }
        .btn-filter { width: 100%; }
        .comparison-grid { grid-template-columns: 1fr 1fr; }
    }
</style>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stats = @json($statsParSalle);

        const labels = stats.map(s => s.nom);
        const fraisInscriptionData = stats.map(s => s.total_frais_inscription || 0);
        const nbEleves = stats.map(s => s.nb_inscriptions);

        // Bar chart
        new Chart(document.getElementById('fraisInscriptionChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Frais inscription ($)',
                    data: fraisInscriptionData,
                    backgroundColor: 'rgba(99, 102, 241, 0.6)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Doughnut chart
        new Chart(document.getElementById('repartitionChart'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: nbEleves,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.7)',
                        'rgba(168, 85, 247, 0.7)',
                        'rgba(236, 72, 153, 0.7)',
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(251, 146, 60, 0.7)',
                        'rgba(14, 165, 233, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } }
                }
            }
        });
    });
</script>
@endsection