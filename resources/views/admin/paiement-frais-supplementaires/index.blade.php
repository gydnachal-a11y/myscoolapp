@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div class="header-title-block">
            <h1 class="index-title">
                <strong>Paiements</strong> des frais supplémentaires
            </h1>
            <p class="index-subtitle">
                Suivi des paiements individuels
                @if(request()->hasAny(['frais', 'salle', 'recherche']))
                    <span class="filter-indicator"> • Filtres actifs</span>
                @endif
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.info-paiements.index') }}" class="btn-secondary">
                <i class="fa-solid fa-file-invoice-dollar"></i> Info Paiements
            </a>
            <a href="{{ route('admin.paiement-frais-supplementaires.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouveau paiement
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fa-solid fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Barre d'export --}}
    <div class="export-bar">
        <span class="export-label">Exporter :</span>
        <a href="{{ route('admin.paiement-frais-supplementaires.export', ['format' => 'pdf', 'frais' => request('frais'), 'salle' => request('salle'), 'recherche' => request('recherche')]) }}" class="btn-export" title="PDF">
            <i class="fa-solid fa-file-pdf"></i><span class="btn-export-text">PDF</span>
        </a>
        <a href="{{ route('admin.paiement-frais-supplementaires.export', ['format' => 'csv', 'frais' => request('frais'), 'salle' => request('salle'), 'recherche' => request('recherche')]) }}" class="btn-export" title="CSV">
            <i class="fa-solid fa-file-csv"></i><span class="btn-export-text">CSV</span>
        </a>
        <a href="{{ route('admin.paiement-frais-supplementaires.export', ['format' => 'xml', 'frais' => request('frais'), 'salle' => request('salle'), 'recherche' => request('recherche')]) }}" class="btn-export" title="XML">
            <i class="fa-solid fa-file-code"></i><span class="btn-export-text">XML</span>
        </a>
        <a href="{{ route('admin.paiement-frais-supplementaires.export', ['format' => 'word', 'frais' => request('frais'), 'salle' => request('salle'), 'recherche' => request('recherche')]) }}" class="btn-export" title="Word">
            <i class="fa-solid fa-file-word"></i><span class="btn-export-text">Word</span>
        </a>
    </div>

    {{-- Statistiques globales --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600"><i class="fa-solid fa-coins"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalPayeUSD ?? 0, 0, ',', ' ') }} $</div>
                <div class="stat-label">Total payé (USD)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600"><i class="fa-solid fa-coins"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalPayeFC ?? 0, 0, ',', ' ') }} FC</div>
                <div class="stat-label">Total payé (FC)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600"><i class="fa-solid fa-receipt"></i></div>
            <div>
                <div class="stat-value">{{ $paiements->total() }}</div>
                <div class="stat-label">Nombre de paiements</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-tag"></i></div>
            <div>
                <div class="stat-value">{{ $nbFraisConcernes ?? 0 }}</div>
                <div class="stat-label">Frais différents</div>
            </div>
        </div>
    </div>

    {{-- Statistiques par frais --}}
    @if(!empty($statsParFrais))
    <div class="stats-mensuelles">
        <div class="section-title">
            <i class="fa-regular fa-chart-bar"></i>
            <span>Répartition par frais</span>
            @if(request()->hasAny(['frais', 'salle', 'recherche']))
                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full ml-2">filtré</span>
            @endif
        </div>
        <div class="mois-stats-grid">
            @foreach($statsParFrais as $stat)
                <div class="mois-stat-card">
                    <div class="mois-nom">{{ $stat['libelle'] }}</div>
                    <div class="mois-total-usd">{{ number_format($stat['total_usd'], 0, ',', ' ') }} $</div>
                    <div class="mois-total-fc">{{ number_format($stat['total_fc'], 0, ',', ' ') }} FC</div>
                    <div class="mois-count">{{ $stat['count'] }} paiement(s)</div>
                    <div class="mois-moyenne">Moyenne : {{ number_format($stat['moyenne_usd'], 0, ',', ' ') }} $</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filtres --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.paiement-frais-supplementaires.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-tag"></i> Frais</label>
                <select name="frais" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    @foreach($frais as $f)
                        <option value="{{ $f->id }}" @selected(request('frais') == $f->id)>{{ $f->libelle }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-building"></i> Salle</label>
                <select name="salle" class="filter-select" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    @foreach($salles as $salle)
                        <option value="{{ $salle->id }}" @selected(request('salle') == $salle->id)>{{ $salle->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-user"></i> Recherche élève</label>
                <div class="search-input-wrap">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" name="recherche" class="filter-input" placeholder="Nom ou prénom..." value="{{ request('recherche') }}">
                </div>
            </div>

            <button type="submit" class="btn-filter" aria-label="Appliquer les filtres">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            @if(request()->hasAny(['frais', 'salle', 'recherche']))
                <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                </a>
            @endif
        </form>
        <div class="filter-result-count" role="status" aria-live="polite">
            {{ $paiements->total() }} paiement(s) trouvé(s)
        </div>
    </div>

    {{-- Tableau --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list text-indigo-500"></i>
                <span>Paiements enregistrés</span>
                <span class="badge-count">{{ $paiements->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">Taux : {{ number_format($tauxChange ?? 2800, 2) }} FC/USD</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Élève</th>
                        <th scope="col">Frais</th>
                        <th scope="col" class="text-right">Montant payé</th>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paiements as $paiement)
                        @php
                            $eleveNom = $paiement->eleve?->nom ?? 'Élève supprimé';
                            $elevePrenom = $paiement->eleve?->prenom ?? '';
                            $nomComplet = trim($eleveNom . ' ' . $elevePrenom);
                            $avatar = strtoupper(substr($eleveNom, 0, 1) . substr($elevePrenom, 0, 1));
                            $fraisLibelle = $paiement->fraisSupplementaire?->libelle ?? 'Frais supprimé';
                        @endphp
                        <tr>
                            <td data-label="Élève">
                                <div class="cell-eleve">
                                    <div class="avatar" aria-hidden="true">{{ $avatar }}</div>
                                    <span class="cell-primary">{{ $nomComplet }}</span>
                                </div>
                            </td>
                            <td data-label="Frais">
                                <span class="font-medium">{{ $fraisLibelle }}</span>
                            </td>
                            <td data-label="Montant payé" class="text-right">
                                <strong>{{ number_format($paiement->montant_paye_usd, 0, ',', ' ') }} $</strong>
                                <span class="block text-xs text-gray-400">{{ number_format($paiement->montant_paye_fc, 0, ',', ' ') }} FC</span>
                            </td>
                            <td data-label="Date">
                                <span class="date-cell">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    {{ $paiement->date_paiement->format('d/m/Y') }}
                                </span>
                            </td>
                            <td data-label="Actions" class="text-center action-cell">
                                <div class="action-icons">
                                    <a href="{{ route('admin.paiement-frais-supplementaires.edit', $paiement) }}" class="action-icon" title="Modifier" aria-label="Modifier le paiement de {{ $nomComplet }}">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('admin.paiement-frais-supplementaires.destroy', $paiement) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer définitivement ce paiement ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-icon danger" title="Supprimer" aria-label="Supprimer le paiement de {{ $nomComplet }}">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                    <p>Aucun paiement enregistré.</p>
                                    <a href="{{ route('admin.paiement-frais-supplementaires.create') }}" class="btn-secondary">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Créer un paiement
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pied du tableau avec taux de change --}}
        <div class="table-footer">
            <span class="text-muted text-sm">Taux de change utilisé : {{ number_format($tauxChange ?? 2800, 2) }} FC/USD</span>
        </div>

        {{-- Pagination --}}
        @if($paiements->hasPages())
            <div class="pagination-container">
                {{ $paiements->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    :root {
        --color-primary: #1e293b;
        --color-primary-hover: #667eea;
        --color-secondary: #475569;
        --color-border: #e2e8f0;
        --color-muted: #94a3b8;
        --color-bg-light: #f8fafc;
        --color-white: #ffffff;
        --shadow-card: 0 4px 12px rgba(0,0,0,0.04);
        --shadow-hover: 0 10px 30px rgba(102,126,234,0.3);
        --radius-card: 16px;
        --radius-btn: 12px;
    }

    .index-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    .index-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
    .header-title-block { flex: 1; min-width: 0; }
    .header-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: flex-end; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: var(--color-primary); margin: 0; line-height: 1.2; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: var(--color-muted); font-size: 0.95rem; margin-top: 0.25rem; }
    .filter-indicator { color: var(--color-primary-hover); font-weight: 600; }

    /* Boutons */
    .btn-primary, .btn-secondary, .btn-print, .btn-export, .btn-filter, .btn-reset {
        display: inline-flex; align-items: center; gap: 8px; padding: 0.7rem 1.5rem;
        border-radius: var(--radius-btn); font-weight: 600; font-size: 0.95rem;
        text-decoration: none; transition: all 0.3s; border: none; cursor: pointer;
    }
    .btn-primary, .btn-print, .btn-filter { background: var(--color-primary); color: white; }
    .btn-primary:hover, .btn-print:hover, .btn-filter:hover {
        background: var(--color-primary-hover); transform: translateY(-2px); box-shadow: var(--shadow-hover);
    }
    .btn-print, .btn-filter { background: var(--color-secondary); }
    .btn-secondary, .btn-reset { background: var(--color-white); color: var(--color-primary); border: 1.5px solid var(--color-border); }
    .btn-secondary:hover, .btn-reset:hover { border-color: var(--color-primary-hover); color: var(--color-primary-hover); background: var(--color-bg-light); }
    .btn-export { padding: 0.5rem 0.8rem; background: var(--color-bg-light); color: var(--color-secondary); border: 1.5px solid var(--color-border); border-radius: 8px; }
    .btn-export:hover { background: var(--color-border); color: var(--color-primary); border-color: #cbd5e1; transform: translateY(-1px); }

    .export-bar {
        display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
        margin-bottom: 1.5rem; background: var(--color-white); border-radius: var(--radius-card);
        padding: 0.75rem 1rem; box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .export-label { font-size: 0.8rem; color: var(--color-secondary); font-weight: 600; margin-right: 0.25rem; }
    .btn-export-text { display: inline; }

    /* Statistiques */
    .stats-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem; margin-bottom: 1.5rem;
    }
    .stat-card {
        background: var(--color-white); border-radius: 14px; padding: 1rem 1.25rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--color-primary); line-height: 1.2; }
    .stat-label { font-size: 0.75rem; color: var(--color-muted); text-transform: uppercase; letter-spacing: 0.3px; }

    .stats-mensuelles {
        background: var(--color-white); border-radius: var(--radius-card);
        padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .section-title { font-size: 1rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
    .mois-stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
    .mois-stat-card {
        background: var(--color-bg-light); border-radius: 12px; padding: 0.75rem 1rem;
        border: 1px solid var(--color-border); transition: border-color 0.2s;
    }
    .mois-stat-card:hover { border-color: var(--color-primary-hover); }
    .mois-nom { font-weight: 600; color: var(--color-primary); font-size: 0.95rem; margin-bottom: 0.25rem; }
    .mois-total-usd { font-size: 1.1rem; font-weight: 700; color: var(--color-primary); }
    .mois-total-fc { font-size: 0.85rem; color: var(--color-secondary); }
    .mois-count { font-size: 0.8rem; color: var(--color-muted); }
    .mois-moyenne { font-size: 0.8rem; color: var(--color-secondary); }

    /* Filtres */
    .filter-card {
        background: var(--color-white); border-radius: var(--radius-card);
        padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; min-width: 160px; }
    .filter-label { font-size: 0.75rem; font-weight: 600; color: var(--color-secondary); letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-select, .filter-input {
        width: 100%; padding: 0.6rem 1rem; border: 2px solid var(--color-border);
        border-radius: 10px; background: var(--color-bg-light); font-size: 0.95rem;
        color: var(--color-primary); transition: all 0.3s; outline: none; appearance: none;
    }
    .filter-input { padding-left: 2.2rem; }
    .filter-select:focus, .filter-input:focus { border-color: var(--color-primary-hover); background: var(--color-white); box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .search-input-wrap { position: relative; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--color-muted); font-size: 0.9rem; pointer-events: none; }
    .filter-result-count { margin-top: 0.75rem; font-size: 0.9rem; color: var(--color-secondary); padding-top: 0.5rem; border-top: 1px solid var(--color-border); }

    /* Tableau */
    .table-card {
        background: var(--color-white); border-radius: var(--radius-card);
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9; overflow: hidden;
    }
    .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 0.5rem; }
    .table-title { font-size: 1.05rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 8px; }
    .badge-count { background: var(--color-border); color: var(--color-secondary); padding: 0.1rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .table-actions { font-size: 0.8rem; }
    .text-muted { color: var(--color-muted); }
    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: var(--color-secondary); }
    .data-table thead th {
        text-align: left; padding: 0.8rem 1.25rem; font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-muted);
        background: var(--color-bg-light); border-bottom: 2px solid var(--color-border);
    }
    .data-table thead th.text-right { text-align: right; }
    .data-table thead th.text-center { text-align: center; }
    .data-table tbody td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: var(--color-bg-light); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-right { text-align: right; }
    .data-table .text-center { text-align: center; }
    .empty-cell { text-align: center; padding: 3rem 1.5rem; color: var(--color-muted); }
    .table-footer { padding: 0.8rem 1.5rem; background: var(--color-bg-light); border-top: 1px solid var(--color-border); text-align: right; font-size: 0.8rem; color: var(--color-secondary); }
    .cell-eleve { display: flex; align-items: center; gap: 0.75rem; }
    .avatar { width: 36px; height: 36px; border-radius: 10px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
    .cell-primary { font-weight: 600; color: var(--color-primary); }
    .date-cell { display: inline-flex; align-items: center; gap: 6px; color: var(--color-secondary); }
    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: center; gap: 0.5rem; }
    .action-icon {
        display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px;
        border-radius: 8px; color: var(--color-muted); text-decoration: none;
        transition: all 0.2s; background: none; border: none; cursor: pointer; font-size: 1rem;
    }
    .action-icon:hover { background: #f1f5f9; color: var(--color-primary-hover); }
    .action-icon.danger:hover { background: #fee2e2; color: #dc2626; }
    .inline-form { display: inline; }
    .empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .empty-state i { font-size: 3rem; color: #cbd5e1; }
    .pagination-container { padding: 0.8rem 1.5rem; border-top: 1px solid #f1f5f9; }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes cardIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* Responsive */
    @media (max-width: 768px) {
        .index-header { flex-direction: column; align-items: stretch; }
        .header-title-block { width: 100%; }
        .header-actions { width: 100%; justify-content: space-between; flex-direction: column; }
        .header-actions .btn-primary, .header-actions .btn-print, .header-actions .btn-secondary { width: 100%; justify-content: center; }
        .export-bar { flex-direction: column; align-items: stretch; }
        .export-label { margin-bottom: 0.5rem; }
        .btn-export { justify-content: center; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .mois-stats-grid { grid-template-columns: 1fr 1fr; }
        .filter-form { flex-direction: column; }
        .filter-field { min-width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }
        .table-responsive { overflow-x: visible; }
        .data-table { min-width: 0; }
        .data-table thead { display: none; }
        .data-table tbody tr {
            display: block; background: var(--color-white); border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem 1.25rem;
        }
        .data-table tbody td {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; gap: 0.5rem;
        }
        .data-table tbody td:last-child { border-bottom: none; }
        .data-table tbody td::before {
            content: attr(data-label); font-weight: 600; color: var(--color-muted);
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.3px;
            min-width: 100px; flex-shrink: 0;
        }
        .data-table tbody td.text-right { text-align: left; justify-content: space-between; }
        .data-table tbody td.text-center { text-align: left; justify-content: space-between; }
        .action-icons { justify-content: flex-start; }
        .badge-count { display: none; }
        .table-header { flex-direction: column; align-items: flex-start; }
        .table-footer { text-align: center; }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .stat-card { padding: 0.75rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 1rem; }
        .stat-value { font-size: 1.1rem; }
        .index-title { font-size: 1.3rem; }
        .index-subtitle { font-size: 0.8rem; }
        .btn-primary, .btn-print, .btn-export, .btn-secondary { font-size: 0.85rem; padding: 0.5rem 1rem; }
        .filter-select, .filter-input { font-size: 0.85rem; padding: 0.5rem 0.75rem; }
        .data-table tbody td { font-size: 0.85rem; }
        .mois-stats-grid { grid-template-columns: 1fr 1fr; }
        .btn-export-text { font-size: 0.8rem; }
    }

    /* Impression */
    @media print {
        .index-header, .filter-card, .pagination-container, .stats-grid,
        .stats-mensuelles, .table-footer, .header-actions, .export-bar { display: none !important; }
        .table-card { box-shadow: none; animation: none; opacity: 1; }
        .data-table { min-width: 0; font-size: 0.75rem; }
        .data-table thead th { background: #f8fafc !important; color: #1e293b !important; }
        .avatar { display: none !important; }
        .table-header { display: none; }

        /* Garder les deux montants bien visibles */
        .data-table td strong {
            font-size: 1rem;
            color: #000 !important;
        }
        .data-table td .block.text-xs {
            font-size: 0.8rem;
            color: #333 !important;
            display: block !important;
            margin-top: 2px;
        }
    }
</style>
@endsection