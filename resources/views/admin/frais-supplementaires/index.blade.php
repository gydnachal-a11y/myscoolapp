@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- EN-TÊTE --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Frais</strong> supplémentaires</h1>
            <p class="index-subtitle">
                Gérez les frais ponctuels en dehors de la scolarité
                @if(request()->hasAny(['search', 'est_ouvert']))
                    <span class="filter-indicator"> • Filtres actifs</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2 header-actions">
            <a href="{{ route('admin.paiement-frais-supplementaires.index') }}" class="btn-secondary">
                <i class="fa-solid fa-money-bill-wave"></i> Paiements
            </a>
            <a href="{{ route('admin.frais-supplementaires.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouveau frais
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- STATISTIQUES --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-tag" aria-hidden="true"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($totalFrais, 0, ',', ' ') }}</div>
                <div class="stat-label">Total frais</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($totalOuverts, 0, ',', ' ') }}</div>
                <div class="stat-label">Ouverts</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-red-100 text-red-600">
                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($totalFermes, 0, ',', ' ') }}</div>
                <div class="stat-label">Fermés</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
                <i class="fa-solid fa-coins" aria-hidden="true"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($montantTotal, 0, ',', ' ') }} $</div>
                <div class="stat-label">Montant total (USD)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
                <i class="fa-solid fa-coins" aria-hidden="true"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($montantTotal * $tauxChange, 0, ',', ' ') }} FC</div>
                <div class="stat-label">Montant total (FC)</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- FILTRES --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.frais-supplementaires.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label" for="f-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Recherche
                </label>
                <input type="text" name="search" id="f-search" class="filter-input"
                       placeholder="Libellé ou description..."
                       value="{{ request('search') }}">
            </div>

            <div class="filter-field">
                <label class="filter-label" for="f-statut">
                    <i class="fa-regular fa-circle" aria-hidden="true"></i> Statut
                </label>
                <select name="est_ouvert" id="f-statut" class="filter-select" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('est_ouvert') == '1')>Ouvert</option>
                    <option value="0" @selected(request('est_ouvert') == '0')>Fermé</option>
                </select>
            </div>

            <div class="filter-field">
                <label class="filter-label" for="f-per-page">
                    <i class="fa-solid fa-list" aria-hidden="true"></i> Lignes par page
                </label>
                <select name="per_page" id="f-per-page" class="filter-select" onchange="this.form.submit()">
                    @foreach([15, 30, 50, 100] as $n)
                        <option value="{{ $n }}" @selected(request('per_page') == $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-filter" aria-label="Appliquer les filtres">
                <i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrer
            </button>

            @if(request()->hasAny(['search', 'est_ouvert']))
                <a href="{{ route('admin.frais-supplementaires.index') }}" class="btn-reset">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Réinitialiser
                </a>
            @endif
        </form>

        <div class="filter-result-count" role="status" aria-live="polite">
            {{ $frais->total() }} frais trouvé(s)
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- TABLEAU --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list text-indigo-500" aria-hidden="true"></i>
                <span>Frais enregistrés</span>
                <span class="badge-count">{{ $frais->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">Taux : {{ number_format($tauxChange, 2, ',', ' ') }} FC/USD</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Libellé</th>
                        <th scope="col" class="text-right">Montant</th>
                        <th scope="col">Période</th>
                        <th scope="col">Portée</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($frais as $fraisItem)
                        @php
                            /* ---------- Calculs ---------- */
                            $montantUSD  = number_format((float) $fraisItem->montant, 0, ',', ' ');
                            $montantFC   = number_format((float) $fraisItem->montant * $tauxChange, 0, ',', ' ');
                            $estOuvert   = (bool) $fraisItem->est_ouvert;
                            $estToutes   = (bool) $fraisItem->est_pour_toutes_salles;
                            $nbSalles    = $fraisItem->salles->count();

                            /* ---------- Portée ---------- */
                            $libellePortee = $estToutes
                                ? 'Toutes les salles'
                                : $nbSalles . ' salle' . ($nbSalles > 1 ? 's' : '');
                            $badgePortee = $estToutes ? 'badge-blue' : 'badge-gray';

                            /* ---------- Statut ---------- */
                            $badgeStatut = $estOuvert ? 'badge-green' : 'badge-red';
                            $labelStatut = $estOuvert ? 'Ouvert' : 'Fermé';

                            /* ---------- Période ---------- */
                            $debut = $fraisItem->date_debut?->format('d/m/Y') ?? '—';
                            $fin   = $fraisItem->date_fin?->format('d/m/Y')   ?? '—';
                        @endphp
                        <tr>
                            <td data-label="Libellé">
                                <div class="cell-primary">{{ $fraisItem->libelle }}</div>
                                @if($fraisItem->description)
                                    <div class="cell-description">{{ \Illuminate\Support\Str::limit($fraisItem->description, 60) }}</div>
                                @endif
                            </td>

                            <td data-label="Montant" class="text-right">
                                <strong>{{ $montantUSD }} $</strong>
                                <span class="block text-xs text-gray-400">{{ $montantFC }} FC</span>
                            </td>

                            <td data-label="Période">
                                <span class="date-cell">
                                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                    {{ $debut }} → {{ $fin }}
                                </span>
                            </td>

                            <td data-label="Portée">
                                <span class="badge {{ $badgePortee }}">
                                    <i class="fa-solid {{ $estToutes ? 'fa-globe' : 'fa-door-open' }}" aria-hidden="true"></i>
                                    {{ $libellePortee }}
                                </span>
                            </td>

                            <td data-label="Statut">
                                <span class="badge {{ $badgeStatut }}">
                                    <i class="fa-solid fa-circle" aria-hidden="true"></i>
                                    {{ $labelStatut }}
                                </span>
                            </td>

                            <td data-label="Actions" class="text-center action-cell">
                                <div class="action-icons">
                                    {{-- Paiement --}}
                                    <a href="{{ route('admin.paiement-frais-supplementaires.create', ['frais' => $fraisItem->id]) }}"
                                       class="action-icon" title="Enregistrer un paiement"
                                       aria-label="Enregistrer un paiement pour {{ $fraisItem->libelle }}">
                                        <i class="fa-solid fa-money-bill" aria-hidden="true"></i>
                                    </a>

                                    {{-- Toggle ouverture (PATCH) --}}
                                    <form action="{{ route('admin.frais-supplementaires.toggle', $fraisItem) }}"
                                          method="POST" class="inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="action-icon"
                                                title="{{ $estOuvert ? 'Fermer' : 'Ouvrir' }}"
                                                aria-label="{{ $estOuvert ? 'Fermer' : 'Ouvrir' }} le frais">
                                            <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    {{-- Modifier --}}
                                    <a href="{{ route('admin.frais-supplementaires.edit', $fraisItem) }}"
                                       class="action-icon" title="Modifier"
                                       aria-label="Modifier le frais">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    {{-- Supprimer --}}
                                    <form action="{{ route('admin.frais-supplementaires.destroy', $fraisItem) }}"
                                          method="POST" class="inline-form"
                                          onsubmit="return confirm('Supprimer définitivement ce frais ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-icon danger"
                                                title="Supprimer"
                                                aria-label="Supprimer le frais">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                    <p>Aucun frais supplémentaire enregistré.</p>
                                    <a href="{{ route('admin.frais-supplementaires.create') }}" class="btn-secondary">
                                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Créer un frais
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span class="text-muted text-sm">Taux de change utilisé : {{ number_format($tauxChange, 2, ',', ' ') }} FC/USD</span>
        </div>

        @if($frais->hasPages())
            <div class="pagination-container">
                {{ $frais->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    /* ════════════════════════════════════════════════════════
       VARIABLES
       ════════════════════════════════════════════════════════ */
    :root {
        --primary: #667eea;
        --primary-dark: #4f46e5;
        --danger: #ef4444;
        --border: #e2e8f0;
        --text: #1e293b;
        --muted: #94a3b8;
        --bg-light: #f8fafc;
        --shadow-card: 0 4px 12px rgba(0,0,0,0.04);
        --radius-card: 16px;
        --radius-btn: 12px;
    }

    .index-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

    /* EN-TÊTE */
    .index-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .header-actions { flex-wrap: wrap; gap: 0.5rem; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: var(--text); margin: 0; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: var(--muted); font-size: 0.95rem; margin-top: 0.25rem; }
    .filter-indicator { color: var(--primary); font-weight: 600; }

    /* BOUTONS */
    .btn-primary, .btn-secondary {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 0.7rem 1.5rem; border-radius: var(--radius-btn);
        font-weight: 600; font-size: 0.95rem; text-decoration: none;
        transition: all 0.3s; border: none; cursor: pointer;
    }
    .btn-primary { background: var(--text); color: white; }
    .btn-primary:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-secondary { background: var(--bg-light); color: var(--text); border: 1.5px solid var(--border); }
    .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }

    /* STATS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .stat-card {
        background: white; border-radius: 14px; padding: 1rem 1.25rem;
        display: flex; align-items: center; gap: 1rem;
        box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--text); line-height: 1.2; }
    .stat-label { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.3px; }

    /* FILTRES */
    .filter-card {
        background: white; border-radius: var(--radius-card); padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem; box-shadow: var(--shadow-card); border: 1px solid #f1f5f9;
    }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; min-width: 160px; }
    .filter-label { font-size: 0.75rem; font-weight: 600; color: #475569; letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-input, .filter-select { width: 100%; padding: 0.6rem 1rem; border: 2px solid var(--border); border-radius: 10px; background: var(--bg-light); font-size: 0.95rem; color: var(--text); transition: all 0.3s; outline: none; appearance: none; }
    .filter-input:focus, .filter-select:focus { border-color: var(--primary); background: white; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .btn-filter, .btn-reset { display: inline-flex; align-items: center; gap: 6px; padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; border: none; }
    .btn-filter { background: var(--text); color: white; }
    .btn-filter:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
    .btn-reset { background: white; border: 1.5px solid var(--border); color: #64748b; }
    .btn-reset:hover { border-color: var(--primary); color: var(--primary); background: var(--bg-light); }
    .filter-result-count { margin-top: 0.75rem; font-size: 0.9rem; color: #64748b; padding-top: 0.5rem; border-top: 1px solid var(--border); }

    /* TABLEAU */
    .table-card { background: white; border-radius: var(--radius-card); box-shadow: var(--shadow-card); border: 1px solid #f1f5f9; overflow: hidden; }
    .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 0.5rem; }
    .table-title { font-size: 1.05rem; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 8px; }
    .badge-count { background: var(--border); color: #475569; padding: 0.1rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .table-actions { font-size: 0.8rem; }
    .text-muted { color: var(--muted); }
    .table-responsive { overflow-x: auto; }

    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.8rem 1.25rem; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); background: var(--bg-light); border-bottom: 2px solid var(--border); }
    .data-table thead th.text-right { text-align: right; }
    .data-table thead th.text-center { text-align: center; }
    .data-table tbody td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: var(--bg-light); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-right { text-align: right; }
    .data-table .text-center { text-align: center; }
    .empty-cell { text-align: center; padding: 3rem 1.5rem; color: var(--muted); }
    .table-footer { padding: 0.8rem 1.5rem; background: var(--bg-light); border-top: 1px solid var(--border); text-align: right; font-size: 0.8rem; color: #64748b; }

    .cell-primary { font-weight: 600; color: var(--text); }
    .cell-description { color: var(--muted); font-size: 0.8rem; margin-top: 0.25rem; line-height: 1.4; }
    .date-cell { display: inline-flex; align-items: center; gap: 6px; color: #64748b; }

    /* BADGES */
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 0.25rem 0.7rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-gray { background: #f1f5f9; color: #64748b; }
    .badge-green { background: #dcfce7; color: #16a34a; }
    .badge-red { background: #fee2e2; color: #dc2626; }

    /* ACTIONS */
    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: center; gap: 0.5rem; }
    .action-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; color: var(--muted); text-decoration: none; transition: all 0.2s; background: none; border: none; cursor: pointer; font-size: 1rem; }
    .action-icon:hover { background: #f1f5f9; color: var(--primary); }
    .action-icon.danger:hover { background: #fee2e2; color: #dc2626; }
    .inline-form { display: inline; }

    /* EMPTY STATE */
    .empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .empty-state i { font-size: 3rem; color: #cbd5e1; }
    .pagination-container { padding: 0.8rem 1.5rem; border-top: 1px solid #f1f5f9; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .index-header { flex-direction: column; align-items: flex-start; }
        .header-actions { width: 100%; flex-direction: column; }
        .btn-primary, .btn-secondary { width: 100%; justify-content: center; }

        .stats-grid { grid-template-columns: 1fr; }
        .stat-card { padding: 0.75rem 1rem; }
        .stat-icon { width: 40px; height: 40px; font-size: 1rem; }
        .stat-value { font-size: 1.2rem; }

        .filter-form { flex-direction: column; align-items: stretch; }
        .filter-field { min-width: 100%; }
        .btn-filter, .btn-reset { width: 100%; justify-content: center; }

        .table-responsive { overflow-x: visible; }
        .data-table thead { display: none; }
        .data-table tbody tr {
            display: block;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 1.25rem;
            padding: 1rem 1.25rem;
            border: 1px solid #f1f5f9;
        }
        .data-table tbody td {
            display: block;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }
        .data-table tbody td:last-child { border-bottom: none; }
        .data-table tbody td::before {
            content: attr(data-label);
            display: block;
            font-weight: 600;
            color: var(--muted);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.25rem;
        }
        .data-table tbody td.text-right,
        .data-table tbody td.text-center { text-align: left; }
        .action-icons { justify-content: flex-start; }
        .badge-count { display: none; }
        .table-header { flex-direction: column; align-items: flex-start; }
        .table-footer { text-align: center; }
    }

    @media (max-width: 480px) {
        .index-title { font-size: 1.3rem; }
        .index-subtitle { font-size: 0.8rem; }
        .stat-card { padding: 0.6rem 0.8rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 0.9rem; }
        .stat-value { font-size: 1rem; }
        .filter-input, .filter-select { font-size: 0.85rem; padding: 0.5rem 0.75rem; }
        .data-table tbody td { font-size: 0.85rem; }
        .action-icon { width: 28px; height: 28px; font-size: 0.9rem; }
    }

    @media print {
        .index-header, .filter-card, .pagination-container,
        .action-icons, .stats-grid, .table-footer { display: none !important; }
        .table-card { box-shadow: none; }
        .data-table { min-width: 0; font-size: 0.75rem; }
    }
</style>
@endsection