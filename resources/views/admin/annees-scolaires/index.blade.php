@extends('layouts.admin')

@section('page_title', 'Années scolaires')
@section('page_subtitle', 'Gérez les périodes académiques')

@section('content')
@php
    // ✅ FIX BUG : les stats doivent porter sur TOUTE la table, pas la page courante.
    //    Idéalement à déplacer dans le contrôleur, mais on les corrige ici pour
    //    que la vue soit immédiatement fonctionnelle.
    $stats = [
        'total'      => \App\Models\AnneeScolaire::count(),
        'actives'    => \App\Models\AnneeScolaire::where('cloturee', false)->count(),
        'cloturees'  => \App\Models\AnneeScolaire::where('cloturee', true)->count(),
        'paiements'  => \App\Models\AnneeScolaire::where('cloturee', false)
                            ->where('paiement_ouvert', true)
                            ->count(),
    ];

    // Année active (non clôturée + dans la période)
    $anneeActive = \App\Models\AnneeScolaire::enCours()->first();
    $anneeActiveId = $anneeActive?->id;
@endphp

<div class="index-container">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- EN-TÊTE --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-calendar-days title-icon" aria-hidden="true"></i>
                Années scolaires
                <span class="count-badge">{{ $stats['total'] }}</span>
            </h1>
            <p class="page-subtitle">
                @if($anneeActive)
                    Année active : <strong>{{ $anneeActive->libelle }}</strong>
                @else
                    Aucune année active actuellement
                @endif
            </p>
        </div>

        <div class="header-actions">
            <a href="{{ route('admin.annees-scolaires.corbeille') }}" class="btn btn-outline">
                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                <span>Corbeille</span>
            </a>
            <a href="{{ route('admin.annees-scolaires.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                <span>Nouvelle année</span>
            </a>
        </div>
    </header>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- STATISTIQUES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="stats-grid" aria-label="Statistiques globales">
        <article class="stat-card stat-indigo">
            <div class="stat-icon"><i class="fa-solid fa-calendar-alt" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Total</p>
                <p class="stat-value">{{ $stats['total'] }}</p>
            </div>
        </article>

        <article class="stat-card stat-emerald">
            <div class="stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Actives</p>
                <p class="stat-value">{{ $stats['actives'] }}</p>
            </div>
        </article>

        <article class="stat-card stat-rose">
            <div class="stat-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Clôturées</p>
                <p class="stat-value">{{ $stats['cloturees'] }}</p>
            </div>
        </article>

        <article class="stat-card stat-amber">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div>
            <div class="stat-content">
                <p class="stat-label">Paiements ouverts</p>
                <p class="stat-value">{{ $stats['paiements'] }}</p>
            </div>
        </article>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- BARRE DE FILTRES --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <section class="filter-bar">
        <form method="GET" role="search" class="filter-form">
            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass search-icon" aria-hidden="true"></i>
                <input type="search"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Rechercher une année (ex : 2024)"
                       aria-label="Rechercher une année scolaire">
            </div>

            <select name="statut" class="filter-select" aria-label="Filtrer par statut">
                <option value="">Tous les statuts</option>
                <option value="en_cours"  @selected(request('statut') === 'en_cours')>En cours</option>
                <option value="cloturee"  @selected(request('statut') === 'cloturee')>Clôturée</option>
                <option value="a_venir"   @selected(request('statut') === 'a_venir')>À venir</option>
                <option value="passee"    @selected(request('statut') === 'passee')>Passée</option>
            </select>

            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrer
            </button>

            @if(request()->filled('search') || request()->filled('statut'))
                <a href="{{ route('admin.annees-scolaires.index') }}" class="btn-reset">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i> Réinitialiser
                </a>
            @endif
        </form>
    </section>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TABLEAU --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Année</th>
                    <th scope="col">Période</th>
                    <th scope="col">Effectif</th>
                    <th scope="col">Structure</th>
                    <th scope="col">État</th>
                    <th scope="col" class="text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($annees as $annee)
                    @php
                        $isActive   = $annee->id === $anneeActiveId;
                        $effectif   = (int) ($annee->effectif_attendu ?? 0);
                        $reel       = (int) ($annee->inscriptions_count ?? 0);
                        $progress   = $effectif > 0 ? min(100, round(($reel / $effectif) * 100)) : null;
                    @endphp

                    <tr class="{{ $isActive ? 'row-active' : '' }}">
                        {{-- Année --}}
                        <td data-label="Année">
                            <div class="cell-year">
                                <div class="year-avatar {{ $isActive ? 'is-active' : '' }}">
                                    {{ $annee->date_debut?->format('y') ?? '—' }}
                                </div>
                                <div class="year-meta">
                                    <span class="cell-primary">{{ $annee->libelle }}</span>
                                    @if($isActive)
                                        <span class="pill pill-active">
                                            <span class="pulse-dot" aria-hidden="true"></span> En cours
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Période --}}
                        <td data-label="Période">
                            <div class="period">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                <span>
                                    {{ $annee->date_debut?->format('d/m/Y') ?? '—' }}
                                    <span class="period-arrow">→</span>
                                    {{ $annee->date_fin?->format('d/m/Y') ?? '—' }}
                                </span>
                            </div>
                        </td>

                        {{-- Effectif --}}
                        <td data-label="Effectif">
                            @if($progress !== null)
                                <div class="effectif">
                                    <div class="effectif-numbers">
                                        <strong>{{ number_format($reel, 0, ',', ' ') }}</strong>
                                        <span class="muted">/ {{ number_format($effectif, 0, ',', ' ') }}</span>
                                    </div>
                                    <div class="progress" role="progressbar"
                                         aria-valuenow="{{ $progress }}"
                                         aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            @else
                                <span class="badge badge-neutral">
                                    <i class="fa-solid fa-user-group" aria-hidden="true"></i>
                                    {{ number_format($reel, 0, ',', ' ') }} élèves
                                </span>
                            @endif
                        </td>

                        {{-- Structure --}}
                        <td data-label="Structure">
                            <div class="badge-group">
                                <span class="badge badge-indigo">
                                    <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
                                    {{ $annee->nombre_mois ?? '—' }} mois
                                </span>
                                <span class="badge badge-purple">
                                    <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                                    {{ $annee->nombre_tranches ?? '—' }} tranches
                                </span>
                            </div>
                        </td>

                        {{-- État --}}
                        <td data-label="État">
                            <div class="badge-group">
                                @if ($annee->cloturee)
                                    <span class="badge badge-rose">
                                        <i class="fa-solid fa-lock" aria-hidden="true"></i> Clôturée
                                    </span>
                                @else
                                    <span class="badge badge-emerald">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Active
                                    </span>
                                    <span class="badge {{ $annee->paiement_ouvert ? 'badge-emerald-soft' : 'badge-neutral' }}">
                                        <i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>
                                        {{ $annee->paiement_ouvert ? 'Paiements ouverts' : 'Paiements fermés' }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td data-label="Actions" class="text-right">
                            <div class="action-bar">
                                {{-- Modifier --}}
                                <a href="{{ route('admin.annees-scolaires.edit', $annee) }}"
                                   class="action-btn"
                                   aria-label="Modifier {{ $annee->libelle }}"
                                   title="Modifier">
                                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                </a>

                                @if (!$annee->cloturee)
                                    {{-- Toggle paiement --}}
                                    <form action="{{ route('admin.annees-scolaires.toggle-paiement', $annee) }}"
                                          method="POST" class="inline-form">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="action-btn action-{{ $annee->paiement_ouvert ? 'warning' : 'success' }}"
                                                aria-label="{{ $annee->paiement_ouvert ? 'Fermer les paiements' : 'Ouvrir les paiements' }}"
                                                title="{{ $annee->paiement_ouvert ? 'Fermer les paiements' : 'Ouvrir les paiements' }}">
                                            <i class="fa-solid fa-money-bill" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    {{-- Clôturer --}}
                                    <a href="{{ route('admin.annees-scolaires.cloturer', $annee) }}"
                                       class="action-btn action-warning"
                                       aria-label="Clôturer {{ $annee->libelle }}"
                                       title="Clôturer l'année">
                                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    </a>
                                @else
                                    {{-- Rouvrir --}}
                                    <form action="{{ route('admin.annees-scolaires.toggle-cloture', $annee) }}"
                                          method="POST" class="inline-form"
                                          onsubmit="return confirm('Rouvrir cette année scolaire ? Les notes et paiements redeviendront modifiables.')">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="action-btn action-success"
                                                aria-label="Rouvrir {{ $annee->libelle }}"
                                                title="Rouvrir l'année">
                                            <i class="fa-solid fa-unlock" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Supprimer --}}
                                <form action="{{ route('admin.annees-scolaires.destroy', $annee) }}"
                                      method="POST" class="inline-form"
                                      onsubmit="return confirm('Déplacer « {{ $annee->libelle }} » dans la corbeille ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="action-btn action-danger"
                                            aria-label="Supprimer {{ $annee->libelle }}"
                                            title="Supprimer">
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
                                <i class="fa-regular fa-calendar-xmark empty-icon" aria-hidden="true"></i>
                                <h3 class="empty-title">
                                    @if(request()->filled('search') || request()->filled('statut'))
                                        Aucun résultat
                                    @else
                                        Aucune année scolaire
                                    @endif
                                </h3>
                                <p class="empty-text">
                                    @if(request()->filled('search') || request()->filled('statut'))
                                        Aucune année ne correspond à vos critères de recherche.
                                    @else
                                        Commencez par créer votre première année scolaire.
                                    @endif
                                </p>
                                <div class="empty-actions">
                                    @if(request()->filled('search') || request()->filled('statut'))
                                        <a href="{{ route('admin.annees-scolaires.index') }}" class="btn btn-secondary">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Effacer les filtres
                                        </a>
                                    @else
                                        <a href="{{ route('admin.annees-scolaires.create') }}" class="btn btn-primary">
                                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Créer une année
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- PAGINATION --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($annees->hasPages())
        <div class="pagination-wrapper">
            {{ $annees->links() }}
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- STYLES --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<style>
    /* ========== LAYOUT ========== */
    .index-container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* ========== HEADER ========== */
    .page-header {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    @media (min-width: 768px) {
        .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.75rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        margin: 0 0 0.25rem;
    }

    .title-icon { color: #6366f1; }

    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 28px;
        padding: 0 0.6rem;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        margin: 0;
    }

    .page-subtitle strong { color: #0f172a; }

    .header-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    /* ========== BOUTONS ========== */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
    }
    .btn-secondary:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-outline {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
    }
    .btn-outline:hover {
        border-color: #6366f1;
        color: #4f46e5;
        background: #f8fafc;
    }

    .btn-reset {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.65rem 1rem;
        border-radius: 10px;
        background: transparent;
        border: 1.5px solid #e2e8f0;
        color: #64748b;
        font-weight: 500;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
    }
    .btn-reset:hover { background: #f1f5f9; color: #0f172a; }

    /* ========== STATS ========== */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    @media (min-width: 640px)  { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(4, 1fr); } }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .stat-indigo  .stat-icon { background: #eef2ff; color: #4f46e5; }
    .stat-emerald .stat-icon { background: #ecfdf5; color: #059669; }
    .stat-rose    .stat-icon { background: #fff1f2; color: #e11d48; }
    .stat-amber   .stat-icon { background: #fffbeb; color: #d97706; }

    .stat-content { min-width: 0; }

    .stat-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        color: #94a3b8;
        margin-bottom: 0.2rem;
    }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
    }

    /* ========== FILTRES ========== */
    .filter-bar {
        background: #fff;
        border-radius: 16px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        border: 1px solid #f1f5f9;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    .search-field {
        position: relative;
        flex: 1;
        min-width: 200px;
    }

    .search-field .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
        font-size: 0.9rem;
    }

    .search-field input {
        width: 100%;
        padding: 0.7rem 1rem 0.7rem 2.5rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.9rem;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
    }

    .search-field input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .filter-select {
        padding: 0.7rem 2.5rem 0.7rem 1rem;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-size: 0.9rem;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath d='M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.7rem center;
        background-size: 1.1rem;
    }

    .filter-select:focus {
        border-color: #6366f1;
        background-color: #fff;
    }

    /* ========== TABLEAU ========== */
    .table-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
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
        padding: 0.9rem 1.25rem;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .data-table tbody td {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .data-table tbody tr:hover { background: #f8fafc; }
    .data-table tbody tr:last-child td { border-bottom: none; }

    /* Ligne de l'année active */
    .data-table tbody tr.row-active {
        background: linear-gradient(to right, #eef2ff 0%, #ffffff 30%);
    }
    .data-table tbody tr.row-active:hover { background: #eef2ff; }

    .text-right { text-align: right; }

    /* ========== CELLULE ANNÉE ========== */
    .cell-year {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .year-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #f1f5f9;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
        letter-spacing: -0.3px;
    }

    .year-avatar.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
    }

    .year-meta {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .cell-primary {
        font-weight: 600;
        color: #0f172a;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        width: fit-content;
    }

    .pill-active {
        background: #ecfdf5;
        color: #059669;
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        background: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-ring 2s infinite;
    }

    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70%  { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* ========== CELLULE PÉRIODE ========== */
    .period {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .period i { color: #94a3b8; }

    .period-arrow {
        color: #cbd5e1;
        margin: 0 0.15rem;
    }

    /* ========== CELLULE EFFECTIF ========== */
    .effectif {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 100px;
    }

    .effectif-numbers {
        font-size: 0.85rem;
        color: #0f172a;
    }

    .effectif-numbers strong { font-weight: 700; }

    .effectif-numbers .muted {
        color: #94a3b8;
        font-weight: 500;
    }

    .progress {
        width: 100%;
        height: 6px;
        background: #f1f5f9;
        border-radius: 9999px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
        border-radius: 9999px;
        transition: width 0.4s ease;
    }

    /* ========== BADGES ========== */
    .badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
        line-height: 1.4;
    }

    .badge i { font-size: 0.7rem; }

    .badge-indigo       { background: #eef2ff; color: #4338ca; }
    .badge-purple       { background: #f5f3ff; color: #7e22ce; }
    .badge-emerald      { background: #ecfdf5; color: #047857; }
    .badge-emerald-soft { background: #f0fdf4; color: #16a34a; }
    .badge-rose         { background: #fff1f2; color: #be123c; }
    .badge-neutral      { background: #f1f5f9; color: #64748b; }

    /* ========== ACTIONS ========== */
    .action-bar {
        display: inline-flex;
        gap: 0.3rem;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 9px;
        color: #64748b;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.18s ease;
        font-size: 0.9rem;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .action-btn.action-success:hover {
        background: #ecfdf5;
        color: #059669;
    }

    .action-btn.action-warning:hover {
        background: #fffbeb;
        color: #d97706;
    }

    .action-btn.action-danger:hover {
        background: #fff1f2;
        color: #e11d48;
    }

    .inline-form { display: inline; }

    /* ========== ÉTAT VIDE ========== */
    .empty-cell {
        padding: 4rem 1rem !important;
        text-align: center;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        max-width: 420px;
        margin: 0 auto;
    }

    .empty-icon {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 0.5rem;
    }

    .empty-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }

    .empty-text {
        color: #94a3b8;
        font-size: 0.9rem;
        margin: 0;
    }

    .empty-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 1rem;
    }

    /* ========== PAGINATION ========== */
    .pagination-wrapper { margin-top: 1.5rem; }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 640px) {
        .table-card {
            background: transparent;
            border: none;
            box-shadow: none;
        }

        .data-table,
        .data-table tbody,
        .data-table tr,
        .data-table td { display: block; width: 100%; }

        .data-table thead { display: none; }

        .data-table tbody tr {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .data-table tbody tr.row-active {
            background: linear-gradient(135deg, #eef2ff, #fff);
            border-color: #c7d2fe;
        }

        .data-table td {
            border: none;
            padding: 0.5rem 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 90px;
            flex-shrink: 0;
        }

        .data-table td[data-label="Actions"] {
            justify-content: flex-end;
            padding-top: 0.75rem !important;
            border-top: 1px solid #f1f5f9;
            margin-top: 0.5rem;
        }

        .data-table td[data-label="Actions"]::before { display: none; }

        .badge-group { justify-content: flex-end; }
        .effectif { align-items: flex-end; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>
@endsection