@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    :root {
        --primary: #667eea;
        --success: #22c55e;
        --warning: #f59e0b;
        --danger: #ef4444;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-600: #475569;
        --gray-800: #1e293b;
        --radius: 16px;
        --shadow: 0 10px 20px rgba(0,0,0,.05);
    }

    * { box-sizing: border-box; }

    body { font-family: system-ui, -apple-system, sans-serif; background: #f5f7fa; }

    .dashboard-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

    .dashboard-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 2rem; }
    .dashboard-title { font-size: 1.75rem; font-weight: 700; color: var(--gray-800); display: flex; align-items: center; gap: .5rem; margin: 0; }
    .dashboard-subtitle { color: var(--gray-400); font-weight: 400; font-size: 1.25rem; }
    .btn-outline { display: inline-flex; align-items: center; gap: .5rem; padding: .6rem 1.2rem; border-radius: 12px; font-weight: 600; font-size: .9rem; text-decoration: none; background: #fff; border: 1.5px solid var(--gray-200); color: var(--gray-500); transition: all .3s; }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: #eef2ff; }

    .filters-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.25rem 1.5rem; margin-bottom: 2rem; }
    .filters-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-group { display: flex; flex-direction: column; gap: .5rem; flex: 1 1 180px; min-width: 150px; }
    .filter-label { font-size: .8rem; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: .3px; }
    .filter-select { width: 100%; padding: .7rem 1rem; border: 2px solid var(--gray-200); border-radius: 10px; background: var(--gray-50); font-size: .95rem; color: var(--gray-600); outline: none; transition: all .3s; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 12px; }
    .filter-select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(102,126,234,.1); }

    .btn-filter, .btn-reset { display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: .9rem; cursor: pointer; border: none; text-decoration: none; transition: all .3s; white-space: nowrap; }
    .btn-filter { background: var(--gray-800); color: #fff; }
    .btn-filter:hover { background: var(--primary); }
    .btn-reset { background: #fff; color: var(--gray-500); border: 1.5px solid var(--gray-200); }
    .btn-reset:hover { border-color: var(--primary); color: var(--primary); background: #eef2ff; }

    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
    @media (max-width: 1024px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .kpi-grid { grid-template-columns: 1fr; } }

    .kpi-card { background: #fff; padding: 1.5rem; border-radius: var(--radius); box-shadow: var(--shadow); display: flex; align-items: center; gap: 1rem; border-left: 4px solid transparent; transition: transform .2s; }
    .kpi-card:hover { transform: translateY(-4px); }
    .kpi-blue { border-left-color: #3b82f6; }
    .kpi-red { border-left-color: #ef4444; }
    .kpi-green { border-left-color: #22c55e; }
    .kpi-indigo { border-left-color: #8b5cf6; }

    .kpi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
    .kpi-blue .kpi-icon { background: #dbeafe; color: #3b82f6; }
    .kpi-red .kpi-icon { background: #fee2e2; color: #ef4444; }
    .kpi-green .kpi-icon { background: #f0fdf4; color: #22c55e; }
    .kpi-indigo .kpi-icon { background: #ede9fe; color: #8b5cf6; }

    .kpi-content { flex: 1; }
    .kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-400); font-weight: 600; }
    .kpi-value { font-size: 1.75rem; font-weight: 700; color: var(--gray-800); margin-top: .25rem; }
    .kpi-sub { font-size: .8rem; color: var(--gray-500); margin-top: .25rem; }
    .kpi-status { font-weight: 600; font-size: .8rem; padding: .1rem .5rem; border-radius: 20px; }
    .text-success { color: #16a34a; background: #f0fdf4; }
    .text-danger { color: #dc2626; background: #fee2e2; }

    .chart-card, .table-card { background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 2rem; }
    .chart-card { padding: 1.5rem; }
    .chart-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.25rem; }
    .chart-header h3, .table-title { font-size: 1.1rem; font-weight: 600; color: var(--gray-800); display: flex; align-items: center; gap: .5rem; margin: 0; }
    .chart-legend { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .8rem; color: var(--gray-500); }
    .legend-item { display: flex; align-items: center; gap: .3rem; }
    .legend-color { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }
    .chart-wrapper { height: 320px; }

    .table-card { overflow: hidden; }
    .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-100); }
    .table-badge { background: var(--gray-100); padding: .25rem .75rem; border-radius: 20px; font-size: .75rem; color: var(--gray-600); font-weight: 600; }
    .table-scroll { overflow-x: auto; }

    .data-table { width: 100%; border-collapse: collapse; font-size: .9rem; color: var(--gray-600); min-width: 900px; }
    .data-table th { text-align: left; padding: .9rem 1.2rem; font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; color: var(--gray-400); background: var(--gray-50); border-bottom: 2px solid var(--gray-200); white-space: nowrap; font-weight: 600; }
    .data-table td { padding: .85rem 1.2rem; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
    .data-table tbody tr:hover { background: var(--gray-50); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .cell-primary { font-weight: 600; color: var(--gray-800); }

    .text-right { text-align: right; }

    /* Colonnes plus larges */
    .col-salle { min-width: 200px; }
    .col-attendu { min-width: 160px; }

    .badge { display: inline-block; padding: .2rem .6rem; border-radius: 20px; font-size: .7rem; font-weight: 600; line-height: 1.4; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-secondary { background: var(--gray-200); color: var(--gray-600); }
    .badge-solde.positive { background: #f0fdf4; color: #16a34a; }
    .badge-solde.negative { background: #fee2e2; color: #dc2626; }
    .badge-global.success { background: #f0fdf4; color: #16a34a; }
    .badge-global.warning { background: #fef3c7; color: #92400e; }
    .badge-global.danger { background: #fee2e2; color: #dc2626; }

    .progress-container { display: flex; align-items: center; gap: .5rem; min-width: 120px; }
    .progress-bar { flex: 1; height: 6px; border-radius: 4px; background: var(--gray-200); overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; transition: width .3s; }
    .bg-success { background: #22c55e; }
    .bg-warning { background: #f59e0b; }
    .bg-danger { background: #ef4444; }
    .progress-label { font-size: .8rem; font-weight: 600; min-width: 40px; text-align: right; }

    .personnel-summary { cursor: pointer; font-weight: 600; color: var(--primary); font-size: .85rem; }
    .personnel-summary:hover { text-decoration: underline; }
    .personnel-list { list-style: none; padding-left: 1rem; margin: .25rem 0 0 0; font-size: .8rem; }
    .personnel-list li { display: flex; justify-content: space-between; padding: .15rem 0; }
    .personnel-list li span:first-child { font-weight: 500; }
    .personnel-list li span:last-child { color: var(--gray-500); }

    .total-row td { background: var(--gray-50); font-weight: 600; border-top: 2px solid var(--gray-200); text-align: right; }
    .empty-cell { text-align: center; padding: 3rem; color: var(--gray-400); }

    /* ========== Responsive amélioré ========== */
    @media (max-width: 1024px) {
        .dashboard-title { font-size: 1.5rem; }
        .chart-wrapper { height: 280px; }
    }

    @media (max-width: 768px) {
        .dashboard-title { font-size: 1.35rem; flex-wrap: wrap; }
        .dashboard-subtitle { font-size: 1rem; display: block; margin-left: 0; }
        .chart-header h3, .table-title { font-size: 1rem; }
        .table-title { font-size: 0.95rem; }
        .chart-legend { font-size: 0.7rem; gap: 0.4rem; }
        .chart-wrapper { height: 250px; }
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
        .data-table tr { margin-bottom: 1rem; border: 1px solid var(--gray-200); border-radius: 12px; overflow: hidden; background: #fff; box-shadow: var(--shadow); }
        .data-table td { display: flex; justify-content: space-between; align-items: center; padding: .6rem 1rem; border-bottom: 1px solid var(--gray-100); font-size: .85rem; }
        .data-table td::before { content: attr(data-label); font-weight: 600; color: var(--gray-500); margin-right: 1rem; flex-shrink: 0; }
        .data-table td:last-child { border-bottom: none; }
        .data-table td.text-right { text-align: right; justify-content: flex-end; }
        .col-salle, .col-attendu { min-width: 0; }
    }

    @media (max-width: 480px) {
        .dashboard-title { font-size: 1.2rem; }
        .dashboard-subtitle { font-size: 0.9rem; }
        .kpi-grid { grid-template-columns: 1fr; }
        .kpi-card { padding: 1rem; }
        .kpi-value { font-size: 1.3rem; }
        .kpi-icon { width: 40px; height: 40px; font-size: 1.2rem; }
        .chart-wrapper { height: 220px; }
        .chart-legend { font-size: 0.65rem; }
        .table-header { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        .table-badge { align-self: flex-start; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fa-solid fa-chart-simple" style="color: #667eea;"></i>
            Tableau de bord des <strong>paiements</strong>
            @if($annee)
                <span class="dashboard-subtitle">– {{ $annee->libelle }}</span>
            @endif
        </h1>
        <a href="{{ route('admin.paiements.index') }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Retour aux paiements
        </a>
    </div>

    <div class="filters-card">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label class="filter-label">Année scolaire</label>
                <select name="annee_scolaire_id" class="filter-select" onchange="this.form.submit()">
                    @foreach($annees as $an)
                        <option value="{{ $an->id }}" {{ $annee->id == $an->id ? 'selected' : '' }}>{{ $an->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Mois</label>
                <select name="mois_scolaire_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous les mois</option>
                    @foreach($tousMois as $m)
                        <option value="{{ $m->id }}" {{ $moisId == $m->id ? 'selected' : '' }}>{{ $m->nom_mois ?? $m->mois }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Section</label>
                <select name="section_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ $sectionId == $section->id ? 'selected' : '' }}>{{ $section->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Salle de classe</label>
                <select name="salle_classe_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    @foreach($salles as $salle)
                        <option value="{{ $salle->id }}" {{ $salleId == $salle->id ? 'selected' : '' }}>{{ $salle->nom }} @if($salle->section)({{ $salle->section->nom }})@endif</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fa-solid fa-sliders"></i> Filtrer</button>
            @if($sectionId || $salleId || $moisId)
                <a href="{{ route('admin.paiement-dashboard.index', ['annee_scolaire_id' => $annee->id]) }}" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Réinitialiser</a>
            @endif
        </form>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="fa-solid fa-arrow-down"></i></div>
            <div class="kpi-content">
                <div class="kpi-label">Total entrées</div>
                <div class="kpi-value">{{ number_format($totalEntreesUSD,0,',',' ') }} $</div>
                <div class="kpi-sub">{{ number_format($totalEntreesFC,0,',',' ') }} FC</div>
            </div>
        </div>
        <div class="kpi-card kpi-red">
            <div class="kpi-icon"><i class="fa-solid fa-arrow-up"></i></div>
            <div class="kpi-content">
                <div class="kpi-label">Total sorties</div>
                <div class="kpi-value">{{ number_format($totalSortiesUSD,0,',',' ') }} $</div>
                <div class="kpi-sub">{{ number_format($totalSortiesFC,0,',',' ') }} FC</div>
            </div>
        </div>
        <div class="kpi-card {{ $soldeUSD >= 0 ? 'kpi-green' : 'kpi-red' }}">
            <div class="kpi-icon"><i class="fa-solid fa-scale-balanced"></i></div>
            <div class="kpi-content">
                <div class="kpi-label">Solde net</div>
                <div class="kpi-value">{{ number_format($soldeUSD,0,',',' ') }} $</div>
                <div class="kpi-sub">
                    {{ number_format($soldeFC,0,',',' ') }} FC
                    <span class="kpi-status {{ $soldeUSD >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $soldeUSD >= 0 ? '▲ Bénéficiaire' : '▼ Déficitaire' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="kpi-card kpi-indigo">
            <div class="kpi-icon"><i class="fa-solid fa-calendar-days"></i></div>
            <div class="kpi-content">
                <div class="kpi-label">Mois suivis</div>
                <div class="kpi-value">{{ count($mois) }}</div>
                <div class="kpi-sub">sur l'année</div>
            </div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-header">
            <h3><i class="fa-solid fa-chart-column"></i> Évolution mensuelle</h3>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-color" style="background:#3b82f6"></span> Mensuel</span>
                <span class="legend-item"><span class="legend-color" style="background:#6366f1"></span> Tranche</span>
                <span class="legend-item"><span class="legend-color" style="background:#22c55e"></span> Inscriptions</span>
                <span class="legend-item"><span class="legend-color" style="background:#a855f7"></span> Frais supp.</span>
                <span class="legend-item"><span class="legend-color" style="background:#ef4444"></span> Sorties</span>
                <span class="legend-item"><span class="legend-color" style="background:#22c55e; border-top:2px solid #22c55e;"></span> Solde</span>
            </div>
        </div>
        <div class="chart-wrapper"><canvas id="evolutionChart"></canvas></div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3 class="table-title"><i class="fa-solid fa-list-ul"></i> Détail mensuel</h3>
            <span class="table-badge">{{ count($dataMensuelle) }} mois</span>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mois</th><th class="text-right">Mensuel</th><th class="text-right">Tranche</th><th class="text-right">Inscriptions</th><th class="text-right">Frais supp.</th><th class="text-right">Total entrées</th><th class="text-right">Sorties</th><th class="text-right">Solde</th><th>Personnel</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dataMensuelle as $data)
                        <tr>
                            <td data-label="Mois" class="cell-primary">{{ $data['mois'] }}</td>
                            <td data-label="Mensuel" class="text-right">{{ number_format($data['entrees_mensuel'],0,',',' ') }} $</td>
                            <td data-label="Tranche" class="text-right">{{ number_format($data['entrees_tranche'],0,',',' ') }} $</td>
                            <td data-label="Inscriptions" class="text-right">{{ number_format($data['entrees_inscriptions'],0,',',' ') }} $</td>
                            <td data-label="Frais supp." class="text-right">{{ number_format($data['entrees_frais_supplementaire'],0,',',' ') }} $</td>
                            <td data-label="Total entrées" class="text-right">{{ number_format($data['entrees'],0,',',' ') }} $</td>
                            <td data-label="Sorties" class="text-right">{{ number_format($data['sorties'],0,',',' ') }} $</td>
                            <td data-label="Solde" class="text-right">
                                <span class="badge badge-solde {{ $data['solde'] >= 0 ? 'positive' : 'negative' }}">
                                    {{ number_format($data['solde'],0,',',' ') }} $
                                </span>
                            </td>
                            <td data-label="Personnel">
                                @if($data['personnel']->count())
                                    <details>
                                        <summary class="personnel-summary">
                                            <i class="fa-solid fa-users"></i> {{ $data['personnel']->count() }} salarié(s)
                                        </summary>
                                        <ul class="personnel-list">
                                            @foreach($data['personnel'] as $p)
                                                <li>
                                                    <span>{{ $p['nom'] }}</span>
                                                    <span>{{ number_format($p['salaire'],0,',',' ') }} $</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-cell">Aucune donnée mensuelle disponible.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3 class="table-title"><i class="fa-solid fa-school"></i> Détails par salle de classe</h3>
            <span class="table-badge">{{ count($sallesDetails) }} salles</span>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-salle">Salle</th><th>Section</th><th>Mode</th><th class="text-right">Inscrits</th><th class="text-right">Frais inscription</th><th class="text-right">Frais annuel</th><th class="text-right">Paiements mensuels</th><th class="text-right">Paiements tranches</th><th class="text-right">Frais supp.</th><th class="text-right col-attendu">Attendu</th><th class="text-right">Total attendu</th><th class="text-right">Total payé</th><th class="text-right">Taux de recouvrement</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalAttenduGen = 0;
                        $totalPayeGen = 0;
                    @endphp
                    @forelse($sallesDetails as $d)
                        @php
                            $totalAttenduGen += $d['total_attendu'];
                            $totalPayeGen += $d['total_paye'];
                            $badgeClass = $d['mode_paiement'] === 'mensuel' ? 'badge-info' : ($d['mode_paiement'] === 'tranche' ? 'badge-warning' : 'badge-secondary');
                            $progressClass = $d['taux_recouvrement'] >= 80 ? 'bg-success' : ($d['taux_recouvrement'] >= 50 ? 'bg-warning' : 'bg-danger');
                        @endphp
                        <tr>
                            <td data-label="Salle" class="cell-primary col-salle">{{ $d['salle'] }}</td>
                            <td data-label="Section">{{ $d['section'] }}</td>
                            <td data-label="Mode">
                                @if($d['mode_paiement'])
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($d['mode_paiement']) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Inscrits" class="text-right">{{ $d['nb_inscriptions'] }}</td>
                            <td data-label="Frais inscription" class="text-right">{{ number_format($d['frais_inscription'],0,',',' ') }} $</td>
                            <td data-label="Frais annuel" class="text-right">{{ number_format($d['frais_annuel'],0,',',' ') }} $</td>
                            <td data-label="Paiements mensuels" class="text-right">{{ number_format($d['paiements_mensuel'],0,',',' ') }} $</td>
                            <td data-label="Paiements tranches" class="text-right">{{ number_format($d['paiements_tranche'],0,',',' ') }} $</td>
                            <td data-label="Frais supp." class="text-right">{{ number_format($d['frais_supplementaires'],0,',',' ') }} $</td>
                            <td data-label="Attendu" class="text-right col-attendu">
                                @if($d['attendu_mensuel'] !== null)
                                    {{ number_format($d['attendu_mensuel'],0,',',' ') }} $ / mois
                                @elseif($d['attendu_par_tranche'] !== null)
                                    {{ number_format($d['attendu_par_tranche'],0,',',' ') }} $ / tranche
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Total attendu" class="text-right">{{ number_format($d['total_attendu'],0,',',' ') }} $</td>
                            <td data-label="Total payé" class="text-right">{{ number_format($d['total_paye'],0,',',' ') }} $</td>
                            <td data-label="Taux de recouvrement" class="text-right">
                                <div class="progress-container" style="justify-content: flex-end;">
                                    <div class="progress-bar">
                                        <div class="progress-fill {{ $progressClass }}" style="width: {{ min(100, $d['taux_recouvrement']) }}%;"></div>
                                    </div>
                                    <span class="progress-label">{{ $d['taux_recouvrement'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="empty-cell">Aucune salle de classe pour cette sélection.</td></tr>
                    @endforelse

                    @if(count($sallesDetails) > 0)
                        @php
                            $tauxGlobal = $totalAttenduGen > 0 ? round(($totalPayeGen / $totalAttenduGen) * 100, 1) : 0;
                        @endphp
                        <tr class="total-row">
                            <td colspan="10" class="text-right"><strong>Totaux généraux</strong></td>
                            <td data-label="Total attendu" class="text-right"><strong>{{ number_format($totalAttenduGen,0,',',' ') }} $</strong></td>
                            <td data-label="Total payé" class="text-right"><strong>{{ number_format($totalPayeGen,0,',',' ') }} $</strong></td>
                            <td data-label="Taux global" class="text-right">
                                <span class="badge badge-global {{ $tauxGlobal >= 80 ? 'success' : ($tauxGlobal >= 50 ? 'warning' : 'danger') }}">
                                    {{ $tauxGlobal }}%
                                </span>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('evolutionChart');
    if (!canvas) return;

    const dataMensuelle = @json($dataMensuelle);
    if (!dataMensuelle || dataMensuelle.length === 0) {
        canvas.parentElement.innerHTML = '<div class="empty-chart" style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;">Aucune donnée mensuelle disponible</div>';
        return;
    }

    const labels = dataMensuelle.map(item => item.mois);
    const datasets = [
        {
            label: 'Mensuel',
            data: dataMensuelle.map(item => item.entrees_mensuel),
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: '#3b82f6',
            borderWidth: 1,
            borderRadius: 4,
            stack: 'entrees'
        },
        {
            label: 'Tranche',
            data: dataMensuelle.map(item => item.entrees_tranche),
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderColor: '#6366f1',
            borderWidth: 1,
            borderRadius: 4,
            stack: 'entrees'
        },
        {
            label: 'Inscriptions',
            data: dataMensuelle.map(item => item.entrees_inscriptions),
            backgroundColor: 'rgba(34, 197, 94, 0.8)',
            borderColor: '#22c55e',
            borderWidth: 1,
            borderRadius: 4,
            stack: 'entrees'
        },
        {
            label: 'Frais supp.',
            data: dataMensuelle.map(item => item.entrees_frais_supplementaire || 0),
            backgroundColor: 'rgba(168, 85, 247, 0.8)',
            borderColor: '#a855f7',
            borderWidth: 1,
            borderRadius: 4,
            stack: 'entrees'
        },
        {
            label: 'Sorties',
            data: dataMensuelle.map(item => item.sorties),
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: '#ef4444',
            borderWidth: 1,
            borderRadius: 4
        },
        {
            label: 'Solde',
            data: dataMensuelle.map(item => item.solde),
            type: 'line',
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 3,
            pointBackgroundColor: '#22c55e',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.4,
            fill: true
        }
    ];

    new Chart(canvas, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(30, 41, 59, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { size: 13, weight: '600' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.06)' },
                    ticks: {
                        callback: value => '$' + value.toLocaleString(),
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});
</script>
@endpush