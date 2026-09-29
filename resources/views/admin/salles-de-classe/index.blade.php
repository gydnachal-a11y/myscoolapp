@extends('layouts.admin')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Salles de classe</strong></h1>
            <p class="index-subtitle">Gérez les salles et leurs paramètres financiers</p>
        </div>
        <a href="{{ route('admin.salles-de-classe.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nouvelle salle
        </a>
    </div>

    {{-- Cartes statistiques --}}
    <div class="stats-grid four-cols">
        <div class="stat-card border-indigo">
            <div class="stat-icon"><i class="fa-solid fa-door-open"></i></div>
            <div>
                <p class="stat-label">Total salles</p>
                <p class="stat-value">{{ $salles->total() }}</p>
            </div>
        </div>
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <p class="stat-label">Capacité totale</p>
                <p class="stat-value">{{ $salles->sum('capacite_max') }}</p>
            </div>
        </div>
        <div class="stat-card border-green">
            <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <p class="stat-label">Mode mensuel</p>
                <p class="stat-value">{{ $salles->where('mode_paiement','mensuel')->count() }}</p>
            </div>
        </div>
        <div class="stat-card border-orange">
            <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div>
                <p class="stat-label">Mode tranches</p>
                <p class="stat-value">{{ $salles->where('mode_paiement','tranche')->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.salles-de-classe.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom de la salle..."
                       class="filter-input">
            </div>
            <div class="filter-field">
                <label class="filter-label">Mode de paiement</label>
                <select name="mode_paiement" class="filter-select">
                    <option value="">Tous</option>
                    <option value="mensuel" {{ request('mode_paiement') == 'mensuel' ? 'selected' : '' }}>Mensuel</option>
                    <option value="tranche" {{ request('mode_paiement') == 'tranche' ? 'selected' : '' }}>Par tranche</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>
            @if(request('search') || request('mode_paiement'))
                <a href="{{ route('admin.salles-de-classe.index') }}" class="btn-reset">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Section</th>
                    <th>Option</th>
                    <th>Capacité</th>
                    <th>Âge</th>
                    <th>Mode</th>
                    <th>Frais insc.</th>
                    <th>Frais périodiques</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salles as $salle)
                <tr>
                    <td data-label="Nom">
                        <div class="salle-cell">
                            <div class="avatar">{{ strtoupper(substr($salle->nom, 0, 2)) }}</div>
                            <span class="cell-primary">{{ $salle->nom }}</span>
                        </div>
                    </td>
                    <td data-label="Section">{{ $salle->section?->nom ?? '—' }}</td>
                    <td data-label="Option">{{ $salle->option?->nom ?? '—' }}</td>
                    <td data-label="Capacité">
                        <span class="capacity-badge">{{ $salle->capacite_max }} places</span>
                    </td>
                    <td data-label="Âge">{{ $salle->age_min }} - {{ $salle->age_max }} ans</td>
                    <td data-label="Mode">
                        @if($salle->mode_paiement === 'mensuel')
                            <span class="badge badge-blue" title="Paiement mensuel">
                                <i class="fa-solid fa-calendar-check"></i> Mensuel
                            </span>
                        @else
                            <span class="badge badge-orange" title="Paiement par tranches">
                                <i class="fa-solid fa-layer-group"></i> Tranche
                            </span>
                        @endif
                    </td>
                    <td data-label="Frais insc.">{{ number_format($salle->frais_inscription, 0) }} $</td>
                    <td data-label="Frais périodiques">
                        @if($salle->mode_paiement === 'mensuel')
                            <div class="frais-detail">
                                <span class="frais-montant">{{ number_format($salle->frais_scolarite_mensuel ?? 0, 0) }} $</span>
                                <span class="frais-periode">/ mois</span>
                                <small class="frais-annuel">(annuel : {{ number_format($salle->frais_annuel, 0) }} $)</small>
                            </div>
                        @else
                            <div class="frais-detail">
                                <span class="frais-montant">{{ number_format($salle->frais_par_tranche ?? 0, 0) }} $</span>
                                <span class="frais-periode">/ tranche</span>
                                <small class="frais-tranches">{{ $salle->nombre_tranches ?? 1 }} tranche(s)</small>
                            </div>
                        @endif
                    </td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            <a href="{{ route('admin.salles-de-classe.show', $salle) }}" class="action-icon" title="Voir détails">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.salles-de-classe.edit', $salle) }}" class="action-icon" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.salles-de-classe.destroy', $salle) }}" method="POST" class="inline-form" onsubmit="return confirm('Supprimer cette salle ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon danger" title="Supprimer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="empty-cell">
                        @if(request('search') || request('mode_paiement'))
                            Aucune salle trouvée avec ces critères.
                        @else
                            Aucune salle de classe enregistrée.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pagination-wrapper">
        {{ $salles->appends(request()->query())->links() }}
    </div>
</div>

<style>
    /* ==== Styles locaux premium (cohérents avec les autres index) ==== */
    .index-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .index-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.25rem;
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

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.7rem 1.5rem;
        background: #1e293b;
        color: white;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.3s;
    }
    .btn-primary:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .stats-grid.four-cols { grid-template-columns: repeat(4, 1fr); }
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
    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .border-indigo { border-left-color: #667eea; }
    .border-purple { border-left-color: #a855f7; }
    .border-green { border-left-color: #22c55e; }
    .border-orange { border-left-color: #f97316; }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .border-indigo .stat-icon { background: #eef2ff; color: #667eea; }
    .border-purple .stat-icon { background: #faf5ff; color: #a855f7; }
    .border-green .stat-icon { background: #f0fdf4; color: #22c55e; }
    .border-orange .stat-icon { background: #fff7ed; color: #f97316; }
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

    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.25rem;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.4s ease forwards;
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
        min-width: 180px;
    }
    .filter-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }
    .filter-input,
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
    .filter-input:focus,
    .filter-select:focus {
        border-color: #667eea;
        background: white;
    }
    .btn-filter,
    .btn-reset {
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
    }
    .btn-filter {
        background: #1e293b;
        color: white;
    }
    .btn-filter:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }
    .btn-reset {
        background: white;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
    }
    .btn-reset:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
    }

    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        animation: cardIn 0.8s 0.5s ease forwards;
        opacity: 0;
    }
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
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    .text-right { text-align: right; }

    .salle-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #e0e7ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .cell-primary {
        font-weight: 600;
        color: #1e293b;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-orange { background: #ffedd5; color: #c2410c; }

    .capacity-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .frais-detail {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .frais-montant {
        font-weight: 700;
        color: #1e293b;
    }
    .frais-periode {
        font-size: 0.8rem;
        color: #64748b;
    }
    .frais-annuel, .frais-tranches {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .action-cell {
        white-space: nowrap;
    }
    .action-icons {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }
    .action-icon:hover {
        background: #e2e8f0;
        color: #1e293b;
    }
    .action-icon.danger:hover {
        background: #fee2e2;
        color: #dc2626;
    }
    .inline-form { display: inline; }

    .empty-cell {
        text-align: center;
        padding: 3rem;
        color: #94a3b8;
    }

    .pagination-wrapper {
        margin-top: 1.5rem;
        animation: fadeUp 0.6s 0.7s ease forwards;
        opacity: 0;
    }

    /* ==== Transformation en cartes sur mobile ==== */
    @media (max-width: 640px) {
        .stats-grid.four-cols {
            grid-template-columns: 1fr;
        }
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
        .data-table thead {
            display: none;
        }
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
        .data-table td[data-label="Actions"] {
            justify-content: flex-end;
        }
        .data-table td[data-label="Actions"]::before {
            display: none;
        }
        .action-icons {
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .salle-cell {
            justify-content: flex-start;
        }
    }
</style>
@endsection