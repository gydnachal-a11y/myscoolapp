@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="avances-index" x-data="avancesIndex()">
    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title">
                <i class="fa-solid fa-hand-holding-dollar text-indigo-500"></i>
                <strong>Avances sur salaire</strong>
            </h1>
            <p class="index-subtitle">Gérez les bons de paiement anticipés du personnel</p>
        </div>
        <div class="header-actions">
            {{-- ✅ NOUVEAU : lien vers les paiements de salaires --}}
            <a href="{{ route('admin.paiement-salaires.index') }}" class="btn-secondary">
                <i class="fa-solid fa-money-check-dollar"></i> Paiements salaires
            </a>

            <a href="{{ route('admin.demandes-avance.index') }}" class="btn-secondary">
                <i class="fa-solid fa-inbox"></i> Demandes
                @if(isset($demandesAvanceEnAttente) && $demandesAvanceEnAttente > 0)
                    <span class="btn-badge">{{ $demandesAvanceEnAttente }}</span>
                @endif
            </a>

            <a href="{{ route('admin.session-avances.index') }}" class="btn-secondary">
                <i class="fa-solid fa-calendar-check"></i> Sessions
            </a>

            <a href="{{ route('admin.avances.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouvelle avance
            </a>
        </div>
    </div>

    {{-- Statistiques rapides --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600"><i class="fa-solid fa-coins"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['montant_total_usd'] ?? 0, 0, ',', ' ') }} $</div>
                <div class="stat-label">Total des avances</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-yellow-100 text-yellow-600"><i class="fa-solid fa-clock"></i></div>
            <div>
                <div class="stat-value">{{ $stats['actives'] ?? $avances->where('statut', 'en_attente')->count() }}</div>
                <div class="stat-label">Actives</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600"><i class="fa-solid fa-check-circle"></i></div>
            <div>
                <div class="stat-value">{{ $stats['remboursees'] ?? $avances->where('statut', 'remboursee')->count() }}</div>
                <div class="stat-label">Remboursées</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100 text-red-600"><i class="fa-solid fa-hand-holding-usd"></i></div>
            <div>
                <div class="stat-value">{{ number_format($stats['dette_totale_usd'] ?? 0, 0, ',', ' ') }} $</div>
                <div class="stat-label">Dette en cours</div>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-user"></i> Employé</label>
                <select name="user_id" class="filter-select" @change="this.form.submit()">
                    <option value="">Tous</option>
                    @foreach($users as $u)
                        @php
                            $dette = $dettesParUser[$u->id]['dette'] ?? 0;
                            $bloque = $dettesParUser[$u->id]['bloque'] ?? false;
                        @endphp
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}
                                {{ $bloque ? 'disabled' : '' }}
                                class="{{ $bloque ? 'text-gray-400' : '' }}">
                            {{ $u->name }}
                            @if($bloque) (Bloqué) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-circle"></i> Statut</label>
                <select name="statut" class="filter-select" @change="this.form.submit()">
                    <option value="">Tous</option>
                    <option value="en_attente" @selected(request('statut') == 'en_attente')>En attente</option>
                    <option value="partiellement_remboursee" @selected(request('statut') == 'partiellement_remboursee')>Partiellement remboursée</option>
                    <option value="remboursee" @selected(request('statut') == 'remboursee')>Remboursée</option>
                    <option value="annulee" @selected(request('statut') == 'annulee')>Annulée</option>
                </select>
            </div>
            <div class="filter-field">
                <label class="filter-label"><i class="fa-solid fa-magnifying-glass"></i> Recherche</label>
                <input type="text" name="recherche" class="filter-select"
                       placeholder="Nom ou email..."
                       value="{{ request('recherche') }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-filter"></i> Filtrer
                </button>
                @if(request()->hasAny(['user_id', 'statut', 'recherche']))
                    <a href="{{ route('admin.avances.index') }}" class="btn-reset">
                        <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Liste des avances --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-list text-indigo-500"></i>
                <span>Avances enregistrées</span>
                <span class="badge-count">{{ $avances->total() }}</span>
            </div>
            <div class="table-actions">
                <span class="text-muted text-sm">Taux : {{ number_format($tauxChange ?? 2800, 2) }} FC/USD</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employé</th>
                        <th>Date</th>
                        <th class="text-right">Montant USD</th>
                        <th class="text-right">Montant FC</th>
                        <th>Mois</th>
                        <th>Statut</th>
                        <th class="text-right">Dette actuelle</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($avances as $avance)
                        @php
                            $userData = $dettesParUser[$avance->user_id] ?? ['dette' => 0, 'salaire' => 0, 'limite' => 0, 'bloque' => false];
                        @endphp
                        <tr>
                            <td data-label="Employé">
                                <div class="user-cell">
                                    <span class="cell-primary">{{ $avance->user?->name ?? 'Utilisateur supprimé' }}</span>
                                    @if($userData['bloque'])
                                        <span class="badge badge-danger"><i class="fa-solid fa-lock"></i> Bloqué</span>
                                    @endif
                                </div>
                            </td>
                            <td data-label="Date">
                                {{ $avance->date_avance ? $avance->date_avance->format('d/m/Y') : '—' }}
                            </td>
                            <td data-label="Montant USD" class="text-right">
                                <strong class="text-indigo-700">{{ number_format($avance->montant_avance_usd, 0, ',', ' ') }} $</strong>
                            </td>
                            <td data-label="Montant FC" class="text-right text-muted">
                                {{ number_format($avance->montant_avance_fc, 0, ',', ' ') }} FC
                            </td>
                            <td data-label="Mois">{{ $avance->moisScolaire?->nom_mois ?? '—' }}</td>
                            <td data-label="Statut">
                                @php
                                    $badge = match($avance->statut) {
                                        'en_attente' => 'badge-warning',
                                        'remboursee' => 'badge-success',
                                        'partiellement_remboursee' => 'badge-info',
                                        'annulee' => 'badge-danger',
                                        default => 'badge-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">
                                    @switch($avance->statut)
                                        @case('en_attente') <i class="fa-regular fa-clock"></i> @break
                                        @case('remboursee') <i class="fa-regular fa-circle-check"></i> @break
                                        @case('partiellement_remboursee') <i class="fa-regular fa-hourglass-half"></i> @break
                                        @case('annulee') <i class="fa-regular fa-circle-xmark"></i> @break
                                    @endswitch
                                    {{ ucfirst(str_replace('_', ' ', $avance->statut)) }}
                                </span>
                            </td>
                            <td data-label="Dette actuelle" class="text-right">
                                <div class="dette-cell">
                                    <span class="font-bold">{{ number_format($userData['dette'], 0, ',', ' ') }} $</span>
                                    <span class="text-muted text-xs">/ {{ number_format($userData['limite'], 0, ',', ' ') }} $</span>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: {{ $userData['limite'] > 0 ? min(100, round(($userData['dette'] / $userData['limite']) * 100)) : 0 }}%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Actions" class="text-center">
                                <div class="action-buttons">
                                    <a href="{{ route('admin.avances.edit', $avance) }}" class="btn-icon" title="Modifier" aria-label="Modifier l'avance">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.avances.destroy', $avance) }}" method="POST" onsubmit="return confirm('Supprimer définitivement cette avance ?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon text-red-500 hover:text-red-700" title="Supprimer" aria-label="Supprimer l'avance">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-cell">
                                <i class="fa-regular fa-circle-info text-2xl block mb-2 text-gray-300"></i>
                                Aucune avance enregistrée.
                                <a href="{{ route('admin.avances.create') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Créer une avance</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            {{ $avances->links() }}
        </div>
    </div>
</div>

<style>
    .avances-index { max-width: 1300px; margin: 0 auto; padding: 2rem 1rem; }

    /* ===== Header ===== */
    .index-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; animation: fadeUp 0.6s ease forwards; opacity: 0; gap: 1rem; flex-wrap: wrap; }
    .index-title { font-size: 1.75rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    .index-title strong { font-weight: 800; }
    .index-subtitle { color: #94a3b8; font-size: 0.95rem; margin-top: 0.25rem; }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .btn-primary, .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.65rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s;
        border: 1.5px solid transparent;
        cursor: pointer;
        white-space: nowrap;
        position: relative;
    }

    .btn-primary {
        background: #1e293b;
        color: white;
    }
    .btn-primary:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }

    .btn-secondary {
        background: white;
        border-color: #e2e8f0;
        color: #475569;
    }
    .btn-secondary:hover {
        border-color: #667eea;
        color: #667eea;
        background: #f8fafc;
        transform: translateY(-1px);
    }

    /* Badge intégré au bouton secondary */
    .btn-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        background: #dc2626;
        color: white;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-left: 2px;
    }

    /* ===== Statistiques ===== */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; animation: fadeUp 0.6s 0.15s ease forwards; opacity: 0; }
    .stat-card { background: white; border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .stat-value { font-size: 1.3rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
    .stat-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.3px; }

    /* ===== Filtres ===== */
    .filter-card { background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; border: 1px solid #f1f5f9; animation: fadeUp 0.6s 0.3s ease forwards; opacity: 0; }
    .filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-field { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; min-width: 180px; }
    .filter-label { font-size: 0.75rem; font-weight: 600; color: #475569; letter-spacing: 0.3px; text-transform: uppercase; display: flex; align-items: center; gap: 4px; }
    .filter-select { width: 100%; padding: 0.6rem 1rem; border: 2px solid #e2e8f0; border-radius: 10px; background: #f8fafc; font-size: 0.95rem; color: #1e293b; transition: all 0.3s; outline: none; appearance: none; font-family: inherit; }
    .filter-select:focus { border-color: #667eea; background: white; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
    .filter-select option:disabled { color: #94a3b8; }
    .filter-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .btn-filter, .btn-reset { display: inline-flex; align-items: center; gap: 6px; padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s; text-decoration: none; border: none; }
    .btn-filter { background: #1e293b; color: white; }
    .btn-filter:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
    .btn-reset { background: white; border: 1.5px solid #e2e8f0; color: #64748b; }
    .btn-reset:hover { border-color: #667eea; color: #667eea; background: #f8fafc; }

    /* ===== Tableau ===== */
    .table-card { background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; overflow: hidden; animation: cardIn 0.8s 0.5s ease forwards; opacity: 0; }
    .table-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 0.5rem; }
    .table-title { font-size: 1.05rem; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 8px; }
    .badge-count { background: #e2e8f0; color: #475569; padding: 0.1rem 0.6rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .table-actions { font-size: 0.8rem; }
    .text-muted { color: #94a3b8; }

    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: #475569; }
    .data-table thead th { text-align: left; padding: 0.8rem 1.25rem; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .data-table thead th.text-right { text-align: right; }
    .data-table thead th.text-center { text-align: center; }
    .data-table tbody td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-right { text-align: right; }
    .data-table .text-center { text-align: center; }

    .user-cell { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .cell-primary { font-weight: 600; color: #1e293b; }
    .empty-cell { text-align: center; padding: 3rem 1.5rem; color: #94a3b8; }

    /* ===== Badges ===== */
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 0.25rem 0.7rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-secondary { background: #e2e8f0; color: #475569; }

    /* ===== Dette ===== */
    .dette-cell { display: flex; flex-direction: column; align-items: flex-end; }
    .dette-cell .font-bold { font-weight: 700; color: #1e293b; }
    .dette-cell .text-muted { color: #94a3b8; font-size: 0.7rem; }
    .progress-bar { width: 100%; height: 4px; background: #e2e8f0; border-radius: 4px; margin-top: 4px; overflow: hidden; max-width: 120px; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 4px; transition: width 0.6s ease; }

    /* ===== Actions ===== */
    .action-buttons { display: flex; justify-content: center; gap: 0.5rem; }
    .btn-icon { background: transparent; border: none; cursor: pointer; color: #94a3b8; font-size: 1rem; transition: all 0.2s; padding: 0.25rem 0.4rem; border-radius: 6px; }
    .btn-icon:hover { background: #f1f5f9; color: #667eea; }
    .btn-icon.text-red-500 { color: #dc2626; }
    .btn-icon.text-red-500:hover { background: #fee2e2; color: #991b1b; }

    /* ===== Pagination ===== */
    .pagination-container { padding: 0.8rem 1.5rem; border-top: 1px solid #f1f5f9; }

    /* ===== Animations ===== */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes cardIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* ===== Responsive ===== */
    @media (max-width: 768px) {
        .index-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
        .header-actions { width: 100%; }
        .header-actions .btn-primary,
        .header-actions .btn-secondary { flex: 1; justify-content: center; }

        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-form { flex-direction: column; }
        .filter-field { min-width: 100%; }
        .filter-actions { width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }

        .data-table thead { display: none; }
        .data-table tbody tr { display: block; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 1.25rem; padding: 1rem 1.25rem; }
        .data-table tbody td { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
        .data-table tbody td:last-child { border-bottom: none; }
        .data-table tbody td::before { content: attr(data-label); font-weight: 600; color: #94a3b8; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.3px; min-width: 80px; }
        .data-table tbody td.text-right { text-align: left; justify-content: space-between; }
        .data-table tbody td.text-center { text-align: left; justify-content: space-between; }
        .dette-cell { align-items: flex-start; }
        .progress-bar { max-width: 100%; }
        .action-buttons { justify-content: flex-start; }
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
        .btn-primary, .btn-secondary { font-size: 0.82rem; padding: 0.55rem 1rem; }
        .filter-select { font-size: 0.85rem; padding: 0.5rem 0.75rem; }
    }
</style>

<script>
    function avancesIndex() {
        return {
            init() {
                // Initialisation
            }
        }
    }
</script>
@endsection