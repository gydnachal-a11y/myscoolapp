@extends('layouts.admin')

@section('page_title', 'Sessions de demandes d\'avance')
@section('page_subtitle', 'Gérez les périodes d\'ouverture des demandes')

@section('content')
<div class="index-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- ============================================================
         EN-TÊTE
         ============================================================ --}}
    <div class="index-header">
        <div class="header-title-block">
            <h1 class="index-title">
                <strong>Sessions</strong> de demandes d'avance
            </h1>
            <p class="index-subtitle">
                Définissez les périodes pendant lesquelles les membres peuvent faire une demande
                @if($sessionActive)
                    <span class="filter-indicator"> • Session active</span>
                @endif
            </p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.demandes-avance.index') }}" class="btn-secondary">
                <i class="fa-solid fa-hand-holding-dollar"></i> Voir les demandes
            </a>
            <a href="{{ route('admin.session-avances.create') }}" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Nouvelle session
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fa-solid fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- ============================================================
         BANDEAU SESSION ACTIVE
         ============================================================ --}}
    @if($sessionActive)
        <div class="active-session-banner">
            <div class="active-session-glow"></div>
            <div class="active-session-content">
                <div class="active-session-left">
                    <div class="active-session-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="active-session-label">Session actuellement ouverte</div>
                        <div class="active-session-title">{{ $sessionActive->libelle }}</div>
                        <div class="active-session-dates">
                            Du {{ $sessionActive->date_debut->translatedFormat('d M Y') }}
                            au {{ $sessionActive->date_fin->translatedFormat('d M Y') }}
                        </div>
                    </div>
                </div>
                <form action="{{ route('admin.session-avances.fermer', $sessionActive) }}" method="POST"
                      onsubmit="return confirm('Fermer cette session ? Les membres ne pourront plus faire de demande.');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn-close-session">
                        <i class="fa-solid fa-lock"></i> Fermer la session
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- ============================================================
         STATISTIQUES
         ============================================================ --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <div class="stat-value">{{ $sessions->total() }}</div>
                <div class="stat-label">Sessions totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-green-100 text-green-600">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div>
                <div class="stat-value">{{ $sessionActive ? 1 : 0 }}</div>
                <div class="stat-label">Session ouverte</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100 text-blue-600">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <div>
                <div class="stat-value">{{ $sessions->sum('demandes_count') }}</div>
                <div class="stat-label">Demandes totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100 text-amber-600">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="stat-value">{{ $sessions->sum('demandes_attente_count') }}</div>
                <div class="stat-label">En attente</div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         FILTRES
         ============================================================ --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.session-avances.index') }}" class="filter-form">
            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-circle-dot"></i> Statut</label>
                <select name="statut" class="filter-select" onchange="this.form.submit()">
                    <option value="">Toutes les sessions</option>
                    <option value="ouverte" @selected(request('statut') === 'ouverte')>Ouvertes</option>
                    <option value="fermee" @selected(request('statut') === 'fermee')>Fermées</option>
                    <option value="expiree" @selected(request('statut') === 'expiree')>Expirées</option>
                    <option value="a_venir" @selected(request('statut') === 'a_venir')>À venir</option>
                </select>
            </div>

            <div class="filter-field">
                <label class="filter-label"><i class="fa-regular fa-user"></i> Recherche</label>
                <div class="search-input-wrap">
                    <i class="fa-solid fa-search search-icon"></i>
                    <input type="text" name="recherche" class="filter-input"
                           placeholder="Nom de la session..."
                           value="{{ request('recherche') }}">
                </div>
            </div>

            <button type="submit" class="btn-filter" aria-label="Appliquer les filtres">
                <i class="fa-solid fa-filter"></i> Filtrer
            </button>

            @if(request()->hasAny(['statut', 'recherche']))
                <a href="{{ route('admin.session-avances.index') }}" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                </a>
            @endif
        </form>

        <div class="filter-result-count" role="status" aria-live="polite">
            {{ $sessions->total() }} session(s) trouvée(s)
        </div>
    </div>

    {{-- ============================================================
         TABLEAU
         ============================================================ --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">
                <i class="fa-solid fa-calendar-check text-indigo-500"></i>
                <span>Liste des sessions</span>
                <span class="badge-count">{{ $sessions->total() }}</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Session</th>
                        <th scope="col">Période</th>
                        <th scope="col" class="text-center">Statut</th>
                        <th scope="col" class="text-center">Demandes</th>
                        <th scope="col" class="text-center">En attente</th>
                        <th scope="col" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        @php
                            $badgeClass = match(true) {
                                !$session->est_active                => 'badge-neutral',
                                $session->date_debut->gt(today())    => 'badge-info',
                                $session->date_fin->lt(today())      => 'badge-warning',
                                default                              => 'badge-success',
                            };
                        @endphp
                        <tr>
                            <td data-label="Session">
                                <div class="cell-session">
                                    <div class="avatar-session">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <span class="cell-primary">{{ $session->libelle }}</span>
                                        <span class="cell-secondary">
                                            Créée par {{ $session->createur->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Période">
                                <div class="period-cell">
                                    <div class="period-date">
                                        <i class="fa-regular fa-calendar"></i>
                                        {{ $session->date_debut->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="period-separator">→</div>
                                    <div class="period-date">
                                        {{ $session->date_fin->translatedFormat('d M Y') }}
                                    </div>
                                </div>
                            </td>
                            <td data-label="Statut" class="text-center">
                                <span class="statut-badge {{ $badgeClass }}">
                                    <span class="statut-dot"></span>
                                    {{ $session->statut_label }}
                                </span>
                            </td>
                            <td data-label="Demandes" class="text-center">
                                <span class="count-badge">{{ $session->demandes_count }}</span>
                            </td>
                            <td data-label="En attente" class="text-center">
                                @if($session->demandes_attente_count > 0)
                                    <span class="attente-badge">
                                        <i class="fa-solid fa-clock"></i> {{ $session->demandes_attente_count }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="text-center action-cell">
                                <div class="action-icons">
                                    <a href="{{ route('admin.session-avances.show', $session) }}"
                                       class="action-icon"
                                       title="Voir le détail"
                                       aria-label="Voir la session {{ $session->libelle }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @if($session->est_active)
                                        <button type="button"
                                                class="action-icon danger"
                                                title="Fermer la session"
                                                aria-label="Fermer la session"
                                                onclick="if(confirm('Fermer cette session ? Les membres ne pourront plus faire de demande.')) document.getElementById('form-fermer-{{ $session->id }}').submit();">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>
                                        <form id="form-fermer-{{ $session->id }}"
                                              action="{{ route('admin.session-avances.fermer', $session) }}"
                                              method="POST" class="hidden-form">
                                            @csrf
                                            @method('PATCH')
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <i class="fa-regular fa-calendar-xmark" aria-hidden="true"></i>
                                    <p>Aucune session créée pour le moment.</p>
                                    <a href="{{ route('admin.session-avances.create') }}" class="btn-primary">
                                        <i class="fa-solid fa-plus"></i> Créer la première session
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sessions->hasPages())
            <div class="pagination-container">
                {{ $sessions->appends(request()->query())->links() }}
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
        --color-success: #16a34a;
        --color-danger: #dc2626;
        --color-warning: #d97706;
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

    /* Boutons d'en-tête */
    .btn-primary, .btn-secondary, .btn-print, .btn-filter, .btn-reset {
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

    /* ============================================================
       BANDEAU SESSION ACTIVE
       ============================================================ */
    .active-session-banner {
        position: relative;
        background: linear-gradient(135deg, #10b981 0%, #14b8a6 100%);
        border-radius: var(--radius-card);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.25);
        color: #fff;
    }
    .active-session-glow {
        position: absolute;
        top: -50%; right: -10%;
        width: 300px; height: 300px;
        background: rgba(255,255,255,0.15);
        border-radius: 50%;
        filter: blur(50px);
        pointer-events: none;
    }
    .active-session-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    .active-session-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .active-session-icon {
        width: 52px; height: 52px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .active-session-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        opacity: 0.9;
    }
    .active-session-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-top: 0.15rem;
        line-height: 1.2;
    }
    .active-session-dates {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-top: 0.25rem;
    }
    .btn-close-session {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.7rem 1.25rem;
        background: rgba(255,255,255,0.2);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        backdrop-filter: blur(10px);
    }
    .btn-close-session:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-1px);
    }

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

    .bg-amber-100   { background: #fef3c7; }
    .text-amber-600 { color: #d97706; }
    .bg-green-100   { background: #dcfce7; }
    .text-green-600 { color: #16a34a; }
    .bg-blue-100    { background: #dbeafe; }
    .text-blue-600  { color: #2563eb; }
    .bg-indigo-100  { background: #e0e7ff; }
    .text-indigo-600{ color: #4f46e5; }

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
    .text-muted { color: var(--color-muted); }
    .table-responsive { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; color: var(--color-secondary); }
    .data-table thead th {
        text-align: left; padding: 0.8rem 1.25rem; font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.5px; color: var(--color-muted);
        background: var(--color-bg-light); border-bottom: 2px solid var(--color-border);
    }
    .data-table thead th.text-center { text-align: center; }
    .data-table tbody td { padding: 0.8rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .data-table tbody tr:hover { background: var(--color-bg-light); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table .text-center { text-align: center; }
    .empty-cell { text-align: center; padding: 3rem 1.5rem; color: var(--color-muted); }

    /* Cellules */
    .cell-session { display: flex; align-items: center; gap: 0.75rem; }
    .avatar-session {
        width: 40px; height: 40px; border-radius: 12px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .cell-primary { font-weight: 600; color: var(--color-primary); display: block; }
    .cell-secondary { font-size: 0.75rem; color: var(--color-muted); }

    /* Période */
    .period-cell { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; flex-wrap: wrap; }
    .period-date { display: inline-flex; align-items: center; gap: 4px; color: var(--color-secondary); }
    .period-separator { color: var(--color-muted); font-weight: 700; }

    /* Badges statut */
    .statut-badge { display: inline-flex; align-items: center; gap: 6px; padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .statut-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .badge-success { background: #dcfce7; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-info    { background: #dbeafe; color: #1e40af; }
    .badge-neutral { background: #f1f5f9; color: #475569; }

    /* Compteurs */
    .count-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 32px; padding: 0 0.5rem;
        background: #f1f5f9; color: var(--color-primary);
        border-radius: 10px; font-weight: 700; font-size: 0.85rem;
    }
    .attente-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 0.25rem 0.6rem;
        background: #fef3c7; color: #92400e;
        border-radius: 20px; font-size: 0.75rem; font-weight: 700;
    }

    /* Actions */
    .action-cell { white-space: nowrap; }
    .action-icons { display: flex; justify-content: center; gap: 0.4rem; }
    .action-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 34px; height: 34px; border-radius: 9px; color: var(--color-muted);
        text-decoration: none; transition: all 0.2s; background: #f8fafc;
        border: 1px solid var(--color-border); cursor: pointer; font-size: 0.95rem;
    }
    .action-icon:hover { background: #e0e7ff; color: var(--color-primary-hover); border-color: #c7d2fe; transform: translateY(-1px); }
    .action-icon.danger { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
    .action-icon.danger:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
    .hidden-form { display: none; }

    .empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
    .empty-state i { font-size: 3rem; color: #cbd5e1; }
    .pagination-container { padding: 0.8rem 1.5rem; border-top: 1px solid #f1f5f9; }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .index-header { flex-direction: column; align-items: stretch; }
        .header-title-block { width: 100%; }
        .header-actions { width: 100%; flex-direction: column; }
        .header-actions .btn-primary,
        .header-actions .btn-print,
        .header-actions .btn-secondary { width: 100%; justify-content: center; }

        .active-session-content { flex-direction: column; align-items: stretch; }
        .btn-close-session { width: 100%; justify-content: center; }

        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-form { flex-direction: column; }
        .filter-field { min-width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }

        .table-responsive { overflow-x: visible; }
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
        .btn-primary, .btn-print, .btn-secondary { font-size: 0.85rem; padding: 0.5rem 1rem; }
        .active-session-title { font-size: 1.05rem; }
    }

    /* Impression */
    @media print {
        .index-header, .filter-card, .pagination-container, .stats-grid,
        .header-actions, .action-cell, .active-session-banner { display: none !important; }
        .table-card { box-shadow: none; }
        .data-table { min-width: 0; font-size: 0.75rem; }
        .data-table thead th { background: #f8fafc !important; color: #1e293b !important; }
        .avatar-session { display: none !important; }
    }
</style>
@endsection