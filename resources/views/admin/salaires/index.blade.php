@extends('layouts.admin')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Salaires</strong> du personnel</h1>
            <p class="index-subtitle">Gérez la rémunération du personnel enseignant</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Personnel
        </a>
    </div>

    {{-- Statistiques globales --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="stat-value">{{ $users->total() }}</div>
                <div class="stat-label">Total personnel</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100 text-blue-600"><i class="fa-solid fa-robot"></i></div>
            <div>
                <div class="stat-value">{{ $nbAutomatique ?? 0 }}</div>
                <div class="stat-label">Salaire automatique</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-gray-100 text-gray-600"><i class="fa-solid fa-user-pen"></i></div>
            <div>
                <div class="stat-value">{{ $nbManuel ?? 0 }}</div>
                <div class="stat-label">Salaire manuel</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600"><i class="fa-solid fa-coins"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalSalairesUSD ?? 0, 0, ',', ' ') }} $</div>
                <div class="stat-label">Total salaires (USD)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600"><i class="fa-solid fa-coins"></i></div>
            <div>
                <div class="stat-value">{{ number_format($totalSalairesFC ?? 0, 0, ',', ' ') }} FC</div>
                <div class="stat-label">Total salaires (FC)</div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.salaires.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-user"></i> Recherche</label>
                <input type="text" name="search" class="filter-input" placeholder="Nom ou email..."
                       value="{{ request('search') }}">
            </div>
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-gear"></i> Type de salaire</label>
                <select name="type_salaire" class="filter-select">
                    <option value="">Tous</option>
                    <option value="manuel" {{ request('type_salaire') == 'manuel' ? 'selected' : '' }}>Manuel</option>
                    <option value="automatique" {{ request('type_salaire') == 'automatique' ? 'selected' : '' }}>Automatique</option>
                </select>
            </div>
            <div class="filter-field checkbox-field">
                <label class="filter-label"><i class="fa-regular fa-book"></i> Cours</label>
                <label class="checkbox-label">
                    <input type="checkbox" name="avec_cours" value="1"
                           class="form-check-input" {{ request('avec_cours') ? 'checked' : '' }}>
                    <span>Uniquement avec cours</span>
                </label>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            @if(request()->hasAny(['type_salaire', 'avec_cours', 'search']))
                <a href="{{ route('admin.salaires.index') }}" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list text-indigo-500"></i>
                <span>Salaires enregistrés</span>
                <span class="badge-count">{{ $users->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">Taux : {{ number_format($tauxChange ?? 2800, 2) }} FC/USD</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Fonction</th>
                        <th>Section</th>
                        <th>Type salaire</th>
                        <th class="text-right">Salaire actuel</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $salaireUsd = match($user->type_salaire) {
                                'manuel' => $user->salaire_mensuel_usd ?? 0,
                                default => $user->salaire_ajuste_usd ?? $user->salaire_auto_base_usd ?? 0,
                            };
                            $salaireFc = match($user->type_salaire) {
                                'manuel' => $user->salaire_mensuel_fc ?? 0,
                                default => $user->salaire_ajuste_fc ?? $user->salaire_auto_base_fc ?? 0,
                            };
                            $typeLabel = $user->type_salaire === 'automatique' ? 'Automatique' : 'Manuel';
                            $badgeClass = $user->type_salaire === 'automatique' ? 'badge-blue' : 'badge-gray';
                            $avatar = strtoupper(substr($user->name, 0, 1));
                        @endphp
                        <tr>
                            <td data-label="Nom">
                                <div class="cell-user">
                                    <div class="avatar" aria-hidden="true">{{ $avatar }}</div>
                                    <span class="cell-primary">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td data-label="Fonction">{{ $user->fonction->nom ?? '—' }}</td>
                            <td data-label="Section">{{ $user->section->nom ?? '—' }}</td>
                            <td data-label="Type salaire">
                                <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                            </td>
                            <td data-label="Salaire actuel" class="text-right">
                                <strong>{{ number_format($salaireUsd, 0, ',', ' ') }} $</strong>
                                <span class="block text-xs text-gray-400">{{ number_format($salaireFc, 0, ',', ' ') }} FC</span>
                            </td>
                            <td data-label="Actions" class="text-center">
                                <div class="action-icons">
                                    <a href="{{ route('admin.salaires.edit', $user) }}" class="action-icon" title="Fixer / Modifier" aria-label="Fixer le salaire de {{ $user->name }}">
                                        <i class="fa-solid fa-coins"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">
                                <i class="fa-regular fa-circle-info text-2xl block mb-2 text-gray-300"></i>
                                Aucun personnel trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pagination-container">
            {{ $users->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<style>
    .index-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }

    .index-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; animation: fadeUp 0.6s ease forwards; opacity: 0; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; margin-top: 0.25rem; }

    .btn-primary, .btn-secondary { display: inline-flex; align-items: center; gap: 8px; padding: 0.7rem 1.5rem; background: #1e293b; color: white; border-radius: 12px; font-weight: 600; font-size: 0.95rem; text-decoration: none; transition: all 0.3s; border: none; cursor: pointer; }
    .btn-primary:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102,126,234,0.3); }
    .btn-secondary { background: #f8fafc; color: #1e293b; border: 1.5px solid #e2e8f0; }
    .btn-secondary:hover { border-color: #667eea; color: #667eea; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; animation: fadeUp 0.6s 0.15s ease forwards; opacity: 0; }
    .stat-card { background: white; border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .stat-value { font-size: 1.3rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
    .stat-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.3px; }

    .filter-card { background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #f1f5f9; animation: fadeUp 0.6s 0.3s ease forwards; opacity: 0; }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; min-width: 160px; }
    .filter-label { font-size: 0.75rem; font-weight: 600; color: #475569; letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-select, .filter-input { width: 100%; padding: 0.6rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #1e293b; transition: all 0.3s; outline: none; appearance: none; }
    .filter-select:focus, .filter-input:focus { border-color: #667eea; background: white; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .checkbox-label { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #475569; cursor: pointer; }
    .form-check-input { width: 18px; height: 18px; border: 1.5px solid #cbd5e1; border-radius: 4px; cursor: pointer; appearance: none; -webkit-appearance: none; position: relative; transition: all 0.2s; }
    .form-check-input:checked { background-color: #667eea; border-color: #667eea; }
    .form-check-input:checked::after { content: '✓'; position: absolute; color: white; font-size: 12px; top: 50%; left: 50%; transform: translate(-50%, -50%); }

    .btn-filter, .btn-reset { display: inline-flex; align-items: center; gap: 6px; padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; border: none; }
    .btn-filter { background: #1e293b; color: white; }
    .btn-filter:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
    .btn-reset { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-reset:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    .table-card { background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; overflow: hidden; animation: cardIn 0.8s 0.5s ease forwards; opacity: 0; }
    .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 0.5rem; }
    .table-title { font-size: 1.05rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    .badge-count { background: #e2e8f0; color: #475569; padding: 0.1rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .table-actions { font-size: 0.8rem; }
    .text-muted { color: #94a3b8; }

    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; min-width: 700px; }
    .data-table thead th { text-align: left; padding: 0.8rem 1.25rem; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .data-table thead th.text-right { text-align: right; }
    .data-table thead th.text-center { text-align: center; }
    .data-table tbody td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-right { text-align: right; }
    .data-table .text-center { text-align: center; }
    .empty-cell { text-align: center; padding: 3rem 1.5rem; color: #94a3b8; }

    .cell-user { display: flex; align-items: center; gap: 0.75rem; }
    .avatar { width: 36px; height: 36px; border-radius: 10px; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
    .cell-primary { font-weight: 600; color: #1e293b; }

    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 0.25rem 0.7rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-gray { background: #f1f5f9; color: #64748b; }

    .action-icons { display: flex; justify-content: center; gap: 0.5rem; }
    .action-icon { background: transparent; border: none; cursor: pointer; color: #94a3b8; font-size: 1rem; transition: all 0.2s; padding: 0.25rem 0.4rem; border-radius: 6px; }
    .action-icon:hover { background: #f1f5f9; color: #667eea; }

    .pagination-container { padding: 0.8rem 1.5rem; border-top: 1px solid #f1f5f9; }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes cardIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .index-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-form { flex-direction: column; }
        .filter-field { min-width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }

        .data-table thead { display: none; }
        .data-table tbody tr { display: block; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem 1.25rem; }
        .data-table tbody td { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
        .data-table tbody td:last-child { border-bottom: none; }
        .data-table tbody td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.3px; min-width: 100px; }
        .data-table tbody td.text-right { text-align: left; justify-content: space-between; }
        .data-table tbody td.text-center { text-align: left; justify-content: space-between; }
        .action-icons { justify-content: flex-start; }
        .badge-count { display: none; }
        .table-header { flex-direction: column; align-items: flex-start; }
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .stat-card { padding: 0.75rem; }
        .stat-icon { width: 36px; height: 36px; font-size: 1rem; }
        .stat-value { font-size: 1.1rem; }
        .index-title { font-size: 1.3rem; }
        .index-subtitle { font-size: 0.8rem; }
    }
</style>
@endsection