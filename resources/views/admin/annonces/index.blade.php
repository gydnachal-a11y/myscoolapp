@extends('layouts.admin')

@section('page_title', 'Annonces')
@section('page_subtitle', 'Gérer les annonces affichées sur le site public')

@section('content')
<div class="index-container">
    {{-- CDN FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="flash flash-success">
            <i class="fa-regular fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="fa-regular fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête --}}
    <div class="index-header">
        <div>
            <h1 class="index-title"><strong>Annonces</strong></h1>
            <p class="index-subtitle">
                Gérer les annonces du site public
                @if($annonces->total() > 0)
                    — <span class="text-indigo">{{ $annonces->total() }} résultat{{ $annonces->total() > 1 ? 's' : '' }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('admin.annonces.create') }}" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nouvelle annonce
        </a>
    </div>

    {{-- Cartes statistiques --}}
    <div class="stats-grid">
        <div class="stat-card border-indigo">
            <div class="stat-icon"><i class="fa-solid fa-megaphone"></i></div>
            <div>
                <p class="stat-label">Total annonces</p>
                <p class="stat-value">{{ $totalAnnonces }}</p>
            </div>
        </div>
        <div class="stat-card border-green">
            <div class="stat-icon"><i class="fa-solid fa-check-circle"></i></div>
            <div>
                <p class="stat-label">Actives</p>
                <p class="stat-value">{{ $totalActives }}</p>
            </div>
        </div>
        <div class="stat-card border-gray">
            <div class="stat-icon"><i class="fa-solid fa-pause-circle"></i></div>
            <div>
                <p class="stat-label">Inactives</p>
                <p class="stat-value">{{ $totalInactives }}</p>
            </div>
        </div>
        <div class="stat-card border-amber">
            <div class="stat-icon"><i class="fa-solid fa-globe"></i></div>
            <div>
                <p class="stat-label">Publiques</p>
                <p class="stat-value">{{ $totalPubliques }}</p>
            </div>
        </div>
        <div class="stat-card border-purple">
            <div class="stat-icon"><i class="fa-solid fa-lock"></i></div>
            <div>
                <p class="stat-label">Privées</p>
                <p class="stat-value">{{ $totalPrivees }}</p>
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('admin.annonces.index') }}" class="filter-form">
            <div class="filter-field filter-search">
                <label for="search" class="filter-label">
                    <i class="fa-solid fa-magnifying-glass"></i> Recherche
                </label>
                <div class="input-with-clear">
                    <input type="text" name="search" id="search"
                           value="{{ request('search') }}"
                           placeholder="Titre de l'annonce..." class="filter-input">
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.annonces.index', request()->except('search', 'page')) }}"
                           class="input-clear" title="Effacer la recherche" aria-label="Effacer la recherche">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="filter-field">
                <label for="statut" class="filter-label">
                    <i class="fa-solid fa-toggle-on"></i> Statut
                </label>
                <select name="statut" id="statut" class="filter-select">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('statut') === '1')>Actives</option>
                    <option value="0" @selected(request('statut') === '0')>Inactives</option>
                </select>
            </div>

            <div class="filter-field">
                <label for="type" class="filter-label">
                    <i class="fa-solid fa-tag"></i> Type
                </label>
                <select name="type" id="type" class="filter-select">
                    <option value="">Tous</option>
                    <option value="public" @selected(request('type') === 'public')>Publique</option>
                    <option value="prive"  @selected(request('type') === 'prive')>Privée</option>
                </select>
            </div>

            <div class="filter-field">
                <label for="per_page" class="filter-label">
                    <i class="fa-solid fa-list-ol"></i> Par page
                </label>
                <select name="per_page" id="per_page" class="filter-select">
                    @foreach([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected((int) request('per_page', 10) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-filter"></i> Filtrer
                </button>
                @if(request()->filled('search') || request()->filled('statut') || request()->filled('type') || request()->filled('per_page'))
                    <a href="{{ route('admin.annonces.index') }}" class="btn-reset">
                        <i class="fa-solid fa-rotate-left"></i> Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tableau / Cartes --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col" class="col-titre">Titre</th>
                    <th scope="col" class="col-type">Type</th>
                    <th scope="col" class="col-date">Début</th>
                    <th scope="col" class="col-date">Fin</th>
                    <th scope="col" class="col-statut">Statut</th>
                    <th scope="col" class="col-actions text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($annonces as $annonce)
                <tr>
                    <td data-label="Titre">
                        <div class="cell-titre">
                            @if($annonce->image_url)
                                <img src="{{ $annonce->image_url }}"
                                     alt="{{ $annonce->titre }}"
                                     class="cell-thumb"
                                     loading="lazy">
                            @else
                                <span class="cell-thumb cell-thumb-empty">
                                    <i class="fa-regular fa-image"></i>
                                </span>
                            @endif
                            <div class="cell-titre-text">
                                <span class="cell-primary">{{ $annonce->titre }}</span>
                                <span class="cell-secondary">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ $annonce->created_at?->format('d/m/Y') }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Type">
                        @if($annonce->estPublique())
                            <span class="badge badge-blue">
                                <i class="fa-solid fa-globe"></i> {{ $annonce->type_libelle }}
                            </span>
                        @else
                            <span class="badge badge-purple">
                                <i class="fa-solid fa-lock"></i> {{ $annonce->type_libelle }}
                            </span>
                        @endif
                    </td>
                    <td data-label="Début" class="cell-date">
                        {{ $annonce->date_debut ? $annonce->date_debut->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td data-label="Fin" class="cell-date">
                        {{ $annonce->date_fin ? $annonce->date_fin->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td data-label="Statut">
                        @php
                            $statutClass = match(true) {
                                !$annonce->est_active      => 'badge-gray',
                                $annonce->estExpiree()     => 'badge-red',
                                $annonce->estAVenir()      => 'badge-amber',
                                default                    => 'badge-green',
                            };
                        @endphp
                        <span class="badge {{ $statutClass }}">
                            {{ $annonce->statut_libelle }}
                        </span>
                    </td>
                    <td data-label="Actions" class="text-right action-cell">
                        <div class="action-icons">
                            {{-- Toggle actif/inactif --}}
                            <form action="{{ route('admin.annonces.toggle-active', $annonce) }}"
                                  method="POST" class="inline-form"
                                  title="{{ $annonce->est_active ? 'Désactiver' : 'Activer' }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="action-icon {{ $annonce->est_active ? 'warning' : 'success' }}"
                                        aria-label="{{ $annonce->est_active ? 'Désactiver' : 'Activer' }} l\'annonce">
                                    <i class="fa-solid {{ $annonce->est_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                            </form>

                            <a href="{{ route('admin.annonces.edit', $annonce) }}"
                               class="action-icon" title="Modifier" aria-label="Modifier l'annonce">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <form action="{{ route('admin.annonces.destroy', $annonce) }}"
                                  method="POST" class="inline-form"
                                  onsubmit="return confirm('Supprimer cette annonce ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-icon danger"
                                        title="Supprimer" aria-label="Supprimer l'annonce">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="empty-row">
                    <td colspan="6" class="empty-cell">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-regular fa-megaphone"></i></div>
                            <h3 class="empty-title">Aucune annonce trouvée</h3>
                            <p class="empty-text">
                                @if(request()->filled('search') || request()->filled('statut') || request()->filled('type'))
                                    Aucun résultat ne correspond à vos critères de recherche.
                                @else
                                    Commencez par créer votre première annonce.
                                @endif
                            </p>
                            <div class="empty-actions">
                                @if(request()->filled('search') || request()->filled('statut') || request()->filled('type'))
                                    <a href="{{ route('admin.annonces.index') }}" class="btn-reset">
                                        <i class="fa-solid fa-rotate-left"></i> Effacer les filtres
                                    </a>
                                @endif
                                <a href="{{ route('admin.annonces.create') }}" class="btn-primary">
                                    <i class="fa-solid fa-plus"></i> Nouvelle annonce
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($annonces->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Affichage de
                <strong>{{ $annonces->firstItem() }}</strong>
                à
                <strong>{{ $annonces->lastItem() }}</strong>
                sur
                <strong>{{ $annonces->total() }}</strong>
                annonce{{ $annonces->total() > 1 ? 's' : '' }}
            </div>
            {{ $annonces->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<style>
    /* =========================================================
       BASE
       ========================================================= */
    .index-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    .index-container * { box-sizing: border-box; }
    .index-container img,
    .index-container table { max-width: 100%; }

    .index-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 2rem;
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
    .text-indigo { color: #667eea; font-weight: 600; }

    /* =========================================================
       FLASH
       ========================================================= */
    .flash {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .flash i { margin-top: 2px; }

    /* =========================================================
       BOUTONS
       ========================================================= */
    .btn-primary,
    .btn-filter,
    .btn-reset {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0.7rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-primary { background: #1e293b; color: white; }
    .btn-primary:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-filter { background: #1e293b; color: white; }
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

    /* =========================================================
       STATS
       ========================================================= */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 640px)  { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 992px)  { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1200px) { .stats-grid { grid-template-columns: repeat(5, 1fr); } }

    .stat-card {
        background: white;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        border-left: 4px solid;
    }
    .border-indigo { border-left-color: #667eea; }
    .border-green  { border-left-color: #10b981; }
    .border-gray   { border-left-color: #94a3b8; }
    .border-amber  { border-left-color: #f59e0b; }
    .border-purple { border-left-color: #8b5cf6; }

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
    .border-green  .stat-icon { background: #d1fae5; color: #10b981; }
    .border-gray   .stat-icon { background: #f1f5f9; color: #94a3b8; }
    .border-amber  .stat-icon { background: #fef3c7; color: #f59e0b; }
    .border-purple .stat-icon { background: #ede9fe; color: #8b5cf6; }

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
        line-height: 1;
    }

    /* =========================================================
       FILTRES
       ========================================================= */
    .filter-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        padding: 1.25rem;
        margin-bottom: 2rem;
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
        flex: 1 1 180px;
        min-width: 0;
    }
    .filter-search { flex: 2 1 260px; }
    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .filter-label i { color: #94a3b8; }

    .input-with-clear { position: relative; }
    .input-with-clear .filter-input { padding-right: 2.5rem; }
    .input-clear {
        position: absolute;
        right: 0.6rem;
        top: 50%;
        transform: translateY(-50%);
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        background: #e2e8f0;
        font-size: 0.75rem;
        text-decoration: none;
        transition: all 0.2s;
    }
    .input-clear:hover { background: #cbd5e1; color: #475569; }

    .filter-input,
    .filter-select {
        width: 100%;
        padding: 0.7rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.95rem;
        color: #1e293b;
        transition: all 0.3s;
        outline: none;
        font-family: inherit;
    }
    .filter-input:focus,
    .filter-select:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    .filter-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        align-items: center;
    }

    /* =========================================================
       TABLEAU
       ========================================================= */
    .table-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        overflow: hidden;
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
        white-space: nowrap;
    }
    .data-table tbody td {
        padding: 0.9rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .text-right { text-align: right; }

    /* Cellule titre avec miniature */
    .cell-titre {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    .cell-thumb {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-shrink: 0;
    }
    .cell-thumb-empty {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #cbd5e1;
        font-size: 1rem;
    }
    .cell-titre-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .cell-primary {
        font-weight: 600;
        color: #1e293b;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .cell-secondary {
        font-size: 0.75rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 2px;
    }
    .cell-date { white-space: nowrap; font-variant-numeric: tabular-nums; }

    /* =========================================================
       BADGES
       ========================================================= */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.65rem;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
        line-height: 1.4;
    }
    .badge-green  { background: #d1fae5; color: #065f46; }
    .badge-gray   { background: #f1f5f9; color: #475569; }
    .badge-blue   { background: #dbeafe; color: #1d4ed8; }
    .badge-purple { background: #ede9fe; color: #6d28d9; }
    .badge-amber  { background: #fef3c7; color: #92400e; }
    .badge-red    { background: #fee2e2; color: #b91c1c; }

    /* =========================================================
       ACTIONS
       ========================================================= */
    .action-cell { white-space: nowrap; }
    .action-icons {
        display: flex;
        justify-content: flex-end;
        gap: 0.35rem;
    }
    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        color: #64748b;
        text-decoration: none;
        transition: all 0.2s;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .action-icon:hover { background: #e2e8f0; color: #1e293b; }
    .action-icon.danger:hover  { background: #fee2e2; color: #dc2626; }
    .action-icon.warning:hover { background: #fef3c7; color: #d97706; }
    .action-icon.success:hover { background: #d1fae5; color: #059669; }
    .inline-form { display: inline; }

    /* =========================================================
       ÉTAT VIDE
       ========================================================= */
    .empty-cell {
        text-align: center;
        padding: 3rem 1.5rem;
    }
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    .empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
    }
    .empty-text {
        font-size: 0.9rem;
        color: #94a3b8;
        max-width: 400px;
    }
    .empty-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 0.75rem;
    }

    /* =========================================================
       PAGINATION
       ========================================================= */
    .pagination-wrapper {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
    .pagination-info {
        font-size: 0.85rem;
        color: #94a3b8;
    }
    .pagination-info strong { color: #475569; }

    /* =========================================================
       RESPONSIVE — TABLETTE (≤ 992px)
       ========================================================= */
    @media (max-width: 992px) {
        .index-container { max-width: 100%; }
        .data-table thead th,
        .data-table tbody td { padding: 0.75rem 1rem; }
        .col-date { display: none; }
        .data-table tbody td[data-label="Début"],
        .data-table tbody td[data-label="Fin"] { display: none; }
    }

    /* =========================================================
       RESPONSIVE — MOBILE / TABLETTE PORTRAIT (≤ 768px)
       → Passage en cartes
       ========================================================= */
    @media (max-width: 768px) {
        .index-container { padding: 1rem 0.75rem; }
        .index-header {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .index-title { font-size: 1.4rem; }
        .index-subtitle { font-size: 0.85rem; }
        .index-header .btn-primary {
            width: 100%;
            justify-content: center;
        }

        /* Stats : 2 colonnes */
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .stat-card {
            padding: 0.85rem 1rem;
            gap: 0.75rem;
            border-radius: 12px;
        }
        .stat-icon { width: 38px; height: 38px; font-size: 1.1rem; border-radius: 10px; }
        .stat-value { font-size: 1.35rem; }
        .stat-label { font-size: 0.68rem; }

        /* Filtres empilés */
        .filter-card { padding: 1rem; border-radius: 12px; margin-bottom: 1.25rem; }
        .filter-form { flex-direction: column; align-items: stretch; gap: 0.75rem; }
        .filter-field,
        .filter-search { flex: 1 1 auto; min-width: 0; }
        .filter-input,
        .filter-select { font-size: 16px; /* anti-zoom iOS */ }
        .filter-actions { justify-content: stretch; }
        .filter-actions .btn-filter,
        .filter-actions .btn-reset { flex: 1; }

        /* Tableau → cartes */
        .table-card {
            background: transparent;
            box-shadow: none;
            border-radius: 0;
            overflow: visible;
        }
        .data-table,
        .data-table tbody,
        .data-table tr,
        .data-table td { display: block; width: 100%; }
        .data-table thead { display: none; }
        .data-table tbody tr {
            background: white;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
            margin-bottom: 0.85rem;
            padding: 1rem;
        }
        .data-table tbody tr:hover { background: white; }
        .data-table td {
            border: none;
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            text-align: right;
        }
        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
            flex-shrink: 0;
        }
        /* On force l'affichage des dates en mode carte */
        .data-table tbody td[data-label="Début"],
        .data-table tbody td[data-label="Fin"] { display: flex; }

        /* Titre : pleine largeur, pas de label */
        .data-table td[data-label="Titre"] {
            display: block;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 0.5rem;
            text-align: left;
        }
        .data-table td[data-label="Titre"]::before { display: none; }
        .cell-titre { justify-content: flex-start; }
        .cell-primary { white-space: normal; }
        .cell-thumb { width: 48px; height: 48px; }

        /* Actions en pleine largeur */
        .data-table td[data-label="Actions"] {
            justify-content: flex-end;
            padding-top: 0.85rem;
            border-top: 1px solid #f1f5f9;
            margin-top: 0.5rem;
        }
        .data-table td[data-label="Actions"]::before { display: none; }
        .action-icons { justify-content: flex-end; gap: 0.5rem; }
        .action-icon {
            width: 40px;
            height: 40px;
            font-size: 1.05rem;
            background: #f8fafc;
        }

        /* État vide */
        .empty-cell { padding: 2rem 1rem; }
        .empty-actions { flex-direction: column; width: 100%; }
        .empty-actions .btn-primary,
        .empty-actions .btn-reset { width: 100%; }
    }

    /* =========================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
       ========================================================= */
    @media (max-width: 480px) {
        .index-container { padding: 0.75rem 0.5rem; }
        .index-title { font-size: 1.2rem; }
        .index-subtitle { font-size: 0.78rem; }
        .stats-grid { grid-template-columns: 1fr; }
        .stat-card { padding: 0.75rem 0.9rem; }
        .stat-value { font-size: 1.2rem; }
        .filter-card { padding: 0.85rem; }
        .data-table tbody tr { padding: 0.85rem; border-radius: 12px; }
        .cell-thumb { width: 42px; height: 42px; }
        .cell-primary { font-size: 0.9rem; }
        .action-icon { width: 38px; height: 38px; }
        .badge { font-size: 0.68rem; padding: 0.2rem 0.55rem; }
    }
</style>
@endsection